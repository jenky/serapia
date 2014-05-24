<?php

namespace Serapia;

use Serapia\Application;
use Serapia\Parser;

class View extends \Slim\View
{
	public $renderer = '\Serapia\View\Base';

	public function render($status = 200, $params = array()) 
	{
        $app = Application::getApp();
        $view = $this->_preRenderView($app->response, $app->request);

        $status = intval($status);

        $app->response()->body($view->render());

        $app->stop();
    }

    public function getParser()
    {
    	return Application::getApp()->request->params('format');
    }

    protected function _preRenderView(\Slim\Http\Response $response, \Slim\Http\Request $request)
    {
    	$view = $this->_getView($this->getParser(), $response, $request);

        return $view;
    }

    protected function _getView($parser, \Slim\Http\Response $response, \Slim\Http\Request $request)
    {
    	switch ($parser) 
    	{
    		case 'json':      return new Parser\Json($response, $request);
			case 'xml':       return new Parser\Xml($response, $request);
			case 'text':      return new Parser\Text($response, $request);
			case 'txt':       return new Parser\Text($response, $request);
			default:          return new Parser\Json($response, $request);
    	}
    }
}