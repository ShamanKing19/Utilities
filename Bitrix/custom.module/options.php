<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

global $USER, $APPLICATION;

$request = Application::getInstance()->getContext()->getRequest();

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/options.php');
Loc::loadMessages(__FILE__);

$moduleId = basename(__DIR__);

$tabControl = new CAdminTabControl('tabControl', [
    [
        'DIV' => 'main',
        'TAB' => 'Основное',
        'TITLE' => 'Основные настройки',
    ],
]);

/**
 * Сохранение настроек
 * (вынес сюда, потому что данные сначала сохранятся, а потом подставятся)
 */
$whatsAppPhoneOptionName = 'whatsapp_phone';
$whatsappPhone = $request->getPost($whatsAppPhoneOptionName);
$saveResult = false;
if (isset($whatsappPhone)) {
    Option::set($moduleId, $whatsAppPhoneOptionName, $whatsappPhone);
    $saveResult = true;
}

if ($saveResult) {
    CAdminMessage::showMessage([
        'MESSAGE' => 'Настройки сохранены',
        'TYPE' => 'OK', // Без этого поля можно выдать сообщение об ошибке
    ]);
}

/**
 * Вкладки с настройками
 */
$tabControl->begin();
?>

<form method="post" action="<?=$request->getRequestedPage() . '?mid=' . urlencode($moduleId) . '&lang=' . LANGUAGE_ID?>">
    <?=bitrix_sessid_post()?>
    <?php $tabControl->beginNextTab();?>
    <tr>
        <td width="40%">
            <label for="<?=$whatsAppPhoneOptionName?>">Номер телефона WhatsApp:</label>
        <td width="60%">
            <input type="text"
                   name="<?=$whatsAppPhoneOptionName?>"
                   value="<?=Option::get($moduleId, $whatsAppPhoneOptionName)?>"
            />
        </td>
    </tr>

    <?php $tabControl->buttons() ?>
    <input type="submit"
           name="save"
           value="Сохранить"
           class="adm-btn-save"
    />
    <?php $tabControl->end() ?>
</form>
