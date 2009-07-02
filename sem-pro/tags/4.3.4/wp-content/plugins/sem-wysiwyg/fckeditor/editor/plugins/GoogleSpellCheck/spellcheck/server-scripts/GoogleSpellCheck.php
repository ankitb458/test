<?php
// {{{ class Google_Service_SpellCheck
// +----------------------------------------------------------------------+
// | PHP versions 5                                                       |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2006 David Coallier                               |
// | All rights reserved.                                                 |
// +----------------------------------------------------------------------+
// |                                                                      |
// | Redistribution and use in source and binary forms, with or without   |
// | modification, are permitted provided that the following conditions   |
// | are met:                                                             |
// |                                                                      |
// | Redistributions of source code must retain the above copyright       |
// | notice, this list of conditions and the following disclaimer.        |
// |                                                                      |
// | Redistributions in binary form must reproduce the above copyright    |
// | notice, this list of conditions and the following disclaimer in the  |
// | documentation and/or other materials provided with the distribution. |
// |                                                                      |
// | Neither the name of David Coallier nor the names of his contributors |
// | may be used to endorse                                               |
// | or promote products derived from this software without specific prior|
// | written permission.                                                  |
// |                                                                      |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
// | FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE      |
// | REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,          |
// | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
// | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS|
// |  OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED  |
// | AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT          |
// | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY|
// | WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE          |
// | POSSIBILITY OF SUCH DAMAGE.                                          |
// +----------------------------------------------------------------------+
// | Author: David Coallier <davidc@agoraproduction.com>                  |
// +----------------------------------------------------------------------+
//

/*************************************************************************
alert box popup confimation message function
*************************************************************************/
function confirm($msg)
{
echo "<script langauge=\"javascript\">alert(\"".$msg."\");</script>";
}//end function 

function errorMessage( $err ) {
	$msg = "Error: " . $err;
	confirm($msg);
}

/**
 * Google Spell Checking API Connector
 *
 * This class will give the ability to connect to the google
 * xml api, pass a word, get the possible words to replace the
 * original word with and return an array. This is used for SpellCheckers
 *
 * @author David Coallier <davidc@agoraproduction.com>
 * @package Google
 * @copyright David Coallier - Agora Production 1998-2006
 */
class Services_Google_SpellCheck
{
	// {{{ var variables
	/**
	 * HTTP HOSTNAME
	 *
	 * This variable contains the
	 * http hostname that we are going to
	 * be executing the request from. This
	 * should stay set to www.google.com
	 *
	 * @access var
	 * @var    string The hostname to connect to
	 */
	var $http_host  = 'www.google.com';
	/**
	 * HTTP Port
	 *
	 * The http port to connect to
	 * currently not used.
	 *
	 * @access var
	 * @var    integer  The port to use
	 */
	var $http_port  = '443';

	/**
	 * HTTP path
	 *
	 * This contains the http path to use, the
	 * query string to pass to http_host
	 *
	 * @access var
	 * @var string http_path  The query string to add
	 */
	var $http_path  = '';

	/**
	 * Language
	 *
	 * The language to use in the query string
	 * when querying the google api.
	 *
	 * @access var
	 * @var string The language used to query the xml api.
	 */
	var $lang;

	/**
	 * XML File encoding
	 *
	 * The xml request created encoding
	 * set to default on utf8
	 *
	 * @access var
	 * @var string The xml file encoding
	 */
	var $encoding = 'utf-8';

