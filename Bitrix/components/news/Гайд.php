<?php

/**
 * Установка
 * 
 * 1. Копируем компоненты news, news.list, и news.detail в local/components/{namespace}
 * 2. Подключаем комплексный компонент
 * 3. Меняем в news.php, detail.php, section.php шаблоны подключаемых компонентов на скопированные ранее
 */

/**
 * Настройка ЧПУ
 * 1. 'SEF_MODE' => 'Y'
 * 2. 'SEF_FOLDER' => '/название папки/', либо '/'
 * 3. "SEF_URL_TEMPLATES" => [
 *          "news" => "",
 *          "detail" => "#ELEMENT_CODE#/",
 *          "section" => "#SECTION_CODE_PATH#/",
 *     ]
 * 4. В настройках инфоблока нужно прописать так, чтобы получались те же пути (с учётом SEF_FOLDER)
 */

 // Для работы ЧПУ надо прописать это
 228 =>
    [
        'CONDITION' => '#^/название папки/#',
        'RULE' => '',
        'ID' => 'unipump:news',
        'PATH' => '/название папки/index.php',
        'SORT' => 100,
    ],



/**
 * Пагинация с циферками
 * 1. Создаём шаблон для system.pagenavigation в local/templates/{название шаблона}/components/bitrix/system.pagenavigation/
 * 2. В параметрах комплексного компонента news в PAGER_TEMPLATE прописываем название шаблона
 * 3. Как-то натягиваем шаблон (пример с многомеба в system.pagenavigation)
 * 
 */


/**
 * Показать ещё
 * 1. Копируем шаблон show_more в local/templates/{название шаблона}/components/bitrix/system.pagenavigation/
 * 2. В параметрах комплексного компонента news в PAGER_TEMPLATE прописываем название шаблона
 * 3. 
 */


 /**
  * Двойная пагинация (циферки + показать ещё)
  * 1. https://pai-bx.com/wiki/1c-bitrix/2301-different_pagination_patterns_for_one_list_items/
  */