<?php

namespace Serapia;

use Serapia\Bootstrap;
use Illuminate\Database\Capsule\Manager as Capsule;

if (!defined('API_BOOTSTRAP_SETUP')) { die('No access.'); }

class Application
{
	protected $_configDir = '.';

	protected $_rootDir = '.';

	protected $_initialized = false;

	protected static $_instance;

	protected static $_classCache = array();

	protected static $_initConfig = array(
		'undoMagicQuotes' => true,
		'setMemoryLimit' => true,
		'resetOutputBuffering' => true
	);

	public static $time = 0;

	public static $host = 'localhost';

	public static $secure = false;

	public function beginApplication($configDir = '.', $rootDir = '.', $loadDefaultData = true)
	{
		if ($this->_initialized)
		{
			return;
		}

		if (static::$_initConfig['undoMagicQuotes'] && function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc())
		{
			static::undoMagicQuotes($_GET);
			static::undoMagicQuotes($_POST);
			static::undoMagicQuotes($_COOKIE);
			static::undoMagicQuotes($_REQUEST);
		}
		if (function_exists('get_magic_quotes_runtime') && get_magic_quotes_runtime())
		{
			@set_magic_quotes_runtime(false);
		}

		if (static::$_initConfig['setMemoryLimit'])
		{
			static::setMemoryLimit(64 * 1024 * 1024);
		}

		ignore_user_abort(true);

		if (static::$_initConfig['resetOutputBuffering'])
		{
			@ini_set('output_buffering', false);
			@ini_set('zlib.output_compression', 0);


			if (!@ini_get('output_handler'))
			{
				$level = ob_get_level();
				while ($level)
				{
					@ob_end_clean();
					$newLevel = ob_get_level();
					if ($newLevel >= $level)
					{
						break;
					}
					$level = $newLevel;
				}
			}
		}

		error_reporting(E_ALL | E_STRICT & ~8192);

		//@ini_set('pcre.backtrack_limit', 1000000);

		date_default_timezone_set('UTC');

		static::$time = time();

		static::$host = (empty($_SERVER['HTTP_HOST']) ? '' : $_SERVER['HTTP_HOST']);

		static::$secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');

		$this->_configDir = $configDir;
		$this->_rootDir = $rootDir;
		//$this->addLazyLoader('requestPaths', array($this, 'loadRequestPaths'));

		if ($loadDefaultData)
		{
			$this->loadDefaultData();
		}

		$this->_initialized = true;
	}

	public static function getApp()
	{
		return \Slim\Slim::getInstance();
	}

	public function loadDefaultData()
	{
		$app = static::getApp();
		$config = $this->loadConfig();
		$app->config($config);

		$this->loadDb($config['db']);

		static::loadCustomData();
	}

	public static function initialize($configDir = '.', $rootDir = '.', $loadDefaultData = true, array $initChanges = array())
	{
		static::changeInitConfig($initChanges);
		static::getInstance()->beginApplication($configDir, $rootDir, $loadDefaultData);
	}

	public static function loadCustomData()
	{
	}

	public static function changeInitConfig(array $changes)
	{
		if ($changes)
		{
			static::$_initConfig = array_merge(static::$_initConfig, $changes);
		}
	}

	public function loadConfig()
	{
		if (file_exists($this->_configDir . '/config.php'))
		{
			$defaultConfig = $this->loadDefaultConfig();

			$config = array();
			require($this->_configDir . '/config.php');

			$outputConfig = static::merge($defaultConfig, $config);
			return $outputConfig;
		}
		else
		{
			throw new \Serapia\Exception("config not found", 1);
			
		}
	}

	public function loadDefaultConfig()
	{
		return array(
			'db' => array(
				'driver' => 'mysql',
				'host' => 'localhost',
				'port' => '3306',
				'username' => '',
				'password' => '',
				'dbname' => '',
				'charset'   => 'utf8',
			    'collation' => 'utf8_unicode_ci',
			    'prefix'    => ''
			),
			'debug' => false,
			'globalKey' => '094u32109ufd0s849sdjk4ji4398',
			'cookie' => array(
				'prefix' => 'api_',
				'path' => '/',
				'domain' => ''
			)
		);
	}

