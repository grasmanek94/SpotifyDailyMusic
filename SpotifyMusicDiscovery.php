<?php

use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;

require_once 'vendor/autoload.php';
require_once 'AlbumInterface.php';

class SpotifyMusicDiscovery
{
    /**
     * @var int
     */
    private $year;

    /**
     * @var int
     */
    private $month;

    /**
     * @var int
     */
    private $day;

    /**
     * @var boolean
     */
    private $get_all;

    /**
     * @var string
     */
    private $base_name;

    /**
     * @var string
     */
    private $playlist_name;

    /**
     * @var string
     */
    private $release_name;

    /**
     * @var int[]
     */
    private $genres;

    /**
     * @var DateTime
     */
    private $date;

    /**
     * @var SpotifyWebAPI
     */
    private $api;

    /**
     * @var
     */
    private $session;

    /**
     * @var string
     */
    private $data_directory;

    /**
     * @var AlbumInterface[]
     */
    private $album_interfaces;

    private $single_songs_from_albums;

    /**
     * SpotifyMusicDiscoveryCreator constructor.
     * @param string $base_name
     * @param int[] $genres
     * @param DateTime|null $date
     * @param AlbumInterface[] $album_interfaces
     * @param string $data_directory
     */
    public function __construct(string $base_name = "", array $genres = [], DateTime $date = null, array $album_interfaces = [], string $data_directory = "data")
    {
        if ($date === null) {
            $date = new \DateTime('now');
        }

        self::setGenres($genres);
        self::setDate($date);
        self::setBaseName($base_name);

        $this->data_directory = $data_directory;

        usleep(100 * 1000);

        self::initSpotifyWebApi();

        $this->album_interfaces = $album_interfaces;
        $this->single_songs_from_albums = false;
    }

    public function getSpotifyApi(): SpotifyWebAPI
    {
        return $this->api;
    }

    public function run()
    {
        $playlist = self::getPlaylist();
        $current_tracks = self::getPlaylistCurrentTracks($playlist->id);

        $albums = self::getAllAlbums();

        if (DEBUG) {
            print_r($albums);
            echo("\r\n");
        }

        $spotify_albums = self::convertToSpotifyAlbums($albums);

        $spotify_tracks = self::getNewSpotifyTracks($spotify_albums, $current_tracks);

        self::updatePlaylistWithTracks($playlist->id, $spotify_tracks);
    }

    /**
     * @param int[] $genres
     */
    public function setGenres($genres)
    {
        $this->genres = $genres;
    }

    /**
     * @param boolean $single_songs_from_albums
     */
    public function setSingleSongsFromAlbums($single_songs_from_albums)
    {
        $this->single_songs_from_albums = $single_songs_from_albums;
    }

    /**
     * @param string $base_name
     */
    public function setBaseName($base_name)
    {
        $this->base_name = $base_name;

        $this->playlist_name = $this->base_name . " " . $this->date->format('Y-m (F Y)');
        $this->release_name = $this->date->format('F Y');
    }

