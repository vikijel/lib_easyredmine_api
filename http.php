<?php
/**
 * @author         EasyJoomla.org, VikiJel <vikijel@gmail.com>
 * @copyright      ©2013-2015 EasyJoomla.org
 * @license        http://opensource.org/licenses/LGPL-3.0 LGPL-3.0
 * @package        Joomla
 * @version        @see easyredmine_api.xml
 */
defined('_JEXEC') or die('Direct access is not allowed!');

/**
 * @author     VikiJel <vikijel@gmail.com>
 * @package    Joomla
 */
class EasyRedmineHttpApi
{
	/** @var array $errors */
	protected $errors = array();

	/** @var string $api_key */
	protected $api_key = '';

	/** @var string $er_url */
	protected $er_url = '';

	/** @var boolean $write_log */
	protected $write_log = true;

	/** @var int $curl_max_loops */
	protected $curl_max_loops = 20;

	/** @var int $curl_done_loops */
	protected $curl_done_loops = 0;

	/** @var array Messages for http error codes */
	private $http_code_message = array(
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => 'I\'m a teapot',
		420 => 'Enhance Your Calm (Twitter)',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		425 => 'Unordered Collection',
		426 => 'Upgrade Required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',
		444 => 'No Response (Nginx)',
		449 => 'Retry With',
		450 => 'Blocked by Windows Parental Controls',
		499 => 'Client Closed Request (Nginx)',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Temporarily Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		508 => 'Loop detected',
		509 => 'Bandwidth Limit Exceeded',
		510 => 'Not Extended',
		511 => 'Network Authentication Required',
		598 => 'Network read timeout error',
		599 => 'Network connect timeout error'
	);

	/**
	 * Initialize API
	 *
	 * @param string  $er_url
	 * @param string  $api_key
	 * @param boolean $write_log
	 */
	public function __construct($er_url, $api_key = '', $write_log = true)
	{
		$this->api_key   = (string) $api_key;
		$this->er_url    = (string) $er_url;
		$this->write_log = (bool) $write_log;

		$this->_initLog();
		$this->_checkConfig();
	}

	/**
	 * Sends a http request using CURL
	 *
	 * @param string           $path
	 * @param string           $method
	 * @param array            $data
	 * @param array            $params
	 * @param array            $headers
	 * @param null|true|string $referer
	 *
	 * @return StdClass
	 */
	public function sendRequest($path = '', $method = 'get', $data = array(), $params = array(), $headers = array(), $referer = null)
	{
		$method = strtolower($method);
		$c      = curl_init();
		$uri    = JURI::getInstance($this->er_url);

		$uri->setPath($path);

		if (!empty($params))
		{
			$uri->setQuery(urldecode(http_build_query($params)));
		}
		else
		{
			$uri->setQuery(array());
		}

		if ($this->api_key != '')
		{
			$uri->setVar('key', $this->api_key);
		}

		curl_setopt($c, CURLOPT_URL, $uri->toString());
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);