	public function loadDb(array $dbConfig)
	{
		$capsule = new Capsule(); 

		$capsule->addConnection(array(
		    'driver'    => $dbConfig['driver'],
		    'host'      => $dbConfig['host'],
		    'database'  => $dbConfig['dbname'],
		    'username'  => $dbConfig['username'],
		    'password'  => $dbConfig['password'],
		    'charset'   => $dbConfig['charset'],
		    'collation' => $dbConfig['collation'],
		    'prefix'    => $dbConfig['prefix']
		));
		 
		$capsule->setEventDispatcher(new \Illuminate\Events\Dispatcher());
		$capsule->setAsGlobal();
		$capsule->bootEloquent();

		return $capsule;
	}

	public static function resolveDynamicClass($class)
	{
		if (!$class)
		{
			return false;
		}

		if (!empty(static::$_classCache[$class]))
		{
			return static::$_classCache[$class];
		}

		$createClass = $class;

		static::$_classCache[$class] = $createClass;
		return $createClass;
	}

	public static function autoload($class)
	{
		return Bootstrap::getInstance()->autoload($class);
	}

	public static function undoMagicQuotes(&$array, $depth = 0)
	{
		if ($depth > 10 || !is_array($array))
		{
			return;
		}

		foreach ($array AS $key => $value)
		{
			if (is_array($value))
			{
				static::undoMagicQuotes($array[$key], $depth + 1);
			}
			else
			{
				$array[$key] = stripslashes($value);
			}

			if (is_string($key))
			{
				$new_key = stripslashes($key);
				if ($new_key != $key)
				{
					$array[$new_key] = $array[$key];
					unset($array[$key]);
				}
			}
		}
	}

	public static function getConfig($key)
	{
		return static::getApp()->config($key);
	}

	public static function merge(array $first, array $second)
	{
		$args = func_get_args();
		unset($args[0]);

		foreach ($args AS $arg)
		{
			if (!is_array($arg) || !$arg)
			{
				continue;
			}
			foreach ($arg AS $key => $value)
			{
				if (is_array($value) && isset($first[$key]) && is_array($first[$key]))
				{
					$first[$key] = static::merge($first[$key], $value);
				}
				else
				{
					$first[$key] = $value;
				}
			}
		}

		return $first;
	}

	protected static $_memoryLimit = null;

	public static function setMemoryLimit($limit)
	{
		$limit = intval($limit);
		$currentLimit = static::getMemoryLimit();

		if ($limit == -1 || ($limit > $currentLimit && $currentLimit >= 0))
		{
			$success = @ini_set('memory_limit', $limit);
			if ($success)
			{
				static::$_memoryLimit = $limit;
			}

			return $success;
		}

		return true; // already big enough
	}

	public static function increaseMemoryLimit($amount)
	{
		$amount = intval($amount);
		if ($amount <= 0)
		{
			return false;
		}

		$currentLimit = static::getMemoryLimit();
		if ($currentLimit < 0)
		{
			return true;
		}

		return static::setMemoryLimit($currentLimit + $amount);
	}

	public static function getMemoryLimit()
	{
		if (static::$_memoryLimit === null)
		{
			$curLimit = @ini_get('memory_limit');
			if ($curLimit === false)
			{
				// reading failed, so we have to treat it as unlimited - unlikely to be able to change anyway
				$curLimit = -1;
			}
			else
			{
				switch (substr($curLimit, -1))
				{
					case 'g':
					case 'G':
						$curLimit *= 1024;
						// fall through

					case 'm':
					case 'M':
						$curLimit *= 1024;
						// fall through

					case 'k':
					case 'K':
						$curLimit *= 1024;
				}
			}

			static::$_memoryLimit = intval($curLimit);
		}

		return static::$_memoryLimit;
	}

	public static function scanDir($dir, &$filePaths = array())
    {
        foreach (scandir($dir) as $filename) 
        {
            if (is_dir($dir . '/' . $filename) && $filename != '.' && $filename != '..') 
            {
                static::scanDir($dir . '/' . $filename, $filePaths);
            }
            elseif (is_file($dir . '/' . $filename)) 
            {
                $filePaths[] = $dir . '/' . $filename;
            }
        }
    }

    public static function arrayFilterKeys(array $data, array $keys)
	{
		// this version will not warn on undefined indexes: return array_intersect_key($data, array_flip($keys));

		$array = array();

		foreach ($keys AS $key)
		{
			$array[$key] = $data[$key];
		}

		return $array;
	}

	public static final function getInstance()
	{
		if (!static::$_instance)
		{
			static::$_instance = new self();
		}

		return static::$_instance;
	}
}