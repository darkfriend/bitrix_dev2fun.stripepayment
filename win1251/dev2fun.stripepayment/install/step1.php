<?php
/**
 * @author darkfriend <hi@darkfriend.ru>
 * @copyright (c) 2017-2023, darkfriend
 * @version 1.5.2
 */

if (!check_bitrix_sessid()) return;
IncludeModuleLangFile(__FILE__);

CModule::IncludeModule("main");
use Bitrix\Main\Localization\Loc;

$res = CopyDirFiles(
    __DIR__.'/sale_payment',
    $_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/sale_payment',
    true,
    true
);

if (!$res) {
    CAdminMessage::ShowMessage(Loc::getMessage('STRIPE_NO_COPY_FILES'));
    return false;
}

RegisterModule('dev2fun.stripepayment');

echo CAdminMessage::ShowNote(GetMessage("STRIPE_INSTALL_SUCCESS"));

echo BeginNote();
echo GetMessage("STRIPE_INSTALL_LAST_MSG");
EndNote();