<?php
class ExampleCachedComponent extends CBitrixComponent {
    public $arResult = [];


    public function onPrepareComponentParams($arParams)
    {
        return $arParams;
    }

    public function executeComponent()
    {
        $cacheTime = $this->arParams["CACHE_TIME"];
        // 1. Всё, что нужно закешировать будет в фигурнх скобках
        // 2. Второй параметр позволяет кэшировать по группам пользователей
        if ($this->startResultCache($cacheTime, $GLOBALS["USER"]->GetGroups()))
        {
            $this->getElementsList();
            $this->getSectionCodes();
            // Можно отменить кэширование по условию
            if ($somethingWrong)
            {
                $this->abortResultCache();
            }
            $this->includeComponentTemplate();
        }
        // Это уже не закешируется
        $this->notCachedFunction();
    }


    private function getElementsList()
    {
        // Something here ...
    }


    private function getSectionCodes(&$sections)
    {
        // Something here too ...
    }

}
