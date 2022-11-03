<?php
    // * Подключение стандартного компонента с кастомным шаблоном
    $APPLICATION->IncludeComponent (
    "bitrix:search.page",
    "search",
    [
        "AJAX_MODE" => "Y",
        "AJAX_OPTION_JUMP" => "N",
        "AJAX_OPTION_HISTORY" => "N",
        "CACHE_TYPE" => "A",
        "CACHE_TIME" => 0,
        "RESTART" => "N",
        "NO_WORD_LOGIC" => "Y",
//        "USE_SUGGEST" => "Y",
//        "USE_LANGUAGE_GUESS" => "Y", // Ломает поиск по части слова (например ввести "фек")
        "DEFAULT_SORT" => "rank",
        "arrFILTER" => ["iblock_content"],
        "arrFILTER_iblock_content" => [App\Tools\IBlock::getIdByCode(UP_CATALOG_IBLOCK_CODE)],
        "PAGE_RESULT_COUNT" => 10,
        ]);