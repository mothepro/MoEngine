<?php
/**
 * Compiles a project
 *
Mo's PHP Project Compiler!

	-h		Print this help message.

	Required
		-c		Configuration file to use
		-a		Composer Autoload file

		-s		Host name of server to compile to
		-p		Location of PPK file to connect to server
		-l		Local Directory to load project
		-d		Remote Directory to upload project

		Composer Packages (Compression & Uploading)
			tpyo/amazon-s3-php-class
			tedivm/jshrink
			ps/image-optimizer
			apigen/apigen

	Optional
		-u		Directories to move from local to remote

		-t		Template Directory
		-y		Documentation Directory

		-x		Compress static files
		-0		Silent mode

		-w		Local SASS Directory
		-j		Local JS Directory
		-i		Local Image Directory

		-k		Amazon S3 Access Key Activates compression
		-v		Amazon S3 Secret Key

		-q		Remote Directory to save sass on server or S3
		-e		Remote Directory to save js on server or S3
		-r		Remote Directory to save images on server or S3
 *
 * @author Maurice Prosper <maurice.prosper@ttu.edu>
 */
class Compiler {
	/**
	 * Temporary Directory
	 */
	const TMP = 'tmp\\';
	
	/**
	 * PSCP executable
	 */
	const PSCP = '"C:\\Program Files (x86)\\PuTTY\\pscp.exe"';
	
	/**
	 * PLink executable
	 */
	const PLINK = '"C:\\Program Files (x86)\\PuTTY\\plink.exe"';
	
	/**
	 * SASS executable
	 */
	const SASS = 'sass';
	
	/**
	 * TWIGJS executable
	 */
	const TWIGJS = 'twigjs';
	
	/**
	 * Image optimizer executable
	 */
	const IMGMIN = 'imagemin';
	
	/**
	 * API Gen exe
	 */
	const APIGEN = '"vendor/bin/apigen.bat"';
	
	// <editor-fold defaultstate="collapsed" desc="properties">
	/**
	 * Private Putty Key fule
	 * @var string
	 */
	private $ppk;

	/**
	 * Hostname of server
	 * @var string
	 */
	private $host;

	/**
	 * Local Core copy of project
	 * @var string
	 */
	private $localProj;

	/**
	 * Local path to data folder
	 * @var string
	 */
	private $localStatic;


	/**
	 * Action being run
	 * @var \SplStack
	 */
	private $status;


	/**
	 * AWS S3 object
	 * @var S3
	 */
	private $s3;

	/**
	 *
	 * @var string
	 */
	private $localTpl;
	
	private $localDoc;
	
	/**
	 * Remote Core copy of project
	 * @var string
	 */
	private $remoteProj;


	/**
	 * AWS Bucket File
	 * @var string
	 */
	private $remoteSASS;


	/**
	 * AWS Bucket File
	 * @var string
	 */
	private $remoteJS;


	/**
	 * AWS Bucket File
	 * @var string
	 */
	private $remoteImage;


	/**
	 * Directories of sass to upload
	 * @var string[]
	 */
	private $localMove= array();
	
	/**
	 * Directories of sass to upload
	 * @var string[]
	 */
	private $localSASS = array();


	/**
	 * Directories of js to upload
	 * @var string[]
	 */
	private $localJS = array();


	/**
	 * Directories of images to upload
	 * @var string[]
	 */
	private $localImage = array();


	/**
	 * Output reports
	 * @var boolean
	 */
	private $silent = false;


	/**
	 * Compress and minify static files
	 * @var boolean
	 */
	private $compress = false; // </editor-fold>

	/**
	 * Report current action being taken
	 * @param string $method
	 */
	private function start($message) {
		if(!isset($this->status))
			$this->status = new SplStack;
		
		$this->status->push(microtime(true));
		
		if(!$this->silent)
			echo PHP_EOL, str_repeat("\t", $this->status->count()-1), $message, '    ';
		
		return $this;
	}
	
	/**
	 * Finish report
	 */
	private function finish() {
		$begin = $this->status->pop();
		$end = microtime(true);
		
		if(!$this->silent)
			echo PHP_EOL, str_repeat("\t", $this->status->count()), ' - ', number_format($end - $begin, 4), 's';
		
		return $this;
	}

