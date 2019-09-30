<?php

error_reporting(E_ALL ^ (E_NOTICE | E_WARNING | E_DEPRECATED));

// contains tools used for cleaning up, validation, etc.
// an attempt to organize functions and avoiding "re-declaring" same functions on
// the same flow. It also could help lessen the overhead bandwith brought about by the
// previously mentioned re-declaration. Consequently, it would make php scripts cleaner
// and organized
class housekeeping {

/*
	public static function serverURL()	// doesn't end in slash
	{
		$prefix = isset($_SERVER["HTTPS"]) ? 'https' : 'http';
		$serverName = $_SERVER["SERVER_NAME"];
		return "$prefix://$serverName";
	}

	public static function sisterURL($newFileName)	// build the full URL of the given relative sister page
	{
		$self = housekeeping::serverURL() . $_SERVER["PHP_SELF"];
		$whereSlash = strpos($self, "/");
		if (false !== $whereSlash)
		{
			$self = substr($self, 0, $whereSlash);
		}
		return $self . $newFileName;
	}
*/

	public static function marketingVersionToFloat($marketingVersion)
	{
		$whereLetter = strpos($marketingVersion, 'a');
		if (FALSE === $whereLetter)
		{
			$whereLetter = strpos($marketingVersion, 'b');
		}
		if (FALSE !== $whereLetter)
		{
			$marketingVersion = substr($marketingVersion, 0, $whereLetter);
		}
		$whereFirstDot = strpos($marketingVersion, '.');
		if (FALSE === $whereFirstDot) return (float)$marketingVersion;		// if no dots, we're done
		$beforeFirstDot = substr($marketingVersion, 0, $whereFirstDot);
		$pastFirstDot = substr($marketingVersion, $whereFirstDot+1);
		$pastFirstDot = str_replace('.','', $pastFirstDot);
	  	if (empty($pastFirstDot)) $pastFirstDot = 0;

		$result = $beforeFirstDot . '.' . $pastFirstDot;
	  	return (float) $result;
	}

	public static function marketingVersionToFixed($marketingVersion)
	{
		$whereLetter = strpos($marketingVersion, 'a');
		if (FALSE === $whereLetter)
		{
			$whereLetter = strpos($marketingVersion, 'b');
		}
		if (FALSE !== $whereLetter)
		{
			$marketingVersion = substr($marketingVersion, 0, $whereLetter);
		}

		$exploded = explode('.', $marketingVersion);
		if (count ($exploded) >= 1) $result  = $exploded[0] * 10000;

		// Now we have to work around issue where a "final version" might be x.9.9
		if (substr($marketingVersion, -4) === '.9.9')
		{
			$result += 9999;
		}
		else
		{
			if (count ($exploded) >= 2) $result += $exploded[1] * 100;
			if (count ($exploded) >= 3) $result += $exploded[2] * 1;
		}

	  	return $result;
	}



	public static function makeMultiParagraphs($text)			// HTML escape and such, surround in paragraphs, add br's
	{
		$text = htmlspecialchars($text);
		$text = preg_replace("/\r*\n\r*\n+/", "</p><p>", $text);	// double-newlines into paragraph separators
		$text = preg_replace("/\r*\n/", "<br />", $text);		// single-newlines into explicit breaks
		// surround with outer paragraph
		$text = "<p>" . $text . "</p>";
		return $text;
	}

	public static function startsWith($Haystack, $Needle)
	{
		// Recommended version, using strpos
    	return strpos($Haystack, $Needle) === 0;
	}

	public static function stripURLQuery($url)
	{
		$result = $url;
		$whereQ = strpos($url, "?");
		if (false !== $whereQ)
		{
			$result = substr($url, 0, $whereQ);
		}
		return $result;
	}

	public static function resultURL($newURL = NULL, $tryReferer = FALSE)
	{
		if ($tryReferer && isset($_SERVER["HTTP_REFERER"]))
		{
			$redirectSite = $_SERVER["HTTP_REFERER"];
			$redirectSite = housekeeping::stripURLQuery($redirectSite);
		}
		else
		{
			$redirectSite = $newURL ? $newURL : '/result.html';
		}
		return $redirectSite;
	}

