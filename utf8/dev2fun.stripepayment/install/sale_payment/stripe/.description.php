<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * @author darkfriend <hi@darkfriend.ru>
 * @copyright (c) 2019-2023, darkfriend
 * @version 1.3.12
 */

\Bitrix\Main\Loader::includeModule('dev2fun.stripepayment');
\Bitrix\Main\Loader::includeModule('sale');
\Bitrix\Main\Localization\Loc::loadLanguageFile(__DIR__ . '/payment.php');

$obStatus = CSaleStatus::GetList();
$arStatus = [];
while ($statusItem = $obStatus->Fetch()) {
    $arStatus[$statusItem['ID']] = $statusItem['NAME'];
}

$psTitle = \Bitrix\Main\Localization\Loc::getMessage("STRIPE_TITLE");
$psDescription = \Bitrix\Main\Localization\Loc::getMessage("STRIPE_DDESCR");
$arTemplates = Dev2funModuleStripeClass::GetSupportTemplates();

$arPSCorrespondence = [
    "LIVE_MODE" => [
        "NAME" => \Bitrix\Main\Localization\Loc::getMessage("LIVE_MODE"),
        "DESCR" => \Bitrix\Main\Localization\Loc::getMessage("LIVE_MODE_DESCR"),
        "SORT" => 100,
        'GROUP' => 'GENERAL_SETTINGS',
        "INPUT" => [
            'TYPE' => 'Y/N',
            "VALUE" => "N",
        ],
    ],
    "STRIPE_TEMPLATE" => [
        "NAME" => \Bitrix\Main\Localization\Loc::getMessage("STRIPE_TEMPLATE_NAME"),
        "DESCR" => \Bitrix\Main\Localization\Loc::getMessage("STRIPE_TEMPLATE_DESCR"),
        'SORT' => 150,
        'GROUP' => 'GENERAL_SETTINGS',
        'DEFAULT' => [
            'PROVIDER_VALUE' => 'POPUP',
            'PROVIDER_KEY' => 'INPUT'
        ],
        'INPUT' => [
            'TYPE' => 'ENUM',
            'OPTIONS' => $arTemplates,
        ]
    ],
    "STRIPE_MODS" => [
        "NAME" => \Bitrix\Main\Localization\Loc::getMessage('STRIPE_MODS_NAME'),
        "DESCR" => \Bitrix\Main\Localization\Loc::getMessage('STRIPE_MODS_DESCR'),
        'SORT' => 160,
        'GROUP' => 'GENERAL_SETTINGS',
        "DEFAULT" => [
            'PROVIDER_KEY' => 'VALUE',
            'PROVIDER_VALUE' => implode(', ', array_keys(Dev2funModuleStripeClass::GetSupportModes())),
        ],
        "TYPE" => "PROPERTY",
    ],
    "PAYED_ORDER_STATUS" => [
        "NAME" => \Bitrix\Main\Localization\Loc::getMessage("STRIPE_PAYED_ORDER_STATUS_NAME"),
        "DESCR" => \Bitrix\Main\Localization\Loc::getMessage("STRIPE_PAYED_ORDER_STATUS_DESCR"),
        'SORT' => 170,
        'GROUP' => 'GENERAL_SETTINGS',
        'DEFAULT' => [],
        'INPUT' => [
            'TYPE' => 'ENUM',
            'OPTIONS' => $arStatus,
        ]
    ],
    "TEST_SECRET_KEY" => [
        "NAME" => \Bitrix\Main\Localization\Loc::getMessage("TEST_SECRET_KEY"),
        "DESCR" => \Bitrix\Main\Localization\Loc::getMessage("TEST_SECRET_KEY_DESCR"),
        "VALUE" => "",
        'SORT' => 200,
        "TYPE" => "ORDER",
    ],
    "TEST_PUBLISH_KEY" => [
        "NAME" => \Bitrix\Main\Localization\Loc::getMessage("TEST_PUBLISH_KEY"),
        "DESCR" => \Bitrix\Main\Localization\Loc::getMessage("TEST_PUBLISH_KEY_DESCR"),
        "VALUE" => "",
        'SORT' => 300,
        "TYPE" => "ORDER"
    ],
    "TEST_SOURCE_WEBHOOK" => [
        "NAME" => \Bitrix\Main\Localization\Loc::getMessage("STRIPE_TEST_SOURCE_WEBHOOK"),
        "DESCR" => \Bitrix\Main\Localization\Loc::getMessage("STRIPE_TEST_SOURCE_WEBHOOK_DESCR"),
        "VALUE" => "",
        'SORT' => 550,
    ],
    "LIVE_SECRET_KEY" => [
        "NAME" => \Bitrix\Main\Localization\Loc::getMessage("LIVE_SECRET_KEY"),
        "DESCR" => \Bitrix\Main\Localization\Loc::getMessage("LIVE_SECRET_KEY_DESCR"),
        "VALUE" => "",
        'SORT' => 400,
        "TYPE" => "ORDER"
    ],
    "LIVE_PUBLISH_KEY" => [
        "NAME" => \Bitrix\Main\Localization\Loc::getMessage("LIVE_PUBLISH_KEY"),
        "DESCR" => \Bitrix\Main\Localization\Loc::getMessage("LIVE_PUBLISH_KEY_DESCR"),
        "VALUE" => "",
        'SORT' => 500,
        "TYPE" => "ORDER"
    ],
    "SOURCE_WEBHOOK" => [
        "NAME" => \Bitrix\Main\Localization\Loc::getMessage("STRIPE_SOURCE_WEBHOOK"),
        "DESCR" => \Bitrix\Main\Localization\Loc::getMessage("STRIPE_SOURCE_WEBHOOK_DESCR"),
        "VALUE" => "",
        'SORT' => 550,
    ],
    "REDIRECT_SUCCESS" => [
        "NAME" => \Bitrix\Main\Localization\Loc::getMessage("REDIRECT_SUCCESS"),
        "DESCR" => \Bitrix\Main\Localization\Loc::getMessage("REDIRECT_SUCCESS_DESCR"),
        "VALUE" => "",
        'SORT' => 600,
        // "TYPE" => "ORDER"
    ],
    "REDIRECT_FAIL" => [
        "NAME" => \Bitrix\Main\Localization\Loc::getMessage("REDIRECT_FAIL"),
        "DESCR" => \Bitrix\Main\Localization\Loc::getMessage("REDIRECT_FAIL_DESCR"),
        "VALUE" => "",
        'SORT' => 700,
        // "TYPE" => "ORDER"
    ],
    "FIND_ORDER_ID" => [
        "NAME" => \Bitrix\Main\Localization\Loc::getMessage("FIND_ORDER_ID"),
        "DESCR" => \Bitrix\Main\Localization\Loc::getMessage("FIND_ORDER_ID_DESCR"),
        "VALUE" => "",
        'SORT' => 800,
        "DEFAULT" => [
            'PROVIDER_KEY' => 'VALUE',
            'PROVIDER_VALUE' => 'ORDER_ID',
        ],
        "TYPE" => "PROPERTY",
    ],
    "URL_TO_PAYMENT" => [
        "NAME" => \Bitrix\Main\Localization\Loc::getMessage("URL_TO_PAYMENT"),
        "DESCR" => \Bitrix\Main\Localization\Loc::getMessage("URL_TO_PAYMENT_DESCR"),
        "VALUE" => "",
        'SORT' => 900,
        "TYPE" => "PROPERTY",
    ],
];