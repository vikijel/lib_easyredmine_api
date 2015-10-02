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

jimport('easyredmine_api.rest.enumerations');

/**
 * @author     VikiJel
 * @package    Joomla
 */
class EasyRedmineRestApiEnumerationsIssuePriorities extends EasyRedmineRestApiEnumerations
{
	/** @var string $context */
	protected $context = 'enumerations/issue_priorities';

	/** @var string $context_one */
	protected $context_one = 'issue_priority';
}