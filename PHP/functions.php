<?php

/**
 * Удобный вывод данных на страницу
 *
 * @param $data
 */
function pprint(...$data)  : void
{
    $data = is_array($data) ? $data : [$data];
    $whereLine = false;
    $debug = debug_backtrace();
    foreach($data as $dt) {
        if(!empty($debug) && is_array($debug)) {
            $file = str_replace($_SERVER['DOCUMENT_ROOT'], '', $debug[0]['file']);
            $whereLine = "\n\n".$file.' (строка: '.$debug[0]['line'].')';
        }
        ?>
        <pre
                style="
            max-height: 500px;
            overflow-y: auto;
            font-size: 14px;
            max-width: 700px;
            padding: 10px;
            overflow-x: auto;
            font-family: Consolas, monospace;
            background: lightgoldenrodyellow;
            text-align: left !important;
            "
        ><?=htmlspecialchars(print_r($dt, true));?><?=$whereLine;?></pre>
        <?php
    }
}


/**
 * Актуальный домен сайта (с протоколом)
 *
 * @param bool $slashAtEnd
 * @return string
 */
function getCurrentDomain(bool $slashAtEnd = false) : string
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
function startsWith(string $haystack, string $needle) : bool 
{
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
function endsWith(string $haystack, string $needle) : bool 
{
    $length = strlen($needle);
    if(!$length) { return true; }

    return substr($haystack, -$length) === $needle;
}

/**
 * Возвращает разделённый массив файлов
 *
 * @param array $filesArray элемент глобального массива $_FILES
 * @return array массив с элементами имеющими структуру $_FILES[$elem]
 */
function splitFilesList(array $filesArray) : array
{
    $prettyFilesList = [];
    if(!is_array($filesArray["tmp_name"])) {
        if(empty($filesArray["tmp_name"])) {
            return [];
        }

        return [$filesArray];
    }

    foreach($filesArray["tmp_name"] as $key => $path)
    {
        if(empty($path)) continue;
        $prettyFilesList[$key] = [
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
 * @param int $number количество
 * @param string $nominativeMessage название в именительном падеже (есть кто? что?) (1)
 * @param string $genitiveMessage название в родительном падеже (нет кого? чего?) (2-4)
 * @param string $accusativeMessage название в винительном падеже (вижу кого? что?) (5-9)
 * @return string отформатированное название
 */
function declinateWord(int $number, string $nominativeMessage, string $genitiveMessage, string $accusativeMessage) : string
{
    $exceptions = range(11, 20);
    if($number % 10 == 1 && !in_array($number % 100, $exceptions)) {
        $word = $nominativeMessage;
    } elseif($number % 10 > 1 && $number % 10 < 5 && !in_array($number % 100, $exceptions)) {
        $word = $genitiveMessage;
    } else {
        $word = $accusativeMessage;
    }

    return $word;
}


/**
 * Чистим номер телефона от всего, кроме цифр
 *
 * @param string $phone Номер телефона в любом формате
 * @param bool $savePlus Сохранять ли плюс в номере
 * @return array|string|null
 */
function cleanPhoneString(string $phone, bool $savePlus = false) : string
{
    $plus = false;
    if($savePlus) {
        $plus = '+';
    }

    $regex = '/[^0-9'.$plus.'.]+/';

    return preg_replace($regex, '', $phone);
}


/**
 * Возвращает размер файла в формате
 *
 * @param int $size         размер файла в байтах
 * @param int $round        точность округления
 * @return string           размер файла в формате
 */
function getFileSizeFormatted(int $size, int $round = 2) : string
{
    $sizes = ['Б', 'Кб', 'Мб', 'Гб', 'Тб'];
    for ($i=0; $size > 1024 && $i < count($sizes) - 1; $i++) {
        $size /= 1024;
    }

    return round($size,$round)." ".$sizes[$i];
}

/**
 * Преобразование количества секунд к строке вида: "2 часа 1 минута 35 секунд"
 *
 * @param int $sumSeconds
 *
 * @return string
 */
function formatTime(int $sumSeconds) : string
{
    if($sumSeconds === 0) {
        return '0 сек.';
    }

    $seconds = $sumSeconds % 60;
    $minutes = (floor($sumSeconds) / 60) % 60;
    $hours = floor($sumSeconds / 60 / 60) % 60;
    $formattedString = '';
    if($hours > 0) {
        $formattedString .= $hours . ' ч. ';
    }
    if($minutes > 0) {
        $formattedString .= $minutes . ' мин. ';
    }
    if($seconds > 0) {
        $formattedString .= $seconds . ' сек.';
    }
    return $formattedString;
}


/**
 * Запись массива в .csv файл
 * 
 * @param array $data Массив к записи
 * @param string $path Путь для сохранения файла
 * @param bool $header Нужно ли выводить шапку (берёт ключи из первого элемента)
 * @param string $columnDelimiter Разделитель колонок
 * @param string $rowDelimiter Разделитель строк
 * @return bool
 */
function writeCSV(array $data, string $path, bool $header = false, $columnDelimiter = ';', $rowDelimiter = "\r\n") : bool
{
    $stringToCSV = '';
    if($header) {
        $header = array_keys(current($data));
        $stringToCSV .= implode($columnDelimiter, $header) . $rowDelimiter;
    }

    foreach ($data as $row) {
        $cols = [];

        foreach ($row as $columnValue)
        {
            if($columnValue && preg_match('/[",;\r\n]/', $columnValue))
            {
                if($rowDelimiter === "\r\n") {
                    $columnValue = str_replace(["\r\n", "\r"], ['\n', ''], $columnValue);
                } elseif($rowDelimiter === "\n") {
                    $columnValue = str_replace(["\n", "\r\r"], '\r', $columnValue);
                }

                $columnValue = str_replace('"', '""', $columnValue);
                $columnValue = '"'. $columnValue .'"';
            }

            $cols[] = $columnValue;
        }

        $stringToCSV .= implode($columnDelimiter, $cols) . $rowDelimiter;
    }

    $stringToCSV = rtrim($stringToCSV, $rowDelimiter);
    if($path) {
        $stringToCSV = iconv("UTF-8", "cp1251",  $stringToCSV);
        $done = file_put_contents($path, $stringToCSV);
        return $done;
    }

    return false;
}


/**
 * Чистит массив от пустых полей
 *
 * @param array $array очищаемый массив
 * @param array $keysToRemove ключи, подлежащие удалению
 * @param array $valuesToKeep значения, которые удалять не надо (0, '0' или '')
 */
function cleanArray(array $array, array $keysToRemove = [], array $valuesToKeep = []) : array
{
    $cleanedArray = [];
    foreach($array as $key => $value) {
        if(in_array($key, $keysToRemove)) {
            continue;
        }

        if(is_array($value)) {
            $value = cleanArray($value, $keysToRemove, $valuesToKeep);
        }

        if(!empty($value) || in_array($value, $valuesToKeep)) {
            $cleanedArray[$key] = $value;
        }
    }

    return $cleanedArray;
}

/**
 * Возвращает JSON-представление данных
 *
 * @param array $array
 * @return false|string
 */
function toJson(array $array) : string
{
    return json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

/**
 * Схлопывание вложенных массивов в один
 */
function collapse(array $array) : array
{
    return array_merge([], ...$array);
}

/**
 * Рекурсивно заменяет ключи массива (заменённый ключ будет добавлен в конец массива)
 *
 * @param array $array Массив, в котором нужно заменить ключи
 * @param array $replacementList Массив, где ключ - старый ключ, значение - новый ключ ($oldKey => $newKey)
 *
 * @return void
 */
function replaceKeysSimple(array &$array, array $replacementList) : void
{
    foreach($array as $oldKey => &$value) {
        if(array_key_exists($oldKey, $replacementList) && isset($replacementList[$oldKey])) {
            $newKey = $replacementList[$oldKey];
            $array[$newKey] = $value;
            unset($array[$oldKey]);
        }
        if(is_array($value)) {
            replaceKeys($value, $replacementList);
        }
    }
}

/**
 * Рекурсивно заменяет ключи массива (сохраняет порядок ключей)
 *
 * @param array $array Массив, в котором нужно заменить ключи
 * @param array $replacementList Массив, где ключ - старый ключ, значение - новый ключ ($oldKey => $newKey)
 *
 * @return void
 */
function replaceKeys(array &$array, array $replacementList) : void
{
    $keys = array_keys($array);
    $values = array_values($array);
    foreach($keys as $index => $oldKey) {
        if(array_key_exists($oldKey, $replacementList) && isset($replacementList[$oldKey])) {
            $newKey = $replacementList[$oldKey];
            array_splice($keys, $index, 1, [$newKey]);
            $array = array_combine($keys, $values);
        }
    }

    foreach($array as &$item) {
        if(is_array($item)) {
            replaceKeys($item, $replacementList);
        }
    }
}

/**
 * Поиск диапазонов дат
 *
 * @param array<string> $dateRangeList Массив дат
 * @param int $daysDifference Количество дней, которое должно пройти, чтобы даты попали в 1 диапазон
 *
 * @return array
 */
function findDateRanges(array $dateRangeList, int $daysDifference = 1): array
{
    $previousDate = array_shift($dateRangeList);
    $dateList = [$previousDate]; // Первый диапазон
    $foundRangesList = []; // Массив с диапазонами
    foreach ($dateRangeList as $currentDate) {

        $currentDateObj = new \DateTime($currentDate);
        $previousDateObj = new \DateTime($previousDate);
        if ($previousDateObj->diff($currentDateObj)->days <= $daysDifference) {
            $dateList[] = $currentDate;
        } else {
            $foundRangesList[] = $dateList;
            $dateList = [$currentDate];
        }

        $previousDate = $currentDate;
    }

    if ($dateList) {
        $foundRangesList[] = $dateList;
    }

    return $foundRangesList;
}