<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * @author darkfriend <hi@darkfriend.ru>
 * @version 1.3.7
 */
$locale = [
    'card' => [
        'cardLabel' => '',
        'submitButton' => 'Перейти к оплате',
    ],
    'sepa' => [
        'name' => 'Имя',
        'email' => 'Email',
        'submitButton' => 'Перейти к оплате',
    ],
    'sofort' => [
        'name' => 'Имя',
        'email' => 'Email',
        'bank' => 'Страна банка',
        'submitButton' => 'Перейти к оплате',
    ],
    'giropay' => [
        'name' => 'Имя',
        'submitButton' => 'Перейти к оплате',
    ],
];
$modeList = [
    'CARD' => [
        'key' => 'card',
        'value' => 'VISA / MasterCard',
    ],
    'SEPA' => [
        'key' => 'sepa',
        'value' => 'Sepa Debit',
    ],
    'SOFORT' => [
        'key' => 'sofort',
        'value' => 'Sofort',
    ],
    'GIROPAY' => [
        'key' => 'giropay',
        'value' => 'Giropay',
    ],
];
$stripeMods = $SALE_CORRESPONDENCE['STRIPE_MODS']['VALUE'];
if (empty($stripeMods)) {
    $stripeMods = array_keys($modeList);
} else {
    $stripeMods = explode(',', strtoupper($stripeMods));
}
foreach ($stripeMods as &$stripeMod) {
    $stripeMod = trim($stripeMod);
    if (!isset($modeList[$stripeMod])) continue;
    $stripeMod = $modeList[$stripeMod];
}
unset($stripeMod);

$finalUrl = (CMain::IsHTTPS() ? 'https' : 'http') . '://' . SITE_SERVER_NAME;

if(!empty($SALE_CORRESPONDENCE['URL_TO_PAYMENT']['VALUE'])) {
    $sessionUrl = $SALE_CORRESPONDENCE['URL_TO_PAYMENT']['VALUE'];
} else {
    $sessionUrl = $APPLICATION->GetCurDir();
}
?>
<script type="text/javascript" src="https://js.stripe.com/v3/"></script>
<div id="cardVue">
    <form-stripe
        :session-url="'<?= $sessionUrl ?>?ORDER_ID=<?= $orderID ?>&sessionMode=1'"
        :stripe-key="'<?= $publishKey ?>'"
        :currency="'<?= mb_strtolower($arOrder['CURRENCY']) ?>'"
        :currency-eur="'eur'"
        :amount="<?= ($arOrder['PRICE'] * 100) ?>"
        :amount-eur="<?= ($arOrder['PRICE_EUR'] * 100) ?>"
        :error="'<?= $error ?>'"
        :order-id="<?= $orderID ?>"
        :mode-list='<?= json_encode($stripeMods) ?>'
        :stripe-client-secret="'<?= $_REQUEST['client_secret'] ?>'"
        :stripe-source="'<?= $_REQUEST['source'] ?>'"
        :locale='<?= json_encode($locale) ?>'
        :params="{
            sofort: {redirectUrl: '<?= $finalUrl . "/pay-pending/" ?>'},
            giropay: {redirectUrl: '<?= $finalUrl . "/pay-pending/" ?>'}
        }"
    >
    </form-stripe>
</div>
<script type="text/javascript">
    <?= file_get_contents(__DIR__ . '/source/dist/build.js')?>
</script>
<style>
    <?= file_get_contents(__DIR__.'/source/dist/style.css')?>
</style>