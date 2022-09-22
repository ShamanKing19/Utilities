<?php
class ExampleCachedComponent extends CBitrixComponent {
    public array $arResult = [];
    private int $cacheTime;

    public function onPrepareComponentParams($arParams)
    {
        $this->cacheTime = $arParams["CACHE_TIME"];
        return $arParams;
    }

    public function executeComponent()
    {
        // 1. Всё, что нужно закешировать будет в фигурнх скобках
        // 2. Второй параметр позволяет кэшировать по группам пользователей
        if ($this->startResultCache($this->cacheTime, $GLOBALS["USER"]->GetGroups()))
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
