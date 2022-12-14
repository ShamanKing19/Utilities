<?php
// ! Работа с файлами

// Отдаёт массив с данными о файле
$fileArray = CFile::GetFileArray($fileId);

// Получение ссылки на файл из свойства элемента инфоблока
$absFilePath = CFile::GetPath($fileId);

/**
 * Ресайз картинки
 * 
 * BX_RESIZE_IMAGE_EXACT - масштабирует в прямоугольник $arSize c сохранением пропорций, обрезая лишнее;
 * BX_RESIZE_IMAGE_PROPORTIONAL - масштабирует с сохранением пропорций, размер ограничивается $arSize;
 * BX_RESIZE_IMAGE_PROPORTIONAL_ALT - масштабирует с сохранением пропорций за ширину при этом принимается максимальное значение из высоты/ширины, размер ограничивается $arSize, улучшенная обработка вертикальных картинок.
 */
$resizedPictureArray = CFile::ResizeImageGet($imageId, ['width' => 100, 'height' => 100], BX_RESIZE_IMAGE_PROPORTIONAL, true);

 /**
  * Сохранение файла
  * @param $photo  - имеет структуру файла из массива $_FILES
  * @param $uploadDir - путь отностиельно папки upload
  */

$file = CFile::MakeFileArray($filePath); // Можно использовать id, путь к файлу, url файла с другого ресурса
$uploadDir = '/files';
CFile::SaveFile($photo, $uploadDir);