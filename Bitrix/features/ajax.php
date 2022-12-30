<?php
/**
 * Обработчик ajax запросов
 * 
 */

define('PUBLIC_AJAX_MODE', true);
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);
define('STOP_STATISTICS', true);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
header('Content-Type: application/json');
