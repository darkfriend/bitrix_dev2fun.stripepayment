<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Created by PhpStorm.
 * User: darkfriend <hi@darkfriend.ru>
 * Date: 26.02.2017
 * Time: 21:13
 */
?>
<link rel="stylesheet" href="/bitrix/php_interface/include/sale_payment/stripe/style.css?v1.9" type="text/css">
<script src="/bitrix/php_interface/include/sale_payment/stripe/js/jquery.inputmask.bundle.min.js"></script>

<script type="text/javascript" src="https://js.stripe.com/v2/"></script>
<form action="<?=$APPLICATION->GetCurDir();?>?ORDER_ID=<?=$orderID?>" method="POST" id="stripe-payment-form" class="stripe_payment__form">
    <span class="payment-errors text-error text-center">
        <?=($error)?$error:''?>
    </span>
    <div class="form-row">
        <label>
            <span class="stripe_payment__label">Card Number</span>
            <input type="text" size="20" name="CARD[number]" data-stripe="number" class="stripe_payment__input stripe_payment__input_card" value="<?=DevHelpers::htmlspecialchars($_REQUEST['CARD']['number'])?>" data-inputmask="'mask': '9999 9999 9999 9999'">
        </label>
    </div>

    <div class="form-row">
        <div class="form-left">
            <span class="stripe_payment__label">Date on which the credit card</span>
            <div class="form-row">
                <input type="text" size="2" name="CARD[month]" data-stripe="exp_month" class="stripe_payment__input stripe_payment__input_date" data-inputmask="'mask': '99', 'alias': 'mm', 'placeholder':'mm'" value="<?=DevHelpers::htmlspecialchars($_REQUEST['CARD']['month'])?>">
                <span class="stripe_payment__slash"> / </span>
                <input type="text" size="2" name="CARD[year]" data-stripe="exp_year" class="stripe_payment__input stripe_payment__input_date" data-inputmask="'mask': '9999', 'alias': 'yyyy', 'placeholder':'yyyy'" value="<?=DevHelpers::htmlspecialchars($_REQUEST['CARD']['year'])?>">
            </div>
        </div>
        <div class="form-right">
            <label>
                <span class="stripe_payment__label">CVC</span>
                <input type="text" size="4" name="CARD[cvc]" data-stripe="cvc" class="stripe_payment__input stripe_payment__input_cvc" data-inputmask="'mask': '999'" value="<?=DevHelpers::htmlspecialchars($_REQUEST['CARD']['cvc'])?>">
            </label>
        </div>
    </div>
    <input type="submit" class="stripe-submit" value="PROCEED">
</form>

<script type="text/javascript">
    Stripe.setPublishableKey('<?=$publishKey?>');
    $(function(){
        var $form = $('#stripe-payment-form');
        $form.submit(function(event) {
            // Disable the submit button to prevent repeated clicks:
            $form.find('.stripe-submit, :input').prop('disabled', true);
            // Request a token from Stripe:
            Stripe.card.createToken($form, stripeResponseHandler);
            // Prevent the form from being submitted:
            return false;
        });
        $(":input").inputmask();
    });
    function stripeResponseHandler(status, response) {
        // Grab the form:
        var $form = $('#stripe-payment-form');
        if (response.error) { // Problem!
            // Show the errors on the form:
            $form.find('.payment-errors').text(response.error.message);
            $form.find('.stripe-submit').prop('disabled', false); // Re-enable submission
        } else { // Token was created!
            // Get the token ID:
            var token = response.id;
            // Insert the token ID into the form so it gets submitted to the server:
            $form.append($('<input type="hidden" name="stripeToken">').val(token));
            // Submit the form:
            $form.get(0).submit();
        }
    }
</script>