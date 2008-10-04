<?php

/**
 * FTP - access to an FTP server.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2008 David Grudl
 * @license    New BSD License
 * @link       http://phpfashion.com/
 * @version    1.0
 */
class Ftp
{
	const ASCII = FTP_ASCII;
	const TEXT = FTP_TEXT;
	const BINARY = FTP_BINARY;
	const IMAGE = FTP_IMAGE;
	const TIMEOUT_SEC = FTP_TIMEOUT_SEC;
	const AUTOSEEK = FTP_AUTOSEEK;
	const AUTORESUME = FTP_AUTORESUME;
	const FAILED = FTP_FAILED;
	const FINISHED = FTP_FINISHED;
	const MOREDATA = FTP_MOREDATA;

	/** @var resource */
	private $resource;

   	/** @var string */
	private $errorMsg;



	/**
	 * Magic method (do not call directly).
	 */
	public function __call($name, $args)
	{
		if (!function_exists('ftp_' .  $name)) {
			throw new FtpException("Call to undefined method Ftp::$name().");
		}

		$this->errorMsg = NULL;
		set_error_handler(array($this, '_errorHandler'));

		if (strcasecmp($name, 'connect') === 0 || strcasecmp($name, 'ssl_connect') === 0) {
			$this->resource = call_user_func_array('ftp_' . $name, $args);
			$res = NULL;

		} elseif (!is_resource($this->resource)) {
			throw new FtpException("Not connected to FTP server. Call connect() or ssl_connect() first.");

		} else {
			array_unshift($args, $this->resource);
			$res = call_user_func_array('ftp_' . $name, $args);
		}

		restore_error_handler();
		if ($this->errorMsg !== NULL) {
			if (ini_get('html_errors')) {
				$this->errorMsg = html_entity_decode(strip_tags($this->errorMsg));
			}
			$a = strpos($this->errorMsg, ': ');
			if ($a !== FALSE) {
				$this->errorMsg = substr($this->errorMsg, $a + 2);
			}
			throw new FtpException($this->errorMsg);
		}

		return $res;
	}



	/**
	 * Internal error handler. Do not call directly.
	 */
	public function _errorHandler($code, $message)
	{
		$this->errorMsg = $message;
	}



	/**
	 * Checks if file or directory exists.
	 * @param  string
	 * @return bool
	 */
	public function file_exists($file)
	{
		return in_array($file, $this->nlist('.'), TRUE);
	}



	/**
	 * Checks if directory exists.
	 * @param  string
	 * @return bool
	 */
	public function is_dir($dir)
	{
		$current = $this->pwd();
		try {
			$this->chdir($dir);
		} catch (FtpException $e) {
		}
		$this->chdir($current);
		return empty($e);
	}



	/**
	 * Recursive creates directory.
	 * @param  string
	 * @return void
	 */
	public function mkdir_r($dir)
	{
		$parts = explode('/', $dir);
		$path = '';
		while (!empty($parts)) {
			$path .= array_shift($parts);
			try {
				if ($path !== '') $this->mkdir($path);
			} catch (FtpException $e) {
				if (!$this->is_dir($path)) {
					throw new FtpException("Cannot create directory '$path'.");
				}
			}
			$path .= '/';
		}
	}

}



class FtpException extends Exception
{
}