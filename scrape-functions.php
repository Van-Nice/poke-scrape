<?php
use Symfony\Component\DomCrawler\Crawler;

function cleanData($input) {
  // Remove HTML tags and decode HTML entities
  $output = html_entity_decode(strip_tags($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
  // Normalize whitespace
  $output = preg_replace('/\s+/', ' ', $output);
  $output = trim($output);

  // Extract numbers from strings with units and percentages
  if (preg_match('/^(\d+\.?\d*)\s*([a-zA-Z%]+)/i', $output, $matches)) {
    $number = $matches[1];
    $unit = $matches[2];

    // Convert weight from kg to pounds, height from meters to feet, etc., if needed
    switch (strtolower($unit)) {
      case 'kg':
        $number *= 2.20462; // Convert kg to pounds
        break;
      case 'm':
        $number *= 3.28084; // Convert meters to feet
        break;
      case '%':
        return floatval($number) / 100; // Convert percentage to decimal
      default:
        break;
    }
    return $number;
  }

  // Automatically convert numeric strings to numbers
  if (is_numeric($output)) {
    return $output + 0;
  }

  return $output;
}

// Function to extract and clean text from paragraphs
function extractParagraphs($crawler, $maxParagraphs = 3) {
  // Use the passed Crawler instance to extract text from the first 2 or 3 paragraphs
  $texts = $crawler->filter('p')->each(function (Crawler $node, $i) use ($maxParagraphs) {
    if ($i < $maxParagraphs) {
      return cleanData($node->text());
    }
  });

  // Remove null values which may occur if there are fewer paragraphs than $maxParagraphs
  return array_filter($texts, function($value) {
    return !is_null($value);
  });
}


function scrapePokemonSprites($crawler) {
  // Check if the crawler has the necessary table, return early if not.
  if (!$crawler->filter('.data-table.sprites-table.sprites-history-table')->count()) {
    return ['error' => 'No valid table found'];
  }

  $spritesData = [];
  $crawler->filter('.data-table.sprites-table.sprites-history-table tbody tr')->each(function ($tr, $i) use (&$spritesData) {
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

    $spritesData[$type] = $sprites;
  });

  return $spritesData;
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
      // Use the Pokémon's name as the key for the associative array
      $evolutionData[$pokemonDetails['name']] = $pokemonDetails;
    } else if ($node->matches('.infocard-arrow')) {
      $evolutionDetail = cleanData($node->text());
      if (!empty($evolutionData)) {
        // Get the last key in the evolutionData array
        $lastKey = array_key_last($evolutionData);
        $evolutionData[$lastKey]['evolution_detail'] = $evolutionDetail;
      }
    }
  }

  return $evolutionData;
}

function extractData($crawler, $section) {
  $data = [];
  $sectionHeader = $crawler->filterXPath("//h2[text()=\"$section\"]");
  $nextElement = $sectionHeader->nextAll('table')->first();

  if ($nextElement->count() > 0) {
    $nextElement->filter('tbody > tr')->each(function ($tr) use (&$data) {
      $key = trim($tr->filter('th')->text());
      $value = $tr->filter('td')->text();
      $data[$key] = cleanData($value); // Use cleanData function to clean each value
    });
  }

  return $data;
}

function extractBaseStats($crawler, $section) {
  $stats = [];

  // Select the <h2> element that contains 'Base Stats'
  $sectionHeader = $crawler->filterXPath("//h2[text()=\"$section\"]");

  // Select the table element directly after the <h2>
  $nextElement = $sectionHeader->nextAll('table')->first();

  if ($nextElement->count() > 0) {
    // Iterate through each row in the table body
    $nextElement->filter('tbody > tr')->each(function ($tr) use (&$stats) {
      $statName = trim($tr->filter('th')->text());
      $baseStat = cleanData(trim($tr->filter('td.cell-num')->eq(0)->text()));
      $minStat = cleanData(trim($tr->filter('td.cell-num')->eq(1)->text()));
      $maxStat = cleanData(trim($tr->filter('td.cell-num')->eq(2)->text()));

      $stats[$statName] = [
        'Base' => intval($baseStat),
        'Min' => intval($minStat),
        'Max' => intval($maxStat)
      ];
    });

    // Extract total stats from the table footer
    $tfoot = $nextElement->filter('tfoot > tr');
    if ($tfoot->count() > 0) {
      $totalBase = cleanData(trim($tfoot->filter('td.cell-num')->text()));
      $minLabel = cleanData(trim($tfoot->filter('th')->eq(1)->text()));
      $maxLabel = cleanData(trim($tfoot->filter('th')->eq(2)->text()));

      $stats['Total'] = [
        'Base' => intval($totalBase),
        'Min' => $minLabel,  // These are labels and not numeric values
        'Max' => $maxLabel
      ];
    }
  }

  return $stats;
}

