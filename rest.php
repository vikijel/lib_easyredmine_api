<?php
/**
 * @author         EasyJoomla.org, VikiJel <vikijel@gmail.com>
 * @copyright      ©2013-2014 EasyJoomla.org
 * @license        http://opensource.org/licenses/LGPL-3.0 LGPL-3.0
 * @package        Joomla
 * @version        @see easyredmine_api.xml
 */
defined('_JEXEC') or die('Direct access is not allowed!');

jimport('easyredmine_api.rest._interface');
jimport('easyredmine_api.rest._xmlelement');

/**
 * Don't use this class directly, always use its child classes!
 * Child classes must have $context and $context_one properties overridden (to match rest api naming/routing)
 *
 * @author     VikiJel <vikijel@gmail.com>
 * @package    Joomla
 * @see        EasyRedmineRestApiInterface
 */
class EasyRedmineRestApi implements EasyRedmineRestApiInterface
{
	/** @var array $errors */
	protected $errors = array();

	/** @var string $context */
	protected $context = '_default_';

	/** @var string $context_one */
	protected $context_one = '_default_';

	/** @var string $api_key */
	protected $api_key = '';

	/** @var string $er_url */
	protected $er_url = '';

	/** @var string $lang */
	protected $lang = '';

	/** @var string $lang_parameter (set this to empty string and lang parameter will not be passed in request) */
	protected $lang_parameter = 'force_lang';

	/** @var boolean $write_log */
	protected $write_log = true;

	/** @var boolean $cache_list */
	protected $cache_list = true;

	/** @var boolean $cache_detail */
	protected $cache_detail = true;

	/** @var array $cache */
	protected static $cache = array();

	/** @var int $curl_max_loops */
	protected $curl_max_loops = 4;

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
		503 => 'Service Temporarily Unavailable - REST API is temporarily unable to service your request probably due to maintenance downtime. Please try again later.',
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
	 * @since 1.1.0
	 *
	 * @param string $context
	 * @param string $er_url
	 * @param string $api_key
	 * @param bool   $write_log
	 * @param bool   $force_new
	 *
	 * @return EasyRedmineRestApi descendant
	 * @throws Exception
	 */
	public static final function getInstance($context, $er_url, $api_key, $write_log = true, $force_new = false)
	{
		static $instances;

		if (!isset($instances))
		{
			$instances = array();
		}

		$context     = str_replace('.', '/', $context);
		$instance_id = md5($context . ':' . $er_url . ':' . $api_key . ':' . (int) $write_log);

		if (!isset($instances[$instance_id]) or $force_new)
		{
			$a           = explode('/', $context);
			$path_prefix = $class_prefix = '';
			$c           = count($a);

			if ($c > 1)
			{
				$c--;

				for ($i = 0; $i < $c; $i++)
				{
					$path_prefix .= $a[$i] . '.';
					$class_prefix .= ucfirst($a[$i]);
				}

				$class_prefix_arr = explode('_', $class_prefix);
				$class_prefix_arr = array_map('ucfirst', $class_prefix_arr);
				$class_prefix     = implode('', $class_prefix_arr);
			}

			$entity = end($a);
			$entity = preg_replace('/[^a-zA-Z0-9]/', '_', $entity);
			$arr    = explode('_', $entity);
			$arr    = array_map('strtolower', $arr);
			$path   = 'easyredmine_api.rest.' . $path_prefix . implode('_', $arr);
			$arr    = array_map('ucfirst', $arr);
			$class  = 'EasyRedmineRestApi' . $class_prefix . implode('', $arr);

			if (!jimport($path))
			{
				throw new Exception('Missing library path "' . $path . '"');
			}

			if (!class_exists($class))
			{
				throw new Exception('Missing library class "' . $class . '"');
			}

			$instances[$instance_id] = new $class($er_url, $api_key, $write_log);
		}

		return $instances[$instance_id];
	}

	/**
	 * Initialize API
	 *
	 * @param string  $er_url
	 * @param string  $api_key
	 * @param boolean $write_log
	 */
	public function __construct($er_url, $api_key, $write_log = true)
	{
		$this->api_key   = (string) $api_key;
		$this->er_url    = (string) $er_url;
		$this->write_log = (bool) $write_log;

		$this->_initLog();
		$this->_checkConfig();
		$this->_checkContext();
		$this->setLanguage();
	}

