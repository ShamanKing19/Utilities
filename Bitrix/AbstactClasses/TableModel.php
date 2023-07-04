<?php
/**
 * Предоставляет доступ к полям таблицы с помощью D7.
 * Всё, что нужно, унаследоваться от этого класса и установить в $table название таблицы, которой нужно выполнять запросы
 */
abstract class TableModel extends \Bitrix\Main\Entity\DataManager
{
    /* @var string Название таблицы */
    public static string $table = '';

    /* @var int Время хранения кэша */
    protected static int $cacheTime = 86400;

    /* @var static Колонки таблицы */
    protected static $columns = [];


    public static function getTableName()
    {
        return static::$table;
    }

    public static function getMap()
    {
        if(empty(static::$columns)) {
            $sqlColumns = static::getCachedData();
            if(empty($sqlColumns)) {
                $sqlColumns = static::getRawFieldsFromTable();
                static::cacheData($sqlColumns);
            }
            static::$columns = static::getColumns($sqlColumns);
        }

        return static::$columns;
    }

    /**
     * Создание полей для \Bitrix\Main\Entity\Datamanager из SQL полей
     *
     * @param array $sqlColumns Поля MySQL таблицы
     *
     * @return array
     */
    protected static function getColumns(array $sqlColumns) : array
    {
        $fields = [];
        foreach($sqlColumns as $rawColumn) {
            $columnName = $rawColumn['Field'];
            $columnType = $rawColumn['Type'];
            $required = $rawColumn['Null'] === 'NO';
            $defaultValue = $rawColumn['Default'];
            $keyType = $rawColumn['Key'];
            $extra = $rawColumn['Extra'];

            if(strpos($columnType, 'int(1)') !== false) {
                $fields[] = static::getBooleanField($columnName, $required, $defaultValue, $keyType);
            } elseif(strpos($columnType, 'int') !== false) {
                $fields[] = static::getIntegerField($columnName, $required, $defaultValue, $keyType, $extra);
            } elseif(strpos($columnType, 'double') !== false) {
                $fields[] = static::getFloatField($columnName, $required, $defaultValue);
            } elseif(strpos($columnType, 'decimal') !== false) {
                $fields[] = static::getFloatField($columnName, $required, $defaultValue);
            } elseif(strpos($columnType, 'varchar') !== false) {
                $fields[] = static::getStringField($columnName, $required, $defaultValue);
            } elseif(strpos($columnType, 'char(1)') !== false) {
                $fields[] = static::getBooleanField($columnName, $required, $defaultValue);
            } elseif(strpos($columnType, 'char') !== false) {
                $fields[] = static::getStringField($columnName, $required, $defaultValue);
            } elseif(strpos($columnType, 'text') !== false) {
                $fields[] = static::getTextField($columnName, $required, $defaultValue);
            } elseif(strpos($columnType, 'timestamp') !== false) {
                $fields[] = static::getTimestampField($columnName, $required, $defaultValue);
            } elseif(strpos($columnType, 'datetime') !== false) {
                $fields[] = static::getTimestampField($columnName, $required, $defaultValue);
            } elseif(strpos($columnType, 'date') !== false) {
                $fields[] = static::getDateField($columnName, $required, $defaultValue);
            } else {
                echo 'Необработанное поле!!!!!!!!!!!!!!!!!!';
                pprint($rawColumn);
                die();
            }
        }

        return $fields;
    }

    /**
     * Создание поля типа "Text" для таблицы
     *
     * @param string $name Название колонки
     * @param bool $required Обязательна ли к заполнению
     * @param string|null $defaultValue Значение по умлочанию
     *
     * @return \Bitrix\Main\Entity\TextField
     */
    protected static function getTextField(string $name, bool $required = false, string $defaultValue = null) : \Bitrix\Main\Entity\TextField
    {
        $options = [
            'required' => $required,
            'default_value' => $defaultValue
        ];

        return new \Bitrix\Main\Entity\TextField($name, $options);
    }

    /**
     * Создание поля типа "Boolean" для таблицы
     *
     * @param string $name Название колонки
     * @param bool $required Обязательна ли к заполнению
     * @param string|int|bool|null $defaultValue Значение по умлочанию
     *
     * @return \Bitrix\Main\Entity\BooleanField
     */
    protected static function getBooleanField(string $name, bool $required = false, $defaultValue = null, string $keyType = null) : \Bitrix\Main\Entity\BooleanField
    {
        $options = [
            'required' => $required,
        ];

        if($keyType === 'PRI') {
            $options['primary'] = true;
        }

        if($keyType === 'UNI') {
            $options['unique'] = true;
        }

        if($keyType === 'MUL') {
            $options['primary'] = true;
            $options['unique'] = false;
        }

        if(is_bool($defaultValue) && $defaultValue) {
            $defaultValue = 1;
        } elseif(is_numeric($defaultValue) && (int)$defaultValue === 1) {
            $defaultValue = 1;
        } elseif(is_string($defaultValue) && ($defaultValue === 'Y' || $defaultValue === 'YES')) {
            $defaultValue = 1;
        } else {
            $defaultValue = 0;
        }

        $options['default_value'] = $defaultValue;

        return new \Bitrix\Main\Entity\BooleanField($name, $options);
    }