function extractPokedexEntries($crawler) {
  // Select the second <div> with class 'resp-scroll'
  $secondRespScrollDiv = $crawler->filter('.resp-scroll')->eq(1);
  echo $secondRespScrollDiv->html();

  // Initialize an array to hold the data
  $pokemonData = [];

  // Iterate over each table row within the second resp-scroll div
  $secondRespScrollDiv->filter('table.vitals-table > tbody > tr')->each(function ($tr) use (&$pokemonData) {
    // Extract the game version from the 'th' element (includes handling multiple spans for multiple games)
    $gameVersions = $tr->filter('th > span.igame')->each(function ($span) {
      return trim($span->text()); // Extract text for each span individually
    });

    // Extract the description text from the 'td' element
    $description = trim($tr->filter('td.cell-med-text')->text());

    // Since gameVersions is an array, iterate over it to pair each version with the description
    foreach ($gameVersions as $version) {
      $pokemonData[] = [
        'Game Version' => $version,
        'Description' => $description
      ];
    }
  });

  return $pokemonData;
}

function processPokemonData($crawler, $pokemonName) {

  // Initialize data containers
  $pokedexData = extractData($crawler, 'Pokédex data');
  $trainingData = extractData($crawler, 'Training');
  $breedingData = extractData($crawler, 'Breeding');
  $findData = extractData($crawler, 'Where to find ' . $pokemonName);
  $baseStats = extractBaseStats($crawler, 'Base stats');
  $typeInteractions = [];
  $evolutionData = processEvolutionChain($crawler);
  $sprites = scrapePokemonSprites($crawler);
  $paragraphs = extractParagraphs($crawler, 3);
  $handledKeys = [];

  // Process vitals data directly in the function
  $crawler->filter('table > tbody')->each(function ($tbody) use (&$pokemonData, &$handledKeys) {
    $data = [];
    $tbody->filter('tr')->each(function ($tr) use (&$data) {
      if ($tr->filter('th')->count() && $tr->filter('td')->count()) {
        $th = trim($tr->filter('th')->text());
        $tds = $tr->filter('td')->each(function ($td) {
          $content = trim($td->text());
          if ($td->filter('div')->count()) {
            $barWidth = $td->filter('div')->attr('style');
            return preg_replace('/width:(\d+\.\d+)%;/', '$1%', $barWidth);
          }
          return cleanData($content);
        });
        $data[$th] = count($tds) === 1 ? $tds[0] : $tds;
      }
    });
  });

  // Process type interactions
  $crawler->filter('.type-table.type-table-pokedex')->each(function ($table) use (&$typeInteractions) {
    $types = $table->filter('th')->each(function ($th) {
      return cleanData(trim($th->text()));
    });
    $table->filter('tr')->eq(1)->filter('td')->each(function ($td, $index) use (&$typeInteractions, $types) {
      $interaction = [
        'effectiveness' => cleanData(trim($td->text())),
        'description' => $td->attr('title')
      ];
      $typeInteractions[$types[$index]] = $interaction;
    });
  });

  // Return all combined data
  return [
    'description' => $paragraphs,
    'pokedexData' => $pokedexData,
    'breedingData' => $breedingData,
    'trainingData' => $trainingData,
    'baseStats' => $baseStats,
    'whereToFind' => $findData,
    'typeInteractions' => $typeInteractions,
    'evolutions' => $evolutionData,
    'sprites' => $sprites,
  ];
}
?>