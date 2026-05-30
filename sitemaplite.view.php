<?php

/**
 * @file sitemaplite.model.php
 * @author Kijin Sung <kijin@kijinsung.com>
 * @license GPLv2 or Later <https://www.gnu.org/licenses/gpl-2.0.html>
 * @brief Sitemap Lite View
 */
class SitemapLiteView extends SitemapLite
{
	/**
	 * Serve sitemap.xml for the correct domain.
	 */
	public function dispSitemapliteServeSitemapXml()
	{
		$config = $this->getConfig();
		$xml_path = $this->getSitemapXmlPath($config->sitemap_file_path, $_SERVER['HTTP_HOST'] ?? null);
		if (!file_exists($xml_path))
		{
			header('HTTP/1.1 404 Not Found');
			echo 'Sitemap XML file not found.';
			exit();
		}

		while (ob_get_level() > 0)
		{
			ob_end_clean();
		}

		header('Content-Type: text/xml; charset=UTF-8');
		readfile($xml_path);
		exit();
	}
}
