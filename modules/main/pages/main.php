<?php
/**
 * Файл отвечает за генерацию древовидной структуры лайт-версии сайта
 *
 * @filesource main.php
 * @author blade39 <blade39@kolosstudio.ru>, north-e <pushkov@kolosstudio.ru>
 * @since 07.04.2009
 * @version 1.1
 * @see lite.php
 */

/* Обязательно вставляем во все файлы для защиты от взлома */
if (!defined("KS_ENGINE"))
	die("Hacking attempt!");

/* Отображение вложенных элементов */
if(array_key_exists('mode',$_GET) && $_GET["mode"] == "ajax" && array_key_exists('q',$_GET) && $_GET["q"] != "")
{
	/* Получаем строку запроса ajax */
	$sReq = base64_decode($_GET["q"]);

	/* Массив запроса */
	$arParams = explode("|", $sReq);

	/* Формуруем массив переменных, переданных через запрос */
	$arRow = array();
	foreach($arParams as $item)
	{
		$arRos = explode("=", $item);
		$arRow[$arRos[0]] = $arRos[1];
	}

	if($arRow["module"] == $_GET["m"])
	{
		/* Читаем запись о модуле из базы */
		if($arModule = $KS_MODULES->GetRecord(array("directory" => $arRow["module"])))
		{
			$arRow = array_merge($arRow, $arModule);
			/* Получаем древовидную структуру модуля */
			$arTree = array();
			$module_tree_file = MODULES_DIR . "/" . $arRow["directory"] . "/.tree.php";
			if (file_exists($module_tree_file))
			{
				/* Подключение файла, формирующего массив дерева модуля */
				include $module_tree_file;
				/* Список элементов */
				$arTree["list"] = $arMyTree;
				/* Опции */
				$arTree["ui"] = $modTreeSettings;
				/* Добавляем случайный id для каждого из элементов */
				foreach($arTree["list"] as $list_item_key => $list_item)
					$arTree["list"][$list_item_key]["liid"] = rand();
				$smarty->assign("tree", $arTree);
				echo $smarty->fetch("admin/main_tree_ajax.tpl");
			}
			die();
		}
	}
	die();
}

/* Формирование дерева сайта */
$arTree = array();

/* Список элементов уровня */
$arTree["list"] = array();

/* Опции уровня */
$arTree["ui"] = array();

/* Читаем список активных модулей */
$arModules = $KS_MODULES->GetList(array("URL_ident"=>'asc'), array("active" => 1));

if ($arModules)
	foreach($arModules as $arRow)
	{
		/* Имя файла, формирующего дерево для текущего модуля */
		$module_tree_file = MODULES_DIR . "/" . $arRow["directory"] . "/.tree.php";

		if ($arRow["URL_ident"] == "default")
		{
			/* Элементы модуля по умолчанию выводим в корне */
			if (file_exists($module_tree_file))
			{
				include $module_tree_file;
				$arTree["list"] = array_merge($arTree["list"], $arMyTree);
				$arTree["ui"] = $modTreeSettings;
			}
		}
		elseif($arRow["URL_ident"] != "")
		{
			/* Остальные модули представляем свёрнутыми */
			if (file_exists($module_tree_file))
			{
				include $module_tree_file;

				$arModRow = array();
				$arModRow["title"] = $arRow["name"];
				$arModRow["path"] = $arRow["URL_ident"];
				$arModRow["type"] = "folder";
				$arModRow["module"] = $arRow["directory"];
				$arModRow["ico"] = $modTreeSettings["ico"];
				$arModRow["admin_url"] = isset($modTreeSettings["admin_url"]) ? $modTreeSettings["admin_url"] : "";
				$arModRow["watch_url"] = isset($modTreeSettings["watch_url"]) ? $modTreeSettings["watch_url"] : "";
				$arModRow["delete_url"] = isset($modTreeSettings["delete_url"]) ? $modTreeSettings["delete_url"] : "";
				$arModRow["add_cat_url"] = isset($modTreeSettings["add_cat_url"]) ? $modTreeSettings["add_cat_url"] : "";
				$arModRow["add_elm_url"] = isset($modTreeSettings["add_elm_url"]) ? $modTreeSettings["add_elm_url"] : "";
				$arModRow["ajax_req"] = base64_encode("parent_id=0|module=" . $arRow["directory"]);
				$arTree["list"][] = $arModRow;
			}
		}
	}

/* Добавляем случайный id для каждого из элементов */
foreach ($arTree["list"] as $list_item_key => $list_item)
	$arTree["list"][$list_item_key]["liid"] = rand();

$smarty->assign("FIRST", "1");
$smarty->assign("tree", $arTree);

if (array_key_exists('mode',$_GET) && $_GET["mode"] == "ajax" && array_key_exists('q',$_GET) && $_GET["q"] == "")
{
	echo $smarty->fetch("admin/main_tree_ajax.tpl");
	die();
}