	// }}}
	// {{{ Constructor
	/**
	 * Constructor
	 *
	 * @access public
	 * @param string $lang  The language to set $this->lang
	 */
	function Services_Google_SpellCheck( $lang = 'en'  )
//	 public function __construct($lang = 'en')
	{
		$this->lang       = $lang;
		$this->http_path  = '/tbproxy/spell?lang=' . $this->lang . '&hl=en&v=2.0f';
	}
	// }}}
	// {{{ public function checkWords
	/**
	 * Check words
	 *
	 * This function will take a text, get each words
	 * and retrieve the words that need or could be
	 * replaced with a better word.
	 * Example:
	 * <code>
	 *  $text = 'Wher were you att';
	 *  $words = Services_Google_SpellCheck::checkWords($text);
	 *  print_r($words);
	 * </code>
	 * This will print:
	 * <code>
	 * [Wher] => Array
	 * (
	 *     [0] => Where
	 *     [1] => Whee
	 *     [2] => Whet
	 *     [3] => Her
	 *     [4] => Weer
	 * )
	 *
	 * [att] => Array
	 * (
	 *     [0] => Art
	 *     [1] => art
	 *     [2] => atty
	 *     [3] => tat
	 *     [4] => At
	 * )
	 * </code>
	 *
	 * @access public
	 * @param  string $wordText A text with many words that
	 *                          will be searched thru in order
	 *                          to find the words to replace
	 *
	 * @return array $words. An associative array of the words
	 *                       returned and their possible replacements
	 */
	function checkWords($wordText)
	{
		$words      = array();
		$matches = $this->getMatches($wordText);
		
		if (is_array($matches)) {
			foreach ($matches as $match)
			{
				if (!empty($match['value']))
				{
					// ge tthe misspeleed word from the original text
					$word = substr($wordText, $match['attributes']['o'], $match['attributes']['l']);
					$suggestions = explode("\t", $match['value']);
					$words[$word] = $suggestions;
				}		
				
			}
			
			return $words;
		}
		else {
			return false;
		}
	}

	// }}}
	// {{{ var function getMatches
	/**
	 * Get Matches per word
	 *
	 * This function will get a word and submit it thru
	 * a curl object to the google xml api, then load
	 * it into simplexml and return an array of the suggested
	 * words from the api.
	 *
	 * @access var
	 * @param string The word to search the good way to write it.
	 * @return array The array of possible word's replacement
	 */

	function getMatches($wordList)
	{
		$url = "https://" .$this->http_host . $this->http_path;
		$text = utf8_encode($wordList);

		// Setup XML request
		$request = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n";
		$request .=	"<spellrequest textalreadyclipped=\"0\" ignoredups=\"0\" ignoredigits=\"1\" ignoreallcaps=\"1\"><text>$text</text></spellrequest>\n";

		// build post header
		$header  = "POST ".$this->http_path." HTTP/1.1\r\n";
		$header .= "Host: $this->http_host\r\n";
		$header .= "Content-Length: " . strlen($request) . "\r\n";
		$header .= "Content-type: application/x-www-form-urlencoded\r\n";
		$header .= "Connection: Close\r\n\r\n";
		$header .= $request;

		// et's try open a socket first
		$fp = @fsockopen("ssl://" . $this->http_host, $this->http_port, $errno, $errstr);
		if ($fp) {
			fputs($fp, $header);
			
			$xml = "";
			while (!feof($fp)) {
				$xml .= fgets($fp);
			}
			
			fclose($fp);

			// get the xml out of the response
			$xml = preg_replace("/^[^<]*|[^>]*$/", "", $xml);
		}
		else {
			// check if curl is installed,.   If so use curl to query google
			if ( function_exists('curl_init')) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,$url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $header);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				$xml = curl_exec($ch);
				curl_close($ch);
			}
			else {
				return false;
			}
		}

		if (is_string($xml) && (strlen($xml) > 0)) {
			// use XML Parser on $data, and your set!
			$xml_parser = xml_parser_create();
			xml_parser_set_option($xml_parser,XML_OPTION_CASE_FOLDING,0);
			xml_parser_set_option($xml_parser,XML_OPTION_SKIP_WHITE,1);
			xml_parse_into_struct($xml_parser, $xml, $vals, $index);
			xml_parser_free($xml_parser);
			return($vals);
		}
		else {
			return false;
		}
	}


	// }}}
	// {{{ var function debugData
	/**
	 * Debug function
	 *
	 * This function is used to debug and appends
	 * to a debug.log file. Just make sure you touch
	 * the file.
	 *
	 * @todo touch debug.txt
	 * @access var
	 * @param string The text to log.
	 */
	function debugData($data)
	{
		$fh = @fopen("debug.log", 'a+');
		fwrite($fh, $data);
		@fclose($fh);
	}
	// }}}
}
// }}}
?>
