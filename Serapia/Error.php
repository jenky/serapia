<?php

namespace Serapia;

use Serapia\Application;

abstract class Error
{
	public static function getErrorMessage(&$message = null)
	{
		$request = Application::getApp()->request;

		if ($request->isGet())
		{
			$message = 'Unsupported GET request.';
		}

		if ($request->isPost())
		{
			$message = 'Unsupported POST request.';
		}

		if ($request->isPut())
		{
			$message = 'Unsupported PUT request.';
		}

		if ($request->isDelete())
		{
			$message = 'Unsupported DELETE request.';
		}
	}
}