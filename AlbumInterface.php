<?php

require_once 'vendor/autoload.php';
require_once 'Genre.php';

interface AlbumInterface
{
	/**
	 * @param int $year
	 * @param int $month
	 * @param int $day
	 * @param int[] $genres
	 * @param bool $get_all
	 * @return AlbumEntry[]
	 */
	public function getAllAlbums($year, $month, $day, $genres, $get_all);
}

class AlbumEntry
{
	public $album;
	public $artist;

	public function __construct($album, $artist)
	{
		$this->album = $album;
		$this->artist = $artist;
	}
}