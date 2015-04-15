<?php

/**
 * FTP - access to an FTP server.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2008 David Grudl
 * @license    New BSD License
 * @link       http://phpfashion.com/
 * @version    1.1
 * 
 * @method bool alloc ( resource $ftp_stream , int $filesize , string &$result ) - Allocates space for a file to be uploaded
 * @method bool cdUp ( resource $ftp_stream ) - Changes to the parent directory
 * @method bool chDir ( resource $ftp_stream , string $directory ) - Changes the current directory on a FTP server
 * @method int chMod ( resource $ftp_stream , int $mode , string $filename ) - Set permissions on a file via FTP
 * @method bool close ( resource $ftp_stream ) - Closes an FTP connection
 * @method resource connect ( string $host , int $port = 21 , int $timeout = 90 ) - Opens an FTP connection
 * @method bool delete ( resource $ftp_stream , string $path ) - Deletes a file on the FTP server
 * @method bool exec ( resource $ftp_stream , string $command ) - Requests execution of a command on the FTP server
 * @method bool fGet ( resource $ftp_stream , resource $handle , string $remote_file , int $mode , int $resumepos = 0 ) - Downloads a file from the FTP server and saves to an open file
 * @method bool fPut ( resource $ftp_stream , string $remote_file , resource $handle , int $mode , int $startpos = 0 ) - Uploads from an open file to the FTP server
 * @method mixed getOption ( resource $ftp_stream , int $option ) - Retrieves various runtime behaviours of the current FTP stream
 * @method bool get ( resource $ftp_stream , string $local_file , string $remote_file , int $mode , int $resumepos = 0 ) - Downloads a file from the FTP server
 * @method bool login ( resource $ftp_stream , string $username , string $password ) - Logs in to an FTP connection
 * @method int mdTm ( resource $ftp_stream , string $remote_file ) - Returns the last modified time of the given file
 * @method string mkDir ( resource $ftp_stream , string $directory ) - Creates a directory
 * @method int nbContinue ( resource $ftp_stream ) - Continues retrieving/sending a file (non-blocking)
 * @method int nbFGet ( resource $ftp_stream , resource $handle , string $remote_file , int $mode , int $resumepos = 0 ) - Retrieves a file from the FTP server and writes it to an open file (non-blocking)
 * @method int nbFPut ( resource $ftp_stream , string $remote_file , resource $handle , int $mode , int $startpos = 0 ) - Stores a file from an open file to the FTP server (non-blocking)
 * @method int nbGet ( resource $ftp_stream , string $local_file , string $remote_file , int $mode , int $resumepos = 0 ) - Retrieves a file from the FTP server and writes it to a local file (non-blocking)
 * @method int nbPut ( resource $ftp_stream , string $remote_file , string $local_file , int $mode , int $startpos = 0 ) - Stores a file on the FTP server (non-blocking)
 * @method array nList ( resource $ftp_stream , string $directory ) - Returns a list of files in the given directory
 * @method bool pasv ( resource $ftp_stream , bool $pasv ) - Turns passive mode on or off
 * @method bool put ( resource $ftp_stream , string $remote_file , string $local_file , int $mode , int $startpos = 0 ) - Uploads a file to the FTP server
 * @method string pwd ( resource $ftp_stream ) - Returns the current directory name
 * @method bool quit ( resource $ftp_stream ) - Closes an FTP connection (alias of close)
 * @method array raw ( resource $ftp_stream , string $command ) - Sends an arbitrary command to an FTP server
 * @method mixed rawList ( resource $ftp_stream , string $directory , bool $recursive = false ) - Returns a detailed list of files in the given directory
 * @method bool rename ( resource $ftp_stream , string $oldname , string $newname ) - Renames a file or a directory on the FTP server
 * @method bool rmDir ( resource $ftp_stream , string $directory ) - Removes a directory
 * @method bool setOption ( resource $ftp_stream , int $option , mixed $value ) - Set miscellaneous runtime FTP options
 * @method bool site ( resource $ftp_stream , string $command ) - Sends a SITE command to the server
 * @method int size ( resource $ftp_stream , string $remote_file ) - Returns the size of the given file
 * @method resource sslConnect ( string $host , int $port = 21 , int $timeout = 90 ) - Opens an Secure SSL-FTP connection
 * @method string sysType ( resource $ftp_stream ) - Returns the system type identifier of the remote FTP server
 */
class Ftp
{
	/**#@+ FTP constant alias */
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
	/**#@-*/

