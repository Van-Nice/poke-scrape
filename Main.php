<?php
require 'vendor/autoload.php';
use Goutte\Client;
$client = new Client();

$url = "https://pokemondb.net/pokedex/all";
$crawler = $client->request('GET', $url);

$crawler->filter('div > div > p')->each(function ($node) {
  print $node->text()."\n";
});


