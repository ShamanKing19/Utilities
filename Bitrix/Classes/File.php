<?php
namespace App;

class File
{
    private static string $uploadDir = 'upload';
    private array $file;


    public function __construct($fileId)
    {
        $this->file = $this->getById($fileId);
    }


    /**
     * Получение массива файла
     * @return array
     */
    public function toArray() : array
    {
        return $this->file;
    }


    /**
     * Получение пути файла
     * @return string
     */
    public function getPath() : string
    {
        return $this->file['SRC'] ?? '';
    }


    /**
     * Получение оригинального названия файла
     * @return string
     */
    public function getName() : string
    {
        return $this->file['ORIGINAL_NAME'] ?? '';
    }


    /**
     * Получение ресайзнутых картинок и файлов из таблицы b_file
     *
     * @param array $fileIdList id файлов
     * @param int $width максимальная ширина картинки
     * @param int $height максимальная высота картинки
     * @param int $mode режим сжатия
     *
     * @return array
     */
    public static function getResizedImages(array $fileIdList, int $width, int $height, int $mode = BX_RESIZE_IMAGE_PROPORTIONAL) : array
    {
        $imagesRaw = self::getList($fileIdList, ['ID', 'SUBDIR', 'FILE_NAME', 'FILE_SIZE', 'WIDTH', 'HEIGHT', 'CONTENT_TYPE']);

        $images = [];
        foreach($imagesRaw as $image) {
            $images[$image['ID']] = [
                'SRC' => $image['SRC'],
                'WIDTH' => $image['WIDTH'],
                'HEIGHT' => $image['HEIGHT'],
                'SIZE' => $image['SIZE']
            ];

            if(in_array($image['CONTENT_TYPE'], ['image/png', 'image/jpeg', 'image/jpg'])) {
                $resizedImage = \CFile::ResizeImageGet($image, ['width' => $width, 'height' => $height], $mode, true);
                $images[$image['ID']] = [
                    'SRC' => $resizedImage['src'],
                    'WIDTH' => $resizedImage['width'],
                    'HEIGHT' => $resizedImage['height'],
                    'SIZE' => $resizedImage['size']
                ];
            }
        }

        return $images;
    }


    /**
     * Получение файла по id
     *
     * @param int $fileId id файла
     * @param array|string[] $select поля, которые нужно выбрать
     *
     * @return array
     */
    public function getById(int $fileId, array $select = ['*']) : array
    {
        $file = self::getList([$fileId], $select);
        return $file ? current($file) : [];
    }


    /**
     * Получение файлов из таблицы b_file
     *
     * @param array $fileIdList id файлов
     * @param array|string[] $select поля, которые нужно выбрать
     *
     * @return array
     */
    public static function getList(array $fileIdList, array $select = ['*']) : array
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

        $request = \Bitrix\Main\FileTable::getList([
            'filter' => ['ID' => $fileIdList],
            'select' => $select
        ]);

        $uploadDir = \COption::GetOptionString('main', 'upload_dir', 'upload') ?: self::$uploadDir;
        $files = [];
        while($file = $request->fetch()) {
            if($file['SUBDIR'] && $file['FILE_NAME']) {
                $file['SRC'] = '/' . implode('/', [$uploadDir, $file['SUBDIR'], $file['FILE_NAME']]);
            }

            $files[$file['ID']] = $file;
        }

        return $files;
    }
}