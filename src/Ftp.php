<?php

declare(strict_types=1);

/**
 * FTP - access to an FTP server.
 *
 * @link       http://phpfashion.com/
 * @method void alloc(int $filesize, string & $result) - Allocates space for a file to be uploaded
 * @method void cdUp() - Changes to the parent directory
 * @method void chDir(string $directory) - Changes the current directory on a FTP server
 * @method int chMod(int $mode, string $filename) - Set permissions on a file via FTP
 * @method void close() - Closes an FTP connection
 * @method void connect(string $host, int $port = 21, int $timeout = 90) - Opens an FTP connection
 * @method void delete(string $path) - Deletes a file on the FTP server
 * @method bool exec(string $command) - Requests execution of a command on the FTP server
 * @method void fGet(resource $handle, string $remote_file, int $mode, int $resumepos = 0) - Downloads a file from the FTP server and saves to an open file
 * @method void fPut(string $remote_file, resource $handle, int $mode, int $startpos = 0) - Uploads from an open file to the FTP server
 * @method mixed getOption(int $option) - Retrieves various runtime behaviours of the current FTP stream
 * @method void get(string $local_file, string $remote_file, int $mode, int $resumepos = 0) - Downloads a file from the FTP server
 * @method void login(string $username, string $password) - Logs in to an FTP connection
 * @method int mdTm(string $remote_file) - Returns the last modified time of the given file
 * @method string mkDir(string $directory) - Creates a directory
 * @method int nbContinue() - Continues retrieving/sending a file(non-blocking)
 * @method int nbFGet(resource $handle, string $remote_file, int $mode, int $resumepos = 0) - Retrieves a file from the FTP server and writes it to an open file(non-blocking)
 * @method int nbFPut(string $remote_file, resource $handle, int $mode, int $startpos = 0) - Stores a file from an open file to the FTP server(non-blocking)
 * @method int nbGet(string $local_file, string $remote_file, int $mode, int $resumepos = 0) - Retrieves a file from the FTP server and writes it to a local file(non-blocking)
 * @method int nbPut(string $remote_file, string $local_file, int $mode, int $startpos = 0) - Stores a file on the FTP server(non-blocking)
 * @method array nList(string $directory) - Returns a list of files in the given directory
 * @method void pasv(bool $pasv) - Turns passive mode on or off
 * @method void put(string $remote_file, string $local_file, int $mode, int $startpos = 0) - Uploads a file to the FTP server
 * @method string pwd() - Returns the current directory name
 * @method void quit() - Closes an FTP connection(alias of close)
 * @method array raw(string $command) - Sends an arbitrary command to an FTP server
 * @method mixed rawList(string $directory, bool $recursive = false) - Returns a detailed list of files in the given directory
 * @method void rename(string $oldname, string $newname) - Renames a file or a directory on the FTP server
 * @method void rmDir(string $directory) - Removes a directory
 * @method bool setOption(int $option, mixed $value) - Set miscellaneous runtime FTP options
 * @method void site(string $command) - Sends a SITE command to the server
 * @method int size(string $remote_file) - Returns the size of the given file
 * @method void sslConnect(string $host, int $port = 21, int $timeout = 90) - Opens an Secure SSL-FTP connection
 * @method string sysType() - Returns the system type identifier of the remote FTP server
 */
class Ftp
{
	/**#@+ FTP constant alias */
	public const ASCII = FTP_ASCII;
	public const TEXT = FTP_TEXT;
	public const BINARY = FTP_BINARY;
	public const IMAGE = FTP_IMAGE;
	public const TIMEOUT_SEC = FTP_TIMEOUT_SEC;
	public const AUTOSEEK = FTP_AUTOSEEK;
	public const AUTORESUME = FTP_AUTORESUME;
	public const FAILED = FTP_FAILED;
	public const FINISHED = FTP_FINISHED;
	public const MOREDATA = FTP_MOREDATA;

	/**#@-*/

	private const Aliases = [
		'sslconnect' => 'ssl_connect',
		'getoption' => 'get_option',
		'setoption' => 'set_option',
		'nbcontinue' => 'nb_continue',
		'nbfget' => 'nb_fget',
		'nbfput' => 'nb_fput',
		'nbget' => 'nb_get',
		'nbput' => 'nb_put',
	];

