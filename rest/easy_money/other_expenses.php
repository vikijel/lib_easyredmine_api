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

jimport('easyredmine_api.rest.easy_money');

/**
 * @author     VikiJel
 * @package    Joomla
 */
class EasyRedmineRestApiEasyMoneyOtherExpenses extends EasyRedmineRestApiEasyMoney
{
	/** @var string $context */
	protected $context = 'easy_money_other_expenses';

	/** @var string $context_one */
	protected $context_one = 'easy_money_other_expense';
}