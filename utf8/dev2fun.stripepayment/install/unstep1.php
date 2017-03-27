<?php
/**
 *
 * @author darkfriend <hi@darkfriend.ru>
 * @copyright (c) 2017, darkfriend
 * @version 1.0.0
 *
 */
if(!check_bitrix_sessid()) return;
IncludeModuleLangFile(__FILE__);

CModule::IncludeModule("main");
CModule::AddAutoloadClasses(
	'',
	array(
        "dev2fun_stripepayment" => '/bitrix/modules/dev2fun.stripepayment/install/index.php',
	)
);
$dev2fun_model = new dev2fun_stripepayment();

COption::RemoveOption($dev2fun_model->MODULE_ID);
$arDir = array(
    '/bitrix/php_interface/include/sale_payment/stripe',
    '/local/php_interface/include/sale_payment/stripe'
);
foreach ($arDir as $dir) {
    if(is_dir($_SERVER['DOCUMENT_ROOT'].$dir)){
        DeleteDirFilesEx($dir);
    }
}

UnRegisterModule($dev2fun_model->MODULE_ID);

echo CAdminMessage::ShowNote(GetMessage("UNINSTALL_SUCCESS"));