		switch ($method)
		{
			case 'get':
				curl_setopt($c, CURLOPT_HTTPGET, 1);
				break;

			case 'post':
				$data = http_build_query($data);
				curl_setopt($c, CURLOPT_POST, 1);
				//$headers = array_merge(array('Content-type: multipart/form-data'), $headers);
				curl_setopt($c, CURLOPT_POSTFIELDS, $data);
				break;

			case 'upload':
				$headers = array_merge(array('Content-Type: application/octet-stream'), $headers);
				curl_setopt($c, CURLOPT_POST, 1);
				curl_setopt($c, CURLOPT_POSTFIELDS, $data);
				break;

			case 'put':
				curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($c, CURLOPT_POSTFIELDS, $data);
				break;

			case 'delete':
				curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'DELETE');
				curl_setopt($c, CURLOPT_POSTFIELDS, $data);
				break;
		}

		if (!empty($headers))
		{
			curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
		}

		if ($referer === true and isset($_SERVER['HTTP_REFERER']) and $_SERVER['HTTP_REFERER'] != '')
		{
			curl_setopt($c, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']); //auto referer
		}
		elseif ($referer !== null and $referer !== false and $referer != '')
		{
			curl_setopt($c, CURLOPT_REFERER, $referer); //custom referer
		}

		$this->_writeLog('Request: ' . $uri->toString() . ' | ' . $method . ' | ' . print_r($data, true));

		$response = $this->_curl_exec($c);
		$r_code   = curl_getinfo($c, CURLINFO_HTTP_CODE);
		$con_type = curl_getinfo($c, CURLINFO_CONTENT_TYPE);
		$arr      = explode(';', $con_type, 2);
		$r_type   = reset($arr);

		curl_close($c);

		$this->_writeLog('Response: ' . $r_code . ' | ' . $r_type . ' | ' . $response); //Todo do not log whole responses

		$return       = new StdClass();
		$return->code = $r_code;
		$return->type = $r_type;
		$return->body = $response;

		if ($return->code >= 400)
		{
			$this->setError('Error' . (isset($this->http_code_message[$return->code]) ? ': ' . $this->http_code_message[$return->code] : ' - response code: ' . $return->code));
		}

		return $return;
	}

	/**
	 * @see curl_exec()
	 *
	 * @param $ch
	 *
	 * @return mixed|string
	 */
	protected function _curl_exec($ch)
	{
		$this->curl_done_loops = 0;

		if (ini_get('open_basedir') == '' && strtolower(ini_get('safe_mode')) == 'off')
		{
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_MAXREDIRS, $this->curl_max_loops);

			return curl_exec($ch);
		}
		else
		{
			if ($this->curl_done_loops++ >= $this->curl_max_loops)
			{
				$this->curl_done_loops = 0;
				$this->_writeLog('Maximum number of redirects exceeded in ' . get_class($this) . '::_curl_exec()');

				return false;
			}

			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$data    = curl_exec($ch);
			$res_arr = explode("\r\n\r\n", $data, 2);

			if (isset($res_arr[1]))
			{
				if (stripos($res_arr[1], 'HTTP') === 0)
				{
					$res_arr2 = explode("\r\n\r\n", $res_arr[1], 2);

					if (isset($res_arr2[1]))
					{
						$header = $res_arr2[0];
						$data   = $res_arr2[1];
					}
					else
					{
						$header = $res_arr[0];
						$data   = $res_arr[1];
					}
				}
				else
				{
					$header = $res_arr[0];
					$data   = $res_arr[1];
				}
			}
			else
			{
				$header = $res_arr[0];
				$data   = '';
			}

			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			if ($http_code == 301 || $http_code == 302)
			{
				$matches = array();

				preg_match('/Location: (.*)/', $header, $matches);

				$url = @parse_url(trim(array_pop($matches)));

				if (!$url)
				{
					$this->curl_done_loops = 0;

					return $data;
				}

				$last_url = parse_url(curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));

				if (!isset($url['scheme']))
				{
					$url['scheme'] = $last_url['scheme'];
				}
				if (!isset($url['host']))
				{
					$url['host'] = $last_url['host'];
				}
				if (!isset($url['path']))
				{
					$url['path'] = $last_url['path'];
				}

				$new_url = $url['scheme'] . '://' . $url['host'] . $url['path'] . (isset($url['query']) ? '?' . $url['query'] : '');

				curl_setopt($ch, CURLOPT_URL, $new_url);

				return $this->_curl_exec($ch);
			}
			else
			{
				$this->curl_done_loops = 0;

				return $data;
			}
		}
	}

	/**
	 * Throws an exception with given message after writing it to logfile
	 *
	 * @param string $message
	 *
	 * @throws Exception
	 * @return boolean
	 */
	protected function _throwException($message)
	{
		$this->_writeLog($message, JLog::ERROR);
		throw new Exception($message);
	}

	/**
	 * Write log entry
	 *
	 * @param string $message
	 * @param int    $priority
	 * @param string $category
	 *
	 * @return boolean
	 */
	protected function _writeLog($message, $priority = JLog::INFO, $category = 'lib_easyredmine_api_http')
	{
		if ($this->write_log)
		{
			JLog::add($message, $priority, $category);
		}

		return true;
	}

	/**
	 * Initialise logfile
	 *
	 * @return boolean
	 */
	protected function _initLog()
	{
		if ($this->write_log)
		{
			jimport('joomla.log.log');
			JLog::addLogger(array('text_file' => 'lib_easyredmine_api_http.log.php'), JLog::ALL, 'lib_easyredmine_api_http');
		}

		return true;
	}

	/**
	 * Check main properties of actual instance
	 *
	 * @return boolean
	 */
	protected function _checkConfig()
	{
		if (trim($this->er_url) == '')
		{
			return $this->_throwException('Missing argument(s) $er_url in ' . get_class($this) . '::__construct()');
		}

		if (!filter_var($this->er_url, FILTER_VALIDATE_URL, array(FILTER_FLAG_SCHEME_REQUIRED, FILTER_FLAG_HOST_REQUIRED)))
		{
			return $this->_throwException('Invalid argument $er_url in ' . get_class($this) . '::__construct()');
		}

		return true;
	}

	/**
	 * Add error message to array of errors
	 *
	 * @param string $message
	 *
	 * @return boolean
	 */
	public function setError($message)
	{
		$this->errors[] = (string) $message;

		return true;
	}

	/**
	 * Set array of errors
	 *
	 * @param array $array
	 *
	 * @return boolean
	 */
	public function setErrors($array)
	{
		if (is_array($array))
		{
			$this->errors = $array;
		}
		else
		{
			$this->errors = array();
			$this->setError($array);
		}

		return true;
	}

	/**
	 * Get last error caused by previous operation
	 *
	 * @returns string last error in errors array
	 */
	public function getError()
	{
		return (string) end($this->errors);
	}

	/**
	 * Get array of errors caused by previous operation
	 *
	 * @returns array of strings containing all error messages
	 */
	public function getErrors()
	{
		return (array) $this->errors;
	}
}