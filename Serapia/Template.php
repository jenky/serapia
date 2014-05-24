<?php

namespace Serapia;

use Serapia\Application;

class Template
{
	protected static $_instance;

	protected $_view;

	protected function __construct($view = null)
	{
		if ($view)
		{
			if (Application::resolveDynamicClass($view))
			{
				$this->_view = new $view();
			}
		}
		else
		{
			$this->_view = new \Slim\View();
		}
	}

	public function render($template, $data = array(), $status = null)
	{
		$this->_view->setTemplatesDirectory(Application::getApp()->config('templates.path'));
        $this->_view->appendData($data);
        $this->_view->display($template);
	}

	public static final function getInstance($view = null)
	{
		if (!self::$_instance)
		{
			self::$_instance = new self($view);
		}

		return self::$_instance;
	}
}