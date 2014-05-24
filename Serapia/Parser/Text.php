<?php

namespace Serapia\Parser;

use Serapia\Parser;

class Text extends Parser
{
	const NEWLINE = "\n";

	public $removeEmptyElements = true;

	public $contentType = 'text/plain';
	
	public function render()
	{
		$result = '';
		$this->_encode($this->_params, $result);

		echo $result;	
	}
	
	private function _encode($value, &$result)
	{
		$type = gettype($value);
		
		switch($type)
		{
			case 'boolean';
				$result .= $value ? 'true' : 'false';
				$result .= self::NEWLINE;
				break;
			case 'integer':
				$result .= $value . self::NEWLINE;
				break;
			case 'double':
				$result .= $value . self::NEWLINE;
				break;
			case 'string':
				$result .= $value . self::NEWLINE;
				break;
			case 'array':
				if($this->removeEmptyElements && count($value) == 0) 
				{
					return;
				}
				foreach($value as $arrKey => $arrVal)
				{
					$this->_encode($arrKey . ' ' . $arrVal, $result);
				}
				break;
			case "object":
				$properties = get_object_vars($value);
				foreach($properties as $objKey => $objVal)
				{
					$this->_encode($objKey . ' ' . $objVal . self::NEWLINE, $result);
				}
				break;
			default:
				
				break;
		}
	}
}