<?php
namespace App\File;

class Actions
{
    private static string $uploadDir = 'upload';

    /**
     * Получение файлов из таблицы b_file
     *
     * @param array $fileIdList id файлов
     * @param array|string[] $select поля, которые нужно выбрать
     *
     * @return array
     */
    public static function getFiles(array $fileIdList, array $select = ['*']) : array
    {
        if(empty($fileIdList)) {
            return [];
        }

        if(!in_array('*', $select) && !in_array('FILE_NAME', $select)) {
            $select[] = 'FILE_NAME';
        }
        if(!in_array('*', $select) && !in_array('SUBDIR', $select)) {
            $select[] = 'SUBDIR';
        }

        $request = \App\File\FileTable::getList([
            'filter' => ['ID' => $fileIdList],
            'select' => $select
        ]);

        $uploadDir = \COption::GetOptionString('main', 'upload_dir', 'upload') ?: self::$uploadDir;
        $files = [];
        while($file = $request->fetch()) {
            if($file['SUBDIR'] && $file['FILE_NAME']) {
                $file['SRC'] = '/' . implode('/', [$uploadDir, $file['SUBDIR'], $file['FILE_NAME']]);
                unset($file['SUBDIR']);
                unset($file['FILE_NAME']);
            }
            $files[$file['ID']] = $file;
        }

        return $files;
    }
}