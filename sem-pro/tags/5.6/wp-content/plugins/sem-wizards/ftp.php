<?php
if ( !function_exists('ftp_connect') ) :

	include ABSPATH . 'wp-admin/includes/class-ftp.php';

else :

class ftp
{
	var $conn;


	#
	# ftp()
	#

	function ftp($debug = false)
	{
		$this->debug = $debug;
	} # ftp()


	#
	# connect()
	#

	function connect($host)
	{
		if ( $this->debug )
		{
			echo 'FTP - CONNECT ' . $host . "\n";
		}

		$this->conn = @ftp_connect($host);

		return $this->conn;
	} # connect()


	#
	# login()
	#

	function login($user, $pass)
	{
		if ( $this->debug )
		{
			echo 'FTP - USR ' . $user . "\n";
		}

		return @ftp_login($this->conn, $user, $pass);
	} # login()


	#
	# Passive()
	#

	function Passive($val = true)
	{
		if ( $this->debug )
		{
			echo 'FTP - PASV ' . ( $val ? 'on' : 'off' ) . "\n";
		}

		if ( $this->conn )
		{
			return ftp_pasv($this->conn, $val);
		}
		else
		{
			return false;
		}
	} # Passive()


	#
	# quit()
	#

	function quit()
	{
		if ( $this->debug )
		{
			echo 'FTP - QUIT' . "\n";
		}

		if ( $this->conn )
		{
			ftp_close($this->conn);
		}

		$this->conn = null;

		return true;
	} # quit()


	#
	# mkdir()
	#

	function mkdir($dir)
	{
		if ( $this->debug )
		{
			echo 'FTP - MKDIR ' . $dir . "\n";
		}

		return ftp_mkdir($this->conn, $dir);
	} # mkdir()


	#
	# chmod()
	#

	function chmod($file, $mode)
	{
		if ( $this->debug )
		{
			echo sprintf('FTP - CHMOD %u %s', decoct($mode), $file) . "\n";
		}

		return ftp_site($this->conn, sprintf('CHMOD %u %s', decoct($mode), $file));
	} # chmod()


	#
	# put()
	#

	function put($local_file, $remote_file, $mode = null)
	{
		if ( is_null($mode) )
		{
			$file_type = exec('file -i -b ' . escapeshellarg($local_file));

			if ( strpos($file_type, 'application') !== false
				|| strpos($file_type, 'image') !== false
				|| strpos($file_type, 'audio') !== false
				|| strpos($file_type, 'video') !== false
				)
			{
				$mode = FTP_BINARY;
			}
			else
			{
				$mode = FTP_ASCII;
			}
		}

		if ( $this->debug )
		{
			echo 'FTP - PUT ' . $local_file . ' ' . $remote_file . "\n";
		}

		return ftp_put($this->conn, $remote_file, $local_file, $mode);
	} # put()


	#
	# chdir()
	#

	function chdir($dir)
	{
		if ( $this->debug )
		{
			echo 'FTP - CHDIR ' . $dir . "\n";
		}

		return @ftp_chdir($this->conn, $dir);
	} # chdir()
} # ftp

endif;
?>