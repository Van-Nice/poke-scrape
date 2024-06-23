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
    $uri = $node->filter('a')->link()->getUri();
    $detailsCrawler = $client->request('GET', $uri);
    $tableKeys = ['pokedexData', 'training', 'breeding', 'baseStats', 'pokedexEntries', 'whereToFind', 'otherLanguages', 'otherLanguagesSpecies'];
    $processedData = processPokemonData($detailsCrawler, $tableKeys);

    $pokemonData[$pokemonName] = array_map('cleanData', $processedData['vitals']) + [
        'typeInteractions' => $processedData['typeInteractions'],
        'evolutions' => $processedData['evolutions'],
        'sprites' => $processedData['sprites']
      ];

    // Save updates immediately to JSON
    file_put_contents($jsonFilePath, json_encode($pokemonData, JSON_PRETTY_PRINT));
    echo "Updated data written to '{$jsonFilePath}' for {$pokemonName}.\n";
  } else {
    echo "Data for {$pokemonName} already exists, skipping...\n";
  }
});

echo "Scraping completed. All data is up to date.\n";
