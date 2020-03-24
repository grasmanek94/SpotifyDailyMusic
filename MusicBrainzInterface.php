<?php
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use MusicBrainz\Exception;
use MusicBrainz\Filters\ReleaseFilter;
use MusicBrainz\HttpAdapters\AbstractHttpAdapter;
use MusicBrainz\HttpAdapters\GuzzleFiveAdapter;
use MusicBrainz\MusicBrainz;
use MusicBrainz\Release;

require_once 'vendor/autoload.php';
require_once 'AlbumInterface.php';

class MusicBrainzInterface implements AlbumInterface
{
	private static $genre_mappings = [
		GENRE::INDIE_ROCK => 'indie rock',
		GENRE::HIP_HOP => 'hip hop',
		GENRE::INDIE_POP => 'indie pop',
		GENRE::ROCK => 'rock',
		GENRE::POP => 'pop',
		GENRE::SOUL => 'soul',
		GENRE::R_AND_B => 'r&b',
		GENRE::POST_PUNK => 'post punk',
		GENRE::SINGER_SONGWRITER => 'singer songwriter',
		GENRE::SYNTHPOP => 'synth-pop',
		GENRE::PSYCHEDELIC => 'psychedelic',
		GENRE::HOUSE => 'house',
		GENRE::POP_ROCK => 'pop rock',
		GENRE::ART_POP => 'art pop',
		GENRE::INDIETRONICA => 'indietronica',
		GENRE::ALTERNATIVE_R_AND_B => 'alternative r and b',
		GENRE::AMBIENT_POP => 'ambient pop',
		GENRE::TRAP_RAP => 'trap rap',
		GENRE::ABSTRACT_HIP_HOP => 'abstract hip hop',
		GENRE::ACID_HOUSE => 'acid house',
		GENRE::ACID_JAZZ => 'acid jazz',
		GENRE::ACID_TECHNO => 'acid techno',
		GENRE::ACOUSTIC_ROCK => 'acoustic rock',
		GENRE::ADULT_CONTEMPORARY => 'adult contemporary',
		GENRE::AFROBEAT => 'afrobeat',
		GENRE::AFROBEATS => 'afrobeats',
		GENRE::ALT_COUNTRY => 'alt country',
		GENRE::ALTERNATIVE_DANCE => 'alternative dance',
		GENRE::ALTERNATIVE_METAL => 'alternative metal',
		GENRE::ALTERNATIVE_ROCK => 'alternative rock',
		GENRE::AMBIENT => 'ambient',
		GENRE::AMBIENT_HOUSE => 'ambient house',
		GENRE::AMBIENT_TECHNO => 'ambient techno',
		GENRE::AMERICAN_PRIMITIVISM => 'american primitivism',
		GENRE::AMERICANA => 'americana',
		GENRE::AOR => 'aor',
		GENRE::ART_PUNK => 'art punk',
		GENRE::ART_ROCK => 'art rock',
		GENRE::ATMOSPHERIC_BLACK_METAL => 'atmospheric black metal',
		GENRE::AVANT_FOLK => 'avant folk',
		GENRE::AVANT_GARDE_JAZZ => 'avant-garde jazz',
		GENRE::AVANT_GARDE_METAL => 'avant-garde metal',
		GENRE::BAROQUE_POP => 'baroque pop',
		GENRE::BASS => 'bass',
		GENRE::BIG_BEAT => 'big beat',
		GENRE::BLACK_METAL => 'black metal',
		GENRE::BLACKGAZE => 'blackgaze',
		GENRE::BLUE_EYED_SOUL => 'blue-eyed soul',
		GENRE::BLUEGRASS => 'bluegrass',
		GENRE::BLUES => 'blues',
		GENRE::BLUES_ROCK => 'blues rock',
		GENRE::BOSSA_NOVA => 'bossa nova',
		GENRE::BREAKBEAT => 'breakbeat',
		GENRE::BRITPOP => 'britpop',
		GENRE::BUBBLEGUM_BASS => 'bubblegum bass',
		GENRE::CELTIC_PUNK => 'celtic punk',
		GENRE::CHAMBER_FOLK => 'chamber folk',
		GENRE::CHAMBER_POP => 'chamber pop',
		GENRE::CHILDREN_S_MUSIC => 'children s music',
		GENRE::CHILLWAVE => 'chillwave',
		GENRE::CHRISTMAS => 'christmas',
		GENRE::CITY_POP => 'city pop',
		GENRE::CLASSIC_ROCK => 'classic rock',
		GENRE::CLASSICAL => 'classical',
		GENRE::COMEDY => 'comedy',
		GENRE::COMEDY_RAP => 'comedy rap',
		GENRE::COUNTRY => 'country',
		GENRE::COUNTRY_POP => 'country pop',
		GENRE::COUNTRY_ROCK => 'country rock',
		GENRE::COUNTRY_SOUL => 'country soul',
		GENRE::DANCE => 'dance',
		GENRE::DANCE_POP => 'dance-pop',
		GENRE::DANCE_PUNK => 'dance-punk',
		GENRE::DANCE_ROCK => 'dance rock',
		GENRE::DANCEHALL => 'dancehall',
		GENRE::DARK_AMBIENT => 'dark ambient',
		GENRE::DARK_JAZZ => 'dark jazz',
		GENRE::DARKWAVE => 'darkwave',
		GENRE::DEATH_INDUSTRIAL => 'death industrial',
		GENRE::DEATH_METAL => 'death metal',
		GENRE::DEATHCORE => 'deathcore',
		GENRE::DECONSTRUCTED_CLUB => 'deconstructed club',
		GENRE::DEEP_HOUSE => 'deep house',
		GENRE::DIGITAL_HARDCORE => 'digital hardcore',
		GENRE::DISCO => 'disco',
		GENRE::DOOM_METAL => 'doom metal',
		GENRE::DOWNTEMPO => 'downtempo',
		GENRE::DREAM_POP => 'dream pop',
		GENRE::DRILL => 'drill',
		GENRE::DRONE => 'drone',
		GENRE::DRONE_METAL => 'drone metal',
		GENRE::DRUM_AND_BASS => 'drum and bass',
		GENRE::DUB => 'dub',
		GENRE::DUB_TECHNO => 'dub techno',
		GENRE::DUBSTEP => 'dubstep',
		GENRE::EDM => 'edm',
		GENRE::ELECTRO => 'electro',
		GENRE::ELECTRO_HOUSE => 'electro house',
		GENRE::ELECTRO_DISCO => 'electro disco',
		GENRE::ELECTRO_INDUSTRIAL => 'electro-industrial',
		GENRE::ELECTROACOUSTIC => 'electroacoustic',
		GENRE::ELECTROCLASH => 'electroclash',
		GENRE::ELECTRONIC => 'electronic',
		GENRE::ELECTRONICA => 'electronica',
		GENRE::ELECTROPOP => 'electropop',
		GENRE::EMO => 'emo',
		GENRE::ETHEREAL_WAVE => 'ethereal wave',
		GENRE::EXPERIMENTAL => 'experimental',
		GENRE::EXPERIMENTAL_HIP_HOP => 'experimental hip hop',
		GENRE::EXPERIMENTAL_METAL => 'experimental metal',
		GENRE::EXPERIMENTAL_ROCK => 'experimental rock',
		GENRE::FOLK => 'folk',
		GENRE::FOLK_METAL => 'folk metal',
		GENRE::FOLK_POP => 'folk pop',
		GENRE::FOLK_PUNK => 'folk punk',
		GENRE::FOLK_ROCK => 'folk rock',
		GENRE::FOLKTRONICA => 'folktronica',
		GENRE::FOOTWORK => 'footwork',
		GENRE::FREAK_FOLK => 'freak folk',
		GENRE::FREE_IMPROVISATION => 'free improvisation',
		GENRE::FRENCH_HOUSE => 'french house',
		GENRE::FRENCH_POP => 'french pop',
		GENRE::FUNK => 'funk',
		GENRE::FUNK_METAL => 'funk metal',
		GENRE::FUNK_ROCK => 'funk rock',
		GENRE::FUTURE_BASS => 'future bass',
		GENRE::FUTURE_FUNK => 'future funk',
		GENRE::FUTURE_GARAGE => 'future garage',
		GENRE::GARAGE_PUNK => 'garage punk',
		GENRE::GARAGE_ROCK => 'garage rock',
		GENRE::GLAM_METAL => 'glam metal',
		GENRE::GLAM_ROCK => 'glam rock',
		GENRE::GLITCH => 'glitch',
		GENRE::GLITCH_HOP => 'glitch hop',
		GENRE::GLITCH_POP => 'glitch pop',
		GENRE::GOSPEL => 'gospel',
		GENRE::GOTHIC_METAL => 'gothic metal',
		GENRE::GOTHIC_ROCK => 'gothic rock',
		GENRE::GRIME => 'grime',
		GENRE::GRINDCORE => 'grindcore',
		GENRE::GROOVE_METAL => 'groove metal',
		GENRE::GRUNGE 	=> 'grunge',
		GENRE::HARD_ROCK => 'hard rock',
	    GENRE::HARDCORE_PUNK => 'hardcore punk',
	    GENRE::HEARTLAND_ROCK => 'heartland rock',
	    GENRE::HEAVY_METAL => 'heavy metal',
	    GENRE::HEAVY_PSYCH => 'heavy psych',
	    GENRE::HIP_HOUSE => 'hip house',
	    GENRE::HYPNAGOGIC_POP => 'hypnagogic pop',
	    GENRE::IDM => 'idm',
	    GENRE::INDIE_FOLK => 'indie folk',
	    GENRE::INDUSTRIAL => 'industrial',
	    GENRE::INDUSTRIAL_METAL => 'industrial metal',
		GENRE::JAZZ => 'jazz',
		GENRE::NEW_AGE => 'new age',
		GENRE::REGGAE => 'reggae'
	];

