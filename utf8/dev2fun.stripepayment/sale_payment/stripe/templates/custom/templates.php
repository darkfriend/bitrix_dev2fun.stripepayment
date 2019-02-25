<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * @author darkfriend <hi@darkfriend.ru>
 * @version 1.1.0
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
?>
<link rel="stylesheet" href="/bitrix/modules/dev2fun.stripepayment/sale_payment/stripe/style.css?v1.1.0" type="text/css">
<script type="text/javascript" src="https://js.stripe.com/v3/"></script>
<div id="cardVue">
	<form-stripe
		:stripe-key="'<?=$publishKey?>'"
		:form-action="'<?=$APPLICATION->GetCurDir();?>?ORDER_ID=<?=$orderID?>'"
		:currency="'eur'"
		:error="'<?=$error?>'"
		:order-id="<?=$orderID?>"
		:amount="<?=($sum*100)?>"
		:locale='<?=json_encode($locale)?>'
		:params="{
			sofort: {redirectUrl: '<?=dev2fun\StripeHelper::getCurDir(true)."?ORDER_ID=$orderID&type=sofort"?>'},
			giropay: {redirectUrl: '<?=dev2fun\StripeHelper::getCurDir(true)."?ORDER_ID=$orderID&type=sofort"?>'}
		}"
	>
	</form-stripe>
</div>
<script src="/bitrix/modules/dev2fun.stripepayment/sale_payment/stripe/templates/custom/script.js?v1.1.0"></script>
<!--<script src="/bitrix/modules/dev2fun.stripepayment/sale_payment/stripe/templates/custom/script.js?v1.1"></script>-->