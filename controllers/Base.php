<?php

namespace Rhymix\Modules\SitemapLite\Controllers;

use Context;
use ModuleModel;
use ModuleObject;

class Base extends ModuleObject
{
	/**
	 * Get the configuration of the current module.
	 *
	 * @return object
	 */
	public static function getConfig(): object
	{
		$config = ModuleModel::getModuleConfig('sitemaplite');
		if (!$config)
		{
			$config = new \stdClass;
		}

		return $config;
	}

	/**
	 * Get the sitemap.xml server-side path.
	 *
	 * @param ?string $type
	 * @param ?string $domain
	 * @return string
	 */
	public static function getSitemapXmlPath(?string $type = null, ?string $domain = null): string
	{
		if (!$type)
		{
			$type = isset(self::getConfig()->sitemap_file_path) ? self::getConfig()->sitemap_file_path : 'root';
		}

		if ($type === 'root')
		{
			return str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '\\/')) . '/sitemap.xml';
		}
		elseif ($type === 'sub')
		{
			return str_replace('\\', '/', rtrim(\RX_BASEDIR, '\\/')) . '/sitemap.xml';
		}
		elseif ($type === 'files')
		{
			return str_replace('\\', '/', rtrim(\RX_BASEDIR, '\\/')) . '/files/sitemaplite/sitemap.xml';
		}
		elseif ($type === 'domains')
		{
			$domain = $domain ?: parse_url(Context::getDefaultUrl(), \PHP_URL_HOST);
			return str_replace('\\', '/', rtrim(\RX_BASEDIR, '\\/')) . '/files/sitemaplite/' . $domain . '/sitemap.xml';
		}
		else
		{
			return '';
		}
	}

	/**
	 * Get the sitemap.xml file URL.
	 *
	 * @param ?string $type
	 * @param ?string $domain
	 * @return string
	 */
	public static function getSitemapXmlUrl(?string $type = null, ?string $domain = null): string
	{
		if (!$type)
		{
			$type = isset(self::getConfig()->sitemap_file_path) ? self::getConfig()->sitemap_file_path : 'root';
		}

		if ($type === 'root')
		{
			$dui = parse_url(Context::getDefaultUrl());
			return $dui['scheme'] . '://' . $dui['host'] . (!empty($dui['port']) ? (':' . $dui['port']) : '') . '/sitemap.xml';
		}
		elseif ($type === 'sub')
		{
			return rtrim(Context::getDefaultUrl(), '\\/') . '/sitemap.xml';
		}
		elseif ($type === 'files')
		{
			return rtrim(Context::getDefaultUrl(), '\\/') . '/files/sitemaplite/sitemap.xml';
		}
		elseif ($type === 'domains')
		{
			$domain = $domain ?: parse_url(Context::getDefaultUrl(), \PHP_URL_HOST);
			return rtrim(Context::getDefaultUrl(), '\\/') . '/files/sitemaplite/' . $domain . '/sitemap.xml';
		}
		else
		{
			return '';
		}
	}

	/**
	 * Check if a file is writable.
	 *
	 * @param string $filename
	 * @return bool
	 */
	public static function isWritable(string $filename): bool
	{
		if (@file_exists($filename) && @is_writable($filename))
		{
			return true;
		}
		elseif (!@file_exists($filename) && @is_writable(dirname($filename)))
		{
			return true;
		}
		elseif (!@file_exists($filename) && !@file_exists(dirname($filename)) && @mkdir(dirname($filename), 0755, true))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}
