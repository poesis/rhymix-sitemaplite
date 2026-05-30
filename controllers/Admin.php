<?php

namespace Rhymix\Modules\SitemapLite\Controllers;

use Rhymix\Framework\Queue;
use Rhymix\Modules\SitemapLite\Models\Sitemap as SitemapModel;
use BaseObject;
use Context;
use MenuAdminModel;
use ModuleController;
use ModuleModel;

class Admin extends Base
{
	/**
	 * Display admin config page
	 */
	public function dispSitemapliteAdminConfig()
	{
		// Get module config.
		$config = self::getConfig();

		// Automatically select the index menu if running this module for the first time.
		$index_menu_srl = $this->_getIndexMenuSrl();
		if (!isset($config->menu_srls) || !is_array($config->menu_srls))
		{
			$config->menu_srls = [$index_menu_srl];
		}

		// Automatically select the sitemap file path.
		if (!isset($config->sitemap_file_path))
		{
			$config->sitemap_file_path = 'root';
		}

		// Initialize the search engine list.
		if (!isset($config->ping_search_engines))
		{
			$config->ping_search_engines = [];
		}

		// Initialize the document source module list.
		if (!isset($config->document_source_modules))
		{
			$config->document_source_modules = [];
		}

		// Initialize the additional URL list.
		if (!isset($config->additional_urls))
		{
			$config->additional_urls = [];
		}

		// Get the list of configured domains.
		$domains = ModuleModel::getAllDomains(100)->data ?? [];
		if (!isset($config->domains))
		{
			$config->domains = [];
		}
		foreach ($domains as $domain)
		{
			if (!isset($config->domains[$domain->domain_srl]))
			{
				$config->domains[$domain->domain_srl] = (object)[
					'menu_srls' => $config->menu_srls ?? [],
					'only_public_menus' => $config->only_public_menus ?? true,
					'document_count' => $config->document_count ?? 100,
					'document_source_modules' => $config->document_source_modules ?? [],
					'document_order' => $config->document_order ?? 'recent',
					'additional_urls' => $config->additional_urls ?? [],
				];
			}
		}

		// Juggle other settings.
		if (!isset($config->refresh_interval))
		{
			$config->refresh_interval = $config->document_interval ?? 'daily';
		}

		Context::set('config', $config);
		Context::set('domains', $domains);
		Context::set('sitemaplite_url_root', self::getSitemapXmlUrl('root'));
		Context::set('sitemaplite_path_root', self::getSitemapXmlPath('root'));
		Context::set('sitemaplite_path_root_writable', $this->isWritable(self::getSitemapXmlPath('root')));
		Context::set('sitemaplite_url_sub', self::getSitemapXmlUrl('sub'));
		Context::set('sitemaplite_path_sub', self::getSitemapXmlPath('sub'));
		Context::set('sitemaplite_path_sub_writable', $this->isWritable(self::getSitemapXmlPath('sub')));
		Context::set('sitemaplite_url_files', self::getSitemapXmlUrl('files'));
		Context::set('sitemaplite_path_files', self::getSitemapXmlPath('files'));
		Context::set('sitemaplite_path_files_writable', $this->isWritable(self::getSitemapXmlPath('files')));
		Context::set('sitemaplite_url_domains', self::getSitemapXmlUrl('domains'));
		Context::set('sitemaplite_path_domains', self::getSitemapXmlPath('domains'));
		Context::set('sitemaplite_path_domains_writable', $this->isWritable(self::getSitemapXmlPath('domains')));
		Context::set('sitemaplite_index_menu_srl', $index_menu_srl);
		Context::set('sitemaplite_module_list', $this->_getModuleList());
		Context::set('sitemaplite_menus', MenuAdminModel::getInstance()->getMenus());

		$this->setTemplatePath($this->module_path . 'views');
		$this->setTemplateFile('config');
	}

