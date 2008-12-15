FTP for PHP (c) David Grudl, 2008 (http://davidgrudl.com)


Introduction
------------

FTP for PHP is a very small and easy-to-use library for accessing FTP servers.


Project at GoogleCode: http://ftp-php.googlecode.com
My PHP blog: http://phpfashion.com


Requirements
------------
- PHP (version 5 or better)


Usage
-----

Opens an FTP connection to the specified host:

	$ftp = new Ftp;
	$ftp->connect($host);

Login with username and password

	$ftp->login($username, $password);

Upload the file

	$ftp->put($destination_file, $source_file, FTP_BINARY);

Close the FTP stream

	$ftp->close();
	// or simply unset($ftp);

Ftp throws exception if operation failed. So you can simply do following:

	try {
		$ftp = new Ftp;
		$ftp->connect($host);
		$ftp->login($username, $password);
		$ftp->put($destination_file, $source_file, FTP_BINARY);

	} catch (FtpException $e) {
		echo 'Error: ', $e->getMessage();
	}

On the other hand, if you'd like the possible exception quietly catch, call methods with the prefix 'try':

	$ftp->tryDelete($destination_file);

When the connection is accidentally interrupted, you can re-establish it using method $ftp->reconnect().


Files
-----
readme.txt        - This file.
license.txt       - The license for this software (New BSD License).
ftp.class.php     - The core Ftp class source.
example.php       - Example.
