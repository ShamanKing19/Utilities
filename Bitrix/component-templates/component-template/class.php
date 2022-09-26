<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class ExampleCompSimple extends CBitrixComponent
{
    public $arResult = [];

    public function onPrepareComponentParams($arParams)
    {
        return $arParams;
    }

    public function executeComponent() 
    {
        $this->includeComponentTemplate();
    }
}