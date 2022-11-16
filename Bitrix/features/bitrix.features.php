<?php

// ! Получение списка элементов инфоблока

$arOrder = [
    "DATE_CREATE" => "DESC"
];

$arFilter = [
    'IBLOCK_ID' => [5, 6, 7], // выборка элементов из инфоблока с ID равным 5, 6 или 7
    'ACTIVE' => 'Y',  // выборка только активных элементов
    "!=PREVIEW_PICTURE" => false // Выборка элементов только если есть картинка
];

$arGroupBy = [
    
];

$navStartParams = [
    "nTopCount" => 3, // Возьмёт только 3 первых элемента
    "nOffset" => 1 // Пропустит первый элемент
];

$arSelectFields = [
    "ID", "NAME", "PROPERTY_SALE_TYPE", "PROPERTY_*", "*"
];

$request = CIBlockElement::GetList($arOrder, $arFilter, $arGroupBy, $navStartParams, $arSelectFields);

while($item = $request->GetNextElement())
{
    $itemFields = $item->GetFields();
    $itemProperties = $item->GetProperties();
}

// * Получение свойств инфоблока (говно, потому что +1 запрос к БД)
CIBlockElement::GetProperty($iblockId, $elementId, ["MY_CUSTOM_PROPERTY"], ["MY_CUSTOM_PROPERTY" => "propValue"])->Fetch();


// * Получение значений поля типа "Список"
$propertyValuesRequest = CIBlockPropertyEnum::GetList([], [
    "CODE" => $propertyCode,
]);


// * Пример фильра по свойству
$shopsRequest = CIBlockElement::GetList([], [
        "IBLOCK_CODE" => $this->shopsIBlockCode,
        "PROPERTY_SALE_TYPE" => $saleType["ID"]
    ],
    false,
    false,
    [
        "*",
    ]
);


// ! Работа с файлами

// Отдаёт массив с данными о файле
$fileArray = CFile::GetFileArray($fileId);

// Получение ссылки на файл из свойства элемента инфоблока
$absFilePath = CFile::GetPath($attachedFileProperty["VALUE"]);

/**
 * Ресайз картинки
 * 
 * BX_RESIZE_IMAGE_EXACT - масштабирует в прямоугольник $arSize c сохранением пропорций, обрезая лишнее;
 * BX_RESIZE_IMAGE_PROPORTIONAL - масштабирует с сохранением пропорций, размер ограничивается $arSize;
 * BX_RESIZE_IMAGE_PROPORTIONAL_ALT - масштабирует с сохранением пропорций за ширину при этом принимается максимальное значение из высоты/ширины, размер ограничивается $arSize, улучшенная обработка вертикальных картинок.
 */
$resizedPictureArray = CFile::ResizeImageGet($imageId, ['width' => 100, 'height' => 100], BX_RESIZE_IMAGE_EXACT, true);

 /**
  * Сохранение файла
  * @param $photo  - имеет структуру файла из массива $_FILES
  * @param $uploadDir - путь отностиельно папки upload
  */
$uploadDir = "/form-files";
CFile::SaveFile($photo, $uploadDir);



// ! Только на D7 с собственными таблицами (Пример в папке DBTable)

// * Запрос с join'ами. Работает не везде
$request = CIBlockElement::getList([
    "filter" => [
        'IBLOCK_ID' => 5, // выборка элементов из инфоблока с ИД равным «5»
        'ACTIVE' => 'Y',  // выборка только активных элементов
    ],
    "select" => [
        "ID", "TITLE", "STAGE_ID", "RESPONSIBLE_ID", "CREATED_DATE", "GROUP_ID",
        "ZOMBIE", // Если удалена, то будет просто помечена ZOMBIE = true
        "OWNER_ID_LIST" => "UF_CRM_TASK",
        "ELAPSED_TIME_ID" => "ELAPSED_TIME.ID",
        "SECONDS" => "ELAPSED_TIME.SECONDS",
        "COMMENT" => "ELAPSED_TIME.COMMENT_TEXT",
        "COMMENT_DATE" => "ELAPSED_TIME.CREATED_DATE",
        "USER_ID" => "USER_INFO.ID",
        "USER_FIRSTNAME" => "USER_INFO.NAME",
        "USER_LASTNAME" => "USER_INFO.LAST_NAME",
        "GROUP_OWNER_ID" => "GROUP_INFO.OWNER_ID",
        "GROUP_OWNER_NAME" => "GROUP_INFO.NAME",
    ],
    "runtime" => [
        new Reference(
            "ELAPSED_TIME",
            ElapsedTimeTable::class,
            Join::on('this.ID', 'ref.TASK_ID'),
        ),
        new Reference(
            "USER_INFO",
            UserTable::class,
            Join::on("this.ELAPSED_TIME.USER_ID", "ref.ID")
        ),
        new Reference(
            "GROUP_INFO",
            WorkgroupTable::class,
            Join::on("this.GROUP_ID", "ref.ID")
        )
    ]
]);