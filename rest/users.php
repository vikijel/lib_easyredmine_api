<?php
/**
 * @author         EasyJoomla.org
 * @copyright      ©2013 - 2015 EasyJoomla.org
 * @license        http://opensource.org/licenses/LGPL-3.0 LGPL-3.0
 * @package        Joomla
 * @subauthor      VikiJel
 * @version        @see easyredmine_api.xml
 */
defined('_JEXEC') or die('Direct access is not allowed!');

jimport('easyredmine_api.rest');

/**
 * @author     VikiJel
 * @package    Joomla
 */
class EasyRedmineRestApiUsers extends EasyRedmineRestApi
{
	/** @var string $context */
	protected $context = 'users';

	/** @var string $context_one */
	protected $context_one = 'user';

	protected function _beforeStore(&$object)
	{
		if(isset($object->firstname))
		{
			$object->firstname = function_exists('mb_substr') ? mb_substr($object->firstname, 0, 30) : substr($object->firstname, 0, 30);
		}

		if(isset($object->lastname))
		{
			$object->lastname = function_exists('mb_substr') ? mb_substr($object->lastname, 0, 30) : substr($object->lastname, 0, 30);
		}
	}

	public function store(&$object, &$response = null)
	{
		$result = parent::store($object, $response);

		if ($result and isset($response->body->api_key))
		{
			$object->api_key = (string) $response->body->api_key;
		}

		return $result;
	}
}