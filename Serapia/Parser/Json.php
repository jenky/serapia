<?php

namespace Serapia\Parser;

use Serapia\Parser;

class Json extends Parser
{
	public $contentType = 'application/json';

	public $prettyJson = true;

	public function render()
	{
		$callback = $this->app->request->params('callback');

		if(isset($callback) && !empty($callback))
		{
			return $callback . '(' . $this->_encode($this->_params) . ')';
		}

		return $this->_encode($this->_params);
	}

	private function _encode($data)
	{
		if ($this->prettyJson)
		{
			if (version_compare(PHP_VERSION, '5.4.0') >= 0) 
			{
				return json_encode($data, JSON_PRETTY_PRINT);
			}
			else
			{
				return $this->_prettyJson(json_encode($data));
			}
		}

		return json_encode($data);
	}

	protected function _prettyJson($json)
	{
		$result = '';
	    $level = 0;
	    $prev_char = '';
	    $in_quotes = false;
	    $ends_line_level = NULL;
	    $json_length = strlen( $json );

	    for( $i = 0; $i < $json_length; $i++ ) {
	        $char = $json[$i];
	        $new_line_level = NULL;
	        $post = "";
	        if( $ends_line_level !== NULL ) {
	            $new_line_level = $ends_line_level;
	            $ends_line_level = NULL;
	        }
	        if( $char === '"' && $prev_char != '\\' ) {
	            $in_quotes = !$in_quotes;
	        } else if( ! $in_quotes ) {
	            switch( $char ) {
	                case '}': case ']':
	                    $level--;
	                    $ends_line_level = NULL;
	                    $new_line_level = $level;
	                    break;

	                case '{': case '[':
	                    $level++;
	                case ',':
	                    $ends_line_level = $level;
	                    break;

	                case ':':
	                    $post = " ";
	                    break;

	                case " ": case "\t": case "\n": case "\r":
	                    $char = "";
	                    $ends_line_level = $new_line_level;
	                    $new_line_level = NULL;
	                    break;
	            }
	        }
	        if( $new_line_level !== NULL ) {
	            $result .= "\n".str_repeat( "\t", $new_line_level );
	        }
	        $result .= $char.$post;
	        $prev_char = $char;
	    }

	    return $result;
	}
}