<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * @author darkfriend <hi@darkfriend.ru>
 * @version 1.2.0
 */
$locale = array(
	'card' => array(
		'cardLabel' => 'Credit or debit card',
		'submitButton' => 'Submit',
	),
	'sepa' => array(
		'name' => 'Name',
		'email' => 'Email Address',
		'submitButton' => 'Submit',
	),
	'sofort' => array(
		'name' => 'Name',
		'email' => 'Email Address',
		'bank' => 'Bank Country',
		'submitButton' => 'Submit',
	),
	'giropay' => array(
		'name' => 'Name',
		'submitButton' => 'Submit',
	),
);
$modeList = array(
	'CARD' => array(
		'key' => 'card',
		'value' => 'Card',
	),
	'SEPA' => array(
		'key' => 'sepa',
		'value' => 'Sepa Debit',
	),
	'SOFORT' => array(
		'key' => 'sofort',
		'value' => 'Sofort',
	),
	'GIROPAY' => array(
		'key' => 'giropay',
		'value' => 'Giropay',
	),
);
$stripeMods = Dev2funModuleStripeClass::GetModesByString($SALE_CORRESPONDENCE['STRIPE_MODS']['VALUE']);
if(empty($stripeMods)) $stripeMods = array_keys($modeList);
foreach ($stripeMods as &$stripeMod) {
	$stripeMod = $modeList[$stripeMod];
}
unset($stripeMod);
?>
<link rel="stylesheet" href="/bitrix/php_interface/include/sale_payment/stripe/style.css?v1.2.0" type="text/css">
<script type="text/javascript" src="https://js.stripe.com/v3/"></script>
<div id="cardVue">
	<form-stripe
		:stripe-key="'<?=$publishKey?>'"
		:form-action="'<?=$APPLICATION->GetCurDir();?>?ORDER_ID=<?=$orderID?>'"
		:currency="'<?=mb_strtolower($arOrder['CURRENCY'])?>'"
		:currency-eur="'eur'"
		:amount="<?=($arOrder['PRICE']*100)?>"
		:amount-eur="<?=($arOrder['PRICE_EUR']*100)?>"
		:error="'<?=$error?>'"
		:order-id="<?=$orderID?>"
		:mode-list='<?=json_encode($stripeMods)?>'
		:locale='<?=json_encode($locale)?>'
		:params="{
			sofort: {redirectUrl: '<?=dev2fun\StripeHelper::getCurDir(true)."?ORDER_ID=$orderID&type=sofort"?>'},
			giropay: {redirectUrl: '<?=dev2fun\StripeHelper::getCurDir(true)."?ORDER_ID=$orderID&type=sofort"?>'}
		}"
	>
	</form-stripe>
</div>
<script src="/bitrix/php_interface/include/sale_payment/stripe/templates/custom/script.js?v1.2.0"></script>
<!--<script src="/bitrix/modules/dev2fun.stripepayment/sale_payment/stripe/templates/custom/script.js?v1.1"></script>-->