<?php
use Symfony\Component\DomCrawler\Crawler;

function cleanData($input) {
  $output = strip_tags($input);  // Remove HTML tags
  $output = html_entity_decode($output, ENT_QUOTES | ENT_HTML5, 'UTF-8');  // Decode HTML entities
  $output = preg_replace('/\s+/', ' ', $output);  // Replace multiple whitespace with single space
  $output = trim($output);  // Trim spaces

  // Attempt to extract numbers from strings containing units (e.g., "6.9 kg")
  if (preg_match('/^(\d+\.?\d*)\s*[a-zA-Z%]+$/i', $output, $matches)) {
    $output = $matches[1];
  }

  // Convert numeric strings to integers or floats where appropriate
  if (is_numeric($output)) {
    return $output + 0;  // Converts to int or float automatically
  }

  // Convert percentages into decimal
  if (strpos($output, '%') !== false) {
    return floatval(str_replace('%', '', $output)) / 100;
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

function extractPokedexData($crawler) {
  // Initialize the array to store Pokédex data
  $pokedexData = [];

  // Select the <h2> element that contains 'Pokédex data'
  $pokedexDataH2 = $crawler->filterXPath('//h2[text()="Pokédex data"]');

  // Select the table element directly after the <h2>
  $nextElement = $crawler->filterXPath('//h2[text()="Pokédex data"]/following-sibling::table[1]');

  // Check if the table exists directly after the H2
  if ($nextElement->count() > 0) {
    // Iterate through each row in the table
    $nextElement->filter('tbody > tr')->each(function ($tr) use (&$pokedexData) {
      $key = trim($tr->filter('th')->text()); // Extract the key from the th element
      $value = $tr->filter('td')->each(function ($td) {
        // Handle special cases with multiple links or nested tags
        if ($td->filter('a')->count() > 0) {
          $links = [];
          $td->filter('a')->each(function ($link) use (&$links) {
            $links[] = trim($link->text());
          });
          return implode(', ', $links);
        } elseif ($td->filter('strong')->count() > 0) {
          return trim($td->filter('strong')->text());
        } else {
          return trim($td->text());
        }
      });

      // Concatenate values if they are in an array (due to multiple elements within td)
      $pokedexData[$key] = is_array($value) ? implode(' ', $value) : $value;
    });
  }

  // Return the associative array containing all Pokédex data
  return $pokedexData;
}

function extractTrainingData($crawler) {
  // Initialize array to store training data
  $trainingData = [];

  // Select the h2 element that contains training
  $trainingDataH2 = $crawler->filterXPath('//h2[text()="Training"]');

  $nextElement = $crawler->filterXPath('//h2[text()="Training"]/following-sibling::table[1]');

  // Check if the table exists directly after the H2
  if ($nextElement->count() > 0) {
    // Iterate through each row in the table
    $nextElement->filter('tbody > tr')->each(function ($tr) use (&$trainingData) {
      $key = trim($tr->filter('th')->text()); // Extract the key from the th element
      $value = $tr->filter('td')->each(function ($td) {
        // Handle special cases with multiple links or nested tags
        if ($td->filter('a')->count() > 0) {
          $links = [];
          $td->filter('a')->each(function ($link) use (&$links) {
            $links[] = trim($link->text());
          });
          return implode(', ', $links);
        } elseif ($td->filter('strong')->count() > 0) {
          return trim($td->filter('strong')->text());
        } else {
          return trim($td->text());
        }
      });

      // Concatenate values if they are in an array (due to multiple elements within td)
      $trainingData[$key] = is_array($value) ? implode(' ', $value) : $value;
    });
  }
  return $trainingData;
}

function processPokemonData($crawler) {
  // Initialize data containers
  $pokedexData = extractPokedexData($crawler);
  $trainingData = extractTrainingData($crawler);
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

    // Remove empty entries and handle duplicates
    $data = array_filter($data);
    foreach ($data as $key => $value) {
      if (!array_key_exists($key, $handledKeys)) {
        $pokemonData[$key] = $value;
        $handledKeys[$key] = true;
      } else {
        $pokemonData[$key] = $value;  // Merge or overwrite logic can be refined here
      }
    }
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
    'trainingData' => $trainingData,
    'typeInteractions' => $typeInteractions,
    'evolutions' => $evolutionData,
    'sprites' => $sprites,
  ];
}
?>