<?php

namespace Serapia;

use Serapia\Exception;

class Input
{
	const STRING     = 'string';
	const NUM        = 'num';
	const UNUM       = 'unum';
	const INT        = 'int';
	const UINT       = 'uint';
	const FLOAT      = 'float';
	const BOOLEAN    = 'boolean';
	const BINARY     = 'binary';
	const ARRAY_SIMPLE = 'array_simple';
	const JSON_ARRAY = 'json_array';
	const DATE_TIME       = 'dateTime';

	protected static $_DEFAULTS = array(
		static::STRING    => '',
		static::NUM       => 0,
		static::UNUM      => 0,
		static::INT       => 0,
		static::UINT      => 0,
		static::FLOAT     => 0.0,
		static::BOOLEAN   => false,
		static::BINARY    => '',
		static::ARRAY_SIMPLE => array(),
		static::JSON_ARRAY => array(),
		static::DATE_TIME => 0
	);

	protected static $_strClean = array(
		"\r" => '', // Strip carriage returns, because jQuery does so in .val()
		"\0" => '', // null
		"\x1A" => '', // substitute control character
		"\xC2\xA0" => ' ', // nbsp
		"\xC2\xAD" => '', // soft hypen
		"\xE2\x80\x8B" => '', // zero width space
		"\xEF\xBB\xBF" => '' // zero width nbsp
	);

	protected $_cleanedVariables = array();

	protected $_request = null;

	protected $_sourceData = null;

	public function __construct($source)
	{
		if ($source instanceof \Slim\Http\Request)
		{
			$this->_request = $source;
		}
		else if (is_array($source))
		{
			$this->_sourceData = $source;
		}
		else
		{
			throw new Exception('Must pass an array or \Slim\Http\Request object to Input');
		}
	}

	public function filterSingle($variableName, $filterData, array $options = array())
	{
		$filters = array();

		if (is_string($filterData))
		{
			$filters = array($filterData);
		}
		else if (is_array($filterData) && isset($filterData[0]))
		{
			$filters = is_array($filterData[0]) ? $filterData[0] : array($filterData[0]);

			if (isset($filterData[1]) && is_array($filterData[1]))
			{
				$options = array_merge($options, $filterData[1]);
			}
			else
			{
				unset($filterData[0]);
				$options = array_merge($options, $filterData);
			}
		}
		else
		{
			throw new Exception("Invalid data passed to " . __CLASS__ . "::" . __METHOD__);
		}

		$firstFilter = reset($filters);

		if (isset($options['default']))
		{
			$defaultData = $options['default'];
		}
		else if (array_key_exists($firstFilter, static::$_DEFAULTS))
		{
			$defaultData = static::$_DEFAULTS[$firstFilter];
		}
		else
		{
			$defaultData = null;
		}

		if ($this->_request)
		{
			$data = $this->_request->params($variableName);
		}
		else
		{
			$data = (isset($this->_sourceData[$variableName]) ? $this->_sourceData[$variableName] : null);
		}

		if ($data === null)
		{
			$data = $defaultData;
		}

		foreach ($filters AS $filterName)
		{
			if (isset($options['array']))
			{
				if (is_array($data))
				{
					foreach (array_keys($data) AS $key)
					{
						$data[$key] = static::_doClean($filterName, $options, $data[$key], $defaultData);
					}
				}
				else
				{
					$data = array();
					break;
				}
			}
			else
			{
				$data = static::_doClean($filterName, $options, $data, $defaultData);
			}
		}

		$this->_cleanedVariables[$variableName] = $data;
		return $data;
	}

