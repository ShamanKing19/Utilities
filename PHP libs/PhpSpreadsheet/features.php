<?php

/**
 * @param array $file   Файл со структурой элемента массива $_FILES
 * @return array        Массив в виде [$vendorCode => $quantity]
 */
function readXLSXOrCSV(array $file) : array
    {
        $fileOriginalName = $file['name'];
        $filenameList = explode('.', $fileOriginalName);
        $originalExtension = end($filenameList);

        $filepath = $file['tmp_name'];
        $filetype = $file['type'];

        if ($filetype === 'text/csv' || $originalExtension === 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        } elseif (
            $filetype === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            || $originalExtension === 'xlsx'
        ) {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        } else {
            return [];
        }

        $spreadsheet = $reader->load($filepath); // Читает файл
        $activeSheet = $spreadsheet->getActiveSheet(); // Выбирает страницу
        $data = $activeSheet->toArray(); // Возвращает массив по строкам файла


        return $data;
    }