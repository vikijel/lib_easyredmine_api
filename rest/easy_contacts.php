<?php
/**
 * @author         EasyJoomla.org
 * @copyright      ©2013 EasyJoomla.org
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
class EasyRedmineRestApiEasyContacts extends EasyRedmineRestApi
{
	/** @var string $context */
	protected $context = 'easy_contacts';

	/** @var string $context_one */
	protected $context_one = 'easy_contact';

	/**
	 * @param Mixed <object|array> $object
	 *
	 * @return boolean
	 */
	protected function validateStore($object)
	{
		$object   = (object) $object;
		$valid    = true;
		$errors   = array();
		$required = array('firstname', 'lastname');

		foreach ($required as $property)
		{
			if (!isset($object->$property) or trim($object->$property) == '')
			{
				$errors[] = "Missing property '$property' or its value is empty";
				$valid    = false;
			}
		}

		$this->setErrors($errors);

		return $valid;
	}
}