	/**
	 * @var MusicBrainz
	 */
	private $brainz;

	/**
	 * @var ReflectionProperty
	 */
	private $adapter;

	/**
	 * @var ReflectionProperty
	 */
	private $data_adapter;


	public function __construct()
	{
		$this->brainz = new MusicBrainz(new GuzzleAlbumAdapter(new Client()));
		$this->brainz->setUserAgent('SpotifyDailyMusic' . strval(rand()), '1.0', 'gz0.nl');

		$this->adapter = new \ReflectionProperty(MusicBrainz::class, 'adapter');
		$this->adapter->setAccessible(true);

		$this->data_adapter = new \ReflectionProperty(Release::class, 'data');
		$this->data_adapter->setAccessible(true);
	}

	/**
	 * @param string $date
	 * @param string[] $tags
	 * @param int $limit
	 * @param int|null $offset
	 * @param int $count
	 * @return mixed
	 * @throws Exception
	 */
	private function albums($date, $tags, $limit, $offset, &$count)
	{
		if ($limit > 100 || $limit < 1) {
			throw new Exception('Limit can only be between 1 and 100');
		}

		$filter = new ReleaseFilter([]);
		$params = $filter->createParameters(array('limit' => $limit, 'offset' => $offset, 'fmt' => 'json'));

		$params['query'] .= "format:(Digital Media)+AND+date:(" . $date . ")";

		// this is a more efficient search for multiple genres, compared to making a request per genre
		$params['query'] .= "+AND+(";
		$params['query'] .= "tag:(" . join(")+OR+tag:(",$tags ) . ")";
		$params['query'] .= ")";
		$params['query'] = str_replace(' ', '+', $params['query']);

		$response = $this->adapter->getValue($this->brainz)->call($filter->getEntity() . '/', $params, $this->brainz->getHttpOptions(), FALSE, TRUE);

		$count = 0;
		if(isset($response['count']))
		{
			$count = intval($response['count']);
		}

		return $filter->parseResponse($response, $this->brainz);
	}

