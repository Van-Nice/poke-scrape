<?php
// Function to clean up HTML tags and entities
use Symfony\Component\DomCrawler\Crawler;

function cleanData($input) {
  if ($input === null) {
    return '';  // Return an empty string or handle the null case as appropriate
  }
  if (is_array($input)) {
    return array_map('cleanData', $input);  // Recursively clean each element
  }
  $output = strip_tags($input);  // Remove HTML tags
  $output = html_entity_decode($output, ENT_QUOTES | ENT_HTML5, 'UTF-8');  // Decode HTML entities
  $output = preg_replace('/\s+/', ' ', $output);  // Replace multiple whitespace with single space
  $output = trim($output);  // Trim spaces

  // Convert numeric strings to integers or floats where appropriate
  if (is_numeric($output)) {
    return $output + 0;  // Converts to int or float automatically
  }

  return $output;
}


function scrapePokemonSprites($crawler) {
  // Check if the crawler has the necessary table, return early if not.
  if (!$crawler->filter('.data-table.sprites-table.sprites-history-table')->count()) {
    return ['error' => 'No valid table found'];
  }

  $spritesData = $crawler->filter('.data-table.sprites-table.sprites-history-table tbody tr')->each(function ($tr, $i) {
    $typeNode = $tr->filter('td')->first();
    $type = $typeNode->count() ? cleanData($typeNode->text()) : 'Unknown Type';

    $sprites = [];
    $tr->filter('td.text-center')->each(function ($td, $index) use (&$sprites, $tr) {
      $header = cleanData($tr->closest('table')->filter('thead th')->eq($index + 1)->text());
      if ($td->filter('a')->count() && $td->filter('img')->count()) {
        $imgSrc = cleanData($td->filter('img')->attr('src'));
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
    'data' => $spritesData
  ];
}


function extractPokemonDetails($node) {
  $pokemonName = cleanData($node->filter('.ent-name')->text());
  $pokemonId = cleanData($node->filter('.infocard-lg-data small')->first()->text(), '#');
  $types = $node->filter('.itype')->each(fn($type) => cleanData($type->text()));
  $imgSrc = $node->filter('img')->attr('src');

  return [
    'name' => $pokemonName,
    'id' => $pokemonId,
    'types' => $types,
    'image' => $imgSrc
  ];
}

function processEvolutionChain($crawler) {
  $evolutionElements = $crawler->filter('.infocard-list-evo > div');
  $evolutionData = [];

  foreach ($evolutionElements as $element) {
    $node = new Crawler($element);
    if ($node->matches('.infocard')) {
      $pokemonDetails = extractPokemonDetails($node);
      $evolutionData[] = $pokemonDetails;
    } else if ($node->matches('.infocard-arrow')) {
      $evolutionDetail = cleanData($node->text());
      if (!empty($evolutionData)) {
        $evolutionData[count($evolutionData) - 1]['evolution_detail'] = $evolutionDetail;
      }
    }
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