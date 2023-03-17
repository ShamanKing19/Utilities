<?php

/**
 * Адекватный вид для пагинации
 */
function OnPageStartHandler() {
    if(isset($_GET['page']) && (int)$_GET['page'] > 0) {
        $GLOBALS['PAGEN_1'] = (int)$_GET['page'];
    }
}