    /**
     * Создание поля типа "Datetime" для таблицы
     *
     * @param string $name Название колонки
     * @param bool $required Обязательна ли к заполнению
     * @param \Bitrix\Main\Entity\DatetimeField|null $defaultValue Значение по умлочанию
     *
     * @return \Bitrix\Main\Entity\DatetimeField
     */
    protected static function getTimestampField(string $name, bool $required = false, $defaultValue = null) : \Bitrix\Main\Entity\DatetimeField
    {
        $options = [
            'required' => $required,
        ];

        if($defaultValue === 'CURRENT_TIMESTAMP') {
            $options['default_value'] = new \Bitrix\Main\Type\DateTime();
        }

        return new \Bitrix\Main\Entity\DatetimeField($name, $options);
    }

    /**
     * Создание поля типа "Date" для таблицы
     *
     * @param string $name Название колонки
     * @param bool $required Обязательна ли к заполнению
     * @param \Bitrix\Main\Type\Date|null $defaultValue Значение по умлочанию
     *
     * @return \Bitrix\Main\Entity\DateField
     */
    protected static function getDateField(string $name, bool $required = false, \Bitrix\Main\Type\Date $defaultValue = null) : \Bitrix\Main\Entity\DateField
    {
        $options = [
            'required' => $required,
            'default_value' => $defaultValue ?? new \Bitrix\Main\Type\Date()
        ];

        return new \Bitrix\Main\Entity\DateField($name, $options);
    }

    /**
     * Создание поля типа "String" для таблицы
     *
     * @param string $name Название колонки
     * @param bool $required Обязательна ли к заполнению
     * @param string|null $defaultValue Значение по умлочанию
     *
     * @return \Bitrix\Main\Entity\StringField
     */
    protected static function getStringField(string $name, bool $required = false, string $defaultValue = null) : \Bitrix\Main\Entity\StringField
    {
        $options = [
            'required' => $required,
            'default_value' => $defaultValue
        ];

        return new \Bitrix\Main\Entity\StringField($name, $options);
    }

    /**
     * Создание поля типа "Float" для таблицы
     *
     * @param string $name Название колонки
     * @param bool $required Обязательна ли к заполнению
     * @param float|null $defaultValue Значение по умлочанию
     *
     * @return \Bitrix\Main\Entity\FloatField
     */
    protected static function getFloatField(string $name, bool $required = false, float $defaultValue = null) : \Bitrix\Main\Entity\FloatField
    {
        $options = [
            'required' => $required,
            'default_value' => $defaultValue
        ];

        return new \Bitrix\Main\Entity\FloatField($name, $options);
    }

    /**
     * Создание поля типа "Integer" для таблицы
     *
     * @param string $name Название колонки
     * @param bool $required Обязательна ли к заполнению
     * @param int|null $defaultValue Значение по умлочанию
     * @param string|null $keyType Тип ключа (PRI/MUL)
     * @param string|null $extra Дополнительные параметры поля
     *
     * @return \Bitrix\Main\Entity\IntegerField
     */
    protected static function getIntegerField(string $name, bool $required = false, int $defaultValue = null, string $keyType = null, string $extra = null) : \Bitrix\Main\Entity\IntegerField
    {
        $options = [
            'required' => $required,
            'default_value' => $defaultValue,
        ];

        if($keyType === 'PRI') {
            $options['primary'] = true;
        }

        if($keyType === 'UNI') {
            $options['unique'] = true;
        }

        if($keyType === 'MUL') {
            $options['primary'] = true;
            $options['unique'] = false;
        }

        if($extra === 'auto_increment') {
            $options['autocomplete'] = true;
        }

        return new \Bitrix\Main\Entity\IntegerField($name, $options);
    }

    /**
     * Получение полей из таблицы MySQL
     *
     * <pre>
     *  [Field] => Название колонки
     *  [Type] => Тип
     *  [Null] => Является ли обязательным (NO/YES)
     *  [Key] => Тип ключа (PRI, MUL)
     *  [Default] =>
     *  [Extra] => auto_increment
     * </pre>
     *
     * @return array
     */
    protected static function getRawFieldsFromTable() : array
    {
        global $DB;
        $request = $DB->Query('DESCRIBE ' . static::$table);
        $columnList = [];
        while($column = $request->fetch()) {
            $columnList[] = $column;
        }

        return $columnList;
    }

    /**
     * Сохранение данных к кэш
     *
     * @param array $data Данные, которое нужно сохранить
     */
    protected static function cacheData(array $data) : void
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cache->startDataCache(static::$cacheTime, static::getCacheKey(), static::getCachePath());
        $cache->endDataCache($data);
    }

    /**
     * Получение данных из кэша
     *
     * @return array
     */
    protected static function getCachedData() : array
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        if($cache->initCache(static::$cacheTime, static::getCacheKey(), static::getCachePath())) {
            return $cache->getVars() ?? [];
        }

        return [];
    }

    /**
     * Очистка кэша
     *
     * @param bool $all false - очистить только для текущей таблицы, true - для всех
     */
    public static function clearCache(bool $all = false) : void
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cachePath = static::getCachePath();
        if($all) {
            $cache->cleanDir($cachePath);
        } else {
            $cache->clean(static::getCacheKey(), $cachePath);
        }
    }

    protected static function getCacheKey() : string
    {
        return static::$table . '_d7_columns';
    }

    protected static function getCachePath() : string
    {
        return 'table_d7_columns_cache';
    }
}