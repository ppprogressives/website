<?php
error_reporting(E_ALL);
date_default_timezone_set('America/Los_Angeles');

// Temporarily don't use $_SERVER['DOCUMENT_ROOT'] - based database.
define('PRIVATE_DIR',      dirname(__FILE__)  . '/../protected');
define('PRIVATE_RESOLVED', is_link(PRIVATE_DIR) ? readlink(PRIVATE_DIR) : PRIVATE_DIR);
include_once(PRIVATE_RESOLVED . '/downcode_db/secrets.php');    // $password

define('DOWNCODE_DBDIR',    PRIVATE_RESOLVED . '/downcode_db');
define('DOWNCODE_FILESDIR', PRIVATE_RESOLVED . '/downcode_files');

function baseURL() {
	$pageURL = 'http';
	if(isset($_SERVER["HTTPS"]))
	if ($_SERVER["HTTPS"] == "on") {
			$pageURL .= "s";
	}
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"];
	} else {
			$pageURL .= $_SERVER["SERVER_NAME"];
	}
	return $pageURL;
}
function curPageURL() {
	return baseURL() . $_SERVER["REQUEST_URI"];
}
// Adapted from https://github.com/dflydev/dflydev-base32-crockford (MIT license)

/**
 * Base32 Crockford implementation
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class Crockford
{
		const NORMALIZE_ERRMODE_SILENT = 0;
		const NORMALIZE_ERRMODE_EXCEPTION = 1;

		private $symbols = array();
		private $flippedSymbols = array();
		private $alphabet = '';
		private $salt = 0;

		function __construct($alphabet, $saltChar)  // string for 0-31 values; char that adjusts value encoded/decoded
		{
			// Constraint: I's and L's and O's in the alphabet not allowed since those get converted to 1's and 0's.
			if (preg_match('/[a-zILO]/', $alphabet)) {
				throw new \RuntimeException("Alphabet '$alphabet' cannot contain lowercase letters, I's, L's, or O's.");
			}
			$salt = strpos($alphabet, $saltChar);
			if (FALSE === $salt) {
				throw new \RuntimeException("Salt '$saltChar' must be part of alphabet '$alphabet'.");
			}
			$this->salt = $salt;

			$this->alphabet = $alphabet;
			$this->symbols = str_split($alphabet);
			$this->flippedSymbols = array();
			$counter = 0;

			// We're not using the checksum feature but let's keep it here for now

			$alphabetPlusChecksum = $alphabet . '*~$=@';  // to make the base-37 checksum thing work. Note we use @ not U
			$moreSymbols = str_split($alphabetPlusChecksum);
			foreach ($moreSymbols as $char) {
				$this->flippedSymbols[$char] = $counter++;
			}
		}

/*
		public static $symbols = array(
				'0', '1', '2', '3', '4',
				'5', '6', '7', '8', '9',
				'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
				'J', 'K', 'M', 'N', 'P', 'Q', 'R', 'S',
				'T', 'V', 'W', 'X', 'Y', 'Z',
				'*', '~', '$', '=', 'U',
		);

		public static $flippedSymbols = array(
				'0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4,
				'5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9,
				'A' => 10, 'B' => 11, 'C' => 12, 'D' => 13,
				'E' => 14, 'F' => 15, 'G' => 16, 'H' => 17,
				'J' => 18, 'K' => 19, 'M' => 20, 'N' => 21,
				'P' => 22, 'Q' => 23, 'R' => 24, 'S' => 25,
				'T' => 26, 'V' => 27, 'W' => 28, 'X' => 29,
				'Y' => 30, 'Z' => 31,
				'*' => 32, '~' => 33, '$' => 34, '=' => 35, 'U' => 36,
		);
*/
		/**
		 * Encode a number
		 *
		 * @param int $number
		 *
		 * @return string
		 *
		 * @throws \RuntimeException
		 */
		public function encode($number, $padding=0)
		{
				if (!is_numeric($number)) {
						throw new \RuntimeException("Specified number '{$number}' is not numeric");
				}

				if (!$number) {
						return 0;
				}

				$number += $this->salt;   // Mess with the number a bit!

				$digits = 0;
				$response = array();
				while ($number || ($padding > 0 && $digits < $padding)) { // go until run out, or padding done
						$remainder = $number % 32;
						$number = (int) ($number/32);
						$response[] = $this->symbols[$remainder];
						$digits++;
				}

				return implode('', array_reverse($response));
		}

		/**
		 * Encode a number with checksum
		 *
		 * @param int $number
		 *
		 * @return string
		 *
		 * @throws \RuntimeException
		 */
		public function encodeWithChecksum($number)
		{
				$encoded = $this->encode($number);

				return $encoded . $this->symbols[$number % 37];
		}

		/**
		 * Decode a string
		 *
		 * @param string $string  Encoded string
		 * @param int    $errmode Error mode
		 *
		 * @return int
		 *
		 * @throws \RuntimeException
		 */
		public function decode($string, $errmode = self::NORMALIZE_ERRMODE_SILENT)
		{
				return $this->internalDecode($string, $errmode);
		}

		/**
		 * Decode a string with checksum
		 *
		 * @param string $string  Encoded string
		 * @param int    $errmode Error mode
		 *
		 * @return int
		 *
		 * @throws \RuntimeException
		 */
		public function decodeWithChecksum($string, $errmode = self::NORMALIZE_ERRMODE_SILENT)
		{
				$checksum = substr($string, (strlen($string) -1), 1);
				$string = substr($string, 0, strlen($string) - 1);

				$value = $this->internalDecode($string, $errmode);
				$checksumValue = $this->internalDecode($checksum, self::NORMALIZE_ERRMODE_EXCEPTION, true);

				if ($checksumValue !== ($value % 37)) {
						throw new \RuntimeException("Checksum symbol '$checksum' is not correct value for '$string'");
				}

				return $value;
		}

		/**
		 * Normalize a string
		 *
		 * @param string $string  Encoded string
		 * @param int    $errmode Error mode
		 *
		 * @return string
		 *
		 * @throws \RuntimeException
		 */
		public function normalize($string, $errmode = self::NORMALIZE_ERRMODE_SILENT)
		{
				$origString = $string;

				$string = strtoupper($string);
				if ($string !== $origString && $errmode) {
						throw new \RuntimeException("String '$origString' requires normalization");
				}

				$string = str_replace('-', '', strtr($string, 'IiLlOo', '111100'));
				if ($string !== $origString && $errmode) {
						throw new \RuntimeException("String '$origString' requires normalization");
				}

				return $string;
		}

		/**
		 * Decode a string
		 *
		 * @param string $string     Encoded string
		 * @param int    $errmode    Error mode
		 * @param bool   $isChecksum Is encoded with a checksum?
		 *
		 * @return int
		 *
		 * @throws \RuntimeException
		 */
		protected function internalDecode($string, $errmode = self::NORMALIZE_ERRMODE_SILENT, $isChecksum = false)
		{
				if ('' === $string) {
						return '';
				}

				if (null === $string) {
						return '';
				}

				$string = $this->normalize($string, $errmode);

				if ($isChecksum) {
						$valid = '/^[' . $this->alphabet . '\*\~\$=@]+$/';
				} else {
						$valid = '/^[' . $this->alphabet . ']+$/';
				}

				if (!preg_match($valid, $string)) {
						throw new \RuntimeException("String '$string' contains invalid characters");
				}

				$total = 0;
				foreach (str_split($string) as $symbol) {

						if (isset($this->flippedSymbols[$symbol])) {              // Double check that it will find character
							$total = $total * 32 + $this->flippedSymbols[$symbol];
						}
				}

				$total -= $this->salt;

				return $total;
		}
}



