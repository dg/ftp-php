<?php
/**
 * Example with downloading remote folder
 * @author Igor Malinovskiy <glide.name>
 * @file downloadExample.php
 * @date: 24.01.13
 * @time: 12:17
 */

require_once 'ftp.class.php';


$config = array(
    'host'              => 'example.com',
    'login'             => 'username',
    'pass'              => 'somepass',
    'downloadRootDir'   => '/usr/www/',
    'excludeFiles'      => '/cache/i', //regex for exclude files
    'localSaveDir'      => '/home/myfolder'
);


try {
    $ftp = new Ftp();

    // Opens an FTP connection to the specified host
    $ftp->connect($config['host']);

    // Login with username and password
    $ftp->login($config['login'], $config['pass']);

    // Enable passive mode
    $ftp->pasv(TRUE);

    //download site sources with out cache
    $ftp->downloadDirContent(
        $config['localSaveDir'], $config['downloadRootDir'], $config['excludeFiles']
    );


} catch (FtpException $e) {
    echo 'Error: ', $e->getMessage();
}