	protected static function _doClean($filterName, array $filterOptions, $data, $defaultData)
	{
		switch ($filterName)
		{
			case static::STRING:
				$data = is_scalar($data) ? strval($data) : $defaultData;
				if (strlen($data) && !preg_match('/./u', $data))
				{
					$data = $defaultData;
				}

				$data = static::cleanString($data);

				if (empty($filterOptions['noTrim']))
				{
					$data = trim($data);
				}
			break;

			case static::NUM:
				$data = strval($data) + 0;
			break;

			case static::UNUM:
				$data = strval($data) + 0;
				$data = ($data < 0) ? $defaultData : $data;
			break;

			case static::INT:
				$data = intval($data);
			break;

			case static::UINT:
				$data = ($data = intval($data)) < 0 ? $defaultData : $data;
			break;

			case static::FLOAT:
				$data = floatval($data);
			break;

			case static::BOOLEAN:
				if ($data === 'n' || $data == 'no' || $data === 'N')
				{
					$data = false;
				}
				else
				{
					$data = (boolean)$data;
				}
				break;

			case static::BINARY:
				$data = strval($data);
			break;

			case static::ARRAY_SIMPLE:
				if (!is_array($data))
				{
					$data = $defaultData;
				}
				$data = static::cleanStringArray($data);
			break;

			case static::JSON_ARRAY:
				if (is_string($data))
				{
					$data = json_decode($data, true);
				}
				if (!is_array($data))
				{
					$data = $defaultData;
				}
				$data = static::cleanStringArray($data);
			break;

			case static::DATE_TIME:
				if (!$data)
				{
					$data = 0;
				}
				else if (is_string($data))
				{
					$data = trim($data);

					if ($data === strval(intval($data)))
					{
						// data looks like an int, treat as timestamp
						$data = intval($data);
					}
					else
					{
						$tz = (Visitor::hasInstance() ? Locale::getDefaultTimeZone() : null);

						try
						{
							$date = new DateTime($data, $tz);
							if (!empty($filterOptions['dayEnd']))
							{
								$date->setTime(23, 59, 59);
							}

							$data = $date->format('U');
						}
						catch (Exception $e)
						{
							$data = 0;
						}
					}
				}

				if (!is_int($data))
				{
					$data = intval($data);
				}
			break;

			default:
				
		}

		return $data;
	}

	/**
	 * Cleans invalid characters out of a string, such as nulls, nbsp, \r, etc.
	 * Characters may not strictly be invalid, but can cause confusion/bugs.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function cleanString($string)
	{
		// only cover the BMP as MySQL only supports that
		$string = preg_replace('/[\xF0-\xF7].../', '', $string);
		return strtr(strval($string), static::$_strClean);
	}

	/**
	 * Recursively run clean string on all strings in an array
	 *
	 * @param array $array
	 *
	 * @return array
	 */
	public static function cleanStringArray(array $array)
	{
		foreach ($array AS &$v)
		{
			if (is_string($v))
			{
				$v = static::cleanString($v);
			}
			else if (is_array($v))
			{
				$v = static::cleanStringArray($v);
			}
		}

		return $array;
	}

	/**
	* Filter an array of items
	*
	* @param array	Key-value pairs with the value being in the format expected by filterSingle. {@link Input::filterSingle()}
	*
	* @return array key-value pairs with the cleaned value
	*/
	public function filter(array $filters)
	{
		$data = array();
		foreach ($filters AS $variableName => $filterData)
		{
			$data[$variableName] = $this->filterSingle($variableName, $filterData);
		}

		return $data;
	}

	/**
	 * Statically filters a piece of data as the requested type.
	 *
	 * @param mixed $data
	 * @param constant $filterName
	 * @param array $options
	 *
	 * @return mixed
	 */
	public static function rawFilter($data, $filterName, array $options = array())
	{
		return static::_doClean($filterName, $options, $data, static::$_DEFAULTS[$filterName]);
	}

	/**
	 * Returns true if the given key was included in the request at all.
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function inRequest($key)
	{
		if ($this->_request)
		{
			return isset($this->_request->$key);
		}
		else
		{
			return isset($this->_sourceData[$key]);
		}
	}

	/**
	 * Gets all input.
	 *
	 * @return array
	 */
	public function getInput()
	{
		return $this->_request->params();
	}

	public function __get($key)
	{
		if (array_key_exists($key, $this->_cleanedVariables))
		{
			return $this->_cleanedVariables[$key];
		}
	}

	public function __isset($key)
	{
		return array_key_exists($key, $this->_cleanedVariables);
	}
}