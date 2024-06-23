
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

Each Pokémon, like "Bulbasaur", is stored as a key in the JSON object. Under each Pokémon's key, there are various nested sections that encapsulate all the relevant data:

```json
"Bulbasaur": {
    "vitals": [...],
    "typeInteractions": [...],
    "evolutions": [...],
    "sprites": {...}
}
```

### Detailed Breakdown

#### 1. **Vitals**
This section contains an array of different categories of vital information about the Pokémon. Each category is a dictionary within the array:

- **Pokedex Data**: Basic identifiers and biological traits.
- **Training**: Information relevant to how the Pokémon is trained.
- **Breeding**: Details about breeding capabilities.
- **Base Stats**: Fundamental stats for the Pokémon's abilities in battles.
- **Pokedex Entries**: Narrative descriptions from various game editions.
- **Where To Find**: Locations where the Pokémon can be found across different game versions.
- **Other Languages**: Names of the Pokémon in different languages.
- **Other Languages Species**: Species name translations.

```json
"vitals": [
    {
        "pokedexData": [
            {"National \u2116": ["0001"]},
            {"Type": ["Grass Poison"]},
            ...
        ],
        "training": [...],
        "breeding": [...],
        "baseStats": [...],
        "pokedexEntries": [...],
        "whereToFind": [...],
        "otherLanguages": [...],
        "otherLanguagesSpecies": [...]
    }
]
```

#### 2. **Type Interactions**
Describes how effective or ineffective the Pokémon's type is against other types, useful for battle strategies.

```json
"typeInteractions": [
    {
        "type": "Nor",
        "effectiveness": "",
        "description": "Normal \u2192 Grass/Poison = normal effectiveness"
    },
    ...
]
```

#### 3. **Evolutions**
Lists the evolutionary stages of the Pokémon, including images and type details.

```json
"evolutions": [
    {
        "name": "Bulbasaur",
        "id": "#0001",
        "types": ["Grass", "Poison"],
        "image": "https://img.pokemondb.net/sprites/home/normal/2x/bulbasaur.jpg"
    },
    ...
]
```

#### 4. **Sprites**
Provides a dictionary of sprites for each Pokémon, categorized by type (Normal, Shiny) and game generation.

```json
"sprites": {
    "name": "Unknown",
    "alias": "Unknown",
    "data": [
        {
            "type": "Normal",
            "sprites": {
                "Generation 1": "https://img.pokemondb.net/sprites/red-blue/normal/bulbasaur.png",
                ...
            }
        },
        ...
    ]
}
```


## Contributing

Contributions to this project are welcome! Please fork the repository and submit a pull request with your features or fixes.

## License

This project is licensed under the MIT License.