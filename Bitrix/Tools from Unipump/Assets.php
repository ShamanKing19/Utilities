<?php
namespace App\Tools;

use Bitrix\Main\Page\AssetLocation;

class Assets
{
    const FOOTER_LOCATION = 'FOOTER_LOCATION';

    private static $bitrixAssetInstance;

    public static function addJs(string $src, array $options=[], bool $inFooter=false)
    {
        if (!self::$bitrixAssetInstance){
            self::setInstance();
        }

        $options['src'] = $src;
        $attributes = implode(' ', array_map(function ($key, $value) {
            if($value === true) {
                return $key;
            } else {
                return sprintf('%s="%s"', $key, $value);
            }
        }, array_keys($options), $options));
        self::$bitrixAssetInstance->addString(
            "<script {$attributes}></script>",
            false,
            $inFooter ? self::FOOTER_LOCATION : AssetLocation::AFTER_JS
        );
    }

    public static function addCss(string $href, array $options=[], $location = AssetLocation::AFTER_CSS)
    {
        if (!self::$bitrixAssetInstance) {
            self::setInstance();
        }

        if(!file_exists($_SERVER['DOCUMENT_ROOT'].$href)) {
            return;
        }

        $options['rel'] = 'stylesheet';
        $options['href'] = $href;
        $attributes = implode(' ', array_map(function ($key, $value) {
            if($value === true) {
                return $key;
            } else {
                return sprintf('%s="%s"', $key, $value);
            }
        }, array_keys($options), $options));
        self::$bitrixAssetInstance->addString(
            "<link {$attributes} />",
            false,
            $location
        );
    }

    public static function showCss(string $href, array $options=[])
    {
        $options['rel'] = 'stylesheet';
        $options['href'] = $href;
        $attributes = implode(' ', array_map(function ($key, $value) {
            if($value === true) {
                return $key;
            } else {
                return sprintf('%s="%s"', $key, $value);
            }
        }, array_keys($options), $options));
        echo "<link {$attributes} />";
    }

    protected static function setInstance()
    {
        self::$bitrixAssetInstance = \Bitrix\Main\Page\Asset::getInstance();
    }
}