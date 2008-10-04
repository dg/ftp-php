<?php

require_once 'ftp.class.php';


try {
	$ftp = new Ftp;

	// Opens an FTP connection to the specified host
	$ftp->connect('ftp.ed.ac.uk');

	// Login with username and password
	$ftp->login('anonymous', 'example@example.com');

	// Download file 'README' to local file 'temp'
	$ftp->get('temp', 'README', Ftp::ASCII);

	// echo file
	echo '<pre>';
	readfile('temp');
	unlink('temp');

} catch (FtpException $e) {
	echo 'Error: ', $e->getMessage();
}

