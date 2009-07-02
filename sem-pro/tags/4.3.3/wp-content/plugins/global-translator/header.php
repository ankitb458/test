<?php
/*
Author: Davide Pozza
Author URI: http://www.nothing2hide.net/
Description: Dynamically translates a blog in thirteen different languages (English, French, Italian, German, Portuguese, Spanish, Japanese, Korean, Chinese, Arabic, Russian, Greek, Dutch) by wrapping the Google Translation Engine or the Babelfish Translation Engine
*/

if(!class_exists("gt_translation_engine")) {
	class gt_translation_engine {
		var $_name;

		var	$_base_url;

		var $_links_pattern;

		var $_links_replacement;

		var $_languages_matrix;

		var $_available_languages;

		function gt_translation_engine(	$name,
									$base_url,
									$links_pattern,
									$links_replacement,
									$languages_matrix,
									$available_languages) {
			$this->set_name($name);
			$this->set_base_url($base_url);
			$this->set_links_pattern($links_pattern);
			$this->set_links_replacement($links_replacement);
			$this->set_languages_matrix($languages_matrix);
			$this->set_available_languages($available_languages);
		}

    function set_name($name){
    	$this->_name = (string)$name;
    }

		function set_base_url($base_url){
    	$this->_base_url = (string)$base_url;
    }

		function set_links_pattern($links_pattern){
    	$this->_links_pattern = (string)$links_pattern;
    }

		function set_links_replacement($links_replacement){
    	$this->_links_replacement = (string)$links_replacement;
    }

		function set_languages_matrix($languages_matrix){
    	$this->_languages_matrix = (array)$languages_matrix;
    }

		function set_available_languages($available_languages){
    	$this->_available_languages = (array)$available_languages;
    }

    function get_name(){
    	return $this->_name;
    }

	function get_base_url(){
    	return $this->_base_url;
    }

	function get_links_pattern(){
    	return $this->_links_pattern;
    }

	function get_links_replacement(){
    	return $this->_links_replacement;
    }

	function get_languages_matrix(){
    	return $this->_languages_matrix;
    }

	function get_available_languages(){
    	return $this->_available_languages;
    }

	}
}
// 	'http://translate.google.com/translate_c?hl=en&prev=/language_tools&ie=UTF-8&oe=UTF-8&u=${URL}&langpair=${SRCLANG}|${DESTLANG}',
$googleEngine = new gt_translation_engine(
	'google',
	'http://translate.google.com/translate_c?hl=en&langpair=${SRCLANG}|${DESTLANG}&u=${URL}',
	"/<a(.*?)href=\"(.*?)u=(.*?)&amp;prev=(.*?)\"([\s|>]{1})/i",
	"<a href=\"\\3\" \\5",
	array(
  'it'    => array( 'it'=>'Italiano', 
                    'en'=>'Inglese'),
  'pt'    => array( 'pt'=>'Portugues',
                    'en'=>'Ingles'),
  'en'    => array( 'en'=>'English',
                    'it'=>'Italian',  
                    'de'=>'German',
                    'es'=>'Spanish',
                    'fr'=>'French',
                    'pt'=>'Portuguese',
                    'ru'=>'Russian'),
  'de'    => array( 'de'=>'Deutsch',
                    'en'=>'Englisch',
                    'fr'=>'Franzosisch'),
  'fr'    => array( 'fr'=>'Francais',
                    'en'=>'Anglais',
                    'de'=>'Allemand'),
  'es'    => array( 'es'=>'Espanol',
                    'en'=>'Ingles'),
  'ru'    => array( 'ru'=>'Russian',
                    'en'=>'English')
  ),
  
	array(
	  'it'    => 'Italian',
	  'pt'    => 'Portuguese',
	  'en'    => 'English',
	  'de'    => 'German',
	  'fr'    => 'French',
	  'es'    => 'Spanish',
	  'ru'	  => 'Russian'
	  )  

	);

$babelfishEngine = new gt_translation_engine(
	'babelfish',
	'http://babelfish.altavista.com/babelfish/trurl_pagecontent?lp=${SRCLANG}_${DESTLANG}&trurl=${URL}',
	"/<a(.*?)href=\"(.*?)trurl=(.*?)\"([\s|>]{1})/i",
	"<a href=\"\\3\" \\4",
	array(
  'it'    => array( 'it'=>'Italiano', 
                    'en'=>'Inglese',
                    'fr'=>'Francese'),
  'ko'    => array( 'ko'=>'Korean',
                    'en'=>'English'),
  'zh' 		=> array( 'zh'=>'Chinese (Simplified)',
                    'en'=>'English'),
  'zt' 		=> array( 'zt'=>'Chinese (Traditional)',
                    'en'=>'English'),
  'pt'    => array( 'pt'=>'Portugues',
                    'en'=>'Ingles',
                    'fr'=>'Francais'),//to be verified
  'en'    => array( 'en'=>'English',
                    'zh'=>'Chinese (Simplified)',
                    'zt'=>'Chinese (Traditional)',
                    'nl'=>'Dutch',
                    'fr'=>'French',
                    'de'=>'German',
                    'el'=>'Greek',
                    'it'=>'Italian',  
                    'ja'=>'Japanese',
                    'ko'=>'Korean',
                    'pt'=>'Portuguese',
                    'ru'=>'Russian',
                    'es'=>'Spanish'),
  'nl'    => array( 'nl'=>'Dutch', 
  					'en'=>'English', 
  					'fr'=>'French'),
  'de'    => array( 'de'=>'Deutsch',
                    'en'=>'Englisch',
                    'fr'=>'Franzosisch'),
  'fr'    => array( 'fr'=>'Francais',
                    'en'=>'Anglais',
                    'de'=>'Allemand', 
                    'el'=>'Grec', 
                    'it'=>'Italien', 
                    'pt'=>'Portugais', 
                    'es'=>'Espagnol', 
                    'nl'=>'Hollandais'),
  'el'    => array( 'el'=>'Greek', 
  					'en'=>'English', 
  					'fr'=>'French'),                    
  'es'    => array( 'es'=>'Espanol',
                    'en'=>'Ingles'),
  'ja'    => array( 'ja'=>'Japanese',
                    'en'=>'English'),
  'ru'    => array( 'ru'=>'Russian',
                    'en'=>'English')
  ),
  
	array(
	  'it'    => 'Italian',
	  'ko'    => 'Korean',
	  'zh' 	  => 'Chinese (Simplified)',
	  'zt'    => 'Chinese (Traditional)',
	  'pt'    => 'Portuguese',
	  'en'    => 'English',
	  'el'    => 'Greek',
	  'nl'    => 'Dutch',
	  'de'    => 'German',
	  'fr'    => 'French',
	  'es'    => 'Spanish',
	  'ja'    => 'Japanese',
	  'ru'	  => 'Russian'
	  ) 
	);

$gt_available_engines = array();
$gt_available_engines['google'] = $googleEngine;
$gt_available_engines['babelfish'] = $babelfishEngine;

?>