	/**
	 * Set API language
	 *
	 * @param $lang string Joomla language tag (at least first 2 letters)
	 *
	 * @since 1.0.6
	 */
	public function setLanguage($lang = null)
	{
		if ($this->lang_parameter != '')
		{
			if ($lang === null)
			{
				$lang = JFactory::getLanguage()->getTag();
			}

			// cs-CZ => cs
			$this->lang = strtolower(substr($lang, 0, 2));
		}
	}

	/**
	 * Set API language request parameter
	 *
	 * @param $lang_parameter string Name of request parameter for api language
	 *
	 * @since 1.0.6
	 */
	public function setLanguageParameter($lang_parameter)
	{
		$this->lang_parameter = $lang_parameter;
	}

	/**
	 * Get list of records for actual context, supports filtering by url params
	 *
	 * @param array $filters URL parameters to filter results
	 *
	 * @returns EasyRedmineXmlElement|false
	 */
	public function getList($filters = array())
	{
		$filters = (array) $filters;

		if ($this->cache_list)
		{
			$cache_id = get_class($this) . '.' . __FUNCTION__ . '.' . JArrayHelper::toString($filters);

			if (isset(self::$cache[$cache_id]) and !empty(self::$cache[$cache_id]))
			{
				return self::$cache[$cache_id];
			}
		}

		if (!empty($filters))
		{
			$filters['set_filter'] = 1;
		}

		$response = $this->_sendRequest('/' . $this->context . '.xml', 'get', null, $filters);

		if ($response->code != 200)
		{
			if (isset($response->body->error))
			{
				$this->setErrors((array) $response->body->error);
			}

			$this->_writeLog('GetList for context "' . $this->context . '/' . $this->context_one . '" failed: ' . implode($this->getErrors()), JLog::ERROR);

			return false;
		}

		if ($this->cache_list)
		{
			self::$cache[$cache_id] = $response->body;
		}

		return $response->body;
	}

	/**
	 * Get record detail with given id for actual context
	 *
	 * @param int   $id     ID of Redmine entity
	 * @param array $params URL parameters for extra features - since 1.0.4
	 *
	 * @returns EasyRedmineXmlElement|false
	 */
	public function get($id, $params = array())
	{
		$id       = (int) $id;
		$cache_id = '';

		if ($this->cache_detail)
		{
			$cache_id = get_class($this) . '.' . __FUNCTION__ . '.' . $id . '.' . JArrayHelper::toString($params);

			if (isset(self::$cache[$cache_id]) and !empty(self::$cache[$cache_id]))
			{
				return self::$cache[$cache_id];
			}
		}

		if (!$id)
		{
			$this->setErrors('Missing ID to get');
			$this->_writeLog('Get for context ' . $this->context . '/' . $this->context_one . '" failed: ' . $this->getError(), JLog::ERROR);

			return false;
		}

		$response = $this->_sendRequest('/' . $this->context . '/' . $id . '.xml', 'get', null, $params);

		if ($response->code != 200)
		{
			if (isset($response->body->error))
			{
				$this->setErrors((array) $response->body->error);
			}

			$this->_writeLog('Get for context "' . $this->context . '/' . $this->context_one . '" failed: ' . implode($this->getErrors()), JLog::ERROR);

			return false;
		}

		if ($this->cache_detail)
		{
			self::$cache[$cache_id] = $response->body;
		}

		return $response->body;
	}

	/**
	 * Delete record with given id for actual context
	 *
	 * @param int         $id       ID of Redmine entity
	 * @param object|null $response Reference to rest api response object
	 *
	 * @returns boolean
	 */
	public function delete($id, &$response = null)
	{
		$id           = (int) $id;
		$this->errors = array();

		if (!$id)
		{
			$this->setErrors('Missing ID to delete');
			$this->_writeLog('Delete for context ' . $this->context . '/' . $this->context_one . '" failed: ' . $this->getError(), JLog::ERROR);

			return false;
		}

		$response = $this->_sendRequest('/' . $this->context . '/' . $id . '.xml', 'delete');

		if ($response->code != 200)
		{
			if (isset($response->body->error))
			{
				$this->setErrors((array) $response->body->error);
			}

			$this->_writeLog('Delete for context "' . $this->context . '/' . $this->context_one . '" failed: ' . implode($this->getErrors()), JLog::ERROR);

			return false;
		}

		return true;
	}

