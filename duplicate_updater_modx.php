<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 25.06.2019
 * Time: 14:19
 */


define('MODX_API_MODE', true);
require 'index.php';

$modx->getService('error', 'error.modError');
$modx->setLogLevel(modX::LOG_LEVEL_FATAL);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

$msg = "";

$table_prefix = $modx->config['table_prefix'];

//Ищем двойников
$find_query = $modx->query("SELECT group_concat(`id`) ids, count(id) c FROM `" . $table_prefix . "site_content` GROUP by concat(uri) HAVING c > 1");

$dubles = $find_query->fetchAll(PDO::FETCH_ASSOC);
//Если нашли
if (count($dubles)) {
    foreach ($dubles as $item) {
        //Разбивем ID
        $ids = explode(",", $item['ids']);
        //Сортируем - старые вверх
        sort($ids, SORT_NUMERIC);

        $count = 0;

        foreach ($ids as $doc_id) {
            $count++;
            //ПРопускаем оригинал
            if ($count > 1) {

                //Обновляем ресурс
                $originalRes = $modx->getObject('modResource', $doc_id);
                $generated = $originalRes->cleanAlias($originalRes->get('pagetitle')) . "-" . $count;
                $originalRes->set('alias', $generated);
                $originalRes->save();
                //В лог
                $msg .= $originalRes->get('id') . " | " . $originalRes->get('pagetitle') . " - обновлен.\n";
            }

        }
         //Сброрс кеша
        $modx->cacheManager->refresh();


    }

} else {
    $msg .= "Дублей нет\n";
}

echo "<pre>" . $msg;
