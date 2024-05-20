<?php

require_once 'vendor/autoload.php';
require_once 'AlbumInterface.php';

use SpotifyWebAPI\SpotifyWebAPI;

class SpotifyAlbumInterface implements AlbumInterface
{
    private static $genre_mappings = [
        GENRE::INDIE_ROCK => 'Indie Rock',
        GENRE::HIP_HOP => 'Hip Hop',
        GENRE::INDIE_POP => 'Indie Pop',
        GENRE::ROCK => 'Rock',
        GENRE::POP => 'Pop',
        GENRE::SOUL => 'Soul',
        GENRE::R_AND_B => 'R&b',
        GENRE::POST_PUNK => 'Post-punk',
        GENRE::SINGER_SONGWRITER => 'Singer-songwriter',
        GENRE::SYNTHPOP => 'Synthpop',
        GENRE::HOUSE => 'House',
        GENRE::POP_ROCK => 'Pop Rock',
        GENRE::INDIETRONICA => 'Indietronica',
        GENRE::ALTERNATIVE_R_AND_B => 'Alternative R&b',
        GENRE::ABSTRACT_HIP_HOP => 'Abstract Hip Hop',
        GENRE::ACID_HOUSE => 'Acid House',
        GENRE::ACID_JAZZ => 'Acid Jazz',
        GENRE::ACID_TECHNO => 'Acid Techno',
        GENRE::AFROBEAT => 'Afrobeat',
        GENRE::AFROBEATS => 'Afrobeats',
        GENRE::ALT_COUNTRY => 'Alternative Country',
        GENRE::ALTERNATIVE_DANCE => 'Alternative Dance',
        GENRE::ALTERNATIVE_METAL => 'Alternative Metal',
        GENRE::ALTERNATIVE_ROCK => 'Alternative Rock',
        GENRE::AMBIENT => 'Ambient',
        GENRE::ART_ROCK => 'Art Rock',
        GENRE::ATMOSPHERIC_BLACK_METAL => 'Atmospheric Black Metal',
        GENRE::AVANT_GARDE_JAZZ => 'Avant-garde Jazz',
        GENRE::AVANT_GARDE_METAL => 'Avantgarde Metal',
        GENRE::BASS => 'Bass Music',
        GENRE::BIG_BEAT => 'Big Beat',
        GENRE::BLACK_METAL => 'Black Metal',
        GENRE::BLACKGAZE => 'Blackgaze',
        GENRE::BLUEGRASS => 'Bluegrass',
        GENRE::BLUES => 'Blues',
        GENRE::BLUES_ROCK => 'Blues-rock',
        GENRE::BOSSA_NOVA => 'Bossa Nova',
        GENRE::BREAKBEAT => 'Breakbeat',
        GENRE::BRITPOP => 'Britpop',
        GENRE::CELTIC_PUNK => 'Celtic Punk',
        GENRE::CHAMBER_POP => 'Chamber Pop',
        GENRE::CHILDREN_S_MUSIC => "Children's Music",
        GENRE::CHILLWAVE => 'Chillwave',
        GENRE::CHRISTMAS => 'Christmas',
        GENRE::CLASSIC_ROCK => 'Classic Rock',
        GENRE::CLASSICAL => 'Classical',
        GENRE::COMEDY => 'Comedy',
        GENRE::COUNTRY => 'Country',
        GENRE::COUNTRY_ROCK => 'Country Rock',
        GENRE::DANCE_POP => 'Dance Pop',
        GENRE::DANCE_PUNK => 'Dance-punk',
        GENRE::DANCE_ROCK => 'Dance Rock',
        GENRE::DANCEHALL => 'Dancehall',
        GENRE::DARK_AMBIENT => 'Dark Ambient',
        GENRE::DARK_JAZZ => 'Dark Jazz',
        GENRE::DARKWAVE => 'Darkwave',
        GENRE::DEATH_METAL => 'Death Metal',
        GENRE::DEATHCORE => 'Death Core',
        GENRE::DEEP_HOUSE => 'Deep House',
        GENRE::DIGITAL_HARDCORE => 'Digital Hardcore',
        GENRE::DISCO => 'Disco',
        GENRE::DOOM_METAL => 'Doom Metal',
        GENRE::DOWNTEMPO => 'Downtempo',
        GENRE::DREAM_POP => 'Dream Pop',
        GENRE::DRONE => 'Drone',
        GENRE::DRONE_METAL => 'Drone Metal',
        GENRE::DRUM_AND_BASS => 'Drum And Bass',
        GENRE::DUB => 'Dub',
        GENRE::DUB_TECHNO => 'Dub Techno',
        GENRE::DUBSTEP => 'Dubstep',
        GENRE::EDM => 'Edm',
        GENRE::ELECTRO => 'Electro',
        GENRE::ELECTRO_HOUSE => 'Electro House',
        GENRE::ELECTRO_INDUSTRIAL => 'Electro-industrial',
        GENRE::ELECTROCLASH => 'Electroclash',
        GENRE::ELECTRONIC => 'Electronic',
        GENRE::ELECTRONICA => 'Electronica',
        GENRE::EMO => 'Emo',
        GENRE::ETHEREAL_WAVE => 'Ethereal Wave',
        GENRE::EXPERIMENTAL => 'Experimental',
        GENRE::EXPERIMENTAL_ROCK => 'Experimental Rock',
        GENRE::FOLK => 'Folk',
        GENRE::FOLK_METAL => 'Folk Metal',
        GENRE::FOLK_POP => 'Folk-pop',
        GENRE::FOLK_PUNK => 'Folk Punk',
        GENRE::FOLK_ROCK => 'Folk Rock',
        GENRE::FOOTWORK => 'Footwork',
        GENRE::FREAK_FOLK => 'Freak Folk',
        GENRE::FREE_IMPROVISATION => 'Free Improvisation',
        GENRE::FRENCH_POP => 'French Pop',
        GENRE::FUNK => 'Funk',
        GENRE::FUNK_METAL => 'Funk Metal',
        GENRE::FUNK_ROCK => 'Funk Rock',
        GENRE::FUTURE_GARAGE => 'Future Garage',
        GENRE::GARAGE_PUNK => 'Garage Punk',
        GENRE::GARAGE_ROCK => 'Garage Rock',
        GENRE::GLAM_METAL => 'Glam Metal',
        GENRE::GLAM_ROCK => 'Glam Rock',
        GENRE::GLITCH => 'Glitch',
        GENRE::GLITCH_HOP => 'Glitch Hop',
        GENRE::GOSPEL => 'Gospel',
        GENRE::GOTHIC_METAL => 'Gothic Metal',
        GENRE::GOTHIC_ROCK => 'Gothic Rock',
        GENRE::GRIME => 'Grime',
        GENRE::GRINDCORE => 'Grindcore',
        GENRE::GROOVE_METAL => 'Groove Metal',
        GENRE::GRUNGE => 'Grunge',
        GENRE::HARD_ROCK => 'Hard Rock',
        GENRE::HARDCORE_PUNK => 'Hardcore Punk',
        GENRE::HEAVY_METAL => 'Heavy Metal',
        GENRE::HEAVY_PSYCH => 'Heavy Psych',
        GENRE::HIP_HOUSE => 'Hip House',
        GENRE::INDIE_FOLK => 'Indie Folk',
        GENRE::INDUSTRIAL => 'Industrial',
        GENRE::INDUSTRIAL_METAL => 'Industrial Metal',
        GENRE::JAZZ => 'Jazz',
        GENRE::NEW_AGE => 'New Age',
        GENRE::REGGAE => 'Reggae'
    ];

