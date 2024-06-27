<?php
require 'vendor/autoload.php';
require 'scrape-functions.php';
use Goutte\Client;

$client = new Client();
$url = "https://pokemondb.net/pokedex/all";
$jsonFilePath = 'pokemonData.json';

// Initialize an empty array to store Pokémon data
$pokemonData = [];

// Load existing data or initialize new if the file exists
if (file_exists($jsonFilePath)) {
  $pokemonData = json_decode(file_get_contents($jsonFilePath), true);
  if (!is_array($pokemonData)) { // Ensuring the loaded data is indeed an array
    $pokemonData = [];
  }
}

// Process each Pokémon entry found on the page
$crawler = $client->request('GET', $url);
$processedCount = 0; // Counter to keep track of the number of processed Pokémon

$crawler->filter('.cell-name')->each(function ($node) use ($client, &$pokemonData, $jsonFilePath, &$processedCount) {
  $smallName = $node->filter('small.text-muted');
  $bigName = trim($node->filter('.ent-name')->text());
  $pokemonName = $smallName->count() > 0 ? trim($smallName->text()) : $bigName;

  // Check if Pokémon data already exists
  $exists = array_search($pokemonName, array_column($pokemonData, 'name'));

  if ($exists === false) {
    $uri = $node->filter('a')->link()->getUri();
    $detailsCrawler = $client->request('GET', $uri);

    // Start the array with the name
    $processedData = ['name' => $pokemonName];

    // Process and store details fetched from the Pokémon's detail page
    $additionalData = processPokemonData($detailsCrawler, $bigName);
    foreach ($additionalData as $key => $value) {
      $processedData[$key] = $value; // Add each piece of additional data to the array
    }

    $pokemonData[] = $processedData; // Append to the array
    $processedCount++;

    // Save the updated Pokémon data to a JSON file
    file_put_contents($jsonFilePath, json_encode($pokemonData, JSON_PRETTY_PRINT));
    echo "{$processedCount}. Data for {$pokemonName} fetched and processed successfully.\n";
  } else {
    echo "Data for {$pokemonName} already exists, skipping...\n";
  }
  sleep(1); // Implement a delay to reduce load on the server
});

echo "Scraping completed. All data is up to date.\n";
