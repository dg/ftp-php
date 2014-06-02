FTP for PHP
===========

[![Downloads this Month](https://img.shields.io/packagist/dm/dg/ftp-php.svg)](https://packagist.org/packages/dg/ftp-php)

FTP for PHP is a very small and easy-to-use library for accessing FTP servers.

It requires PHP 5.0 or newer and is licensed under the New BSD License.
You can obtain the latest version from our [GitHub repository](http://github.com/dg/ftp-php)
or install it via Composer:

	php composer.phar require dg/ftp-php


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


Changelog
---------
v1.1 (6/2014)
- added support for passive mode

v1.0 (8/2012)
- initial release


-----
(c) David Grudl, 2008, 2014 (http://davidgrudl.com)
