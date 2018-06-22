FTP for PHP
===========

[![Downloads this Month](https://img.shields.io/packagist/dm/dg/ftp-php.svg)](https://packagist.org/packages/dg/ftp-php)
[![Latest Stable Version](https://poser.pugx.org/dg/ftp-php/v/stable)](https://github.com/dg/ftp-php/releases)
[![License](https://img.shields.io/badge/license-New%20BSD-blue.svg)](https://github.com/dg/ftp-php/blob/master/license.md)

FTP for PHP is a very small and easy-to-use library for accessing FTP servers.

It requires PHP 5.0 or newer and is licensed under the New BSD License.
You can obtain the latest version from our [GitHub repository](https://github.com/dg/ftp-php/releases)
or install it via Composer:

```
php composer.phar require dg/ftp-php
```

If you like it, **[please make a donation now](https://nette.org/make-donation?to=ftp-php)**. Thank you!


Usage
-----

Opens an FTP connection to the specified host:

```php
$ftp = new Ftp;
$host = 'ftp.example.com';
$ftp->connect($host);
```

Login with username and password

```php
$ftp->login($username, $password);
```

Upload the file

```php
$ftp->put($destination_file, $source_file, FTP_BINARY);
```

Close the FTP stream

```php
$ftp->close();
// or simply unset($ftp);
```

Ftp throws exception if operation failed. So you can simply do following:

```php
try {
	$ftp = new Ftp;
	$ftp->connect($host);
	$ftp->login($username, $password);
	$ftp->put($destination_file, $source_file, FTP_BINARY);

} catch (FtpException $e) {
	echo 'Error: ', $e->getMessage();
}
```

On the other hand, if you'd like the possible exception quietly catch, call methods with the prefix 'try':

```php
$ftp->tryDelete($destination_file);
```

When the connection is accidentally interrupted, you can re-establish it using method $ftp->reconnect().


-----
(c) David Grudl, 2008, 2014 (http://davidgrudl.com)
