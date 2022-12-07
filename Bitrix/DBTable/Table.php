<?php

namespace App\Comparison;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\IntegerField;


/* -------------------- Создание таблицы -------------------- */

use App\Namespace\Table;
$entity = Table::getEntity();
$connection = \Bitrix\Main\Application::getConnection();

if ($connection->isTableExists(Table::getTableName())) {
   return false;
}

$entity->createDbTable();



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
                    "required" => false
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