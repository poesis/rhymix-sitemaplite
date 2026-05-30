<?php

namespace Rhymix\Modules\SitemapLite\Controllers;

class Serve extends Base
{
	/**
	 * Serve sitemap.xml for the correct domain.
	 */
	public function dispSitemapliteServeSitemapXml()
	{
		// Check if the sitemap.xml file exists for the current domain.
		$config = self::getConfig();
		$xml_path = self::getSitemapXmlPath($config->sitemap_file_path, $_SERVER['HTTP_HOST'] ?? null);
		if (!file_exists($xml_path))
		{
			header('HTTP/1.1 404 Not Found');
			echo 'Sitemap XML file not found.';
			exit();
		}

		// Clear the output buffer.
		while (ob_get_level() > 0)
		{
			ob_end_clean();
		}

		// Read the serve the sitemap.xml file.
		header('Content-Type: text/xml; charset=UTF-8');
		header('Content-Length: ' . filesize($xml_path));
		readfile($xml_path);
		exit();
	}
}
