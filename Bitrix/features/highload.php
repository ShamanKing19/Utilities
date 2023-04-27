<?php

/**
 * Добавление кастомных свойств
 * 1. Создание свойства как для обычного инфоблока
 * 2. Вызов AddEventHandler как указано ниже
 */
AddEventHandler("main", "OnUserTypeBuildList", ['App\CustomProperties\SomeProperty', 'getTypeDescription']);


 /**
  * Работа с таблицей
  */
class HighloadTable
{
    private static string $tableName = 'название таблицы';
    private static int $tableId = 1;

    /**
     * Получение Highload таблицы для запросов в стиле Entity\DataManager
     *
     * @return \Bitrix\Highloadblock\HighloadBlockTable
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getTable()
    {
        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity(self::$tableName); // Можно обоими способами
        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity(self::$tableId);
        return $entity->getDataClass();
    }
}