class DowncodeDB extends SQLite3
{
	function __construct()
	{
		$dbPath = DOWNCODE_DBDIR . '/downcode.sqlite3';
		if (!is_writable($dbPath)) {
			error_log("Not writeable database: " . $dbPath);
			echo '<h1><a href="applescript://com.apple.scripteditor?action=new&script=do%20shell%20script%20%22chmod%20666%20~%2FDropbox%2Florenzowoodmusic_private%2Fdowncode_db%2Fdowncode.sqlite3%22">chmod 666 downcode.sqlite3</a></h1>';
			throw new \RuntimeException("Database cannot be updated");
		}
		$this->open($dbPath, SQLITE3_OPEN_READWRITE);
	}

	function backup()
	{
		$dbPath = DOWNCODE_DBDIR . '/downcode.sqlite3';
		$copyPath = DOWNCODE_DBDIR . '/backup.' . date('Y-m-d.G;i;s') . '.sqlite3';
		copy($dbPath, $copyPath);
		error_log("copy $dbPath to $copyPath");
	}

	function formats() {

		$result = Array();
		$query = 'SELECT * FROM format ORDER BY ordering';
		$ret = $this->query($query);
		while ($row = $ret->fetchArray(SQLITE3_ASSOC) ){
			$result[] = $row;
		}
		return $result;
	}

