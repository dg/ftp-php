<?php

require_once 'src/Ftp.php';


try {
    // instance with host
    $ftp = new Ftp('ftp.ed.ac.uk');

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
