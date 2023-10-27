<?php

namespace App\Comparison;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\IntegerField;


/* -------------------- Создание таблицы -------------------- */

$table = CustomTable::class;
$entity = $table::getEntity();
$connection = \Bitrix\Main\Application::getConnection();

if ($connection->isTableExists($table::getTableName())) {
    return false;
}

$entity->createDbTable();
/* ---------------------------------------------------------- */



/* Удаление таблицы */
$connection = \Bitrix\Main\Application::getConnection();
$connection->dropTable('tableName');
/* ---------------- */



/* -------------------- Поля таблицы -------------------- */
class Table extends Entity\DataManager
{
    public static function getTableName()
    {
        return "table_name";
    }
    
    public static function getMap()
    {
        return [
            new IntegerField(
                "ID",
                [
                    "primary" => true,
                    "autocomplete" => true,
                ]
            ),
            new DatetimeField(
                "TIMESTAMP_X",
                [
                    "default_value" => new Main\Type\DateTime(),
                ]
            ),
            new StringField(
                "STRING_FIELD_NAME",
                [
                    "required" => false,
                    "unique" => true // Скорее всего нужно для полей, у которых стоит primary => true (в паре с первичным ключом должно быть unique => false)
                    ]
                ),
            new FloatField(
                'FLOAT_FIELD_NAME',
                [
                    "required" => false        
                ]),
            new EnumField(
                'ENUM_FIELD_NAME', 
                [
                    'values' => ['reserved', 'transit', 'ns']
                ]),
        ];
    }
}
/* ---------------------------------------------------- */
