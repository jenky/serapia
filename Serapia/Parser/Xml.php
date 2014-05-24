<?php

namespace Serapia\Parser;

use Serapia\Parser;

class Xml extends Parser
{
	public $rootElement = 'messages';

	public $removeEmptyElements = true;

	public $addVarType = false;

	public $contentType = 'text/xml';

	public function render()
	{
		$root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><response/>');

		$root->addAttribute("client", $_SERVER['REMOTE_ADDR']);
		$root->addAttribute("time", time());

		$this->_encode($this->rootElement, $this->_params, $root);

		echo $root->asXML();
	}

	private function _encode($key, $value, &$root)
	{	
		$type = gettype($value);

		if(is_numeric($key))
		{
			$key = substr($root->getName(), 0, -1);
		}
		
		switch($type)
		{
			case 'boolean';
				$value = $value ? 'true' : 'false';
				$root->addChild($key, $value);
				break;
			case 'integer':
				$root->addChild($key, $value);
				break;
			case 'double':
				$root->addChild($key, $value);
				break;
			case 'string':
				$root->addChild($key, $value);
				break;
			case 'array':
				if ($this->removeEmptyElements && count($value) == 0) 
				{
					return;
				}
				$child = $root->addChild($key, '');
				foreach($value as $arrKey => $arrVal)
				{
					$this->_encode($arrKey, $arrVal, $child);
				}
				break;
			case 'object':
				$properties = get_object_vars($value);
				$child = $root->addChild($key, '');
				foreach($properties as $arrKey => $arrVal)
				{
					$this->_encode($arrKey, $arrVal, $child);
				}
				break;
			default:
				
				break;
		}

		if ($this->addVarType) 
		{
			@$root->$key->addAttribute('type', $type);
		}
	}

	public function setRoot($name)
	{
		$this->rootElement = $name;	
	}
	
	public function enableRemoveEmpty($enable = true)
	{
		$this->removeEmptyElements = $enable;	
	}

	public function enableAddVarTypes( $enable = true )
	{
		$this->addVarType = $enable;	
	}
}