	public static function buildURL($url, $dict)	// add the associative array to the existing URL
	{
		$hasQuery = (false !== strpos($url, "?"));
		$separator = $hasQuery ? '&' : '?';
		$result = $url . $separator;		// start with the first separator
		foreach ($dict as $key => $value)
		{
			if (!empty($value))	// don't bother with empty values
			{
				$result .= urlencode($key) . '=' . urlencode($value) . '&';
			}
		}
		$result = substr($result, 0, -1);	// take off the last seprator we added
		return $result;
	}

	public static function buildErrorURL($url, $err, $details = '', $title = '')
	{
		return housekeeping::buildURL($url, array('err' => $err, 'details' => $details, 'title' => $title));
	}

	public static function buildMessageURL($url, $msg, $details = '', $title = '')
	{
		return housekeeping::buildURL($url, array('msg' => $msg, 'details' => $details, 'title' => $title));
	}


	public static function redirectToURL($url)
	{
		header("Location: $url");
	}

	public static function trimFirst($string)	// gets the first line of a multi-line input and trims it. Really cleans the input!
	{
		$lines = explode("\n",$string);
		$string = $lines[0];
		$string = trim($string);
		return $string;
	}

	public static function testStringAsBoolean($string)
	{
		$result = TRUE;
   		$string = strtolower($string);
		if (empty($string)
			|| $string == 'no'
			|| $string == '0'
	   		|| $string == 'false'
			|| $string == 'f'
			|| $string == 'n'
		    )
		{
			$result = FALSE;
		}
		return $result;
	}

	// GET FUNCTIONS.  Use getraw for a textarea.
	public static function getraw($key, $default='')
	{
   		return isset ( $_GET[$key] ) ? $_GET[$key] : $default;
	}
	public static function get($key, $default='')
	{
   		return isset ( $_GET[$key] ) ? housekeeping::trimFirst($_GET[$key]) : $default;
	}
	public static function isGet($key)
	{
   		return isset ( $_GET[$key] ) ? housekeeping::testStringAsBoolean($_GET[$key]) : FALSE;
   	}

	// POST FUNCTIONS.  Use postraw for a textarea.
	public static function postraw($key, $default='')
	{
   		return isset ( $_POST[$key] ) ? $_POST[$key] : $default;
	}
	public static function post($key, $default='')
	{
   		return isset ( $_POST[$key] ) ? housekeeping::trimFirst($_POST[$key]) : $default;
	}
	public static function isPost($key)
	{
   		return isset ( $_POST[$key] ) ? housekeeping::testStringAsBoolean($_POST[$key]) : FALSE;
   	}

	// REQUEST FUNCTIONS - get or post.  Use requestraw for a textarea.
	public static function requestraw($key, $default='')
	{
   		return isset ( $_REQUEST[$key] ) ? $_REQUEST[$key] : $default;
	}
	public static function request($key, $default='')
	{
   		return isset ( $_REQUEST[$key] ) ? housekeeping::trimFirst($_REQUEST[$key]) : $default;
	}
	public static function isRequest($key)
	{
   		return isset ( $_REQUEST[$key] ) ? housekeeping::testStringAsBoolean($_REQUEST[$key]) : FALSE;
   	}

	// SESSION FUNCTIONS.  Use sessionraw for a textarea.
	public static function sessionraw($key, $default='')
	{
   		return isset ( $_SESSION[$key] ) ? $_SESSION[$key] : $default;
	}
	public static function session($key, $default='')
	{
   		return isset ( $_SESSION[$key] ) ? housekeeping::trimFirst($_SESSION[$key]) : $default;
	}
	public static function isSession($key, $default=FALSE)
	{
   		return isset ( $_SESSION[$key] ) ? housekeeping::testStringAsBoolean($_SESSION[$key]) : $default;
   	}


