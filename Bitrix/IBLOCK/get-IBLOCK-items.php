<?php
$arFilter = [
    'IBLOCK_ID' => 5, // выборка элементов из инфоблока с ИД равным «5»
    'ACTIVE' => 'Y',  // выборка только активных элементов
];

$request = CIBlockElement::GetList([], $arFilter);

// вывод элементов через цикл для случая, когда их очень много (чтобы не грузить память)
while ($element = $request->GetNext()) {
    // $element['NAME'];
    // и другие свойства элемента
}

// Получение всех элементов (не всегда работает)
$response = $request->fetchAll(); 

// Получение свойств инфоблока (говно, потому что +1 запрос к БД)
CIBlockElement::GetProperty($iblockId, $elementId, $filter)->Fetch();

// Получение ссылки на файл из свойства элемента инфоблока
$attachedFileProperty = CIBlockElement::GetProperty($iblockId, $elementId, [])->Fetch();
CFile::GetPath($attachedFileProperty["VALUE"])


/* Пиздатый способ для получения полей и свойств */
$request = CIBlockElement::GetList($order, $filter);

while($item = $request->GetNextElement())
{
    $itemFields = $item->GetFields();
    $itemProperties = $item->GetProperties();
}