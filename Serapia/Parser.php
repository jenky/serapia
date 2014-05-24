<?php

namespace Serapia;

use Serapia\Application;
use Serapia\Exception;

abstract class Parser
{
	protected $app;

	protected $_view;

	public $contentType = 'application/json';

	protected $_params = array();

	public function __construct(\Slim\Http\Response $response, \Slim\Http\Request $request)
	{
		$this->app = Application::getApp();
		$this->_view = $this->app->view;

		$this->_render();
	}
	
	protected function _render()
	{
		$baseViewClass = '\Serapia\View\Base';

        $class = Application::resolveDynamicClass($this->_view->renderer);
        if (!$class)
        {
            $class = $baseViewClass;
        }

        $viewClass = new $class();
        if (!$viewClass instanceof $baseViewClass)
        {
            throw new Exception('View must be a child of ' . $baseViewClass);
        }

        $parser = $this->_view->getParser();

        $viewClass->prepareParams();

        if (class_exists($this->_view->renderer) && is_callable(array($this->_view->renderer, $parser)))
		{
			try
			{
				call_user_func(array($viewClass, $parser));
			}
			catch (Exception $e) {}
		}

		$this->_params = $viewClass->getParams();

		$this->app->response['Content-Type'] = $this->contentType;
	}
}
