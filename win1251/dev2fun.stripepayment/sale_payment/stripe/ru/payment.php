<?
global $MESS;

$MESS["STRIPE_TITLE"]="Stripe";
$MESS["STRIPE_DDESCR"]="Платежная система Stripe.com";
$MESS["STRIPE_TEMPLATE_NAME"]="Шаблон";
$MESS["STRIPE_TEMPLATE_DESCR"]="Укажите один из нескольких шаблонов или создайте свой";
$MESS["LIVE_MODE"]="Включить LIVE-режим";
$MESS["TEST_SECRET_KEY"]="Укажите тестовый Secret key";
$MESS["TEST_PUBLISH_KEY"]="Укажите тестовый Publish key";
$MESS["LIVE_SECRET_KEY"]="Укажите Secret key";
$MESS["LIVE_PUBLISH_KEY"]="Укажите Publish key";
$MESS["STRIPE_SOURCE_WEBHOOK"]="Укажите Webhook-token";

$MESS["REDIRECT_SUCCESS"]="Страница после успешной оплаты";
$MESS["REDIRECT_SUCCESS_DESCR"]="Укажите ссылку на страницу после успешной оплаты. К ссылке будет добавлено pay=success&ORDER_ID=#ID#";
$MESS["STRIPE_SOURCE_WEBHOOK_DESCR"]="Вам нужно указать Webhook-token, чтоб работали вебхуки.";

$MESS["REDIRECT_FAIL"]="Страница при ошибке во время оплаты";
$MESS["REDIRECT_FAIL_DESCR"]="Укажите ссылку на страницу при ошибке оплаты. К ссылке будет добавлено pay=fail&ORDER_ID=#ID#";
?>