	// Encryption/Decryption used for _________________ by the consultants form.  Hard-wired secret key.
	public static function encryptData($value, $key='Colonel Panic')
	{
	   $text = $value;
	   $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
	   $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	   $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $text, MCRYPT_MODE_ECB, $iv);
	   return $crypttext;
	}

	public static function decryptData($value, $key='Colonel Panic')
	{
	   $crypttext = $value;
	   $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
	   $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	   $decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $crypttext, MCRYPT_MODE_ECB, $iv);
	   return trim($decrypttext);
	}

	public static function lastPathComponent($path)
	{
		$pos = strrpos($path, '/');
		if (FALSE !== $pos)
		{
			$name = substr($path, $pos+1);
			return $name;
		}
		else
		{
			return FALSE;
		}
	}

	public static function passThruAttachment($path, $name='')
	{
		if (empty($name))
		{
			$name = housekeeping::lastPathComponent($path);
		}

		$fp = fopen($path, 'rb');

		// send the right headers
		header("Content-Type: application/zip");
		header("Content-Length: " . filesize($path));
		header('Content-Disposition: attachment; filename="' . $name . '"');
		header("Content-Description: File Transfer");
		header("Cache-Control: public");
		header("Content-Transfer-Encoding: binary");

		// dump the picture and stop the script
		fpassthru($fp);
	}

	public static function condense($str)		// multiple white
	{
		$str = preg_replace('/\s\s*/', ' ', $str);
		$str = trim($str);
		return $str;
	}

	public static function fixNameCapitalization($str)
	{
		$str = self::condense($str);
		if (preg_match('/^[a-z \.\']+$/iD', $str))	// only muck with it if we are fully ASCII.  Allow . and '
		{
			if (	(preg_match('/[a-z]/', $str) && !preg_match('/[A-Z]/', $str) )	// lower but not upper,
				||	(!preg_match('/[a-z]/', $str) && preg_match('/[A-Z]/', $str) )	// OR upper but not lower?
				)
			{
				$str = ucwords(strtolower($str));

				// Some special additional capitalization: from http://us3.php.net/ucwords
				$str = preg_replace('/(?:^|\\b)(O\'|Ma?c|Fitz)([^\W\d_])/xe', "'\$1' . strtoupper('\$2')", $str);
			}
		}

		return $str;
	}



	public static function getLang()
	{
		// gets from lang GET/POST parameter, or from server headers. Normalizes to lower case and underscore like zh_cn


		$lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : 'en';		// defalt to en

 		// split complex patterns like from FIREFOX:  en_us,en;q=0.5,
       	$lang = explode(";", $lang);
	    $lang = explode(",", $lang[0]);
	    $lang = $lang[0];

		$lang = housekeeping::post('lang', $lang);
		$lang = housekeeping::get('lang', $lang);
		$lang = strtolower(str_replace('-','_',$lang));		// convert zh-cn to zh_cn, and zh_CN to zh_cn
		return $lang;
		// probably you will want to also set $la = substr($lang,0,2);
	}

	public static function getLangs()		// Like above, but returns an ordered array for us to scan through until we find a match
	{
		// gets from lang GET/POST parameter, or from server headers. Normalizes to lower case and underscore like zh_cn


		$langString = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : 'en';		// defalt to en

		$result = array();
 		// split complex patterns like from FIREFOX:  en_us,en;q=0.5,
       	$semicolonPieces = explode(";", $langString);
       	foreach ($semicolonPieces as $semicolonPiece)
       	{
		$semicolonPiece = trim($semicolonPiece);
      		$commaPieces = explode(',', trim($semicolonPiece));
       		foreach ($commaPieces as $commaPiece)
       		{
       			$commaPiece = trim($commaPiece);
       			if (substr($commaPiece, 0, 2) !== 'q=')
       			{
       				$result[] = $commaPiece;
       			}
       		}
       	}

		$declaredLang = housekeeping::post('lang', '');
		$declaredLang = housekeeping::get('lang', $declaredLang);
		$declaredLang = strtolower(str_replace('-','_',$declaredLang));		// convert zh-cn to zh_cn, and zh_CN to zh_cn
		if (!empty($declaredLang))
		{
			array_unshift($result, $declaredLang);
		}
		// error_log("langString = $langString --> " . implode('+', $result));
		return $result;
	}

	// Clean up and escape input for passing to shell
	public static function cleanShell($input, $maxlength)
	{
		$input = substr($input, 0, $maxlength);
		$input = EscapeShellCmd($input);
		return ($input);
	}

	public static function commandLineQuote($str)
	{
		// Escape apostrophes by un-quoting, adding apos, then re-quoting
		// so this turns ' into '\'' ... we have to double-slash for this php.
		return "'" . str_replace("'", "'\\''", $str) . "'";
	}

	public static function jsSingleQuoteEscape($str)
	{
		// We have to double-slash for this php.
		return str_replace("'", "\\'", $str);
	}

	public static function fetch($keyArray, $key) {	// returns item if it exists, or NULL
		$result = NULL;
		if ($keyArray && array_key_exists($key, $keyArray))
		{
			$result = $keyArray[$key];
		}
		return $result;
	}

	public static function isfetch($keyArray, $key) {	// returns as a boolean if it exists, or NULL
		$result = NULL;
		if ($keyArray && array_key_exists($key, $keyArray))
		{
			$result = housekeeping::testStringAsBoolean($keyArray[$key]);
		}
		return $result;
	}


	// Based on SEHL  http://suda.co.uk/projects/SEHL/
	// Returns NULL if no search query terms found
	// Returns search_engine: query string
	public static function refererSearchTerms($referer)
	{
		/* If there is no referer, exit */
		if ($referer == '' || $referer == 'direct') {
			// error_log("refererSearchTerms returned NULL because referer $referer was empty\n\n" . print_r(debug_backtrace(), 1), 1, "server@karelia.com");
			return NULL;
		}

		/* parse the referer */
		$url_array = parse_url($referer);
		$host = (array_key_exists('host', $url_array)) ? $url_array['host'] : '';

		// result will be host name plus query terms.	Start with the host name.
		$result = $host . ": ";

		// special case: a9.  the whole path is just the query string.
		if (strstr($host, 'a9.com'))
		{
			$term = urldecode(substr($url_array['path'], 1));
			$result .= $term;
			return $result;
		}

		// If not from a list of known search engines, don't process.
		if (!( strstr($host, 'google')				// Well, Google isn't telling us the search terms anymore, so not as useful as it used to be pre-Fall-2013
			|| strstr($host, 'dogpile.com')
			|| strstr($host, 'bing.com')
			|| strstr($host, 'cuil.com')
			|| strstr($host, 'altavista.com')
			|| strstr($host, 'lycos.com')
			|| strstr($host, 'metacrawler.com')
			|| strstr($host, 'excite.com')
			|| strstr($host, 'yahoo.com')
			|| strstr($host, 'askjeeves.com')
			|| strstr($host, 'alltheweb.com')
			|| strstr($host, 'aolsearch.com')
			|| strstr($host, 'teoma.com')
			|| strstr($host, 'del.icio.us')
			|| strstr($host, 'hotbot.com')
			|| strstr($host, 'msn.com')
			|| strstr($host, 'netscape.com') ) )
		{
			// error_log("refererSearchTerms returned NULL because referer $referer has a host of '$host' which didn't match known search engines\n\n" . print_r(debug_backtrace(), 1), 1, "server@karelia.com");
			return NULL;
		}


		/* If there is no query string, exit */
		if ((!isset($url_array['query'])) || $url_array['query'] == '') {return NULL;}

		/* This is where you can add support for more search engines */
		$acceptedQueryKeys = array(
		'q',	// google, bing
		'p',	// yahoo
		'ask', 'searchfor', 'key', 'query', 'search', 'keyword', 'keywords', 'qry', 'searchitem', 'kwd', 'recherche', 'search_text', 'search_term', 'term', 'terms', 'qq', 'qry_str', 'qu', 's', 'k', 't', 'va',
		'all' // delicious
		);

		/* parse the query into keys and values */
		/* Here we are making the assumption that the query will be split by
		ampersands.  The W3C recommend using a semi-colon to avoid problems with
		HTML entities, and so some user-agents may use these.	However, all said
		and done it's a fairly safe assumption. */
		$query_array = explode('&',$url_array['query']);

		/* get the search terms from the query string */
		foreach($query_array as $var){
			$var_array = explode('=',$var);

			if (in_array($var_array[0], $acceptedQueryKeys)){

				$result .= urldecode($var_array[1]);
				// error_log("refererSearchTerms returned '$result' because referer $referer was successfully parsed\n\n" . print_r(debug_backtrace(), 1), 1, "server@karelia.com");
				return $result;			// done ... don't parse URL any more.
			}
		}

		// return the original referer if search term isn't found
		// error_log("refererSearchTerms returned NULL because referer $referer fell through to the end\n\n" . print_r(debug_backtrace(), 1), 1, "server@karelia.com");
		return NULL;
	}

	// based on this: http://techpatterns.com/downloads/scripts/browser_detection_php_if.txt
	public static function whichBrowser($userAgent)
	{
		$browser = '';
		$userAgent = strtolower( $userAgent );

		// run through the main browser possibilities, assign them to the main $browser variable
		if (stristr($userAgent, "opera"))
		{
			$browser = 'Opera';
		}
		elseif (stristr($userAgent, "msie"))
		{
			$browser = 'Internet Explorer';
		}
		elseif (stristr($userAgent, "iphone"))
		{
			$browser = 'iPhone';
		}
		elseif (stristr($userAgent, "chrome"))
		{
			$browser = 'Chrome';
		}
		elseif (stristr($userAgent, "flock"))
		{
			$browser = 'Flock';
		}
		elseif (stristr($userAgent, "omniweb"))
		{
			$browser = 'OmniWeb';
		}
		elseif (stristr($userAgent, "icab"))
		{
			$browser = 'iCab';
		}
		elseif (stristr($userAgent, "firefox"))
		{
			$browser = 'Firefox';
		}
		elseif (stristr($userAgent, "safari"))
		{
			$browser = 'Safari';
		}
		elseif (stristr($userAgent, "gecko") || stristr($userAgent, "mozilla"))
		{
			$browser = 'Mozilla';
		}
		elseif (stristr($userAgent, "camino"))
		{
			$browser = 'Camino';
		}
		elseif (stristr($userAgent, "webkit"))
		{
			$browser = 'Webkit-based browser';
		}
		return $browser;
	}






	public static function pageTracker($pageName='', $campaignSetters='')	// usually leave blank
	{
		if (!empty($pageName))
		{
			$pageName = '"'. $pageName . '"';
		}
		$pageTrackerCode = <<<EOF
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
var pageTracker = _gat._getTracker("UA-1619058-1");
pageTracker._setDomainName("karelia.com");
{$campaignSetters}pageTracker._trackPageview($pageName);
</script>
EOF;
		return $pageTrackerCode;
	}

	public static function printPageTracker($pageName='')	// usually leave blank
	{
		echo housekeeping::pageTracker($pageName='');
	}

	public static function location($url, $ixXHR = FALSE)
	{
		global $isXHR;
		if ($isXHR)
		{
			echo "<script type='text/javascript'>window.location.assign('" . htmlspecialchars($url) . "');</script>";
		}
		else
		{
			header("Location: " . $url);
		}
	}


	public static function filename_extension($filename)
	{
		$pos = strrpos($filename, '.');
		if($pos===false) {
			return false;
		} else {
			return substr($filename, $pos+1);
		}
	}

	public static function checkedIf($flag)	// output checked="checked" if the given flag is true.
	{
		if ($flag) echo 'checked="checked"';
	}

	public static function selectedIf($flag)	// output checked="checked" if the given flag is true.
	{
		if ($flag) echo 'selected="selected"';
	}

 	// =============================================================================================
	//
	//
	//	ID <--> Encoded parameter used for the signup list.  Light encoding and checksumming.
	//
	//
	// =============================================================================================

	public static function generalEncode($id, $debug = FALSE)
	{
		$stringToCRC = $id . 'flooby-nooby';
		$crc = crc32($stringToCRC) & 0xFFFF;
		$crcDecHex = dechex($crc);
		$idInHex = dechex($id);
		$combined = $idInHex . 't' . $crcDecHex;

		$result = str_replace(array('0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f'),
							  array('y','w','g','x','q','r','h','n','j','k','z','l','m','p','s','v'), $combined);

		if ($debug) error_log("encoding '$id': stringToCRC='$stringToCRC' crc=$crc crcDecHex=$crcDecHex idInHex=$idInHex combined='$combined' result='$result'");

		return $result;
	}

	// Called by lots of functional files

	public static function generalDecode($codedID, $debug = FALSE)	// returns NULL if invalid
	{
		$decodedString = str_replace(array('y','w','g','x','q','r','h','n','j','k','z','l','m','p','s','v'), array('0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f'), $codedID);
		if ($debug) error_log("decodedString from '$codedID' = '$decodedString'" );
		$idAndChecksum = explode('t', $decodedString);
		$valid = (2 == count($idAndChecksum));
		if ($debug && !$valid) error_log("Couldn't split into two pieces from 't' separator");

		$id = hexdec($idAndChecksum[0]);

		if ($debug) error_log("id = $id ... checksum = " . $idAndChecksum[1] );

		if ($valid)
		{
			$givenCRC = hexdec($idAndChecksum[1]);
			$expectedCRC = crc32($id . 'flooby-nooby')  & 0xFFFF;
			$valid = ($idAndChecksum[1] == dechex($expectedCRC));
			if ($debug && !$valid) error_log("CRC not working: " . $idAndChecksum[1] ." != " . dechex($expectedCRC) );
		}
		if ($valid)
		{
			return $id;
		}
		else
		{
			return NULL;
		}
	}


	public static function relative_date($time) {
		$today = time();

		$reldays = ($time - $today)/86400;
		if ($reldays >= -1 && $reldays < 1) {
			return 'today';
		} else if ($reldays >= 1 && $reldays < 2) {
			return 'tomorrow';
		} else if ($reldays >= -2 && $reldays < -1) {
			return 'yesterday';
		}
		if (abs($reldays) < 7) {
			if ($reldays > 0) {
				$reldays = floor($reldays);
				return 'in ' . $reldays . ' day' . ($reldays != 1 ? 's' : '');
			} else {
				$reldays = abs(floor($reldays));
				return $reldays . ' day'  . ($reldays != 1 ? 's' : '') . ' ago';
			}
		}
		if (abs($reldays) < 45 && (date('Y', $today) == date('Y', $time) ) ) {
			return date('l, j F',$time ? $time : time());
		}
		else if (abs($reldays) < 182 && (date('Y', $today) == date('Y', $time) ) ) {
			return date('j F',$time ? $time : time());
		} else {
			return date('j F Y',$time ? $time : time());
		}
	}

	// for price countdowns

	public static function relative_datetime_escaped($time) {
		$today = time();

		$reldays = ($time - $today)/86400;
		$relhours = floor(($time - $today)/3600);
		$relminutes = floor(($time - $today)/60);
		if ($relminutes < 45)
		{
			return "in&nbsp;{$relminutes}&nbsp;minute" . ($relminutes != 1 ? 's' : '');
		}
		if ($relhours < 1)
		{
			return "in&nbsp;less&nbsp;than&nbsp;an&nbsp;hour";
		}
		if ($relhours <= 36)
		{
			return "in&nbsp;less&nbsp;than&nbsp;{$relhours}&nbsp;hours";
		}
		if (abs($reldays) < 30) {
			if ($reldays > 0) {
				$reldays = floor($reldays);
				return 'in ' . $reldays . ' day' . ($reldays != 1 ? 's' : '');
			} else {
				$reldays = abs(floor($reldays));
				return $reldays . ' day'  . ($reldays != 1 ? 's' : '') . ' ago';
			}
		}
		if (abs($reldays) < 182 && (date('Y', $today) == date('Y', $time) ) ) {
			return date('l, j F',$time ? $time : time());
		} else {
			return date('j F Y',$time ? $time : time());
		}
	}







}
?>
