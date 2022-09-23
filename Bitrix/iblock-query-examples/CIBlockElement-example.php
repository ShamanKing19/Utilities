<?php

/**
 * * Получение списка элементов инфоблока
 * 
 * CIBlockElement::GetList(array $order, array $filter, array|bool $groupBy, array|bool $navStartParams, array $selectFields)
 * 
 * $order = [
 *    "SORT" => "DESC", // Сортировка по полю SORT по убыванию
 * ] 
 * 
 * 
 * $filter = [
 *   'IBLOCK_ID' => [5, 6, 7], // выборка элементов из инфоблока с ID равным 5, 6 или 7
 *   'ACTIVE' => 'Y',  // выборка только активных элементов
 * ];
 * 
 * 
 * $navStartParams = [
 *      "nTopCount" => 3, // Возьмёт только 3 первых элемента
 *      "nOffset" => 1 // Пропустит первый элемент
 * ]
 * 
 * * Методы
 * 
 * Fetch() - Возвращает элемент с полями (чтобы добраться до свойств нужно вызывать их через ["PROPERTY_<PROPERTYNAME>"]) 
 * GetNext() - Возвращает массив с полями
 * GetNextElement() - Возвращает объект с методами GetFields() и GetProperties()
 * fetchAll() - Есть не у всех таблиц
 */


$arFilter = [
    'IBLOCK_ID' => [5, 6, 7], // выборка элементов из инфоблока с ID равным 5, 6 или 7
    'ACTIVE' => 'Y',  // выборка только активных элементов
];

$request = CIBlockElement::GetList([], $arFilter);

// Получение свойств инфоблока (говно, потому что +1 запрос к БД)
CIBlockElement::GetProperty($iblockId, $elementId, ["MY_CUSTOM_PROPERTY"])->Fetch();

// Получение ссылки на файл из свойства элемента инфоблока
$attachedFileProperty = CIBlockElement::GetProperty($iblockId, $elementId, [], ["CODE" => "SALE_TYPE"])->Fetch();
$absFilePath = CFile::GetPath($attachedFileProperty["VALUE"])


/* Пиздатый способ для получения полей и свойств */
$request = CIBlockElement::GetList($order, $filter, false, ["nTopCount" => $limit]);

while($item = $request->GetNextElement())
{
    $itemFields = $item->GetFields();
    $itemProperties = $item->GetProperties();
}