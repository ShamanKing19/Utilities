<?php
// ! Получение списка элементов инфоблока

$arOrder = ['DATE_CREATE' => 'DESC'];

$arFilter = [
    'IBLOCK_ID' => [1, 2, 3], // выборка элементов из инфоблока с ID равным 5, 6 или 7
    'ACTIVE' => 'Y',  // выборка только активных элементов
    '!=PREVIEW_PICTURE' => false // Выборка элементов только если есть картинка
];

$arGroupBy = [];

$navStartParams = [
    'nTopCount' => 3, // Возьмёт только 3 первых элемента
    'nOffset' => 1 // Пропустит первый элемент
];

$arSelectFields = ['ID', 'NAME', 'PROPERTY_SALE_TYPE', 'PROPERTY_*']; // '*' для получения всех полей

$request = CIBlockElement::GetList($arOrder, $arFilter, $arGroupBy, $navStartParams, $arSelectFields);

while($item = $request->GetNextElement())
{
    $itemFields = $item->GetFields();
    $itemProperties = $item->GetProperties();
}

// * Получение свойств инфоблока (говно, потому что +1 запрос к БД)
CIBlockElement::GetProperty($iblockId, $elementId, ['MY_CUSTOM_PROPERTY'], ['MY_CUSTOM_PROPERTY' => 'propValue'])->Fetch();


// * Получение значений поля типа 'Список'
$propertyValuesRequest = CIBlockPropertyEnum::GetList([], ['CODE' => $propertyCode]);


// * Пример фильра по свойству
$shopsRequest = CIBlockElement::GetList([], [
        'IBLOCK_CODE' => $this->shopsIBlockCode,
        'PROPERTY_SALE_TYPE' => $propertyValue
    ], false, false, ['PROPERTY_SALE_TYPE']
);


// ! Только на D7 с собственными таблицами (Пример в папке DBTable)

// * Запрос с join'ами. Работает не везде
$request = Table::getList([
    'filter' => [
        'IBLOCK_ID' => 5, // выборка элементов из инфоблока с ИД равным «5»
        'ACTIVE' => 'Y',  // выборка только активных элементов
    ],
    'select' => [
        'ID', 'TITLE', 'STAGE_ID', 'RESPONSIBLE_ID', 'CREATED_DATE', 'GROUP_ID',
        'ZOMBIE', // Если удалена, то будет просто помечена ZOMBIE = true
        'OWNER_ID_LIST' => 'UF_CRM_TASK',
        'ELAPSED_TIME_ID' => 'ELAPSED_TIME.ID',
        'SECONDS' => 'ELAPSED_TIME.SECONDS',
        'COMMENT' => 'ELAPSED_TIME.COMMENT_TEXT',
        'COMMENT_DATE' => 'ELAPSED_TIME.CREATED_DATE',
        'USER_ID' => 'USER_INFO.ID',
        'USER_FIRSTNAME' => 'USER_INFO.NAME',
        'USER_LASTNAME' => 'USER_INFO.LAST_NAME',
        'GROUP_OWNER_ID' => 'GROUP_INFO.OWNER_ID',
        'GROUP_OWNER_NAME' => 'GROUP_INFO.NAME',
    ],
    'runtime' => [
        new Reference(
            'ELAPSED_TIME',
            ElapsedTimeTable::class,
            Join::on('this.ID', 'ref.TASK_ID'),
        ),
        new Reference(
            'USER_INFO',
            UserTable::class,
            Join::on('this.ELAPSED_TIME.USER_ID', 'ref.ID')
        ),
        new Reference(
            'GROUP_INFO',
            WorkgroupTable::class,
            Join::on('this.GROUP_ID', 'ref.ID')
        )
    ]
]);