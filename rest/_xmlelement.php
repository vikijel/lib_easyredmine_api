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

/**
 * @author     VikiJel
 * @package    Joomla
 */
class EasyRedmineXmlElement extends SimpleXMLElement
{
	/**
	 * Create a child with CDATA value
	 *
	 * @param string $name       The name of the child element to add.
	 * @param string $cdata_text The CDATA value of the child element.
	 * @param bool   $force      True to force CDATA, otherwise simple values will not be cdata
	 */
	public function addChildCData($name, $cdata_text, $force = false)
	{
		if (!$force and (ctype_digit((string) $cdata_text) or ctype_alpha((string) $cdata_text)) or trim((string) $cdata_text) == '')
		{
			$this->addChild($name, (string) $cdata_text);
		}
		else
		{
			$child = $this->addChild($name);
			$child->addCData((string) $cdata_text);
		}
	}

	/**
	 * Add SimpleXMLElement code into a SimpleXMLElement
	 *
	 * @param SimpleXMLElement $append
	 */
	public function appendXML($append)
	{
		if ($append)
		{
			if (strlen(trim((string) $append)) == 0)
			{
				$xml = $this->addChild($append->getName());

				foreach ($append->children() as $child)
				{
					$xml->appendXML($child);
				}
			}
			else
			{
				$xml = $this->addChild($append->getName(), (string) $append);
			}

			foreach ($append->attributes() as $n => $v)
			{
				$xml->addAttribute($n, $v);
			}
		}
	}

	/**
	 * Add CDATA text in a node
	 *
	 * @param string $cdata_text The CDATA value  to add
	 */
	private function addCData($cdata_text)
	{
		$node = dom_import_simplexml($this);
		$no   = $node->ownerDocument;

		$node->appendChild($no->createCDATASection($cdata_text));
	}
}