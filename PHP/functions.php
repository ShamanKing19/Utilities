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


/**
 * Изменяет окончание слова в зависимости от количества позиций
 *
 * @param int    $number                Количество
 * @param string $nominativeMessage     Слово в именительном падеже
 * @param string $genitiveMessage       Слово в родительном падеже
 * @param string $accusativeMessage     Слово в винительном падеже
 * @return string   Слово в падеже, соответствующему указанному количеству
 */
function declinateProductWord(int $number, string $nominativeMessage, string $genitiveMessage, string $accusativeMessage) : string
{
    $exceptions = range(11, 20);
    if ($number % 10 == 1 && !in_array($number % 100, $exceptions)) {
        $word = $nominativeMessage;
    } elseif ($number % 10 > 1 && $number % 10 < 5 && !in_array($number % 100, $exceptions)) {
        $word = $genitiveMessage;
    } else {
        $word = $accusativeMessage;
    }

    return $word;
}


/**
 * Возвращает размер файла в формате
 *
 * @param int $size         размер файла в байтах
 * @param int $round        точность округления
 * @return string           размер файла в формате
 */
function getStrFileSize(int $size, int $round = 2): string
{
    $sizes = ['Б', 'Кб', 'Мб', 'Гб', 'Тб'];
    for ($i=0; $size > 1024 && $i < count($sizes) - 1; $i++) {
        $size /= 1024;
    }

    return round($size,$round)." ".$sizes[$i];
}