	function formatExtensionFromID($formatID)
	{
		$result = NULL;
		$statement = $this->prepare('SELECT extension FROM format WHERE id=:formatID;');
		$statement->bindValue(':formatID', $formatID);
		$ret = $statement->execute();
		if ($row = $ret->fetchArray(SQLITE3_ASSOC) ){
			$result = $row['extension'];
		}
		return $result;
	}

	function findAndRedeemAlbumFromCode($code, $iOSDevice) {

		$code = strtoupper(trim($code));    // quick and dirty normalize
		$code = str_replace(' ', '', $code);// before we know the actual alphabet
		$code = str_replace('-', '', $code);// allow spaces and hyphens just in case
		$result = Array();
		$statement = $this->prepare('SELECT * FROM album WHERE prefix = :prefix;');
		$statement->bindValue(':prefix', substr($code, 0, 1));
		$ret = $statement->execute();
		if ($album = $ret->fetchArray(SQLITE3_ASSOC) ){
			// We found an album that matches this prefix.
			$secondChar = substr($code, 1, 1);  // Second char is marketing code and salt so it affects encoding
			$base32Converter = new Crockford($album['alphabet32'], $secondChar);
			$code = $base32Converter->normalize($code); // Now properly normalize the code
			$restOfCode = substr($code, 2);     // The rest is base-32 encoded
			$decoded = $base32Converter->decode($restOfCode);
			$modulo = $decoded % $album['seed'];
			if (0 == $modulo) {

				// Code is valid - $album holds album array.
				// But now let's see if the code is not already redeemed.
				// We don't bother to 'redeem' code if we are on an iOS device though.
				// That way if somebody tries to redeem from iOS they are not penalized,
				// and they can try from a desktop later.

				$statement = $this->prepare('SELECT *, datetime(timestamp, \'localtime\') AS whenCreated, datetime(downloadTimestamp, \'localtime\') AS whenDownloaded FROM redemption WHERE code = :code;');
				$statement->bindValue(':code', $code);
				// code albumID campaignCode  IP  cookie  email formatID  whenDownloaded  timestamp
				$ret = $statement->execute();
				if ($redemption = $ret->fetchArray(SQLITE3_ASSOC) ){

					$valid = FALSE;
					// Already redeemed.
					// OK if an iOS device,
					// OK if not downloaded yet,
					// OK first redeemed within last 24 hours, and from same IP address.
					if ($iOSDevice)
					{
						error_log("found redemption, but we are on iOS.");
						$valid = TRUE;
					}
					if (!$redemption['whenDownloaded'])
					{
						error_log("found redemption, not downloaded yet.");
						$valid = TRUE;
					}

					$whenDownloaded = strtotime($redemption['whenDownloaded']);
					$howLongAgo = time() - $whenDownloaded;

					if ($_SERVER['REMOTE_ADDR'] = $redemption['IP'] && $howLongAgo < 24*60*60 )
					{
						error_log("found redemption but IP matches and it was created < 24 hours ago.");
						$valid = TRUE;
					}

					if ($valid) {

						$album['formatID'] = $redemption['formatID'];   // copy this to album record so we can pre-select last used format
						return $album;
					}
					else {
						error_log("found redemption, not allowing to download again");
						return null;
					}
				}
				else {
				 error_log("Inserting redemption.");
				 // Not redeemed yet.  Insert!
					$statement = $this->prepare(
						'INSERT INTO redemption(code,albumID,campaignCode,IP,cookie,email) values ('
						. ':code,:albumID,:campaignCode,:IP,:cookie,:email);');
					$statement->bindValue(':code', $code);
					$statement->bindValue(':albumID', $album['ID']);
					$statement->bindValue(':campaignCode', $secondChar);
					$statement->bindValue(':IP', $_SERVER['REMOTE_ADDR']);
					// $statement->bindValue(':cookie', 'COOKIE GOES HERE');
					// $statement->bindValue(':email', 'EMAIL GOES HERE');
					$ret = $statement->execute();
					return $album;
				}
			}
		}
		return null;
	}

	// Set the downloadTimeStamp on this redemption record if it is null.
	function updateRedemption($code)
	{
		$statement = $this->prepare(
			'UPDATE redemption SET downloadTimestamp=CURRENT_TIMESTAMP WHERE code=:code AND downloadTimestamp IS NULL;');
		$statement->bindValue(':code', $code);
		$ret = $statement->execute();
	}

