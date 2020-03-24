<?php

use voku\helper\HtmlDomParser;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;

require_once 'vendor/autoload.php';

const DEBUG = false;

class SpotifyMusicDiscoveryCreator
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
	 * @var string
	 */
	private $data_directory;

	/**
	 * @var string[]
	 */
	private static $months = [
		"",
		"january-01",
		"february-02",
		"march-03",
		"april-04",
		"may-05",
		"june-06",
		"july-07",
		"august-08",
		"september-09",
		"october-10",
		"november-11",
		"december-12"
	];

	/**
	 * SpotifyMusicDiscoveryCreator constructor.
	 * @param string $base_name
	 * @param int[] $genres
	 * @param DateTime $date
	 * @param string $data_directory
	 */
	public function __construct($base_name = "", $genres = [], $date = null, $data_directory = "data")
	{
		if($date === null)
		{
			$date = new \DateTime('now');
		}

		self::setGenres($genres);
		self::setDate($date);
		self::setBaseName($base_name);

		$this->api = new SpotifyWebAPI();
		$this->data_directory = $data_directory;

		usleep(100 * 1000);
		$this->api->setAccessToken(self::getAccessToken());
	}

	public function run()
	{
		$playlist = self::getPlaylist();
		$current_tracks = self::getPlaylistCurrentTracks($playlist->id);

		$albums = self::getAllAlbums();

		if(DEBUG)
		{
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

	private function getBaseUrl()
	{
		return "https://www.albumoftheyear.org/" . $this->year . "/releases/" . self::$months[$this->month] . ".php?genre=";
	}

	private function getAlbums($url)
	{
		$list = [];

		$dom = HtmlDomParser::str_get_html(file_get_contents($url));

		foreach ($dom->findMulti('.albumBlock') as $albumBlock)
		{
			$date = $albumBlock->find(".date")->innertext[0];
			$exploded_date = explode(' ', $date);

			$continue = $this->get_all;
			if(!$continue && count($exploded_date) == 2)
			{
				$day_of_month = $exploded_date[1]; // Mar 20
				$continue = ($day_of_month <= $this->day);
			}

			if($continue)
			{
				$artist = $albumBlock->find(".artistTitle")->innertext[0];
				$album = $albumBlock->find(".albumTitle")->innertext[0];

				if(strlen($artist) > 0 && strlen($album) > 0)
				{
					$list[] = [
						"artist" => $artist,
						"album" => $album
					];
				}
			}
		}

		return $list;
	}

	private function getAllAlbums()
	{
		$list = [];

		foreach($this->genres as $genre)
		{
			$url = self::getBaseUrl() . $genre;
			$list = array_merge($list, self::getAlbums($url));
		}

		return array_unique($list, SORT_REGULAR);
	}

	private function getAccessToken()
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

		$session = new Session(
			$client_id,
			$client_secret,
			$redirect_uri
		);

		if (strlen($code) > 0 && strlen($access_token) <= 0)
		{
			usleep(100 * 1000);
			$session->requestAccessToken($code);

			$access_token = $session->getAccessToken();
			$refresh_token = $session->getRefreshToken();

			file_put_contents($token_file, $access_token);
			file_put_contents($refresh_file, $refresh_token);
		}
		else if(strlen($code) <= 0)
		{
			$options = [
				'scope' => [
					'playlist-read-collaborative',
					'playlist-modify-public',
					'playlist-read-private',
					'playlist-modify-private'
				],
			];

			usleep(100 * 1000);
			print_r($session->getAuthorizeUrl($options));
			die();
		}

		usleep(100 * 1000);
		if($session->refreshAccessToken($refresh_token))
		{
			$access_token = $session->getAccessToken();
			$refresh_token = $session->getRefreshToken();

			file_put_contents($token_file, $access_token);
			file_put_contents($refresh_file, $refresh_token);
		}

		return $access_token;
	}

	private function getPlaylist()
	{
		$current_offset = 0;
		$limit = 50;

		$current_playlist = null;
		do
		{
			usleep(100 * 1000);
			$playlists = $this->api->getMyPlaylists(['limit' => $limit, 'offset' => $current_offset]);
			$current_offset += $limit;
			foreach ($playlists->items as $playlist)
			{
				if(isset($playlist->name) && $playlist->name === $this->playlist_name)
				{
					$current_playlist = $playlist;
					break;
				}
			}
		}
		while($current_offset < $playlists->total);

		if($current_playlist === null)
		{
			usleep(100 * 1000);
			$current_playlist = $this->api->createPlaylist([
				'name' => $this->playlist_name,
				'public' => true,
				'collaborative' => false,
				'description' => "Albums released in " . $this->release_name . " (UPDATED DAILY!) | Suggestions can be mailed to playlists@gz0.nl"
			]);

			if(!isset($current_playlist->id))
			{
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

		do
		{
			usleep(100 * 1000);
			$results = $this->api->getPlaylistTracks($current_playlist_id, [
				'limit' => $limit,
				'offset' => $current_offset
			]);

			$current_offset += $limit;
			$total = $results->total;

			if(isset($results->items))
			{
				foreach ($results->items as $spotify_item)
				{
					if (isset($spotify_item->track->id))
					{
						$spotify_playlist_tracks[] = trim($spotify_item->track->id);
					}
				}
			}
		}
		while($current_offset < $total);

		return $spotify_playlist_tracks;
	}

	private function checkAlbumNameAndArtist($album, $artist_name, $album_name)
	{
		if(!isset($album->name) || !isset($album->artists))
		{
			return false;
		}

		if($album->name !== $album_name)
		{
			return false;
		}

		foreach($album->artists as $artist)
		{
			if(isset($artist->name) && $artist->name === $artist_name)
			{
				return true;
			}
		}
		return false;
	}

	private function convertToSpotifyAlbums($albums)
	{
		$spotify_albums = [];

		foreach ($albums as $entry)
		{
			$query = "album:" . $entry['album'] . " artist:" . $entry['artist'] . " year:" . $this->year;

			$current_offset = 0;
			$limit = 50;
			$total = 0;

			do
			{
				usleep(100 * 1000);
				$results = $this->api->search($query, "album", [
					'limit' => $limit,
					'offset' => $current_offset
				]);

				if(DEBUG)
				{
					echo("\r\n===ALBUM Searching for: ");
					print_r($entry);
					echo("\r\nQuery: ");
					print_r($query);
					echo("\r\n");
				}

				if(isset($results->albums))
				{
					$current_offset += $limit;
					$total = $results->albums->total;

					foreach ($results->albums->items as $spotify_album)
					{
						if (self::checkAlbumNameAndArtist($spotify_album, $entry['artist'], $entry['album']))
						{
							$spotify_albums[] = $spotify_album->id;

							if(DEBUG)
							{
								print_r($spotify_album);
							}
						}
					}
				}

				if(DEBUG)
				{
					echo("===ALBUM\r\n");
				}
			}
			while($current_offset < $total);
		}

		return $spotify_albums;
	}

	private function getNewSpotifyTracks($spotify_albums, $spotify_playlist_tracks)
	{
		$spotify_tracks = [];

		foreach($spotify_albums as $spotify_album)
		{
			$current_offset = 0;
			$limit = 50;
			$total = 0;
			do
			{
				usleep(100 * 1000);
				$results = $this->api->getAlbumTracks($spotify_album, [
					'limit' => $limit,
					'offset' => $current_offset
				]);

				$current_offset += $limit;
				$total = $results->total;

				if(DEBUG)
				{
					echo("\r\n===TRACKS Spotify Album ID: ");
					print_r($spotify_album);
					echo("\r\n");
				}

				foreach ($results->items as $spotify_track)
				{
					if( isset($spotify_track->id) &&
						isset($spotify_track->type) &&
						$spotify_track->type === 'track' &&
						!in_array(trim($spotify_track->id), $spotify_playlist_tracks) &&
						!in_array(trim($spotify_track->id), $spotify_tracks)
					)
					{
						$spotify_tracks[] = trim($spotify_track->id);

						if(DEBUG)
						{
							print_r($spotify_track);
						}
					}
				}

				if(DEBUG)
				{
					echo("===TRACKS\r\n");
				}
			}
			while($current_offset < $total);
		}

		return $spotify_tracks;
	}

	private function updatePlaylistWithTracks($playlist_id, $spotify_tracks)
	{
		$spotify_tracks_chunks = array_chunk($spotify_tracks, 100);

		if(DEBUG)
		{
			echo("==== ADD TRACKS:\r\n");
		}

		foreach($spotify_tracks_chunks as $chunk)
		{
			usleep(100 * 1000);
			$this->api->addPlaylistTracks($playlist_id, $chunk);
			if(DEBUG)
			{
				print_r($chunk);
			}
		}
	}
}

class GENRE
{
	const INDIE_ROCK = 1;
	const HIP_HOP = 3;
	const INDIE_POP = 4;
	const ROCK = 7;
	const POP = 15;
	const SOUL = 19;
	const R_AND_B = 22;
	const POST_PUNK = 23;
	const SINGER_SONGWRITER = 37;
	const SYNTHPOP = 38;
	const PSYCHEDELIC = 46;
	const HOUSE = 53;
	const POP_ROCK = 103;
	const ART_POP = 141;
	const INDIETRONICA = 156;
	const ALTERNATIVE_R_AND_B = 192;
	const AMBIENT_POP = 201;
	const TRAP_RAP = 213;
	const ABSTRACT_HIP_HOP = 234;
	const ACID_HOUSE = 137;
	const ACID_JAZZ = 112;
	const ACID_TECHNO = 275;
	const ACOUSTIC_ROCK = 182;
	const ADULT_CONTEMPORARY = 204;
	const AFROBEAT = 122;
	const AFROBEATS = 268;
	const ALT_COUNTRY = 17;
	const ALTERNATIVE_DANCE = 114;
	const ALTERNATIVE_METAL = 57;
	const ALTERNATIVE_ROCK = 2;
	const AMBIENT = 34;
	const AMBIENT_HOUSE = 278;
	const AMBIENT_TECHNO = 160;
	const AMERICAN_PRIMITIVISM = 246;
	const AMERICANA = 70;
	const AOR = 286;
	const ART_PUNK = 100;
	const ART_ROCK = 99;
	const ATMOSPHERIC_BLACK_METAL = 171;
	const AVANT_FOLK = 186;
	const AVANT_GARDE_JAZZ = 173;
	const AVANT_GARDE_METAL = 152;
	const BAROQUE_POP = 136;
	const BASS = 163;
	const BIG_BEAT = 183;
	const BLACK_METAL = 62;
	const BLACKGAZE = 217;
	const BLUE_EYED_SOUL = 250;
	const BLUEGRASS = 76;
	const BLUES = 43;
	const BLUES_ROCK = 66;
	const BOSSA_NOVA = 264;
	const BREAKBEAT = 276;
	const BRITPOP = 79;
	const BUBBLEGUM_BASS = 258;
	const CELTIC_PUNK = 164;
	const CHAMBER_FOLK = 225;
	const CHAMBER_POP = 63;
	const CHILDREN_S_MUSIC = 272;
	const CHILLWAVE = 36;
	const CHRISTMAS = 166;
	const CITY_POP = 273;
	const CLASSIC_ROCK = 44;
	const CLASSICAL = 143;
	const COMEDY = 64;
	const COMEDY_RAP = 262;
	const COUNTRY = 18;
	const COUNTRY_POP = 216;
	const COUNTRY_ROCK = 207;
	const COUNTRY_SOUL = 285;
	const DANCE = 132;
	const DANCE_POP = 174;
	const DANCE_PUNK = 73;
	const DANCE_ROCK = 96;
	const DANCEHALL = 80;
	const DARK_AMBIENT = 91;
	const DARK_JAZZ = 240;
	const DARKWAVE = 172;
	const DEATH_INDUSTRIAL = 187;
	const DEATH_METAL = 89;
	const DEATHCORE = 138;
	const DECONSTRUCTED_CLUB = 248;
	const DEEP_HOUSE = 158;
	const DIGITAL_HARDCORE = 283;
	const DISCO = 118;
	const DOOM_METAL = 51;
	const DOWNTEMPO = 128;
	const DREAM_POP = 39;
	const DRILL = 289;
	const DRONE = 20;
	const DRONE_METAL = 179;
	const DRUM_AND_BASS = 147;
	const DUB = 74;
	const DUB_TECHNO = 82;
	const DUBSTEP = 12;
	const EDM = 263;
	const ELECTRO = 85;
	const ELECTRO_HOUSE = 190;
	const ELECTRO_DISCO = 197;
	const ELECTRO_INDUSTRIAL = 249;
	const ELECTROACOUSTIC = 236;
	const ELECTROCLASH = 181;
	const ELECTRONIC = 6;
	const ELECTRONICA = 115;
	const ELECTROPOP = 31;
	const EMO = 133;
	const ETHEREAL_WAVE = 224;
	const EXPERIMENTAL = 9;
	const EXPERIMENTAL_HIP_HOP = 214;
	const EXPERIMENTAL_METAL = 148;
	const EXPERIMENTAL_ROCK = 8;
	const FOLK = 5;
	const FOLK_METAL = 154;
	const FOLK_POP = 247;
	const FOLK_PUNK = 145;
	const FOLK_ROCK = 14;
	const FOLKTRONICA = 104;
	const FOOTWORK = 161;
	const FREAK_FOLK = 127;
	const FREE_IMPROVISATION = 205;
	const FRENCH_HOUSE = 117;
	const FRENCH_POP = 223;
	const FUNK = 24;
	const FUNK_METAL = 232;
	const FUNK_ROCK = 233;
	const FUTURE_BASS = 269;
	const FUTURE_FUNK = 282;
	const FUTURE_GARAGE = 102;
	const GARAGE_PUNK = 92;
	const GARAGE_ROCK = 11;
	const GLAM_METAL = 266;
	const GLAM_ROCK = 72;
	const GLITCH = 129;
	const GLITCH_HOP = 150;
	const GLITCH_POP = 191;
	const GOSPEL = 144;
	const GOTHIC_METAL = 105;
	const GOTHIC_ROCK = 157;
	const GRIME = 61;
	const GRINDCORE = 146;
	const GROOVE_METAL = 135;
	const GRUNGE = 86;
	const HARD_ROCK = 58;
	const HARDCORE_PUNK = 49;
	const HEARTLAND_ROCK = 206;
	const HEAVY_METAL = 93;
	const HEAVY_PSYCH = 235;
	const HIP_HOUSE = 244;
	const HYPNAGOGIC_POP = 208;
	const IDM = 47;
	const INDIE_FOLK = 16;
	const INDUSTRIAL = 52;
	const INDUSTRIAL_METAL = 107;
}

$now = new DateTime('now');
$creator = new SpotifyMusicDiscoveryCreator();

function UpdatePlaylist($base_name, $genres)
{
	global $now;
	global $creator;
	
	$creator->setDate($now);
	$creator->setBaseName($base_name . " - Daily Album Releases - by GZ0.NL");
	$creator->setGenres($genres);
	$creator->run();
}

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