	public function getAllAlbums($input_date, $genres, $get_all)
	{
		$list = [];

		if($get_all) {
			$date = $input_date->format('Y-m');
		} else {
			$date = $input_date->format('Y-m-d');
		}

		$tags = [];
		foreach($genres as $genre)
		{
			if(array_key_exists($genre, self::$genre_mappings))
			{
				$tags[] = urlencode(self::$genre_mappings[$genre]);
			}
		}

		try {
			$current_offset = 0;
			$limit = 100;
			$total = 0;

			do
			{
				$recordings = $this->albums($date, $tags, $limit, $current_offset, $total);

				$current_offset += $limit;
				foreach ($recordings as $release)
				{
					$data = $this->data_adapter->getValue($release);
					if (isset($release->title) && isset($data['artist-credit'][0]['name']))
					{
						$album = $release->title;
						$artist = $data['artist-credit'][0]['name'];
						if (strlen($album) > 0 && strlen($artist) > 0)
						{
							$list[] = new AlbumEntry($album, $artist);
						}
					}
				}
			}
			while($current_offset < $total);

		} catch (Exception $e) {
			print $e->getMessage();
		}

		return $list;
	}
}

class GuzzleAlbumAdapter extends AbstractHttpAdapter
{
	/**
	 * @var ClientInterface
	 */
	private $client;

	/**
	 * Initialize class
	 *
	 * @param ClientInterface $client
	 * @param null $endpoint
	 */
	public function __construct(ClientInterface $client, $endpoint = NULL)
	{
		$this->client = $client;

		if (filter_var($endpoint, FILTER_VALIDATE_URL)) {
			$this->endpoint = $endpoint;
		}
	}

	/**
	 * Perform an HTTP request on MusicBrainz
	 *
	 * @param  string $path
	 * @param  array $params
	 * @param  array $options
	 * @param  boolean $isAuthRequired
	 * @param  boolean $returnArray
	 *
	 * @throws Exception
	 * @return array
	 */
	public function call($path, array $params = array(), array $options = array(), $isAuthRequired = FALSE, $returnArray = FALSE)
	{
		if ($options['user-agent'] == '') {
			throw new Exception('You must set a valid User Agent before accessing the MusicBrainz API');
		}

		$requestOptions = [
			'headers'        => [
				'Accept'     => 'application/json',
				'User-Agent' => $options['user-agent']
			],
			'query' => []
		];

		if ($isAuthRequired) {
			if ($options['user'] != NULL && $options['password'] != NULL) {
				$requestOptions['auth'] = [
					'username' => $options['user'],
					'password' => $options['password'],
					CURLAUTH_DIGEST
				];
			} else {
				throw new Exception('Authentication is required');
			}
		}

		$params = urldecode(http_build_query($params));
		$request = $this->client->createRequest('GET', $this->endpoint . '/' . $path . '?' . $params, $requestOptions);

		// musicbrainz throttle
		sleep(1);

		return $this->client->send($request)->json();
	}
}