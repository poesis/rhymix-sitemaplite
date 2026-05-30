<?php

/**
 * @file sitemaplite.model.php
 * @author Kijin Sung <kijin@kijinsung.com>
 * @license GPLv2 or Later <https://www.gnu.org/licenses/gpl-2.0.html>
 * @brief Sitemap Lite Model
 */
class SitemapLiteModel extends SitemapLite
{
	/**
	 * Trigger called when sitemap.xml may need to be updated
	 */
	public function triggerUpdateSitemapXML($trigger_obj)
	{
		$config = $this->getConfig();

		$menu_target_actions = array(
			'procMenuAdminInsert' => true,
			'procMenuAdminUpdate' => true,
			'procMenuAdminDelete' => true,
			'procMenuAdminInsertItem' => true,
			'procMenuAdminUpdateItem' => true,
			'procMenuAdminDeleteItem' => true,
			'procDocumentInsertCategory' => true,
			'procDocumentDeleteCategory' => true,
		);

		$document_target_actions = array(
			'/^proc\w+(?:Insert|Update|Delete|Vote)Document$/' => true,
		);

		// Update sitemap.xml if the menu has changed
		if (isset($menu_target_actions[$trigger_obj->act]))
		{
			$this->updateSitemap($config);
			return;
		}

		// Update sitemap.xml if documents have changed and the interval has passed
		foreach ($document_target_actions as $regexp => $true)
		{
			if (preg_match($regexp, $trigger_obj->act))
			{
				if (!empty($config->document_count) && !empty($config->document_source_modules))
				{
					switch ($config->refresh_interval ?? $config->document_interval)
					{
						case 'always': $timediff = 3; break;
						case 'hourly': $timediff = 3600; break;
						case 'daily': $timediff = 86400; break;
						case 'weekly': $timediff = 86400 * 7; break;
						case 'monthly': $timediff = 86400 * 30; break;
						case 'manual': $timediff = -1; break;
						default: $timediff = 86400; break;
					}

					$xml_path = $this->getSitemapXmlPath($config->sitemap_file_path);
					if ($timediff > 0 && filemtime($xml_path) < time() - $timediff)
					{
						@touch($xml_path);
						$this->updateSitemap($config);
					}
				}
			}
		}
	}

	/**
	 * Update wrapper #1
	 */
	public function updateSitemap($config = null)
	{
		if (($config->use_async ?? 'N') === 'Y' && config('queue.enabled'))
		{
			Rhymix\Framework\Queue::addTask(self::class . '::updateSitemapStatic', new stdClass());
		}
		else
		{
			getAdminController('sitemaplite')->writeSitemapXml($config);
		}
	}

	/**
	 * Update wrapper #2
	 */
	public static function updateSitemapStatic()
	{
		getAdminController('sitemaplite')->writeSitemapXml();
	}
}
