<?php
/**
 * @author         EasyJoomla.org
 * @copyright      ©2015 EasyJoomla.org
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
 * @since      1.0.0
 */
class EasyRedmineRestApiEasyCrmCases extends EasyRedmineRestApi
{
	/** @var string $context */
	protected $context = 'easy_crm_cases';

	/** @var string $context_one */
	protected $context_one = 'easy_crm_case';
}