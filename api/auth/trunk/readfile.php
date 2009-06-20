<?php
include_once dirname(__FILE__) . '/restrict.php';

$file = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI'];

if ( !file_exists($file) )
{
	$protocol = $_SERVER['SERVER_PROTOCOL'];

	if ( ( $protocol != 'HTTP/1.1' ) && ( $protocol != 'HTTP/1.0' ) )
	{
		$protocol = 'HTTP/1.0';
	}

	$args = array(
		'file' => $file,
		);
	new status_404($args);
}

$file_name = basename($file);
$file_name = preg_replace('/\./', '%2e', $file_name, substr_count($file_name, '.') - 1);
$file_type = exec('file -i -b ' . escapeshellarg($file));
$file_size = filesize($file);

/*
if ( isset($_SERVER['HTTP_RANGE']) )
{
	list($a, $range) = explode("=", $_SERVER['HTTP_RANGE']);
	$range = explode('-', $range);

	$start = ( $range[0] > 0 ) ? intval($range[0]) : 0;
	$end = ( $range[1] > 0 ) ? intval($range[1]) : $file_size - 1;

	header("$protocole 206 Partial Content");
	$file_size = ( $end - $start + 1 );
}
else
{
	$start = 0;
	$end = $file_size - 1;
}
die;
*/

#echo 'please try the download again in a few minutes, I\'m debugging a script -- Denis';
#var_dump($file_type);
#die;

header("Content-type: $file_type");
if ( strpos($file_type, 'application') !== false )
{
header("Content-Disposition: attachment; filename=\"$file_name\"");
header("Content-Length: $file_size");
#header("Content-Range: bytes $start-$end/$file_size");
header('Expires: '. gmdate("D, d M Y H:i:s", strtotime("+2 hours")) . ' GMT');
#header('Accept-Ranges: bytes');
header('Cache-control: no-cache, must-revalidate');
header('Pragma: no-cache');
}

set_time_limit(0);

/*
if ( $file = fopen($file, 'rb') )
{
	while( !feof($file) && ( connection_status()==0 ) )
	{
		print(fread($file, 1024 * 8));
		flush();
	}

	fclose($file);
}
*/

readfile($file);

die;
?>