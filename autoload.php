<?php
/**
 * @author         EasyJoomla.org, VikiJel <vikijel@gmail.com>
 * @copyright      ©2013-2014 EasyJoomla.org
 * @license        http://opensource.org/licenses/LGPL-3.0 LGPL-3.0
 * @package        Joomla
 * @version        @see easyredmine_api.xml
 */
defined('_JEXEC') or die('Direct access is not allowed!');

JLoader::register('EasyRedmineRestApi', JPATH_LIBRARIES.'/easyredmine_api/rest.php');
JLoader::discover('EasyRedmineRestApi', JPATH_LIBRARIES.'/easyredmine_api/rest', true, true);
JLoader::register('EasyRedmineHttpApi', JPATH_LIBRARIES.'/easyredmine_api/http.php');
