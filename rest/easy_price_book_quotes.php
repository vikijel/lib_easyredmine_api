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
 * @since 2.6.0
 */
class EasyRedmineRestApiEasyPriceBookQuotes extends EasyRedmineRestApi
{
	/** @var string $context */
	protected $context = 'easy_price_book_quotes';

	/** @var string $context_one */
	protected $context_one = 'easy_price_book_quote';
}