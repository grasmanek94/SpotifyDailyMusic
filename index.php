<?php

const DEBUG = false;

// =====================================================================================================================

require_once 'vendor/autoload.php';

require_once 'SpotifyMusicDiscovery.php';
require_once 'AlbumOfTheYearInterface.php';
require_once 'MusicBrainzInterface.php';
require_once 'AllMusicInterface.php';
require_once 'SpotifyAlbumInterface.php';

try {
// =====================================================================================================================

$now = new DateTime('now');
$creator = new SpotifyMusicDiscovery();

// =====================================================================================================================

//$creator->addAlbumInterface(new AlbumOfTheYearInterface());
//$creator->addAlbumInterface(new MusicBrainzInterface()); // can break easily for no reason..
$creator->addAlbumInterface(new AllMusicInterface());
$creator->addAlbumInterface(new SpotifyAlbumInterface($creator->getSpotifyApi()));

// =====================================================================================================================

function UpdatePlaylist($base_name, $genres, $single_songs_from_albums = false)
{
    global $now;
    global $creator;

    $creator->setDate($now);
    $creator->setBaseName($base_name . " - Daily Album Releases - by GZ0.NL");
    $creator->setGenres($genres);
    $creator->setSingleSongsFromAlbums($single_songs_from_albums);

    try {
        $creator->run();
    } finally {
        $creator->updateTokens();
    }
}

// =====================================================================================================================

UpdatePlaylist("EDM / Drum and Bass", [
    GENRE::EDM,
    GENRE::DRUM_AND_BASS,
    GENRE::FUTURE_BASS,
    GENRE::BASS
]);

UpdatePlaylist("Rock", [
    GENRE::ROCK,
    GENRE::ACOUSTIC_ROCK,
    GENRE::ALTERNATIVE_ROCK,
    GENRE::ART_ROCK,
    GENRE::BLUES_ROCK,
    GENRE::CLASSIC_ROCK,
    GENRE::COUNTRY_ROCK,
    GENRE::DANCE_ROCK,
    GENRE::EXPERIMENTAL_ROCK,
    GENRE::FOLK_ROCK,
    GENRE::FUNK_ROCK,
    GENRE::GARAGE_ROCK,
    GENRE::GLAM_ROCK,
    GENRE::HEARTLAND_ROCK,
    GENRE::HARD_ROCK,
    GENRE::INDIE_ROCK,
    GENRE::POP_ROCK,
    GENRE::GOTHIC_ROCK
]);

UpdatePlaylist("Pop", [
    GENRE::POP,
    GENRE::POP_ROCK,
    GENRE::AMBIENT_POP,
    GENRE::COUNTRY_POP,
    GENRE::BAROQUE_POP,
    GENRE::CHAMBER_POP,
    GENRE::ART_POP,
    GENRE::CITY_POP,
    GENRE::DANCE_POP,
    GENRE::DREAM_POP,
    GENRE::FOLK_POP,
    GENRE::FRENCH_POP,
    GENRE::GLITCH_POP,
    GENRE::HYPNAGOGIC_POP,
    GENRE::INDIE_POP,
    GENRE::BRITPOP,
    GENRE::ELECTROPOP,
    GENRE::SYNTHPOP
]);

UpdatePlaylist("Electronic", [
    GENRE::ELECTROPOP,
    GENRE::ELECTRO,
    GENRE::ELECTRONIC,
    GENRE::ELECTRO_HOUSE,
    GENRE::ELECTRO_DISCO,
    GENRE::ELECTRONICA,
    GENRE::ELECTRO_INDUSTRIAL,
    GENRE::ELECTROCLASH,
    GENRE::ELECTROACOUSTIC,
    GENRE::INDIETRONICA
]);

UpdatePlaylist("Metal", [
    GENRE::ALTERNATIVE_METAL,
    GENRE::ATMOSPHERIC_BLACK_METAL,
    GENRE::AVANT_GARDE_METAL,
    GENRE::BLACK_METAL,
    GENRE::DEATH_METAL,
    GENRE::DOOM_METAL,
    GENRE::DRONE_METAL,
    GENRE::EXPERIMENTAL_METAL,
    GENRE::FOLK_METAL,
    GENRE::FUNK_METAL,
    GENRE::GLAM_METAL,
    GENRE::GOTHIC_METAL,
    GENRE::GROOVE_METAL,
    GENRE::HEAVY_METAL,
    GENRE::INDUSTRIAL_METAL
]);

UpdatePlaylist("House", [
    GENRE::HOUSE,
    GENRE::ELECTRO_HOUSE,
    GENRE::HIP_HOUSE,
    GENRE::ACID_HOUSE,
    GENRE::DEEP_HOUSE,
    GENRE::AMBIENT_HOUSE,
    GENRE::FRENCH_HOUSE
]);

UpdatePlaylist("Industrial", [
    GENRE::INDUSTRIAL_METAL,
    GENRE::INDUSTRIAL,
    GENRE::ELECTRO_INDUSTRIAL,
    GENRE::DEATH_INDUSTRIAL
]);

UpdatePlaylist("Dance", [
    GENRE::DANCE,
    GENRE::ALTERNATIVE_DANCE,
    GENRE::DANCE_PUNK,
    GENRE::DANCE_POP,
    GENRE::DANCE_ROCK,
    GENRE::DANCEHALL
]);

UpdatePlaylist("Country", [
    GENRE::COUNTRY,
    GENRE::COUNTRY_POP,
    GENRE::COUNTRY_ROCK,
    GENRE::COUNTRY_SOUL,
    GENRE::ALT_COUNTRY
]);

UpdatePlaylist("Punk", [
    GENRE::DANCE_PUNK,
    GENRE::POST_PUNK,
    GENRE::ART_PUNK,
    GENRE::CELTIC_PUNK,
    GENRE::FOLK_PUNK,
    GENRE::GARAGE_PUNK,
    GENRE::HARDCORE_PUNK
]);

UpdatePlaylist("Funk", [
    GENRE::FUNK,
    GENRE::FUNK_METAL,
    GENRE::FUNK_ROCK,
    GENRE::FUTURE_FUNK
]);

UpdatePlaylist("Techno", [
    GENRE::AMBIENT_TECHNO,
    GENRE::DUB_TECHNO,
    GENRE::AMBIENT_TECHNO
]);

UpdatePlaylist("DubStep", [
    GENRE::DUBSTEP
]);

UpdatePlaylist("Acid", [
    GENRE::ACID_HOUSE,
    GENRE::ACID_JAZZ,
    GENRE::ACID_TECHNO
]);

UpdatePlaylist("Jazz", [
    GENRE::AVANT_GARDE_JAZZ,
    GENRE::DARK_JAZZ,
    GENRE::ACID_JAZZ
]);

UpdatePlaylist("Hip-Hop", [
    GENRE::HIP_HOP,
    GENRE::ABSTRACT_HIP_HOP,
    GENRE::EXPERIMENTAL_HIP_HOP,
    GENRE::GLITCH_HOP
]);

UpdatePlaylist("Folk", [
    GENRE::FOLK,
    GENRE::INDIE_FOLK,
    GENRE::FOLK_PUNK,
    GENRE::FOLK_POP,
    GENRE::FOLK_ROCK,
    GENRE::CHAMBER_FOLK,
    GENRE::AVANT_FOLK,
    GENRE::FOLKTRONICA,
    GENRE::FREAK_FOLK
]);

UpdatePlaylist("Experimental", [
    GENRE::EXPERIMENTAL,
    GENRE::EXPERIMENTAL_METAL,
    GENRE::EXPERIMENTAL_ROCK,
    GENRE::EXPERIMENTAL_HIP_HOP
]);

UpdatePlaylist("Disco", [
    GENRE::DISCO,
    GENRE::ELECTRO_DISCO
]);

UpdatePlaylist("Ambient", [
    GENRE::AMBIENT,
    GENRE::DARK_AMBIENT,
    GENRE::AMBIENT_TECHNO,
    GENRE::AMBIENT_HOUSE,
    GENRE::AMBIENT_POP
]);

UpdatePlaylist("Glitch", [
    GENRE::GLITCH,
    GENRE::GLITCH_HOP,
    GENRE::GLITCH_POP
]);

UpdatePlaylist("R&B", [
    GENRE::R_AND_B,
    GENRE::ALTERNATIVE_R_AND_B
]);

UpdatePlaylist("Blues", [
    GENRE::BLUES,
    GENRE::BLUES_ROCK
]);

UpdatePlaylist("Soul", [
    GENRE::SOUL,
    GENRE::COUNTRY_SOUL,
    GENRE::BLUE_EYED_SOUL
]);

UpdatePlaylist("Indie Rock/Pop", [
    GENRE::INDIE_ROCK,
    GENRE::INDIE_POP,
    GENRE::ROCK,
    GENRE::POP_ROCK
]);

UpdatePlaylist("Electronic / House", [
    GENRE::HOUSE,
    GENRE::ELECTRO_HOUSE,
    GENRE::ELECTRONIC
]);

UpdatePlaylist("Song Discovery", [
    GENRE::HOUSE,
    GENRE::ELECTRO_HOUSE,
    GENRE::ELECTRONIC,
    GENRE::INDIE_ROCK,
    GENRE::INDIE_POP,
    GENRE::ROCK,
    GENRE::POP_ROCK,
    GENRE::POP
], true);

// =====================================================================================================================
} catch (Exception $e) {
    if (DEBUG) {
        var_dump($e);
    }
}
