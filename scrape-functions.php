<?php
// Function to clean up HTML tags and entities
function cleanData($input): string|array {
  if (is_array($input)) {
    return array_map('cleanData', $input);  // Recursively clean each element
  }
  $output = strip_tags($input);  // Remove HTML tags
  $output = html_entity_decode($output);  // Decode HTML entities
  $output = trim($output);  // Trim spaces
  return $output;
}

function scrapePokemonSprites($crawler) {
  // Check if the crawler has the necessary table, return early if not.
  if (!$crawler->filter('.data-table.sprites-table.sprites-history-table')->count()) {
    return ['error' => 'No valid table found'];
  }

  $pokemonName = $crawler->attr('data-pkname') ?: 'Unknown';
  $pokemonAlias = $crawler->attr('data-pkalias') ?: 'Unknown';

  $spritesData = $crawler->filter('.data-table.sprites-table.sprites-history-table tbody tr')->each(function ($tr, $i) {
    $typeNode = $tr->filter('td')->first();
    $type = $typeNode->count() ? trim($typeNode->text()) : 'Unknown Type';

    $sprites = [];
    $tr->filter('td.text-center')->each(function ($td, $index) use (&$sprites, $tr) {
      $header = $tr->closest('table')->filter('thead th')->eq($index + 1)->text();
      if ($td->filter('a')->count() && $td->filter('img')->count()) {
        $imgSrc = $td->filter('img')->attr('src');
        $sprites[$header] = $imgSrc;
      } else {
        $sprites[$header] = '—';
      }
    });

    return [
      'type' => $type,
      'sprites' => $sprites
    ];
  });

  return [
    'name' => $pokemonName,
    'alias' => $pokemonAlias,
    'data' => $spritesData
  ];
}


function processEvolutionChain($crawler) {
  $evolutionElements = $crawler->filter('.infocard-list-evo > div')->each(function ($node) {
    $classAttribute = $node->attr('class');
    if (preg_match('/\binfocard\b/', $classAttribute)) {
      $pokemonName = $node->filter('.ent-name')->text();
      $pokemonId = trim($node->filter('.infocard-lg-data small')->first()->text(), '#');
      $types = $node->filter('.itype')->each(function ($type) {
        return trim($type->text());
      });
      $imgSrc = $node->filter('img')->attr('src');
      return [
        'type' => 'pokemon',
        'name' => $pokemonName,
        'id' => $pokemonId,
        'types' => $types,
        'image' => $imgSrc
      ];
    } else if (preg_match('/\binfocard-arrow\b/', $classAttribute)) {
      $evolutionDetail = trim($node->text());
      return [
        'type' => 'evolution_detail',
        'detail' => $evolutionDetail
      ];
    }
  });

  // Organize evolution data into structured relationships
  $evolutionData = [];
  $currentPokemon = null;
  foreach ($evolutionElements as $element) {
    if ($element['type'] === 'pokemon') {
      if ($currentPokemon) {
        $evolutionData[] = $currentPokemon;
      }
      $currentPokemon = [
        'pokemon' => $element,
        'evolution_condition' => null  // Initialize with no evolution condition
      ];
    } elseif ($element['type'] === 'evolution_detail' && $currentPokemon) {
      $currentPokemon['evolution_condition'] = $element['detail'];
    }
  }
  if ($currentPokemon) {
    $evolutionData[] = $currentPokemon; // Add the last Pokémon if needed
  }

  return $evolutionData;
}

function processPokemonData($crawler, $tableKeys) {
  $pokemonData = $crawler->filter('.vitals-table')->each(function ($table, $index) use ($tableKeys) {
    $key = $tableKeys[$index] ?? 'unknown';
    $data = $table->filter('tbody tr')->each(function ($tr) {
      $th = $tr->filter('th')->text();
      $tds = $tr->filter('td')->each(function ($td) {
        if ($td->filter('div')->count()) {
          $barWidth = $td->filter('div')->attr('style');
          return preg_replace('/width:(\d+\.\d+)%;/', '$1%', $barWidth);
        }
        return $td->text();
      });
      return [$th => $tds];
    });
    return [$key => $data];
  });

  // Scrape the type interactions from the provided HTML structure
  $typeInteractions = [];
  $crawler->filter('.type-table.type-table-pokedex')->each(function ($table) use (&$typeInteractions) {
    $types = $table->filter('th')->each(function ($th) {
      return trim($th->text());
    });
    $table->filter('tr')->eq(1)->filter('td')->each(function ($td, $index) use (&$typeInteractions, $types) {
      $interaction = [
        'type' => $types[$index],
        'effectiveness' => trim($td->text()),
        'description' => $td->attr('title')
      ];
      $typeInteractions[] = $interaction;
    });
  });

  $evolutionData = processEvolutionChain($crawler);
  $sprites = scrapePokemonSprites($crawler);

  return [
    'vitals' => $pokemonData,
    'typeInteractions' => $typeInteractions,
    'evolutions' => $evolutionData,
    'sprites' => $sprites,
  ];
}
?>