	private static $aliases = array(
		'sslconnect' => 'ssl_connect',
		'getoption' => 'get_option',
		'setoption' => 'set_option',
		'nbcontinue' => 'nb_continue',
		'nbfget' => 'nb_fget',
		'nbfput' => 'nb_fput',
		'nbget' => 'nb_get',
		'nbput' => 'nb_put',
	);

	/** @var resource */
	private $resource;

	/** @var array */
	private $state;

	/** @var string */
	private $errorMsg;


	/**
	 * @param  string  URL ftp://...
	 * @param  bool
	 */
	public function __construct($url = NULL, $passiveMode = TRUE)
	{
		if (!extension_loaded('ftp')) {
			throw new Exception('PHP extension FTP is not loaded.');
		}
		if ($url) {
			$parts = parse_url($url);
			if (!isset($parts['scheme']) || !in_array($parts['scheme'], array('ftp', 'ftps', 'sftp'))) {
				throw new InvalidArgumentException('Invalid URL.');
			}
			$func = $parts['scheme'] === 'ftp' ? 'connect' : 'ssl_connect';
			$this->$func($parts['host'], empty($parts['port']) ? NULL : (int) $parts['port']);
			$this->login(urldecode($parts['user']), urldecode($parts['pass']));
			$this->pasv((bool) $passiveMode);
			if (isset($parts['path'])) {
				$this->chdir($parts['path']);
			}
		}
	}


	/**
	 * Magic method (do not call directly).
	 * @param  string  method name
	 * @param  array   arguments
	 * @return mixed
	 * @throws Exception
	 * @throws FtpException
	 */
	public function __call($name, $args)
	{
		$name = strtolower($name);
		$silent = strncmp($name, 'try', 3) === 0;
		$func = $silent ? substr($name, 3) : $name;
		$func = 'ftp_' . (isset(self::$aliases[$func]) ? self::$aliases[$func] : $func);

		if (!function_exists($func)) {
			throw new Exception("Call to undefined method Ftp::$name().");
		}

		$this->errorMsg = NULL;
		set_error_handler(array($this, '_errorHandler'));

		if ($func === 'ftp_connect' || $func === 'ftp_ssl_connect') {
			$this->state = array($name => $args);
			$this->resource = call_user_func_array($func, $args);
			$res = NULL;

		} elseif (!is_resource($this->resource)) {
			restore_error_handler();
			throw new FtpException("Not connected to FTP server. Call connect() or ssl_connect() first.");

		} else {
			if ($func === 'ftp_login' || $func === 'ftp_pasv') {
				$this->state[$name] = $args;
			}

			array_unshift($args, $this->resource);
			$res = call_user_func_array($func, $args);

			if ($func === 'ftp_chdir' || $func === 'ftp_cdup') {
				$this->state['chdir'] = array(ftp_pwd($this->resource));
			}
		}

		restore_error_handler();
		if (!$silent && $this->errorMsg !== NULL) {
			if (ini_get('html_errors')) {
				$this->errorMsg = html_entity_decode(strip_tags($this->errorMsg));
			}

			if (($a = strpos($this->errorMsg, ': ')) !== FALSE) {
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
	 * Reconnects to FTP server.
	 * @return void
	 */
	public function reconnect()
	{
		@ftp_close($this->resource); // intentionally @
		foreach ($this->state as $name => $args) {
			call_user_func_array(array($this, $name), $args);
		}
	}


	/**
	 * Checks if file or directory exists.
	 * @param  string
	 * @return bool
	 */
	public function fileExists($file)
	{
		return (bool) $this->nlist($file);
	}


	/**
	 * Checks if directory exists.
	 * @param  string
	 * @return bool
	 */
	public function isDir($dir)
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
	 * Recursive creates directories.
	 * @param  string
	 * @return void
	 */
	public function mkDirRecursive($dir)
	{
		$parts = explode('/', $dir);
		$path = '';
		while (!empty($parts)) {
			$path .= array_shift($parts);
			try {
				if ($path !== '') $this->mkdir($path);
			} catch (FtpException $e) {
				if (!$this->isDir($path)) {
					throw new FtpException("Cannot create directory '$path'.");
				}
			}
			$path .= '/';
		}
	}


	/**
	 * Recursive deletes path.
	 * @param  string
	 * @return void
	 */
	public function deleteRecursive($path)
	{
		if (!$this->tryDelete($path)) {
			foreach ((array) $this->nlist($path) as $file) {
				if ($file !== '.' && $file !== '..') {
					$this->deleteRecursive(strpos($file, '/') === FALSE ? "$path/$file" : $file);
				}
			}
			$this->rmdir($path);
		}
	}

}



class FtpException extends Exception
{
}