	/**
	 * Save admin config
	 */
	public function procSitemapliteAdminInsertConfig()
	{
		// Get current config and request vars
		$config = $this->getConfig();
		$vars = Context::getRequestVars();

		// Sitemap path
		$file_path = $vars->sitemaplite_file_path;
		$config->sitemap_file_path = in_array($file_path, array('root', 'sub', 'files', 'domains')) ? $file_path : 'root';

		// Load per-domain config
		$config->domains = [];
		$domains = ModuleModel::getAllDomains(100)->data ?? [];
		foreach ($domains as $domain)
		{
			$config->domains[$domain->domain_srl] = new \stdClass;

			$menu_srls = $vars->sitemaplite_menu_srls[$domain->domain_srl] ?? [];
			$menu_srls = is_array($menu_srls) ? array_values($menu_srls) : [];
			$config->domains[$domain->domain_srl]->menu_srls = array_unique(array_map('intval', $menu_srls));

			$only_public_menus = $vars->sitemaplite_only_public_menus[$domain->domain_srl] ?? 'Y';
			$config->domains[$domain->domain_srl]->only_public_menus = ($only_public_menus === 'Y') ? true : false;

			$document_source_modules = $vars->sitemaplite_document_source_modules[$domain->domain_srl] ?? [];
			$document_source_modules = is_array($document_source_modules) ? array_values($document_source_modules) : [];
			$config->domains[$domain->domain_srl]->document_source_modules = array_unique(array_map('intval', $document_source_modules));

			$config->domains[$domain->domain_srl]->document_count = intval($vars->sitemaplite_document_count[$domain->domain_srl] ?? 100);
			if ($config->domains[$domain->domain_srl]->document_count < 0)
			{
				$config->domains[$domain->domain_srl]->document_count = 0;
			}
			if ($config->domains[$domain->domain_srl]->document_count > 48000)
			{
				$config->domains[$domain->domain_srl]->document_count = 48000;
			}

			$config->domains[$domain->domain_srl]->document_order = $vars->sitemaplite_document_order[$domain->domain_srl] ?? 'recent';
			if (!in_array($config->domains[$domain->domain_srl]->document_order, ['recent', 'view', 'vote']))
			{
				$config->domains[$domain->domain_srl]->document_order = 'recent';
			}

			$additional_urls = [];
			$additional_urls = explode("\n", $vars->sitemaplite_additional_urls[$domain->domain_srl] ?? '');
			$config->domains[$domain->domain_srl]->additional_urls = [];
			foreach ($additional_urls as $additional_url)
			{
				$additional_url = trim($additional_url);
				if ($additional_url)
				{
					$config->domains[$domain->domain_srl]->additional_urls[] = $additional_url;
				}
			}
		}

		// Refresh settings
		$config->refresh_interval = $vars->sitemaplite_refresh_interval;
		if (!in_array($config->refresh_interval, array('always', 'hourly', 'daily', 'weekly', 'monthly', 'manual')))
		{
			$config->refresh_interval = 'daily';
		}

		// Async settings
		$config->use_async = ($vars->sitemaplite_use_async === 'Y') ? 'Y' : 'N';

		// Search engine ping settings
		$ping_search_engines = $vars->sitemaplite_ping_search_engines ?? [];
		$config->ping_search_engines = is_array($ping_search_engines) ? $ping_search_engines : [];

		// Delete old config items
		unset($config->menu_srls);
		unset($config->only_public_menus);
		unset($config->document_source_modules);
		unset($config->document_count);
		unset($config->document_order);
		unset($config->additional_urls);
		unset($config->document_interval);

		// Save new config
		$oModuleController = ModuleController::getInstance();
		$output = $oModuleController->insertModuleConfig('sitemaplite', $config);

		// Try to write new sitemap.xml file
		if ($output->toBool())
		{
			if (($config->use_async ?? 'N') === 'Y' && config('queue.enabled'))
			{
				Queue::addTask(SitemapModel::class . '::updateSitemapStatic', new \stdClass());
			}
			else
			{
				$write_success = SitemapModel::writeSitemapXml($config);
				if ($write_success)
				{
					$this->setMessage('success_registed');
				}
				else
				{
					return new BaseObject(-1, 'msg_sitemaplite_failed_to_write_xml_file');
				}
			}
		}
		else
		{
			return $output;
		}

		// Redirect back to config page
		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSitemapliteAdminConfig'));
		}
	}

	/**
	 * Get menu_srl of index module
	 */
	protected function _getIndexMenuSrl()
	{
		$start_module = ModuleModel::getSiteInfo(0);
		$output = executeQuery('menu.getMenuItemByUrl', [
			'url' => $start_module->mid,
			'site_srl' => 0,
		]);
		if (!$output->toBool())
		{
			return false;
		}
		else
		{
			return $output->data->menu_srl;
		}
	}

	/**
	 * Get the list of modules to extract documents from
	 */
	protected function _getModuleList()
	{
		$args = new \stdClass;
		$args->module = ['board', 'bodex', 'beluxe'];
		$output = executeQueryArray('sitemaplite.getModuleList', $args);
		if ($output->data)
		{
			$result = [];
			foreach ($output->data as $module)
			{
				$result[intval($module->module_srl)] = $module->browser_title;
			}
			return $result;
		}
		else
		{
			return [];
		}
	}
}