	/**
	 * @param boolean $cache_detail
	 */
	public function setCacheDetail($cache_detail)
	{
		$this->cache_detail = $cache_detail;
	}

	/**
	 * @param boolean $cache_list
	 */
	public function setCacheList($cache_list)
	{
		$this->cache_list = $cache_list;
	}

	/**
	 * @param object $object Object to store as Redmine entity
	 */
	protected function _beforeStore(&$object)
	{
	}

	/**
	 * Insert/Update record for actual context (I/U depends on object id property)
	 *
	 * @param object      $object   Object to store as Redmine entity
	 * @param object|null $response Reference to rest api response object
	 *
	 * @returns boolean
	 */
	public function store(&$object, &$response = null)
	{
		$this->errors = array();

		if (!$this->validateStore($object))
		{
			$this->_writeLog('Store() - validation for context "' . $this->context . '/' . $this->context_one . '" failed: ' . implode($this->getErrors()), JLog::ERROR);

			return false;
		}

		$this->_beforeStore($object);

		$xml        = new EasyRedmineXMLElement('<' . $this->context_one . '/>');
		$properties = get_object_vars($object);

		foreach ($properties as $property_name => $property_value)
		{
			switch ($property_name)
			{
				case 'custom_fields':

					if (is_array($property_value) and !empty($property_value))
					{
						$xml_cf = $xml->addChild('custom_fields');
						$xml_cf->addAttribute('type', 'array');

						foreach ($property_value as $custom_field)
						{
							if (isset($custom_field['id']) and intval($custom_field['id']) and isset($custom_field['value']))
							{
								if (isset($custom_field['lookup']) and $custom_field['lookup'])
								{
									$cf = $xml_cf->addChild('custom_field');
									$cf->addAttribute('id', (int) $custom_field['id']);

									$v  = $cf->addChild('value');
									$v2 = $v->addChild('selected_value');
									$v3 = $v2->addChild((int) $custom_field['value']);

									$v3->addChild('id', (int) $custom_field['value']);
									$v3->addChild('display_name', $custom_field['display_name']);
								}
								else
								{
									$cf = $xml_cf->addChild('custom_field');
									$cf->addAttribute('id', (int) $custom_field['id']);

									if (is_array($custom_field['value']))
									{
										$v0 = $cf->addChild('value');
										$v0->addAttribute('type', 'array');

										foreach ($custom_field['value'] as $cf_val)
										{
											$v0->addChildCData('value', $cf_val);
										}
									}
									else
									{
										$cf->addChildCData('value', $custom_field['value']);
									}
								}
							}
						}
					}
					break;

				case 'uploads':

					if (is_array($property_value) and !empty($property_value))
					{
						$xml_up = $xml->addChild('uploads');
						$xml_up->addAttribute('type', 'array');

						foreach ($property_value as $upload)
						{
							if (isset($upload['filename']) and isset($upload['token']) and isset($upload['content_type']))
							{
								$up = $xml_up->addChild('upload');
								$up->addChild('token', $upload['token']);
								$up->addChild('filename', $upload['filename']);
								$up->addChild('content_type', $upload['content_type']);
							}
						}
					}
					break;

				case 'easy_crm_case_items':

					if (is_array($property_value) and !empty($property_value))
					{
						$xml_up = $xml->addChild('easy_crm_case_items_attributes');
						$xml_up->addAttribute('type', 'array');

						foreach ($property_value as $item)
						{
							$it = $xml_up->addChild('easy_crm_case_item');

							foreach ($item as $item_property_name => $item_property_value)
							{
								$it->addChildCData($item_property_name, $item_property_value);
							}
						}
					}
					break;

				default:

					if (is_array($property_value))
					{
						$xml_arr = $xml->addChild($property_name);
						$xml_arr->addAttribute('type', 'array');

						foreach ($property_value as $pn => $pv)
						{
							if (is_array($pv))
							{
								$a_key = array_keys($pv);
								$a_val = array_values($pv);
								$xml_arr->addChildCData($a_key[0], (string) $a_val[0]);
							}
							else
							{
								$xml_arr->addChildCData($pn, (string) $pv);
							}
						}
					}
					else
					{
						$xml->addChildCData($property_name, (string) $property_value);
					}

					break;
			}
		}

		if (isset($object->id) and (int) $object->id > 0)
		{
			$response = $this->_sendRequest('/' . $this->context . '/' . (int) $object->id . '.xml', 'put', $xml->asXML()); //edit/update

			if ($response->code != 200)
			{
				if (isset($response->body->error))
				{
					$this->setErrors((array) $response->body->error);
				}

				$this->_writeLog('Store() - update for context "' . $this->context . '/' . $this->context_one . '" failed: ' . implode($this->getErrors()), JLog::ERROR);

				return false;
			}
		}
		else
		{
			$response = $this->_sendRequest('/' . $this->context . '.xml', 'post', $xml->asXML()); //create

			if ($response->code != 201)
			{
				if (isset($response->body->error))
				{
					$this->setErrors((array) $response->body->error);
				}

				$this->_writeLog('Store() - insert for context "' . $this->context . '/' . $this->context_one . '" failed: ' . implode($this->getErrors()), JLog::ERROR);

				return false;
			}
		}

		$object->id = (int) $response->body->id ? (int) $response->body->id : $object->id;

		return true;
	}

