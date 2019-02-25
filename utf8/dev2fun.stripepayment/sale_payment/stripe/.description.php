<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?><?
/**
 * @author darkfriend <hi@darkfriend.ru>
 * @copyright (c) 2019, darkfriend
 * @version 1.1.0
 */

\Bitrix\Main\Loader::includeModule('dev2fun.stripepayment');
include(GetLangFileName(dirname(__FILE__) . "/", "/payment.php"));

$psTitle = GetMessage("STRIPE_TITLE");
$psDescription = GetMessage("STRIPE_DDESCR");

//$arTemplates = array(
//    'CUSTOM' => 'CUSTOM',
//    'POPUP' => 'POPUP',
//);
$arTemplates = Dev2funModuleStripeClass::GetSupportTemplates();

$arPSCorrespondence = array(
	"LIVE_MODE" => array(
		"NAME" => GetMessage("LIVE_MODE"),
		"DESCR" => GetMessage("LIVE_MODE_DESCR"),
		"SORT" => 100,
		'GROUP' => 'GENERAL_SETTINGS',
		"INPUT" => array(
			'TYPE' => 'Y/N',
			"VALUE" => "N",
		),
	),
	"STRIPE_TEMPLATE" => array(
		"NAME" => GetMessage("STRIPE_TEMPLATE_NAME"),
		"DESCR" => GetMessage("STRIPE_TEMPLATE_DESCR"),
		'SORT' => 200,
		'GROUP' => 'GENERAL_SETTINGS',
		'DEFAULT' => array(
			'PROVIDER_VALUE' => 'POPUP',
			'PROVIDER_KEY' => 'INPUT'
		),
		'INPUT' => array(
			'TYPE' => 'ENUM',
			'OPTIONS' => $arTemplates,
		)
	),
	"TEST_SECRET_KEY" => array(
		"NAME" => GetMessage("TEST_SECRET_KEY"),
		"DESCR" => GetMessage("TEST_SECRET_KEY_DESCR"),
		"VALUE" => "",
		'SORT' => 200,
		"TYPE" => "ORDER",
	),
	"TEST_PUBLISH_KEY" => array(
		"NAME" => GetMessage("TEST_PUBLISH_KEY"),
		"DESCR" => GetMessage("TEST_PUBLISH_KEY_DESCR"),
		"VALUE" => "",
		'SORT' => 300,
		"TYPE" => "ORDER"
	),
	"LIVE_SECRET_KEY" => array(
		"NAME" => GetMessage("LIVE_SECRET_KEY"),
		"DESCR" => GetMessage("LIVE_SECRET_KEY_DESCR"),
		"VALUE" => "",
		'SORT' => 400,
		"TYPE" => "ORDER"
	),
	"LIVE_PUBLISH_KEY" => array(
		"NAME" => GetMessage("LIVE_PUBLISH_KEY"),
		"DESCR" => GetMessage("LIVE_PUBLISH_KEY_DESCR"),
		"VALUE" => "",
		'SORT' => 500,
		"TYPE" => "ORDER"
	),
	"SOURCE_WEBHOOK" => array(
		"NAME" => GetMessage("STRIPE_SOURCE_WEBHOOK"),
		"DESCR" => GetMessage("STRIPE_SOURCE_WEBHOOK_DESCR"),
		"VALUE" => "",
		'SORT' => 500,
	),
	"REDIRECT_SUCCESS" => array(
		"NAME" => GetMessage("REDIRECT_SUCCESS"),
		"DESCR" => GetMessage("REDIRECT_SUCCESS_DESCR"),
		"VALUE" => "",
		'SORT' => 600,
		// "TYPE" => "ORDER"
	),
	"REDIRECT_FAIL" => array(
		"NAME" => GetMessage("REDIRECT_FAIL"),
		"DESCR" => GetMessage("REDIRECT_FAIL_DESCR"),
		"VALUE" => "",
		'SORT' => 700,
		// "TYPE" => "ORDER"
	),
);