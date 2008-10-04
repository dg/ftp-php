<?php

require_once 'ftp.class.php';


try {
	$ftp = new Ftp;

	// Opens an FTP connection to the specified host
	$ftp->connect('ftp.ed.ac.uk');

	// Login with username and password
	$ftp->login('anonymous', 'example@example.com');

	// Download file 'README' to local temporary file
	$temp = tmpfile();
	$ftp->fget($temp, 'README', Ftp::ASCII);

	// echo file
	echo '<pre>';
	fseek($temp, 0);
	fpassthru($temp);

} catch (FtpException $e) {
	echo 'Error: ', $e->getMessage();
}
