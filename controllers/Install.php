<?php

namespace Rhymix\Modules\SitemapLite\Controllers;

use ModuleController;
use ModuleModel;

class Install extends Base
{
	public function checkUpdate()
	{
		if (ModuleModel::getTrigger('moduleObject.proc', 'sitemaplite', 'model', 'triggerUpdateSitemapXML', 'after'))
		{
			return true;
		}
	}

	public function moduleUpdate()
	{
		if (ModuleModel::getTrigger('moduleObject.proc', 'sitemaplite', 'model', 'triggerUpdateSitemapXML', 'after'))
		{
			$oModuleController = ModuleController::getInstance();
			$oModuleController->deleteTrigger('moduleObject.proc', 'sitemaplite', 'model', 'triggerUpdateSitemapXML', 'after');
		}
	}
}
