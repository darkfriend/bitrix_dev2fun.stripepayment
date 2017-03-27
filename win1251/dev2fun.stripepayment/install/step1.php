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

//$arScanDir  = array(
//    '/bitrix/modules/dev2fun.stripepayment/sale_payment/stripe/templates',
//    '/local/php_interface/sale_payment/stripe/templates',
//    '/bitrix/php_interface/sale_payment/stripe/templates',
//);
//$template = 'custom'; //popup
//$st = '';
//foreach ($arScanDir as $sdir) {
//    $dirPath = $_SERVER['DOCUMENT_ROOT'].$sdir;
//    $d = dir($dirPath);
//    if(!$d) continue;
//    while ($dir=$d->read()) {
////        $dir = mb_strtoupper($dir);
//        var_dump($dir);
//        if(!in_array($dir,array('.','..')) && $dir==$template){
//            $st = $dirPath.'/'.$dir.'/templates.php';
//            break;
//        }
//    }
//}

//var_dump($st);
//die();

CModule::IncludeModule("main");
use Bitrix\Main\Localization\Loc;

CModule::AddAutoloadClasses(
	'',
	array(
		"dev2fun_stripepayment" => '/bitrix/modules/dev2fun.stripepayment/install/index.php',
	)
);
$dev2fun_model = new dev2fun_stripepayment();

$res = CopyDirFiles(__DIR__.'/sale_payment', $_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/sale_payment', true, true);

if(!$res) {
    CAdminMessage::ShowMessage(Loc::getMessage('STRIPE_NO_COPY_FILES'));
    return false;
}

RegisterModule($dev2fun_model->MODULE_ID);

echo CAdminMessage::ShowNote(GetMessage("STRIPE_INSTALL_SUCCESS"));

echo BeginNote();
	echo GetMessage("STRIPE_INSTALL_LAST_MSG");
EndNote();