<?php

use voku\helper\HtmlDomParser;

require_once 'vendor/autoload.php';
require_once 'AlbumInterface.php';

class AllMusicInterface implements AlbumInterface
{
	/**
	 * @var array
	 */
	private static $html_cache = [];

	private static $genre_mappings = [
		GENRE::BLUES => 'MA0000002467',
		GENRE::CHILDREN_S_MUSIC => 'MA0000002944',
		GENRE::CLASSICAL => 'MA0000002521',
		GENRE::COMEDY => 'MA0000004433',
		GENRE::COUNTRY => 'MA0000002532',
		GENRE::ELECTRONIC => 'MA0000002572',
		GENRE::FOLK => 'MA0000002592',
		GENRE::JAZZ => 'MA0000002674',
		GENRE::NEW_AGE => 'MA0000002745',
		GENRE::POP_ROCK => 'MA0000002613',
		GENRE::R_AND_B => 'MA0000002809',
		GENRE::REGGAE => 'MA0000002820'
	];

	/**
	 * @param string $path
	 * @return string
	 */
	private function getUrl($path)
	{
		return "https://www.allmusic.com/newreleases/all/" . $path;
	}

	/**
	 * @param string $data
	 * @param int $genre
	 * @return array
	 */
	private function getAlbums($data, $genre, $url)
	{
		if(!array_key_exists($genre, self::$genre_mappings))
		{
			return [];
		}

		$real_genre = self::$genre_mappings[$genre];

		$list = [];
		$dom = HtmlDomParser::str_get_html($data);

		foreach ($dom->findMulti('tr[data-genre-filter="' . $real_genre . '"]') as $album_block)
		{
			// echo($url . "\r\n");
			try
			{
				$artist = $album_block->find(".artist")->find("a")->innertext[0] ?? "";
				$album = $album_block->find(".album")->find("a")->innertext[0] ?? "";

				if(strlen($artist) > 0 && strlen($album) > 0)
				{
					$list[] = new AlbumEntry($album, $artist, $url);
				}
			}
			catch(Exception $e)
			{
				// echo("Exception: " . $e->getMessage() . " on " . $url . "\r\n");
			}
		}

		return $list;
	}

	public function getAllAlbums($date, $genres, $get_all)
	{
		$end_of_month = new DateTime($date->format('Y-m-t'), $date->getTimezone());
		$end_of_month->sub(new DateInterval('P7D'));

		if($date > $end_of_month)
		{
			$date = $end_of_month;
		}

		$date_string = $date->format('Ymd');

 		$url = self::getUrl($date_string);

		if(!array_key_exists($date_string, self::$html_cache) || strlen(self::$html_cache[$date_string]) < 2)
		{

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

			self::$html_cache[$date_string] = curl_exec($ch);

			curl_close($ch);
		}

		if(strlen(self::$html_cache[$date_string]) < 2)
		{
			return [];
		}

		$list = [];

		foreach($genres as $genre)
		{
			$list = array_merge($list, self::getAlbums(self::$html_cache[$date_string], $genre, $url));
		}

		return $list;
	}
}
