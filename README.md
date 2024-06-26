
# Pokémon Data Scraper

This project is designed to scrape Pokémon data from [Pokémon Database](https://pokemondb.net/pokedex/all) using PHP and the Goutte web scraping library. It extracts various details about each Pokémon, such as Pokedex data, training info, breeding, base stats, and more, and saves this data in JSON format.

## Features

- Scrape detailed Pokémon data including stats, abilities, evolution, and sprites.
- Data is saved in a structured JSON format.
- Efficient handling of web requests and data processing.

## Requirements

- PHP 8.3.8 or higher
- Composer for managing dependencies

## Installation

1. **Clone the repository:**
```
   git clone https://yourrepository.git
   cd poke-scrape 
```

2. **Install dependencies:**
   ```bash
   composer install
   php composer.phar require symfony/browser-kit    
   ```

3. **Run the scraper:**
   ```bash
   php main.php
   ```

## Usage

To start scraping Pokémon data, simply run the `main.php` script from your command line:

```bash
php main.php
```

The script will fetch details for each Pokémon not already present in `pokemonData.json` and update this file with new data.

### Overview of the JSON Structure

```json
{
  "Bulbasaur": {
    "vitals": {
      "pokedexData": {
        "National №": "0001",
        "Type": "Grass Poison",
        ...
      },
      "training": {...},
      "breeding": {...},
      "baseStats": {...},
      "pokedexEntries": {...},
      "whereToFind": {...},
      "otherLanguages": {...},
      "otherLanguagesSpecies": {...}
    },
    "typeInteractions": {
      "Nor": {...},
      "Fir": {...},
      ...
    },
    "evolutions": {
      "Bulbasaur": {...},
      "Ivysaur": {...},
      "Venusaur": {...}
    },
    "sprites": {
      "Normal": {...},
      "Shiny": {...}
    }
  }
}
```

### Description

- **Vitals**: Contains categorized information about the Pokémon, including its Pokedex data, training details, breeding info, base stats, pokedex entries, habitat, and more.
- **Type Interactions**: Provides details on how this Pokémon's type interacts with other Pokémon types in battles.
- **Evolutions**: Lists all known evolutions of the Pokémon with links to their sprites and basic data.
- **Sprites**: Displays the sprites for the Pokémon across different generations and for both normal and shiny appearances.


## Contributing

Contributions to this project are welcome! Please fork the repository and submit a pull request with your features or fixes.

## License

This project is licensed under the MIT License.