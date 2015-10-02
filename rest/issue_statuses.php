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
class EasyRedmineRestApiIssueStatuses extends EasyRedmineRestApi
{
	/** @var string $context */
	protected $context = 'issue_statuses';

	/** @var string $context_one */
	protected $context_one = 'issue_status';
}