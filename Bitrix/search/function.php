<?php

/**
 * Использует битриксовый поиск по слове переданному в $query
 * 
 * @param string $query     Строка, по которой осуществляется поиск
 * @param int $iBlockId     Id инфоблока, в котом осуществляется поиск
 * @param int $sectionId    Id раздела инфоблока, в котом осуществляется поиск
 * @param int $limit        Максимальное количество результатов на выходе
 * @param string $module    Всегда будет 'iblock'
 * @param array $aSort      Сортировка результатов на выходе
 * @return array            Массив с полями найденных элементов
 */
public static function fullTextSearchInIBlock(
    string $query,
    $iBlockId = false,
    $sectionId = false,
    int $limit = 4,
    string $module = 'iblock',
    array $aSort = []
): array
{
    $replaceListToEmpty = [
        '"', '»', '«', '\'', '(', ')', '/', '-'
    ];

    $replaceListToSpace = [
        '_', ','
    ];

    foreach($replaceListToEmpty as $repE) {
        $query = str_replace($repE, '', $query);
    }

    foreach($replaceListToSpace as $repS) {
        $query = str_replace($repS, ' ', $query);
    }

    \Bitrix\Main\Loader::includeModule("search");
    $obSearch = new \CSearch();

    if(empty($aSort)) {
        $aSort = [
            'CUSTOM_RANK' => 'DESC',
            'RANK' => 'DESC'
        ];
    }

    $filter = [
        'QUERY' => "*" . $query . "*",
        'SITE_ID' => "s1",
        'MODULE_ID' => $module,
    ];

    if($iBlockId && $iBlockId > 0) {
        $filter['PARAM2'] = (int)$iBlockId;
    }

    if ($sectionId) {
        $filter['PARAMS'] = [
            'iblock_section' => $sectionId
        ];
    }

    $aParamsEx = [
        'STEMMING' => false,
    ];

    $obSearch->SetLimit($limit);
    $obSearch->Search($filter, $aSort, $aParamsEx);

    $itemsId = [];
    while ($row = $obSearch->fetch()) {
        $itemsId[] = $row;
    }

    return $itemsId;
}