	private FTP\Connection $resource;
	private array $state = [];


	public function __construct(?string $url = null, bool $passiveMode = true)
	{
		if (!extension_loaded('ftp')) {
			throw new Exception('PHP extension FTP is not loaded.');
		}
		if ($url) {
			$parts = parse_url($url);
			if (!isset($parts['scheme']) || !in_array($parts['scheme'], ['ftp', 'ftps', 'sftp'], true)) {
				throw new InvalidArgumentException('Invalid URL.');
			}
			$func = $parts['scheme'] === 'ftp' ? 'connect' : 'ssl_connect';
			$this->$func($parts['host'], empty($parts['port']) ? 0 : (int) $parts['port']);
			if (isset($parts['user'])) {
				$this->login(urldecode($parts['user']), isset($parts['pass']) ? urldecode($parts['pass']) : '');
			}
			$this->pasv((bool) $passiveMode);
			if (isset($parts['path'])) {
				$this->chdir($parts['path']);
			}
		}
	}


	/**
	 * Magic method (do not call directly).
	 * @throws Exception
	 * @throws FtpException
	 */
	public function __call(string $name, array $args): mixed
	{
		$name = strtolower($name);
		$silent = strncmp($name, 'try', 3) === 0;
		$func = $silent ? substr($name, 3) : $name;
		$func = 'ftp_' . (self::Aliases[$func] ?? $func);

		if (!function_exists($func)) {
			throw new Exception("Call to undefined method Ftp::$name().");
		}

		$errorMsg = null;
		set_error_handler(function (int $code, string $message) use (&$errorMsg) {
			$errorMsg = $message;
		});

		if ($func === 'ftp_connect' || $func === 'ftp_ssl_connect') {
			$this->state = [$name => $args];
			$this->resource = $func(...$args);
			$res = null;

		} elseif (!$this->resource instanceof FTP\Connection) {
			restore_error_handler();
			throw new FtpException('Not connected to FTP server. Call connect() or ssl_connect() first.');

		} else {
			if ($func === 'ftp_login' || $func === 'ftp_pasv') {
				$this->state[$name] = $args;
			}

			array_unshift($args, $this->resource);
			$res = $func(...$args);

			if ($func === 'ftp_chdir' || $func === 'ftp_cdup') {
				$this->state['chdir'] = [ftp_pwd($this->resource)];
			}
		}

		restore_error_handler();
		if (!$silent && $errorMsg !== null) {
			if (ini_get('html_errors')) {
				$errorMsg = html_entity_decode(strip_tags($errorMsg));
			}

			if (($a = strpos($errorMsg, ': ')) !== false) {
				$errorMsg = substr($errorMsg, $a + 2);
			}

			throw new FtpException($errorMsg);
		}

		return $res;
	}


	/**
	 * Reconnects to FTP server.
	 */
	public function reconnect(): void
	{
		@ftp_close($this->resource); // intentionally @
		foreach ($this->state as $name => $args) {
			[$this, $name](...$args);
		}
	}


	/**
	 * Checks if file or directory exists.
	 */
	public function fileExists(string $file): bool
	{
		return (bool) $this->nlist($file);
	}


	/**
	 * Checks if directory exists.
	 */
	public function isDir(string $dir): bool
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
	 */
	public function mkDirRecursive(string $dir): void
	{
		$parts = explode('/', $dir);
		$path = '';
		while (!empty($parts)) {
			$path .= array_shift($parts);
			try {
				if ($path !== '') {
					$this->mkdir($path);
				}
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
	 */
	public function deleteRecursive(string $path): void
	{
		if (!$this->tryDelete($path)) {
			foreach ((array) $this->nlist($path) as $file) {
				if ($file !== '.' && $file !== '..') {
					$this->deleteRecursive(!str_contains($file, '/') ? "$path/$file" : $file);
				}
			}
			$this->rmdir($path);
		}
	}
}



class FtpException extends Exception
{
}