    private SpotifyWebAPI $api;

    public function __construct(SpotifyWebAPI $api)
    {
        $this->api = $api;
    }

    /*
     * ONLY LAST TWO WEEKS (probably only today/yesterday), can't specify date here!
     * WE DON'T DO ANYTHING WITH THE DATE
     */
    public function getAllAlbums($input_date, $genres, $get_all)
    {
        $list = [];

        if ($get_all) {
            $date = $input_date->format('Y-m');
        } else {
            $date = $input_date->format('Y-m-d');
        }

        $today = date('Y-m-d');
        $today_exploded = explode('-', $today);
        if (!str_starts_with($today, $date)) {
            // Only work with 'today' date
            return $list;
        }

        $cache_filename = 'data/SpotifyAlbumInterface.cache';
        $cache = [
            'filled_albums' => false,
            'filled_artists' => false,
            'filled_genres' => false,
            'filled_map' => false,
            'date' => $today,
            'albums' => [],
            'artists' => [],
            'genred_albums' => []
        ];

        if (file_exists($cache_filename)) {
            $cache_2 = unserialize(file_get_contents($cache_filename));
            if (isset($cache_2['date']) && $cache_2['date'] === $today) {
                $cache = $cache_2;
            }
        }

        ////////////////////// ALBUMS //////////////////////////////////////////////////////////////////////////////////

        if (!$cache['filled_albums']) {
            try {
                $query = 'tag:new year:' . $input_date->format('Y');

                $current_offset = 0;
                $limit = 50;

                do {
                    usleep(100 * 1000);

                    try {
                        $results = $this->api->search($query, 'album', [
                            'limit' => $limit,
                            'offset' => $current_offset
                        ]);

                        $current_offset += $limit;
                        $albums = $results->albums;

                        foreach ($albums->items as $album) {
                            $release_date = explode('-', $album->release_date);
                            if (isset($release_date[0]) && isset($release_date[1]) &&
                                intval($today_exploded[0], 10) == intval($release_date[0], 10) &&
                                intval($today_exploded[1], 10) == intval($release_date[1], 10)) {

                                $last_day_matches = isset($today_exploded[2]) && isset($release_date[2]) &&
                                    intval($today_exploded[2], 10) == intval($release_date[2], 10);

                                $previous_day_matches = isset($today_exploded[2]) && isset($release_date[2]) &&
                                    (intval($today_exploded[2], 10) - 1) == intval($release_date[2], 10);

                                if ($get_all || $last_day_matches || $previous_day_matches) {
                                    $cache['albums'][$album->id] = $album;
                                }
                            }
                        }
                    } catch (\SpotifyWebAPI\SpotifyWebAPIException $e) {
                        if ($e->getCode() === 400) {
                            $cache['filled_albums'] = true;
                            file_put_contents($cache_filename, serialize($cache));
                            break;
                        }
                    }
                } while (true);
            } catch (Exception $e) {
                print $e->getMessage();
            }
        }

        ////////////////////// ARTISTS//////////////////////////////////////////////////////////////////////////////////

        if (!$cache['filled_artists']) {
            $artists = [];
            foreach ($cache['albums'] as $album_id => $album) {
                foreach ($album->artists as $artist) {
                    if (!in_array($artist->id, $artists)) {
                        $artists[] = $artist->id;
                    }
                }
            }

            $artists = array_chunk($artists, 50);

            try {
                foreach ($artists as $artist_list) {
                    usleep(100 * 1000);
                    $results = $this->api->getArtists($artist_list);
                    foreach ($results->artists as $artist) {
                        $cache['artists'][$artist->id] = $artist;
                    }
                }

                $cache['filled_artists'] = true;
                file_put_contents($cache_filename, serialize($cache));

            } catch (Exception $e) {
                print $e->getMessage();
            }
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        if (!$cache['filled_albums'] || !$cache['filled_artists']) {
            return $list;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        if(!$cache['filled_genres']) {
            foreach($cache['albums'] as $album_id => $album) {
                $cache['albums'][$album_id]->genres = [];

                foreach($cache['albums'][$album_id]->artists as $artist) {
                    foreach($cache['artists'][$artist->id]->genres as $genre) {
                        if(!in_array($genre, $cache['albums'][$album_id]->genres)) {
                            $cache['albums'][$album_id]->genres[] = $genre;
                        }
                    }
                }
            }

            $cache['filled_genres'] = true;
            file_put_contents($cache_filename, serialize($cache));
        }

        if(!$cache['filled_map']) {
            foreach($cache['albums'] as $album_id => $album) {
                foreach($cache['albums'][$album_id]->genres as $genre) {
                    $genre = strtolower($genre);
                    if(!isset($cache['genred_albums'][$genre])) {
                        $cache['genred_albums'][$genre] = [];
                    }

                    if(!in_array($album_id, $cache['genred_albums'][$genre])) {
                        $cache['genred_albums'][$genre][] = $album;
                    }
                }
            }

            $cache['filled_map'] = true;
            file_put_contents($cache_filename, serialize($cache));
        }
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $tags = [];
        foreach ($genres as $genre) {
            if (array_key_exists($genre, self::$genre_mappings)) {
                $tags[] = strtolower(self::$genre_mappings[$genre]);
            }
        }

        $added_ids = [];
        foreach($tags as $genre) {
            if(in_array($genre, $cache['genred_albums'])) {
                foreach ($cache['genred_albums'][$genre] as $album) {
                    if (!in_array($album->id, $added_ids)) {
                        $added_ids[] = $album->id;
                        $list[] = $album;
                    }
                }
            }
        }

        return $list;
    }
}