	/**
	 * Returns boolean if the object is ok to store
	 *
	 * @param object|array $object
	 *
	 * @return boolean
	 */
	protected function validateStore($object)
	{
		$object     = (object) $object;
		$valid      = true;
		$errors     = array();
		$properties = get_object_vars($object);

		if (empty($properties))
		{
			$valid    = false;
			$errors[] = 'Empty object is not allowed to store';
		}

		$this->setErrors($errors);

		return $valid;
	}

	/**
	 * Uploads file contents
	 *
	 * @param $file_contents
	 *
	 * @return string|false Token of uploaded file or false on fail
	 */
	public function upload($file_contents)
	{
		if (empty($file_contents))
		{
			$this->setError('Nothing to upload');

			return false;
		}

		$response = $this->_sendRequest('/uploads.xml', 'upload', $file_contents);

		if ($response->code != 201 or !isset($response->body->token) or empty($response->body->token))
		{
			$this->setError('Upload failed');

			return false;
		}

		return (string) $response->body->token;
	}

	/**
	 * Wrapper for upload() by file path instead of its contents
	 *
	 * @param $file_path
	 *
	 * @see upload()
	 *
	 * @return string|false Token of uploaded file or false on fail
	 */
	public function uploadFile($file_path)
	{
		if (!file_exists($file_path))
		{
			$this->setError('File not found');

			return false;
		}

		if (($file_contents = file_get_contents($file_path)) === false)
		{
			$this->setError('File not readable');

			return false;
		}

		return $this->upload($file_contents);
	}

	/**
	 * Sends a http request using CURL
	 *
	 * @param string $path
	 * @param string $method
	 * @param string $xml
	 * @param array  $params
	 *
	 * @return StdClass
	 */
	protected function _sendRequest($path = '', $method = 'get', $xml = null, $params = array())
	{
		foreach ((array) $params as $pk => $pv)
		{
			unset($params[$pk]);
			$params[trim($pk)] = trim($pv);
		}

		$method = strtolower($method);
		$c      = curl_init();
		$uri    = JURI::getInstance($this->er_url);

		$uri->setPath($path);

		if (!empty($params))
		{
			$uri->setQuery($params);
		}
		else
		{
			$uri->setQuery(array());
		}

		$uri->setVar('key', $this->api_key);

		if ($this->lang_parameter != '' and $this->lang != '')
		{
			$uri->setVar($this->lang_parameter, $this->lang);
		}

		curl_setopt($c, CURLOPT_URL, $uri->toString());
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($c, CURLOPT_TIMEOUT, 120);

		if (!empty($xml) and $method != 'upload')
		{
			curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-type: text/xml'));
		}

		switch ($method)
		{
			case 'get':
				curl_setopt($c, CURLOPT_HTTPGET, 1);
				break;

			case 'post':
				curl_setopt($c, CURLOPT_POST, 1);
				curl_setopt($c, CURLOPT_POSTFIELDS, $xml);
				break;

			case 'upload':
				curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/octet-stream'));
				curl_setopt($c, CURLOPT_POST, 1);
				curl_setopt($c, CURLOPT_POSTFIELDS, $xml);
				break;

			case 'put':
				curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($c, CURLOPT_POSTFIELDS, $xml);
				break;

			case 'delete':
				curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'DELETE');
				curl_setopt($c, CURLOPT_POSTFIELDS, $xml);
				break;
		}