	/**
	 * Recursively gets files in path
	 * @param string $pattern
	 * @param byte $flags
	 * @return array
	 */
	private static function rglob($pattern, $flags = 0) {
		$files = glob($pattern, $flags);
		foreach (glob(dirname($pattern) . DIRECTORY_SEPARATOR .'*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir)
			$files = array_merge(self::rglob($dir . DIRECTORY_SEPARATOR . basename($pattern), $flags), $files);
		return $files;
	}
	
	private static function path($name) {
		return rtrim(realpath($name), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
	}

	private function makeTpl() {
		$this->start('Creating JS Templates from ' . $this->localTpl);

		// fix path
		$lpath = self::path($this->localTpl); // tpl/
		
		foreach(self::rglob($lpath . '*.twig') as $file) { // C:\...\tpl\.twig
			$rel	= $this->localTpl . DIRECTORY_SEPARATOR . substr($file, strlen($lpath)); // tpl\.twig
			$output = self::TMP; // substr($this->localStatic . 'js' . DIRECTORY_SEPARATOR, strlen($this->localProj)); // data\js
			$dirs	= dirname($output . $rel);
			
			// for some reason the twigjs compiler can't make dir's
			if(!is_dir($dirs))
				mkdir($dirs, 0777, true);
			
			$this->runLocal([
				self::TWIGJS,
				$rel,
				'--output', $output
			]);
		}
		$this->finish();
		
		$this->addJS(self::TMP);
		$this->addMove($this->localTpl);
	}
	
	private function makeDoc() {
		$this
		->start('Creating Documentation from ' . $this->localDoc)
		->runLocal([
			self::APIGEN,
			'generate',
			'--config', $this->localDoc,
		])
		->finish();
	}

	/**
	 * Combines SASS to a file foreach folder
	 */
	private function sass2css($c) {
		self::wipeDir($this->localStatic . 'css');
		
		// get all sass dirs
		foreach($this->localSASS as $dir) {
			$this->start('Converting SASS to CSS in '. $dir);
		
			foreach(glob($dir . DIRECTORY_SEPARATOR . '*.scss') as $file) {
				//skip if partial
				$output = basename($file);
				if(substr($output, 0, 1) === '_')
					continue;

				$this->start('Working on ' . $output);
			
				// sass which will actuall be compiled [hidden]
				$input = $file . md5(time()) . '.scss';
				$f = fopen($input, 'w');
				exec('attrib +H ' . escapeshellarg($input));

				// get the URL Constants
				$urls = array();
				$class = new \ReflectionClass('\URL');
				foreach($class->getConstants() as $name => $val)
					$urls[] = '$url-' . strtolower($name) . ': \'' . $val . '\' !default;' . PHP_EOL; // $bootstrap-sass-asset-helper: (function-exists(twbs-font-path)) !default;

				// add URL vars to temp sass file
				fwrite($f, implode($urls));
				fwrite($f, file_get_contents($file));

				// the correct path for output
				$output = $this->localStatic .'css'. DIRECTORY_SEPARATOR . $output;
				$output = substr($output, 0, -4) . 'css';

				// run sass
				$this->runLocal([
					self::SASS,
					'--scss',
					'--trace',
					'--unix-newlines',

					'--style',
						($c ? 'compressed' : 'expanded -l'),

					$input,
					$output,
				]);

				fclose($f);
				unlink($input);
				
				$this->finish();
			}
			
			$this->finish();
		}
	}

	/**
	 * Overwrites JS files with its minified self
	 */
	private function minifyJS() {
		foreach(glob($this->localStatic . 'js' . DIRECTORY_SEPARATOR . '*.js') as $file) {
			$this->start('Minifing ' . basename($file));
			
			$js = file_get_contents($file);
			$min = \JShrink\Minifier::minify($js);
			file_put_contents($file, $min);
			
			$this->finish();
		}
	}
	
	/**
	 * Merges all files
	 * @param string $type
	 */
	private function mergeJS() {
		self::wipeDir($this->localStatic . 'js');
		
		foreach($this->localJS as $jswip) {
			// make file for each sub folder
			foreach (glob($jswip .DIRECTORY_SEPARATOR. '*', GLOB_ONLYDIR) as $v) {
				$this->start('Merging '. basename($v) .'\'s JS files');
				
				$full = $this->localStatic . 'js' . DIRECTORY_SEPARATOR . basename($v) .'.js';

				//remove compiled file
				if(is_file($full))
					unlink($full);

				// append to new js file
				$f = fopen($full, 'a'); //match with $g!

				// get js files
				$g = self::rglob($v . DIRECTORY_SEPARATOR . '*.js');

				// add js files
				foreach ($g as $vv) {
					//$this->start('Adding in '. basename($vv) .' to '. basename($v));
					
					// start buffer
					ob_start();

					// nice title
					echo "\n\n\n/*** ", basename($vv), " ***/\n\n";

					// output file
					require $vv;

					// save the executed css file
					$file = ob_get_clean();

					// add to file
					fwrite($f, $file);

					//$this->finish();
				}

				fclose($f);
				$this->finish();
			}
		}
	}
	
	private function optimizeImages() {
		foreach($this->localImage as $imgDir) {
			self::wipeDir($this->localStatic . 'img');
			
			//if(is_file($imgDir . DIRECTORY_SEPARATOR . 'Thumbs.db'))
			//	unlink($imgDir . DIRECTORY_SEPARATOR . 'Thumbs.db');
			/*
			$this->start('Optimizing '. $imgDir)
				->runLocal([
					self::IMGMIN,
					'-o', 7,
					$imgDir,// . DIRECTORY_SEPARATOR. '*',
					$this->localStatic . 'img'
				])->finish();
			 */
			
			foreach(self::rglob($imgDir . DIRECTORY_SEPARATOR . '*') as $image)
				$this->start('Optimizing '. $image)
					->runLocal([
						self::IMGMIN,
						//'-o', 7,
						$image,
						'>',
						$this->localStatic .'img'. substr($image, strlen($imgDir))
					])->finish();
		}
	}
	
	/**
	 * Uploads to S3
	 */
	private function upload() {
		foreach([
			$this->remoteSASS	=> $this->localStatic . 'css' . DIRECTORY_SEPARATOR,
			$this->remoteJS		=> $this->localStatic . 'js' . DIRECTORY_SEPARATOR,
			$this->remoteImage	=> $this->localStatic . 'img' . DIRECTORY_SEPARATOR,
		] as $bucket => $localDir) {
			if(isset($this->s3)) {
				foreach(glob($localDir . '*', GLOB_BRACE) as $file) {
					$info = new SplFileInfo($file);
					
					$this->start('Putting '. $info->getBasename() .' on '. $bucket);

					$data = file_get_contents($file);
					$data = gzencode($data, 9);

					switch ($info->getExtension()) {
					case 'css':
						$mime = 'text/css';
						break;
					case 'js':
						$mime = 'application/javascript';
						break;
					case 'png':
						$mime = 'image/png';
						break;
					case 'gif':
						$mime = 'image/gif';
						break;
					case 'jpg':
					case 'jpeg':
						$mime = 'image/jpeg';
						break;
					default:
						break;
					}
					// full MIME type
					//$mime = 'text/' . $type;
					//if($type === 'js')
					//	$mime = 'text/javascript';

					$this->s3->putObject(
						$data,
						$bucket,
						$info->getBasename(),
						S3::ACL_PUBLIC_READ,
						array(),
						[
							'Content-Type'		=> $mime,
							'Cache-Control'		=> 'max-age=315360000',
							'Expires'			=> 'Thu, 31 Dec 2037 23:55:55 GMT', //gmdate('D, d M Y H:i:s T', strtotime('+5 years'))
							'Vary'				=> 'Accept-Encoding',
							'Content-Encoding'	=> 'gzip',
							'Content-Length'	=> strlen($data),
						]
					);

					$this->finish();
				}
			} else {
				$this	->start('Uploading '. $localDir .' to '. $bucket)
						->runRemote('mkdir -p '. $bucket)
						->runLocal([
							self::PSCP,					// SCP
							'-r',						// copy recursively
							'-sftp',					// for use of SFTP protocal
							'-C',						// enable compression
							'-i', $this->ppk,			// Private key file to access server
							$localDir,					// Directory to upload
							$this->host .':'. $bucket,	// host:path on server to save data
						])->finish();
			}
		}
	}

	/**
	 * Run some commands on the remote server
	 * @param array $command commands to run
	 * @return \Compiler this
	 */
	private function runRemote($command) {
		return $this->runLocal([
			self::PLINK,
			'-ssh',				//
			'-i ', $this->ppk,	// Private key file to access server
			$this->host,		// username and hostname to connect to
			'"'. $command .'"',	// Commands to run
		]);
	}
	
	/**
	 * Run some commands on this computer
	 * @param array $command commands to run
	 * @return \Compiler $this
	 */
	private function runLocal($command) {
		// make commands a list
		if(!is_array($command))
			$command = array($command);
		$command = (implode(' ', $command));
		
		// run
		//echo "\n`$command`\n";
		exec($command);
		
		return $this;
	}
	
	/**
	 * Help File
	 */
	public static function showHelp() {
		echo <<<HELP
Mo's PHP Project Compiler!

	-h		Print this help message.

	Required
		-c		Configuration file to use
		-a		Composer Autoload file

		-s		Host name of server to compile to
		-p		Location of PPK file to connect to server
		-l		Local Directory to load project
		-d		Remote Directory to upload project

		Composer Packages (Compression & Uploading)
			tpyo/amazon-s3-php-class
			tedivm/jshrink
			ps/image-optimizer
			apigen/apigen

	Optional
		-u		Directories to move from local to remote
		
		-t		Template Directory
		-y		Documentation Directory

		-x		Compress static files
		-0		Silent mode

		-w		Local SASS Directory
		-j		Local JS Directory
		-i		Local Image Directory

		-k		Amazon S3 Access Key Activates compression
		-v		Amazon S3 Secret Key

		-q		Remote Directory to save sass on server or S3
		-e		Remote Directory to save js on server or S3
		-r		Remote Directory to save images on server or S3
HELP;
	}
	
	/**
	 * Puts everything on the chosen server
	 */
	public function compile() {
		$this->start('Compiling to '. $this->host);

		// fix path
		$this->localProj	= self::path($this->localProj);
		$this->remoteProj	= rtrim($this->remoteProj, DIRECTORY_SEPARATOR);
		$this->localStatic	= $this->localProj . 'data' . DIRECTORY_SEPARATOR;

		if(isset($this->localTpl))
			$this->makeTpl();
		
		if(isset($this->localDoc))
			$this->makeDoc();
		
		$this->sass2css($this->compress);
		$this->mergeJS();

		if($this->compress) {
			$this->minifyJS();
			$this->optimizeImages();
		}
		
		$this->upload();

		// upload project
		$this->addMove('composer.json');
		foreach($this->localMove as $name) {
			$this	->start('Uploading Project '. $name)
						->runLocal([
							self::PSCP,								// SCP
							'-r',									// copy recursively
							'-sftp',								// for use of SFTP protocal
							'-C',									// enable compression
							'-i '. $this->ppk,						// Private key file to access server
							$this->localProj . $name,				// Directory to upload
							$this->host .':'. $this->remoteProj,	// host:path on server to save data
						])
					->finish();
		}

		self::wipeDir(self::TMP);
		rmdir(self::TMP);
		
		// config
		$this->start('Updating Server Permissions')
				->runRemote('composer update -d '. $this->remoteProj);
		
		if(!isset($this->s3))
			$this->runRemote('chmod 774 -R '. $this->remoteImage)
				->runRemote('chmod 774 -R '. $this->remoteSASS)
				->runRemote('chmod 774 -R '. $this->remoteJS);
				//->runRemote('chmod 774 '. $this->remoteProj . DIRECTORY_SEPARATOR .'* -R
				
		$this->finish();
		
		// finally
		$this->finish();
	}
	
	/**
	 * Remove everything in a directory
	 * @param string $dir
	 */
	private static function wipeDir($dir) {
		$it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
		$files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
		foreach($files as $file) 
			if ($file->isDir())
				rmdir($file->getRealPath());
			else
				unlink($file->getRealPath());
	}


	// <editor-fold defaultstate="collapsed" desc="Setters">
	public function setLocalTpl($tpl) {
		$this->localTpl = $tpl;
		return $this;
	}
	public function setLocalDoc($localDoc) {
		$this->localDoc = $localDoc;
		return $this;
	}

			public function setPpk($ppk) {
		$this->ppk = $ppk;
		return $this;
	}

	public function setHost($host) {
		$this->host = $host;
		return $this;
	}
	public function setSilent($silent) {
		$this->silent = $silent;
		return $this;
	}

	public function setRemote($remote) {
		$this->remoteProj = $remote;
		return $this;
	}
	public function setLocal($local) {
		$this->local = $local;
		return $this;
	}

	/**
	 * Uploads the static files S3
	 * @param string $key Amazon S3 Key
	 * @param string $secret Amazon S3 Secret
	 */
	public function setS3($key = null, $secret = null) {
		$this->s3 = new S3($key, $secret);
		$this->setCompress(true);
	}
	
	public function addSASS($dir) {
		$this->localSASS[] = $dir;
		return $this;
	}


	public function addJS($dir) {
		$this->localJS[] = $dir;
		return $this;
	}

	public function addMove($dir) {
		$this->localMove[] = $dir;
		return $this;
	}


	public function addImage($dir) {
		$this->localImage[] = $dir;
		return $this;
	}
	public function setRemoteSASS($remoteSASS) {
		$this->remoteSASS = $remoteSASS;
		return $this;
	}

	public function setRemoteJS($remoteJS) {
		$this->remoteJS = $remoteJS;
		return $this;
	}

	public function setRemoteImage($remoteImage) {
		$this->remoteImage = $remoteImage;
		return $this;
	}

	public function setCompress($compress) {
		$this->compress = $compress;
		return $this;
	}



// </editor-fold>
}

// no errors wanted
set_time_limit(0);
date_default_timezone_set('UTC');
error_reporting(-1);
$opt = getopt('hc:a:s:p:l:d:u:w:j:y:i:k:v:q:e:r:t:xm0');

// just needs a little help
if(isset($opt['h'])) {
	Compiler::showHelp();
	exit;
}

// missing requirments
if(!isset($opt['a']) || !isset($opt['c']) || !isset($opt['s']) || !isset($opt['p']) || !isset($opt['d'])) {
	echo 'Error, missing required option.', PHP_EOL, PHP_EOL;
	Compiler::showHelp();
	exit(1);
}

// install configuration
require $opt['a']; // composer autoloader
require $opt['c']; // server app config

$c = new Compiler;
$c	->setHost($opt['s'])
	->setPpk($opt['p'])
	->setRemote($opt['d'])
	->setLocal($opt['l']);

// misc
$c->setCompress(isset($opt['x']));
$c->setSilent(isset($opt['0']));

// clean up dumb function
$opt['w'] = (isset($opt['w']) ? (is_array($opt['w']) ? $opt['w'] : array($opt['w'])) : []);
$opt['j'] = (isset($opt['j']) ? (is_array($opt['j']) ? $opt['j'] : array($opt['j'])) : []);
$opt['i'] = (isset($opt['i']) ? (is_array($opt['i']) ? $opt['i'] : array($opt['i'])) : []);
$opt['u'] = (isset($opt['u']) ? (is_array($opt['u']) ? $opt['u'] : array($opt['u'])) : []);

// add directories
foreach($opt['w'] as $dir)	$c->addSASS($dir);
foreach($opt['j'] as $dir)	$c->addJS($dir);
foreach($opt['i'] as $dir)	$c->addImage($dir);
foreach($opt['u'] as $dir)	$c->addMove($dir);

if(isset($opt['t']))		$c->setLocalTpl($opt['t']);
if(isset($opt['y']))		$c->setLocalDoc($opt['y']);

// S3
if(isset($opt['k']))		$c->setS3($opt['k'], $opt['v']);

// remote
if(isset($opt['q']))		$c->setRemoteSASS($opt['q']);
if(isset($opt['e']))		$c->setRemoteJS($opt['e']);
if(isset($opt['r']))		$c->setRemoteImage($opt['r']);

// start
$c->compile();