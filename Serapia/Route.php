<?php

namespace Serapia;

use Serapia\Application;
use Serapia\Bootstrap;
use Serapia\Model;
use Serapia\Input;

class Route
{
	protected static $_instance;

	public $app;

	protected static $_path;

	protected $_routes = array();

	protected $_input; 

	protected $_view;

	protected $_modelCache = array(); 

	protected function __construct()
	{
		$this->app = Application::getApp();
		$this->_input = new Input($this->app->request);

		$this->app->notFound(array($this, 'notFound'));

		//$this->app->hook('slim.before.router', array($this, 'beforeRouter'));
		$this->app->hook('slim.before.dispatch', array($this, 'beforeDispatch'));
		$this->app->hook('slim.after.dispatch', array($this, 'afterDispatch'));
	}

	final public function notFound()
	{
		$this->responseError('Not found', 404);
	}

	final public function beforeRouter()
	{
	}

	final public function beforeDispatch()
	{
		$route = $this->app->router()->getCurrentRoute();
		$this->_beforeDispatch($route);
	}

	final public function afterDispatch()
	{
		$route = $this->app->router()->getCurrentRoute();
		$this->_afterDispatch($route);
	}

	protected function _beforeDispatch() 
	{
	}

	protected function _afterDispatch()
	{
	}

	/**
	 * Get all route files from directory
	 * 
	 * @param string $dir
	 */ 
	protected function _getRoutesFromDirectory($dir)
	{
		$root = Bootstrap::getInstance()->getRootDir();
		Application::scanDir($dir, $files);
		
		foreach ($files as $file) 
		{
			$route = str_replace(array($root . '/', '.php'), '', $file);
			$this->_routes[] = '\\' . str_replace('/', '\\', $route);
		}
	}

	protected function _addRoutes()
	{
		foreach ($this->_routes as $route) 
		{
			if (class_exists($route) && is_callable(array($route, 'init')))
			{
				$route = new $route;
				if (!($route instanceof Route))
				{
					return;
				}

				try
				{
					call_user_func(array($route, 'init'));
				}
				catch (Exception $e)
				{
				}
			}
		}
	}

	protected function _getTemplateView($templateClass)
	{
		return Template::getInstance($templateClass);
	}

	public function responseRedirect($redirectTarget, $redirectStatus = 303, $redirectMessage = null, array $redirectParams = array())
	{
		$this->app->redirect($redirectTarget, $redirectStatus);
	}

	public function responseView($viewName = '', array $params, $status = 200)
	{
		$this->app->renderer = $viewName;
		return $this->app->render($status, $params);
	}

	public function responseMessage($message)
	{
		return $this->app->render(200, array('message' => $message));
	}
	
	public function responseError($message, $code = 0, $extraData = array())
	{
		$response = array('error' => array(
			'message' => $message,
			'code' => $code
		));

		if ($extraData)
		{
			$response['error'] += $extraData;
		}

		return $this->app->render(200, $response);
	}

	public function getModelFromCache($class)
	{
		if (!isset($this->_modelCache[$class]))
		{
			$this->_modelCache[$class] = Model::createModel($class);
		}

		return $this->_modelCache[$class];
	}

	public static function addRoute($route)
	{
		$route = static::setup();

		$route->_routes[] = $route;
	}

	public static function addRoutes(array $routes)
	{
		foreach ($routes as $route) 
		{
			static::addRoute($route);
		}
	}

	final public function run()
	{
		$this->_getRoutesFromDirectory(static::$_path);
		$this->_addRoutes();
		$this->app->run();
	}

	public static final function setup($path = '.')
	{
		static::$_path = $path;

		if (!static::$_instance)
		{
			static::$_instance = new self();
		}

		return static::$_instance;
	}
}