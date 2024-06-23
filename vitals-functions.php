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

function processTables($crawler, $tableKeys) {
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



  return [
    'vitals' => $pokemonData,
    'typeInteractions' => $typeInteractions
  ];
}
?>