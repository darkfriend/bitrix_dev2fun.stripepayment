<?php
/**
 * Created by PhpStorm.
 * User: darkfriend <hi@darkfriend.ru>
 * Date: 08.09.2020
 * Time: 23:28
 */

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('main');

CopyDirFiles(
    $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/dev2fun.stripepayment/install/sale_payment',
    $_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/sale_payment',
    true,
    true
);

die("1.3.4 - Success");