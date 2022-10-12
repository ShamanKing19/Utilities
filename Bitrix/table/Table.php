<?php

namespace App\Comparison;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\IntegerField;


// * Можно создать через командную строку, можно где-то в коде
use App\Comparison\Table; // Нужно указать актуальный namespace
$entity = Table::getEntity();
$connection = \Bitrix\Main\Application::getConnection();

if ($connection->isTableExists(Table::getTableName())) {
   return false;
}

$entity->createDbTable();



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
            new IntegerField(
                "USER_ID",
                [
                    "required" => true
                ]
            ),
            new IntegerField(
                "PRODUCT_ID",
                [
                    "required" => true
                ]
            ),
            new DatetimeField(
                "TIMESTAMP_X",
                [
                    "default_value" => new Main\Type\DateTime(),
                ]
            )
        ];
    }
}