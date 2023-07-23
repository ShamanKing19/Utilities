<?php
namespace App\Models;

class File extends Model
{
    public static string $table = \Bitrix\Main\FileTable::class;

    protected static string $moduleName = 'main';
    protected static bool $useCache = false;
    protected static array $clearCacheEventList = ['OnFileSave', 'OnFileDelete'];

    private static string $uploadDir = 'upload';

    protected function __construct(int $id, array $fields)
    {
        parent::__construct($id, $fields);
        $this->fields['SRC'] = '/' . implode('/', [static::$uploadDir, $fields['SUBDIR'], $fields['FILE_NAME']]);
    }

    /**
     * Получение пути файла
     * @return string
     */
    public function getPath() : string
    {
        return $this->fields['SRC'] ?? '';
    }

    /**
     * Получение оригинального названия файла
     * @return string
     */
    public function getName(bool $withExtension = true) : string
    {
        if(empty($this->fields['ORIGINAL_NAME'])) {
            return '';
        }

        if($withExtension) {
            return $this->fields['ORIGINAL_NAME'];
        }

        $nameList = explode('.', $this->fields['ORIGINAL_NAME']);
        array_pop($nameList);
        return implode('.', $nameList);
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
        $imagesRaw = self::getList(['ID' => $fileIdList]);

        $images = [];
        foreach($imagesRaw as $image) {
            $images[$image['ID']] = [
                'ID' => $image['ID'],
                'SRC' => $image['SRC'],
                'WIDTH' => $image['WIDTH'],
                'HEIGHT' => $image['HEIGHT'],
                'SIZE' => $image['SIZE']
            ];

            if(in_array($image['CONTENT_TYPE'], ['image/png', 'image/jpeg', 'image/jpg'])) {
                $resizedImage = \CFile::ResizeImageGet($image->toArray(), ['width' => $width, 'height' => $height], $mode, true);
                $images[$image['ID']] = [
                    'ID' => $image['ID'],
                    'SRC' => $resizedImage['src'],
                    'WIDTH' => $resizedImage['width'],
                    'HEIGHT' => $resizedImage['height'],
                    'SIZE' => $resizedImage['size']
                ];
            }
        }

        return $images;
    }
}