		$this->_writeLog('Request: ' . $uri->toString() . ' | ' . $method . ' | ' . $xml);

		$response = $this->_curl_exec($c);
		$r_code   = curl_getinfo($c, CURLINFO_HTTP_CODE);
		$con_type = curl_getinfo($c, CURLINFO_CONTENT_TYPE);
		$arr      = explode(';', $con_type, 2);
		$r_type   = reset($arr);

		curl_close($c);

		$this->_writeLog('Response: ' . $r_code . ' | ' . $r_type . ' | ' . $response);

		$return       = new StdClass();
		$return->code = $r_code;
		$return->type = $r_type;

		if ($return->code >= 400)
		{
			$this->setError('Error' . (isset($this->http_code_message[$return->code]) ? ': ' . $this->http_code_message[$return->code] : ' - response code: ' . $return->code));
		}

		if (trim($response) != '' and strpos($r_type, '/xml') !== false)
		{
			if (function_exists('mb_stripos'))
			{
				$xml_start = mb_stripos($response, '<?xml');
			}
			else
			{
				$xml_start = stripos($response, '<?xml');
			}

			if ($xml_start > 0)
			{
				if (function_exists('mb_substr'))
				{
					$response = mb_substr($response, $xml_start);
				}
				else
				{
					$response = substr($response, $xml_start);
				}
				$this->_writeLog('Cutting off beginning of response, xml start = ' . $xml_start, JLog::NOTICE);
			}

			if (($body = @simplexml_load_string($response, 'EasyRedmineXmlElement')) !== false)
			{
				$return->body = $body;
			}
			else
			{
				$this->_writeLog('Cannot parse response body using simplexml_load_string()', JLog::WARNING);
				$return->body = $response;
			}
		}
		else
		{
			$this->_writeLog('Response is empty or is not of */xml content-type', JLog::WARNING);
			$return->body = $response;
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
				$header = $res_arr[0];
				$data   = $res_arr[1];
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
	protected function _writeLog($message, $priority = JLog::INFO, $category = 'lib_easyredmine_api')
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
			JLog::addLogger(array('text_file' => 'lib_easyredmine_api.log.php'), JLog::ALL, 'lib_easyredmine_api');
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
//		if (trim($this->api_key) == '' or trim($this->er_url) == '')
//		{
//			return $this->_throwException('Missing argument(s) $er_url and/or $api_key in ' . get_class($this) . '::__construct()');
//		}

		if (!filter_var($this->er_url, FILTER_VALIDATE_URL, array(FILTER_FLAG_SCHEME_REQUIRED, FILTER_FLAG_HOST_REQUIRED)))
		{
			return $this->_throwException('Invalid argument $er_url in ' . get_class($this) . '::__construct()');
		}

		return true;
	}

	/**
	 * Check context property of actual instance
	 *
	 * @return boolean
	 */
	protected function _checkContext()
	{
		if ($this->context == '_default_' or trim($this->context) == '')
		{
			return $this->_throwException('Unsupported $context "' . $this->context . '" in ' . get_class($this));
		}

		if ($this->context_one == '_default_' or trim($this->context_one) == '')
		{
			return $this->_throwException('Unsupported $context_one "' . $this->context_one . '" in ' . get_class($this));
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