    /**
     * @param DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;

        $this->day = $date->format('j');
        $this->month = $date->format('n');
        $this->year = $date->format('Y');
        $this->get_all = ($this->day == $date->format('t'));

        self::setBaseName($this->base_name);
    }

    public function addAlbumInterface($album_interface)
    {
        if ($album_interface instanceof AlbumInterface) {
            $this->album_interfaces[] = $album_interface;
        }
    }

    private function getAllAlbums()
    {
        $list = [];

        foreach ($this->album_interfaces as $album_interface) {
            $list = array_merge($list, $album_interface->getAllAlbums($this->date, $this->genres, $this->get_all));
        }

        return array_unique($list, SORT_REGULAR);
    }


    public function updateTokens()
    {
        $token_file = $this->data_directory . DIRECTORY_SEPARATOR . "token.txt";
        $refresh_file = $this->data_directory . DIRECTORY_SEPARATOR . "refresh.txt";

        $access_token = $this->session->getAccessToken();
        $refresh_token = $this->session->getRefreshToken();

        file_put_contents($token_file, $access_token);
        file_put_contents($refresh_file, $refresh_token);
    }

    private function initSpotifyWebApi()
    {
        $code_file = $this->data_directory . DIRECTORY_SEPARATOR . "code.txt";
        $token_file = $this->data_directory . DIRECTORY_SEPARATOR . "token.txt";
        $refresh_file = $this->data_directory . DIRECTORY_SEPARATOR . "refresh.txt";
        $client_id_file = $this->data_directory . DIRECTORY_SEPARATOR . "id.txt";
        $client_secret_file = $this->data_directory . DIRECTORY_SEPARATOR . "secret.txt";
        $redirect_uri_file = $this->data_directory . DIRECTORY_SEPARATOR . "uri.txt";

        touch($code_file);
        touch($token_file);
        touch($refresh_file);
        touch($client_id_file);
        touch($client_secret_file);
        touch($redirect_uri_file);

        $code = file_get_contents($code_file);
        $access_token = file_get_contents($token_file);
        $refresh_token = file_get_contents($refresh_file);
        $client_id = file_get_contents($client_id_file);
        $client_secret = file_get_contents($client_secret_file);
        $redirect_uri = file_get_contents($redirect_uri_file);

        $this->session = new Session(
            $client_id,
            $client_secret,
            $redirect_uri
        );

        if (strlen($access_token) > 0) {
            $this->session->setAccessToken($access_token);
            $this->session->setRefreshToken($refresh_token);
        } else if (strlen($refresh_token) > 0) {
            // Or request a new access token
            $this->session->refreshAccessToken($refresh_token);
        } else {
            $options = [
                'scope' => [
                    'playlist-read-collaborative',
                    'playlist-modify-public',
                    'playlist-read-private',
                    'playlist-modify-private'
                ],
            ];

            usleep(100 * 1000);
            print_r($this->session->getAuthorizeUrl($options));
            die();
        }

        $options = [
            'auto_refresh' => true,
        ];

        $this->api = new SpotifyWebAPI($options, $this->session);

        self::updateTokens();
    }

    private function getPlaylist()
    {
        $current_offset = 0;
        $limit = 50;

        $current_playlist = null;
        do {
            usleep(100 * 1000);
            $playlists = $this->api->getMyPlaylists(['limit' => $limit, 'offset' => $current_offset]);

            self::updateTokens();

            $current_offset += $limit;
            foreach ($playlists->items as $playlist) {
                if (isset($playlist->name) && $playlist->name === $this->playlist_name) {
                    $current_playlist = $playlist;
                    break;
                }
            }
        } while ($current_offset < $playlists->total);

        if ($current_playlist === null) {
            usleep(100 * 1000);
            $description = "Albums released in " . $this->release_name . " (UPDATED DAILY!) | Suggestions can be mailed to playlists@gz0.nl";
            if ($this->single_songs_from_albums) {
                $description = "Tracks randomly chosen from albums released in " . $this->release_name . " (UPDATED DAILY!). Single track per Spotify Album! | Suggestions can be mailed to playlists@gz0.nl";
            }

            $current_playlist = $this->api->createPlaylist($this->api->me()->id, [
                'name' => $this->playlist_name,
                'public' => true,
                'collaborative' => false,
                'description' => $description
            ]);

            self::updateTokens();

            if (!isset($current_playlist->id)) {
                print_r($current_playlist);
                die();
            }
        }

        return $current_playlist;
    }

    private function getPlaylistCurrentTracks($current_playlist_id)
    {
        $spotify_playlist_tracks = [];

        $current_offset = 0;
        $limit = 50;
        $total = 0;

        $fields = 'total, items(track(id, name, type, album(id, name), artists(id, name)))';
        if (!$this->single_songs_from_albums) {
            $fields = 'total, items(track(id))';
        }

        do {
            usleep(100 * 1000);

            $results = $this->api->getPlaylistTracks($current_playlist_id, [
                'limit' => $limit,
                'offset' => $current_offset,
                'fields' => $fields
            ]);

            self::updateTokens();

            $current_offset += $limit;
            $total = $results->total;

            if (isset($results->items)) {
                foreach ($results->items as $spotify_item) {
                    if (isset($spotify_item->track->id)) {
                        if ($this->single_songs_from_albums) {
                            $spotify_playlist_tracks[] = $spotify_item->track;
                        } else {
                            $spotify_playlist_tracks[] = trim($spotify_item->track->id);
                        }
                    }
                }
            }
        } while ($current_offset < $total);

        return $spotify_playlist_tracks;
    }

    private function checkAlbumNameAndArtist($album, $artist_name, $album_name)
    {
        if (!isset($album->name) || !isset($album->artists)) {
            return false;
        }

        if ($album->name !== $album_name) {
            return false;
        }

        foreach ($album->artists as $artist) {
            if (isset($artist->name) && $artist->name === $artist_name) {
                return true;
            }
        }
        return false;
    }

    private function convertToSpotifyAlbums($albums)
    {
        $spotify_albums = [];

        foreach ($albums as $entry) {
            if(isset($entry->id) && isset($entry->type) && $entry->type === 'album') {
                $spotify_albums[] = $entry;
                continue;
            }

            try {
                $query = "album:" . $entry->album . " artist:" . $entry->artist . " year:" . $this->year;

                $current_offset = 0;
                $limit = 50;
                $total = 0;

                do {
                    usleep(100 * 1000);

                    $results = $this->api->search($query, "album", [
                        'limit' => $limit,
                        'offset' => $current_offset
                    ]);

                    self::updateTokens();

                    if (DEBUG) {
                        echo("\r\n===ALBUM Searching for: ");
                        print_r($entry);
                        echo("\r\nQuery: ");
                        print_r($query);
                        echo("\r\n");
                    }

                    if (isset($results->albums)) {
                        $current_offset += $limit;
                        $total = $results->albums->total;

                        foreach ($results->albums->items as $spotify_album) {
                            if (self::checkAlbumNameAndArtist($spotify_album, $entry->artist, $entry->album)) {
                                $spotify_albums[] = $spotify_album;

                                if (DEBUG) {
                                    print_r($spotify_album);
                                }
                            }
                        }
                    }

                    if (DEBUG) {
                        echo("===ALBUM\r\n");
                    }

                } while ($current_offset < $total);
            } catch (Exception $e) {
                self::updateTokens();
                if ($e->getMessage() != "Not found.") {
                    echo("Exception occurred: " . $e->getMessage() . "\n");
                }
            }
        }

        return $spotify_albums;
    }

    private function getNewSpotifyTracks($spotify_albums, $spotify_playlist_tracks)
    {
        $spotify_tracks = [];
        $already_added_albums = [];

        foreach ($spotify_albums as $spotify_album) {
            $current_offset = 0;
            $limit = 50;

            $total = 0;
            do {
                usleep(100 * 1000);
                $results = $this->api->getAlbumTracks($spotify_album->id, [
                    'limit' => $limit,
                    'offset' => $current_offset
                ]);

                self::updateTokens();

                $current_offset += $limit;
                $total = $results->total;

                if (DEBUG) {
                    echo("\r\n===TRACKS Spotify Album ID: ");
                    print_r($spotify_album->id);
                    echo("\r\n");
                }

                if (!$this->single_songs_from_albums) {
                    foreach ($results->items as $spotify_track) {
                        if (isset($spotify_track->id) &&
                            isset($spotify_track->type) &&
                            $spotify_track->type === 'track' &&
                            !in_array(trim($spotify_track->id), $spotify_playlist_tracks) &&
                            !in_array(trim($spotify_track->id), $spotify_tracks)
                        ) {
                            $spotify_tracks[] = trim($spotify_track->id);

                            if (DEBUG) {
                                print_r($spotify_track);
                            }
                        }
                    }
                } else {
                    $has_already_album_track = count(array_filter($spotify_playlist_tracks, function ($spotify_track) use ($spotify_album) {
                        $check_a = isset($spotify_track->id) &&
                            isset($spotify_track->type) &&
                            $spotify_track->type === 'track';

                        if (!$check_a) {
                            return false;
                        }

                        if ($spotify_track->album->id === $spotify_album->id) {
                            return true;
                        }

                        // Try to search by string name & artists
                        if ($spotify_track->album->name === $spotify_album->name) {
                            if (count($spotify_track->album->artists) == count($spotify_album->artists)) {
                                $matches = 0;
                                foreach ($spotify_track->album->artists as $track_artist) {
                                    foreach ($spotify_album->artists as $album_artist) {
                                        if ($track_artist->id === $album_artist->id) {
                                            $matches += 1;
                                        }
                                    }
                                }

                                if ($matches == count($spotify_track->album->artists)) {
                                    return true;
                                }
                            }
                        }

                        return false;
                    }));

                    $has_already_added_album_now = count(array_filter($already_added_albums, function ($added_album) use ($spotify_album) {
                        $check_a = isset($added_album->id) &&
                            isset($spotify_album->id);

                        if (!$check_a) {
                            return false;
                        }

                        if ($added_album->id === $spotify_album->id) {
                            return true;
                        }

                        // Try to search by string name & artists
                        if ($added_album->name === $spotify_album->name) {
                            if(count($added_album->artists) == count($spotify_album->artists)) {
                                $matches = 0;
                                foreach ($added_album->artists as $track_artist) {
                                    foreach ($spotify_album->artists as $album_artist) {
                                        if ($track_artist->id === $album_artist->id) {
                                            $matches += 1;
                                        }
                                    }
                                }

                                if ($matches == count($added_album->artists)) {
                                    return true;
                                }
                            }
                        }

                        return false;
                    }));

                    if ($has_already_album_track > 0 || $has_already_added_album_now > 0) {
                        $current_offset = $total; // exit loop
                    } else {
                        $valid_album_songs = array_filter($results->items, function ($spotify_track) {
                            return isset($spotify_track->id) &&
                                isset($spotify_track->type) &&
                                $spotify_track->type === 'track';
                        });

                        if (count($valid_album_songs) > 0) {
                            $current_offset = $total; // exit loop

                            $rand_key = array_rand($valid_album_songs, 1);
                            $random_chosen_track = $valid_album_songs[$rand_key];

                            $already_added_albums[] = $spotify_album;
                            $spotify_tracks[] = trim($random_chosen_track->id);
                        }
                    }
                }

                if (DEBUG) {
                    echo("===TRACKS\r\n");
                }
            } while ($current_offset < $total);
        }

        return $spotify_tracks;
    }

    private function updatePlaylistWithTracks($playlist_id, $spotify_tracks)
    {
        $spotify_tracks_chunks = array_chunk($spotify_tracks, 100);

        if (DEBUG) {
            echo("==== ADD TRACKS:\r\n");
        }

        foreach ($spotify_tracks_chunks as $chunk) {
            usleep(100 * 1000);
            $this->api->addPlaylistTracks($playlist_id, $chunk);

            self::updateTokens();

            if (DEBUG) {
                print_r($chunk);
            }
        }
    }
}
