<?php

namespace Serapia;

define('API_BOOTSTRAP_SETUP', true);

class Bootstrap
{
	protected static $_instance;

	protected $_rootDir = '.';

	protected $_setup = false;

	protected function __construct()
	{
	}

	public function setup($rootDir, $autoload = false)
	{
		if ($this->_setup)
		{
			return;
		}

		$this->_rootDir = $rootDir;
		$this->_setup();

		if ($autoload)
		{
			$this->_setupAutoloader();
		}

		$this->_setup = true;
	}

	protected function _setup()
	{
		$app = new \Slim\Slim(array(
			'view' => new \Serapia\View()
		));
	}

	protected function _setupAutoloader()
	{
		if (@ini_get('open_basedir'))
		{
			// many servers don't seem to set include_path correctly with open_basedir, so don't use it
			set_include_path($this->_rootDir . PATH_SEPARATOR . '.');
		}
		else
		{
			set_include_path($this->_rootDir . PATH_SEPARATOR . '.' . PATH_SEPARATOR . get_include_path());
		}

		spl_autoload_register(array($this, 'autoload'));
	}

	public function autoload($class)
	{
		if (class_exists($class, false) || interface_exists($class, false))
		{
			return true;
		}

		$filename = $this->autoloaderClassToFile($class);
		if (!$filename)
		{
			return false;
		}

		if (file_exists($filename))
		{
			include($filename);
			return (class_exists($class, false) || interface_exists($class, false));
		}

		return false;
	}

	public function autoloaderClassToFile($class)
	{
		if (preg_match('#[^a-zA-Z0-9_\\\\]#', $class))
		{
			return false;
		}

		return $this->_rootDir . '/' . str_replace(array('_', '\\'), '/', $class) . '.php';
	}

	public function getRootDir()
	{
		return $this->_rootDir;
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