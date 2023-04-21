<?php
namespace App\File;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ScalarField;
use Bitrix\Main\Entity\StringField;

class FileTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'b_file';
    }

    public static function getMap(): array
    {
        return [
            new IntegerField(
                'ID',
                [
                    'primary' => true,
                ]
            ),
            new DatetimeField(
                'TIMESTAMP_X',
                [
                    'column_name' => 'TIMESTAMP_X',
                ]
            ),
            new StringField(
                'MODULE_ID',
                [
                    'column_name' => 'MODULE_ID',
                ]
            ),
            new IntegerField(
                'HEIGHT',
                [
                    'column_name' => 'HEIGHT',
                ]
            ),
            new IntegerField(
                'WIDTH',
                [
                    'column_name' => 'WIDTH',
                ]
            ),
            new IntegerField(
                'FILE_SIZE',
                [
                    'column_name' => 'FILE_SIZE',
                ]
            ),
            new StringField(
                'CONTENT_TYPE',
                [
                    'column_name' => 'CONTENT_TYPE',
                ]
            ),
            new StringField(
                'CONTENT_TYPE',
                [
                    'column_name' => 'CONTENT_TYPE',
                ]
            ),
            new StringField(
                'SUBDIR',
                [
                    'column_name' => 'SUBDIR',
                ]
            ),
            new StringField(
                'FILE_NAME',
                [
                    'column_name' => 'FILE_NAME',
                ]
            ),
            new StringField(
                'ORIGINAL_NAME',
                [
                    'column_name' => 'ORIGINAL_NAME',
                ]
            ),
            new StringField(
                'DESCRIPTION',
                [
                    'column_name' => 'DESCRIPTION',
                ]
            ),
            new StringField(
                'HANDLER_ID',
                [
                    'column_name' => 'HANDLER_ID',
                ]
            ),
            new StringField(
                'EXTERNAL_ID',
                [
                    'column_name' => 'EXTERNAL_ID',
                ]
            )
        ];
    }
}