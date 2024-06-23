<?php
require 'vendor/autoload.php';
require 'vitals-functions.php';
use Goutte\Client;
$client = new Client();

$url = "https://pokemondb.net/pokedex/all";
$crawler = $client->request('GET', $url);

$crawler->filter('.ent-name')->each(function ($node) use ($client) {
  $link = $node->link();
  $uri = $link->getUri();
  $crawler = $client->request('GET', $uri);

  $tableKeys = ['pokedexData', 'training', 'breeding', 'baseStats', 'pokedexEntries', 'whereToFind', 'otherlanguages', 'otherLanguagesSpecies'];
  $processedData = processTables($crawler, $tableKeys);

  $pokemon = array_map('cleanData', $processedData['vitals']);
  $pokemon['typeInteractions'] = $processedData['typeInteractions'];

  print_r($pokemon);
  sleep(5); // Sleep to avoid rate limiting
});