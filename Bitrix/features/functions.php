<?php

/**
 * Удобный вывод информации через print_r
 * 
 * @param mixed $data
 */
function prettyPrint($data) : void
{
    ?><pre
        style="
        max-height: 500px;
        overflow-y: auto;
        font-size: 14px;
        max-width: 700px;
        padding: 10px;
        overflow-x: auto;
        font-family: Consolas, monospace;
        background: lightgoldenrodyellow;"
    ><?=htmlspecialchars(print_r($data, true))?></pre><?php
}


/**
 * @param $elementId
 * @param $elementIBlockId
 * @return array            Возвращает ссылки EDIT_LINK и DELETE_LINK
 */
function getActionLinks($elementId, $elementIBlockId) : array
{
    $actions = CIBlock::GetPanelButtons($elementIBlockId, $elementId);

    $links = [
        "EDIT_LINK" => $actions["edit"]["edit_element"]["ACTION_URL"],
        "DELETE_LINK" => $actions["edit"]["delete_element"]["ACTION_URL"],
    ];

    return $links;
}


/**
 * Актуальный домен сайта (с протоколом)
 *
 * @param bool $slashAtEnd
 * @return string
 */
function currentDomain(bool $slashAtEnd = false): string
{
    $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ||
        $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

    $domainName = $_SERVER['HTTP_HOST'].($slashAtEnd ? '/' : '');

    return $protocol.$domainName;
}

/**
 * Проверяет, начинается ли строка с заданной подстроки
 *
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function startsWith(string $haystack, string $needle): bool {
    $length = strlen($needle);

    return substr($haystack,0, $length) === $needle;
}

/**
 * Проверяет, заканчивается ли строка с заданной подстроки
 *
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function endsWith(string $haystack, string $needle): bool {
    $length = strlen($needle);
    if(!$length) { return true; }

    return substr($haystack, -$length) === $needle;
}

/**
 * Возвращает разделённый массив файлов
 *
 * @param array $filesArray элемент глобального массива $_FILES
 * @return array            массив с элементами имеющими структуру $_FILES[$elem]
 */
function splitFilesList(array $filesArray): array
{
    $prettyFilesList = [];
    foreach ($filesArray["tmp_name"] as $key => $photo)
    {
        $prettyFilesList[] = [
            "name" => $filesArray["name"][$key],
            "type" => $filesArray["type"][$key],
            "tmp_name" => $filesArray["tmp_name"][$key],
            "error" => $filesArray["error"][$key],
            "size" => $filesArray["size"][$key]
        ];
    }
    return $prettyFilesList;
}