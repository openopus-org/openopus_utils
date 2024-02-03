# openopus_utils
[Open Opus](https://openopus.org) is a free and open source API to classical music metadata.

This is a library of simple PHP functions used by the main Open Opus API and by the player [Concertmaster](https://getconcertmaster.com), which rely on the Open Opus data to improve Spotify.

## Usage

There is a [wiki](https://wiki.openopus.org/wiki/Using_the_Open_Opus_Utils_Library) explaining all functions.

## How to build

1. Clone the git repository (for example, in the `/var/www/` folder)
2. Change the `UTILIB` constant in the `lib/inc.php` file of each API ([Open Opus](https://github.com/openopus-org/openopus_api) and [Concertmaster](https://github.com/openopus-org/concertmaster_api), to reflect the actual folder
