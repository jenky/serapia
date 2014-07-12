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
		$this->_render();
	}
	
	protected function _render()
	{
		$this->_params = $this->app->view->all();

		unset($this->_params['flash']);
		if ($this->app->request->params('_debug'))
		{
			$this->_params['debug'] = array( 
				'status' => $this->app->response->getStatus(),
				//'page_time' => abs(Application::$time - Application::get('page_start_time')),
				'memory_usage' => memory_get_usage(),
			);
		}

		$this->app->response['Content-Type'] = $this->contentType;
	}
}
