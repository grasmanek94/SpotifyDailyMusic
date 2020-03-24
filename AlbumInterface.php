<?php

require_once 'vendor/autoload.php';
require_once 'Genre.php';

interface AlbumInterface
{
	/**
	 * @param DateTime $date
	 * @param int[] $genres
	 * @param bool $get_all
	 * @return AlbumEntry[]
	 *
	 * Returns a list of albums for the complete $month,
	 * where the album release date is earlier than the provided $year-$month-$day,
	 * unless get_all is true, then return all released albums in the whole month.
	 *
	 * Each album must contain at least one of the specified genres.
	 */
	public function getAllAlbums($date, $genres, $get_all);
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