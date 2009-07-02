<?php
header('Content-type: text/html; charset=utf-8');

require_once(dirname(__FILE__) . '/' . 'GoogleSpellCheck.php');

$spellercss = '../spellerStyle.css';			// by FredCK
$word_win_src = '../wordWindow.js';				// by FredCK
$textinputs = $_POST['textinputs']; # array
$lang = $_POST['lang'];
$spellcheck = new Services_Google_SpellCheck($lang); // MAK


# set the JavaScript variable to the submitted text.
# textinputs is an array, each element corresponding to the (url-encoded)
# value of the text control submitted for spell-checking
function print_textinputs_var() {
	global $textinputs;

	foreach( $textinputs as $key=>$val ) {
		# $val = str_replace( "'", "%27", $val );
		echo "textinputs[$key] = decodeURIComponent(\"" . $val . "\");\n";
	}
}

# make declarations for the text input index
function print_textindex_decl( $text_input_idx ) {
	echo "words[$text_input_idx] = [];\n";
	echo "suggs[$text_input_idx] = [];\n";
}

# set an element of the JavaScript 'words' array to a misspelled word
function print_words_elem( $word, $index, $text_input_idx ) {
	echo "words[$text_input_idx][$index] = '" . escape_quote( $word ) . "';\n";
}


# set an element of the JavaScript 'suggs' array to a list of suggestions
function print_suggs_elem( $suggs, $index, $text_input_idx ) {
	echo "suggs[$text_input_idx][$index] = [";
	foreach( $suggs as $key=>$val ) {
		if( $val ) {
			echo "'" . escape_quote( $val ) . "'";
			if ( $key+1 < count( $suggs )) {
				echo ", ";
			}
		}
	}
	echo "];\n";
}

# escape single quote
function escape_quote( $str ) {
	return preg_replace ( "/'/", "\\'", $str );
}


# handle a server-side error.
function error_handler( $err ) {
	echo "error = '" . escape_quote( $err ) . "';\n";
}


function html2txt($document){
	$search = array('@<script[^>]*?>.*?</script>@si',  	// Strip out javascript
               '@<style[^>]*?>.*?</style>@siU',    		// Strip style tags properly
               '@<[\/\!]*?[^<>]*?>@si',            		// Strip out HTML tags
               '@<![\s\S]*?--[ \t\n\r]*>@',        		// Strip multi-line comments including CDATA
			   '@&(nbsp|#160);@i'						// remove non breaking space
	);
	$text = preg_replace($search, ' ', $document);
	return $text;
}

## get the list of misspelled words. Put the results in the javascript words array
## for each misspelled word, get suggestions and put in the javascript suggs array
function print_checker_results() {

	global $spellcheck;
	global $textinputs;

	$text = '';
	for( $i = 0; $i < count( $textinputs ); $i++ ) {
		$text .= urldecode( $textinputs[$i] );
	}

//	debugData("Raw text: $text\n");
	// strip out all html, javascript, and such.
	// MAK: Might need to add php and plugin tags (like contact form) as well
	$text = html2txt($text);
//		debugData("HTML stripped text: $text\n");
	// remove punctuation  - MAK: might need to add 's exclusion
	$text = preg_replace('/[^a-zA-Z0-9 \']/i', ' ', $text);

//		debugData("Non-punct text: $text\n");

	$gspellret = $spellcheck->checkWords($text);

	if (is_array($gspellret)) {
		$text_input_index = 0;
		$index = 0;
		# parse each line of gspell return
		print_textindex_decl( $text_input_index );
		foreach( $gspellret as $key => $val ) {
				print_words_elem( $key, $index, $text_input_index );
				print_suggs_elem( $val, $index, $text_input_index );
				$index++;
		}
	}
	else {
		$errormsg = "Error connecting to Google";
		error_handler($errormsg);
	}
}

	function debugData($data)
	{
		$fh = @fopen("debug.log", 'a+');
		fwrite($fh, $data);
		@fclose($fh);
	}

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" type="text/css" href="<?php echo $spellercss ?>" />
<script type="text/javascript" src="<?php echo $word_win_src ?>"></script>
<script type="text/javascript">
var suggs = new Array();
var words = new Array();
var textinputs = new Array();
var error;
<?php
print_textinputs_var();

print_checker_results();
?>
</script>
<script type="text/javascript">
var wordWindowObj = new wordWindow();
wordWindowObj.originalSpellings = words;
wordWindowObj.suggestions = suggs;
wordWindowObj.textInputs = textinputs;

function init_spell() {
	// check if any error occured during server-side processing
	if( error ) {
		alert( error );
	} else {
		// call the init_spell() function in the parent frameset
		if (parent.frames.length) {
			parent.init_spell( wordWindowObj );
		} else {
			alert('This page was loaded outside of a frameset. It might not display properly');
		}
	}
}

</script>

</head>
<!-- <body onLoad="init_spell();">		by FredCK -->
<body onLoad="init_spell();" bgcolor="#ffffff">

<script type="text/javascript">
wordWindowObj.writeBody();
</script>

</body>
</html>