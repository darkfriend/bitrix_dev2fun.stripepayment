<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Created by PhpStorm.
 * User: darkfriend <hi@darkfriend.ru>
 * Date: 26.02.2017
 * Time: 21:13
 */
?>
<link rel="stylesheet" href="/bitrix/modules/dev2fun.stripepayment/sale_payment/stripe/style.css?v1.10" type="text/css">
<script type="text/javascript" src="https://js.stripe.com/v2/"></script>
<div id="cardVue">
  <card-stripe
    :stripe-key="'<?=$publishKey?>'"
    :form-action="'<?=$APPLICATION->GetCurDir();?>?ORDER_ID=<?=$orderID?>'"
    :labels="{inputs:{number:'Card Number',date:'Date on which the credit card',cvc:'CVC',button:'PROCEED'},emptyErrors: { number: 'Укажите номер', month: 'Укажите месяц', year: 'Укажите год', cvc: 'Укажите cvc' }}"
  >
  </card-stripe>
</div>
<script src="/bitrix/modules/dev2fun.stripepayment/sale_payment/stripe/templates/custom/script.js?v1.1"></script>