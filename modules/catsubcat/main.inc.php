<?php

/**
 * Главный файл модуля текстовых страниц
 *
 * @author DoTJ
 * @version 0.1
 * @since 25.03.2008
 */

/* Защита от взлома */
if (!defined("KS_ENGINE"))
	die("Hacking attempt!");

/* Глобальные переменные */
global $USER, $KS_MODULES, $KS_IND_matches, $KS_IND_dir,  $CCatsubcat, $global_template, $smarty;

/* Идентификатор модуля */
$module = "catsubcat";

/* Проверка прав доступа пользователя */
if ($USER->GetLevel($module) == 10)
	throw new CAccessError("SYSTEM_NOT_ACCESS_MODULE");

/* Установка директории с плагинами модуля для Смарти */
$smarty->plugins_dir[] = MODULES_DIR . "/" . $module . "/widgets/";

/* Чтение конфигурации модуля */
$module_config = $KS_MODULES->GetConfigArray($module);

/* Если модуль подключается из шаблона, то не обращаем внимание на УРЛ */
if ($KS_IND_matches[3] == "index")
	$KS_IND_matches[2] = "";

/* Работаем как модуль, значит надо провести полную проверку переданного пути
	* на правильность и на права доступа, если что-то не так, лучше отдать ошибку */

	/* Путь к корню модуля */
$root_path = $this->GetSitePath($module);

if ($root_path != "/")
{
	/* Добавляем элемент навигационной цепочки */
	if ($this->GetConfigVar("catsubcat", "show_nav_chain",'1'))
		CNNavChain::get_instance()->Add( $this->arModules[$module]['name'],$root_path);

	$sUrl = $root_path;
	$iBase = 2;
}
else
{
	/* Модуль является модулем по умолчанию */
	$sUrl = "/";
	$iBase = 1;
}

/* Устанавливать заголовок страницы или нет */
$module_parameters['setPageTitle'] = $this->GetConfigVar($module, "set_title",1) ==1 ? "Y" : "N";

/* Родительский раздел */
$module_parameters['parent_id'] = 0;

/* Формирование навигационной цепочки */
if (count($KS_IND_dir) > $iBase)
{
	/* Объект для работы с категориями */
	$obCategory = new CCategory();
	$arFilter = array('parent_id' => 0);

	for ($i = $iBase; $i < count($KS_IND_dir); $i++)
	{
		$arFilter['text_ident'] = $KS_IND_dir[$i];
		$arCategory = $obCategory->GetRecord($arFilter);
		if($arCategory)
		{
			$arFilter['parent_id'] = $arCategory['id'];
			$sUrl .= $arCategory['text_ident'] . "/";
			if ($module_config['show_nav_chain'] == "1")
			{
				if ($this->IsActive("navigation"))
					CNNavChain::get_instance()->Add( $arCategory['title'],$sUrl);
			}

			/* Проверка прав доступа */
			if ($access_level > 8)
				if(!in_array($arCategory['access_view'], $arUserGroups))
					throw new CAccessError("CATSUBCAT_NOT_ACCESS_SECTION");
			$module_parameters['parent_id'] = $arCategory['id'];
		}
		else
		{
			throw new CHTTPError("SYSTEM_SECTION_NOT_FOUND", 404);
		}
	}
}
/* Определение виджета для подключения в качестве контента страницы */
if (strlen($KS_IND_matches[2]) > 0)
{
	/* Элемент каталога */
	$module_parameters['text_ident'] = $KS_IND_matches[3];
	$module_parameters['setPageTitle']=$KS_MODULES->GetConfigVar('catsubcat','set_title',1)==1?'Y':'N';
	$res=$this->IncludeWidget($module,'CatElement',$module_parameters);
}
elseif(array_key_exists('type',$_GET) && $_GET['type'] == "rss")
{
	/* RSS */
	$module_parameters['tpl'] = "RSS";
	$module_parameters['sort_by'] = $KS_MODULES->GetConfigVar($module, "sort_by",'id');
	$module_parameters['sort_dir'] = $KS_MODULES->GetConfigVar($module, "sort_dir",'asc');
	$module_parameters['announces_count'] = $KS_MODULES->GetConfigVar($module, "count",'10');
	$module_parameters['parent_id'] = $module_parameters['parent_id'];
	$module_parameters['select_from_children'] = $KS_MODULES->GetConfigVar($module, "select_from_children",'y');

	$res=$this->IncludeWidget($module,'CatAnnounce',$module_parameters);
	$output['include_global_template'] = 0;
}
else
{
	/* Категория */
	$module_parameters['ID'] = $module_parameters['parent_id'];
	$res=$this->IncludeWidget($module,'CatCategory',$module_parameters);
}
/* Возвращаем результат работы */
$output['main_content'] = $res;

