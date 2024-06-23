<?php
require 'vendor/autoload.php';
require 'scrape-functions.php';
use Goutte\Client;

$client = new Client();
$url = "https://pokemondb.net/pokedex/all";
$crawler = $client->request('GET', $url);
$jsonFilePath = 'pokemonData.json';

// Load existing data or initialize new array
$pokemonData = file_exists($jsonFilePath) ? json_decode(file_get_contents($jsonFilePath), true) : [];

$crawler->filter('.cell-name')->each(function ($node) use ($client, &$pokemonData, $jsonFilePath) {
  $smallName = $node->filter('small.text-muted');
  $pokemonName = $smallName->count() && trim($smallName->text()) ? trim($smallName->text()) : trim($node->filter('.ent-name')->text());

  if (!isset($pokemonData[$pokemonName])) {
    // Get the URI of the Pokémon details page from the link associated with its name.
    $uri = $node->filter('a')->link()->getUri();

    // Fetch the Pokémon details page using the URI.
    $detailsCrawler = $client->request('GET', $uri);

    // Define keys that correspond to different sections of data expected from the details page.
    $tableKeys = [
      'pokedexData',
      'training',
      'breeding',
      'baseStats',
      'pokedexEntries',
      'whereToFind',
      'otherLanguages',
      'otherLanguagesSpecies'
    ];

    // Process the fetched data using these keys.
    $processedData = processPokemonData($detailsCrawler, $pokemonName);

    $pokemonData[$pokemonName] = $processedData;

    // Save or update your JSON file/database with the newly obtained data.
    file_put_contents('pokemonData.json', json_encode($pokemonData, JSON_PRETTY_PRINT));

    // Output to console or log file.
    echo "Data for {$pokemonName} fetched and processed successfully.\n";
  } else {
    echo "Data for {$pokemonName} already exists, skipping...\n";
  }
  sleep(1);  // Be nice to the server
});

echo "Scraping completed. All data is up to date.\n";
