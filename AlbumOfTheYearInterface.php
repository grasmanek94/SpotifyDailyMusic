<?php

use voku\helper\HtmlDomParser;

require_once 'vendor/autoload.php';
require_once 'AlbumInterface.php';

class AlbumOfTheYearInterface implements AlbumInterface
{
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

	private function getBaseUrl($year, $month)
	{
		return "https://www.albumoftheyear.org/" . $year . "/releases/" . self::$months[$month] . ".php?genre=";
	}

	private function getAlbums($url, $day, $get_all)
	{
		$list = [];

		$dom = HtmlDomParser::str_get_html(file_get_contents($url));

		foreach ($dom->findMulti('.albumBlock') as $albumBlock)
		{
			$date = $albumBlock->find(".date")->innertext[0];
			$exploded_date = explode(' ', $date);

			$continue = $get_all;
			if(!$continue && count($exploded_date) == 2)
			{
				$day_of_month = $exploded_date[1]; // Mar 20
				$continue = ($day_of_month <= $day);
			}

			if($continue)
			{
				$artist = $albumBlock->find(".artistTitle")->innertext[0];
				$album = $albumBlock->find(".albumTitle")->innertext[0];

				if(strlen($artist) > 0 && strlen($album) > 0)
				{
					$list[] = new AlbumEntry($album, $artist);
				}
			}
		}

		return $list;
	}

	public function getAllAlbums($year, $month, $day, $genres, $get_all)
	{
		$list = [];

		foreach($genres as $genre)
		{
			$url = self::getBaseUrl($year, $month) . $genre;
			$list = array_merge($list, self::getAlbums($url, $day, $get_all));
		}

		return $list;
	}
}