	function updateDownloadCount($albumID, $trackID)
	{
		if (!$trackID)  // downloading whole album
		{
			$statement = $this->prepare(
				'UPDATE album SET downloadCount=downloadCount+1 WHERE ID=:albumID;');
			$statement->bindValue(':albumID', $albumID);
			$ret = $statement->execute();
		}
		else
		{
			$statement = $this->prepare(
				'UPDATE track SET downloadCount=downloadCount+1 WHERE ID=:trackID;');
			$statement->bindValue(':trackID', $trackID);
			$ret = $statement->execute();
		}
	}

	function updateFormatCount($formatID)
	{
		$statement = $this->prepare(
			'UPDATE format SET downloadCount=downloadCount+1 WHERE ID=:formatID;');
		$statement->bindValue(':formatID', $formatID);
		$ret = $statement->execute();
	}

	function tracksOfAlbumID($albumID)
	{
		$result = Array();
		$statement = $this->prepare('SELECT * FROM track WHERE albumID = :albumID;');
		$statement->bindValue(':albumID', $albumID);
		$ret = $statement->execute();
		while ($track = $ret->fetchArray(SQLITE3_ASSOC) ){
			$result[] = $track;
		}
		return $result;
	}

	function allAlbums()
	{
		$result = Array();
		$statement = $this->prepare('SELECT * FROM album order by ID');
		$ret = $statement->execute();
		while ($album = $ret->fetchArray(SQLITE3_ASSOC) ){
			$result[] = $album;
		}
		return $result;
	}

	function albumForSlug($slug)
	{
		$result = NULL;
		$statement = $this->prepare('SELECT * FROM album WHERE slug = :slug');
		$statement->bindValue(':slug', strtolower(trim($slug)));
		$ret = $statement->execute();
		while ($album = $ret->fetchArray(SQLITE3_ASSOC) ){
			$result = $album;
		}
		return $result;
	}

	function fileNameForAlbumTrackExtension($albumID, $trackID, $formatID)
	{
		$result = NULL;
		$extension = $this->formatExtensionFromID($formatID);

		if (0 == $trackID) {
			// special case, entire album
			$statement = $this->prepare('SELECT * FROM album WHERE id = :albumID');
			$statement->bindValue(':albumID', $albumID);
			$ret = $statement->execute();
			if ($track = $ret->fetchArray(SQLITE3_ASSOC) ){
				$result = $track['zipFileBase'] . '.' . $extension . '.zip';
			}
		} else {
			$statement = $this->prepare('SELECT * FROM track WHERE albumID = :albumID AND ID = :ID;');
			$statement->bindValue(':albumID', $albumID);
			$statement->bindValue(':ID', $trackID);
			$ret = $statement->execute();
			if ($track = $ret->fetchArray(SQLITE3_ASSOC) ){
				$result = $track['fileBase'] . '.' . $extension;
			}
		}
		return $result;
	}



// Note: we should update the timestamp when we actually download album, so that the 24 hour rule is since it was DOWNLOADED



/*
Looks like we can generate approximately 16,384 (2^14) 8-character codes (6 characters is a number, which is 30 bits, where we are multiplying by almost 2^16, which leaves about 2^14 so that makes sense.)

If we had a higher seed like 2^18, presumably to make even less likely to guess a code, then that would leave 2^12 codes which is about 4096.  But we probably don't need to do that.

If we had 9-character codes that would be another 5 bits, so 2^35 / 2^16 = 2^19 which would be > 500K codes available in that space!

However, due to the second character acting as a salt, we now have the ability to generate 32 different "runs" of 16K codes.

 */
	function generateCodes($secondChar)      // Private for command line use
	{
		if (strlen($secondChar) != 1) { error_log("second character must be 1 alpha character"); return; }
		$statement = $this->prepare('SELECT * FROM album');
		$ret = $statement->execute();
		while ($result = $ret->fetchArray(SQLITE3_ASSOC) ){
			echo $result['title'] . PHP_EOL . PHP_EOL;
			$base32Converter = new Crockford($result['alphabet32'], $secondChar);
			$counter = 0;
			$seed = $result['seed'];
			$prefix = $result['prefix'];
			for ($i = 1 ; $i < 100000 ; $i++) {
				$fullCode = $prefix . $secondChar . $base32Converter->encode($i * $seed, 6 );
				if (strlen($fullCode) > 8) break;
				echo /* 'For basis ' . $i . ' -> ' . $i * $seed . ' : ' . */ $fullCode /* . ' ===== ' . $base32Converter->decode(substr($fullCode, 2)) */ . PHP_EOL;
				$counter++;
			}
			echo PHP_EOL . 'CODES GENERATED: ' . $counter . PHP_EOL;
		}
	}
}
?>