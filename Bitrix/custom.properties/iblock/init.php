<?php
// Тут что-то происходит ...

// Регистрация пользовательского поля
AddEventHandler("iblock", "OnIBlockPropertyBuildList", ['App\CustomProperties\PromotionRule', 'getTypeDescription']);
