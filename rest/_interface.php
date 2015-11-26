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
 * Interface for all api classes, implemented by general rest api class EasyRedmineRestApi and its subclasses
 *
 * Don't create instances of EasyRedmineRestApi directly, always use its subclasses such as EasyRedmineRestApiIssues
 *
 * @author     VikiJel <vikijel@gmail.com>
 * @package    Joomla
 * @see        EasyRedmineRestApi
 */
interface EasyRedmineRestApiInterface
{
	/**
	 * Initialize API
	 *
	 * @param string  $er_url
	 * @param string  $api_key
	 * @param boolean $write_log
	 */
	public function __construct($er_url, $api_key, $write_log = true);

	/**
	 * Get list of records for actual context, supports filtering by url params
	 *
	 * @param array $filters URL parameters to filter results
	 *
	 * @returns EasyRedmineXmlElement|false
	 */
	public function getList($filters = array());

	/**
	 * Get record detail with given id for actual context
	 *
	 * @param int $id ID of Redmine entity
	 *
	 * @returns EasyRedmineXmlElement|false
	 */
	public function get($id);

	/**
	 * Delete record with given id for actual context
	 *
	 * @param int         $id       ID of Redmine entity
	 * @param object|null $response Reference to rest api response object
	 *
	 * @returns boolean
	 */
	public function delete($id, &$response = null);

	/**
	 * Insert/Update record for actual context (Insert/Update depends on $object->id property)
	 *
	 * @param object      $object   Object to store as Redmine entity
	 * @param object|null $response Reference to rest api response object
	 *
	 * @returns boolean
	 */
	public function store(&$object, &$response = null);

	/**
	 * Get last error caused by previous operation
	 *
	 * @returns string last error in errors array
	 */
	public function getError();

	/**
	 * Get array of errors caused by previous operation
	 *
	 * @returns array of strings containing all error messages
	 */
	public function getErrors();

	/**
	 * Add error message to array of errors
	 *
	 * @param string $message
	 *
	 * @return boolean
	 */
	public function setError($message);

	/**
	 * Set array of errors
	 *
	 * @param array $array
	 *
	 * @return boolean
	 */
	public function setErrors($array);
}