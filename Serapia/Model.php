<?php

namespace Serapia;

use Serapia\Application;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Model extends Eloquent
{
	protected $_modelCache = array();

	public static function getOffset($page, $perPage)
	{
		$page = intval($page);
		$perPage = intval($perPage);
		if ($page < 1)
		{
			$page = 1;
		}

		$offset = intval(($page - 1) * $perPage);

		return $offset;
	}

	public static function filterKey($key = null)
	{
		$app = Application::getApp();		

		if ($key === null)
		{
			$key = $app->request->params('key');
		}

		if (!empty($key) && $key == $app->config('globalKey'))
		{
			return true;
		}

		return false;
	}

	public static function prepare($params)
	{
		if ($params instanceof \Illuminate\Database\Eloquent\Collection)
		{
			$params->each(function($model)
			{
			    $model->setHidden($model->getHidden());
			});

			return $params->toArray();			
		}

		return is_array($params) ? $params : array();
	}

	public function getHidden()
	{
		return array();
	}

	public function getModelFromCache($class)
	{
		if (!isset($this->_modelCache[$class]))
		{
			$this->_modelCache[$class] = static::createModel($class);
		}

		return $this->_modelCache[$class];
	}

	public static function createModel($class)
	{
		$createClass = Application::resolveDynamicClass($class);
		if (!$createClass)
		{
			throw new Exception("Invalid model '$class' specified");
		}

		return new $createClass;
	}
}