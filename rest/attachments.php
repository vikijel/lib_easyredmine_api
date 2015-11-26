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
 * @author     VikiJel
 * @package    Joomla
 */
class EasyRedmineRestApiAttachments extends EasyRedmineRestApi
{
	/** @var string $context */
	protected $context = 'attachments';

	/** @var string $context_one */
	protected $context_one = 'attachment';

	/**
	 * Downloads file contents
	 *
	 * @param int $attachment_id
	 *
	 * @return string|false Contents of downloaded file
	 */
	public function download($attachment_id)
	{
		$attachment_id = (int)$attachment_id;

		if (!$attachment_id)
		{
			$this->setError('Missing $attachment_id to download');

			return false;
		}

		$response = $this->_sendRequest('/attachments/download/'.$attachment_id, 'get');

		if ($response->code != 200 or !isset($response->body) or empty($response->body))
		{
			$this->setError('Download failed');

			return false;
		}

		return (string) $response->body;
	}
}