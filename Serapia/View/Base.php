<?php

namespace Serapia\View;

use Serapia\Application;

class Base
{
	protected $app;

	protected $_params = array();

	public function __construct()
	{
		$this->app = Application::getApp();
		$this->_params = $this->app->view->all();
	}

	public function prepareParams()
	{
		unset($this->_params['flash']);
		$this->_prepareParams();
		if ($this->app->request->params('_debug'))
		{
			$this->_params += array( 
				'status' => $this->app->response->getStatus(),
				//'page_time' => abs(Application::$time - Application::get('page_start_time')),
				'memory_usage' => memory_get_usage(),
			);
		}
	}

	public function getParams()
	{
		return $this->_params;
	}

	public function setParam($key, $value)
	{
		$this->_params[$key] = $value;
	}

	protected function _prepareParams() {}
}