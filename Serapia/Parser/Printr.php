<?php

namespace Serapia\Parser;

use Serapia\Parser;

class Printr extends Parser
{
	public $contentType = 'text/plain';

	public function render()
	{
		return print_r($this->_params);
	}
}