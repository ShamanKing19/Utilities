<?php
// Подключение bitrix:main.include

$includePath = "Путь до папки с include файлами";

$APPLICATION->IncludeComponent(
    "bitrix:main.include",
    "",
    [
        "AREA_FILE_SHOW" => "file",
        "PATH" => $includePath . "/personal-data-processing.php"
    ]
);