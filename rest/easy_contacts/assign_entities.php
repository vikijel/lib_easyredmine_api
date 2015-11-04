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
class EasyRedmineRestApiEasyContactsAssignEntities extends EasyRedmineRestApi
{
	/** @var string $context */
	protected $context = 'easy_contacts/assign_entities';

	/** @var string $context_one */
	protected $context_one = 'easy_contact_ids';

	public function store(&$object, &$response = null)
	{
		$params = array(
			'entity_type' => $object->entity_type,
			'entity_ids'  => $object->entity_ids
		);

		$xml = new EasyRedmineXMLElement('<' . $this->context_one . '/>');
		$xml->addAttribute('type', 'array');

		$object->ids = (array)$object->ids;

		foreach ($object->ids as $id)
		{
			$xml->addChild('id', $id);
		}

		$response = $this->_sendRequest('/' . $this->context . '.xml', 'post', $xml->asXML(), $params); //create

		if ($response->code != 200 and $response->code != 201)
		{
			if (isset($response->body->error))
			{
				$this->setErrors((array) $response->body->error);
			}

			$this->_writeLog('Store() - insert for context "' . $this->context . '/' . $this->context_one . '" failed: ' . implode($this->getErrors()), JLog::ERROR);

			return false;
		}

		return true;
	}
}