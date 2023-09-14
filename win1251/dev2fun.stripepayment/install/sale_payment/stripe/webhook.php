<?php
/**
 * @author darkfriend <hi@darkfriend.ru>
 * @copyright (c) 2020-2023, darkfriend
 * @version 1.5.2
 */
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

use \Bitrix\Main\Application;
use \Bitrix\Sale\Order;

\Bitrix\Main\Loader::includeModule('iblock');
\Bitrix\Main\Loader::includeModule('sale');

$output = '';
$paySystem = CSalePaySystem::GetList(
    [],
    ['NAME' => ['stripe', 'stripe2', 'Stripe']],
    false,
    false,
    [
        'ID', 'NAME', 'ACTIVE', 'PSA_PARAMS', 'PSA_ID', 'PSA_HAVE_PREPAY',
        'PSA_NAME', 'PSA_ACTION_FILE', 'PSA_RESULT_FILE'
    ]
)->Fetch();

try {

    if(empty($paySystem)) {
        throw new ErrorException('Stripe not found. Please set "Stripe" for field with "NAME"');
    }

    if(!empty($paySystem['PSA_PARAMS'])) {
        $paySystem['PSA_PARAMS'] = \unserialize($paySystem['PSA_PARAMS'], ['allowed_classes' => false]);
    }

    \Bitrix\Main\Loader::includeModule('dev2fun.stripepayment');
    $request = Application::getInstance()->getContext()->getRequest();

    include __DIR__ . '/vendor/autoload.php';

    if(isset($paySystem['PSA_PARAMS']['LIVE_MODE']) && $paySystem['PSA_PARAMS']['LIVE_MODE']['VALUE']==='Y') {
        $secretKey = $paySystem['PSA_PARAMS']['LIVE_SECRET_KEY']['VALUE'];
        $publishKey = $paySystem['PSA_PARAMS']['LIVE_PUBLISH_KEY']['VALUE'];
        $webhookToken = $paySystem['PSA_PARAMS']['SOURCE_WEBHOOK']['VALUE'];
    } else {
        $secretKey = $paySystem['PSA_PARAMS']['TEST_SECRET_KEY']['VALUE'];
        $publishKey = $paySystem['PSA_PARAMS']['TEST_PUBLISH_KEY']['VALUE'];
        $webhookToken = $paySystem['PSA_PARAMS']['TEST_SOURCE_WEBHOOK']['VALUE'];
    }

    $payload = @\file_get_contents('php://input');

    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;
    $event = null;

    if (empty($webhookToken)) {
        throw new \Exception('Webhook token is not found!');
    }

    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $webhookToken
    );

    $stripe = new \Stripe\StripeClient($secretKey);

    switch ($event->type) {
        case 'charge.succeeded':
            /** @var \Stripe\Charge $charge */
            $charge = $event->data->object;
            if ($charge->status!=='succeeded') {
                throw new \Exception('Status is not succeeded');
            }
            $customer = $stripe->customers->retrieve($charge->customer);
            if (empty($customer->metadata)) {
                throw new \Exception('customer metadata is not found!');
            }
            $orderId = $customer->metadata->toArray()['orderId'] ?? null;
            if (!$orderId) {
                throw new \Exception('$orderId is not found!');
            }
            break;

        case 'checkout.session.completed':
            /** @var \Stripe\Checkout\Session $charge */
            $charge = $event->data->object;
            if ($charge->status === 'complete' && $charge->payment_status === 'paid') {
                if (empty($charge->metadata)) {
                    throw new \Exception('charge metadata is not found!');
                }
                $orderId = $charge->metadata->toArray()['orderId'] ?? null;
                if (!$orderId) {
                    throw new \Exception('OrderId is not found!');
                }
            }
            break;

        default:
            throw new \Exception("Received unknown event type {$event->type}");
    }

    if ($orderId) {
        /** @var \Bitrix\Sale\Order $order */
        $order = Order::load($orderId);
        if (empty($order)) {
            throw new \Exception('Order is not found!');
        }

        if ($order->isPaid()) {
            http_response_code(200);
            die('OK');
        }

        if ($order->isCanceled()) {
            throw new \Exception('Order has status is canceled');
        }

        $arOrder = $order->getFieldValues();
        $orderID = $arOrder['ID'];
        $userId = $order->getUserId();

        $arFields = [
            "PAYED"=>"Y",
            "DATE_PAYED" => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG))),
            "USER_ID" => $userId,
            "EMP_PAYED_ID" => $userId,
            "PS_SUM" => ($charge['amount']/100),
            "PS_CURRENCY" => $charge['currency'],
            "PS_STATUS " => "Y",
        ];
        if(!empty($paySystem['PSA_PARAMS']['PAYED_ORDER_STATUS']['VALUE'])) {
            $arFields['STATUS_ID'] = $paySystem['PSA_PARAMS']['PAYED_ORDER_STATUS']['VALUE'];
        }

        $events = GetModuleEvents("dev2fun.stripepayment", "OnBeforeUpdateOrder", true);
        foreach ($events as $arEvent) {
            ExecuteModuleEventEx($arEvent, array(&$arFields, $charge, $orderID));
        }

        //    $saleOrder = new CSaleOrder;
        $resOrder = CSaleOrder::Update($orderID, $arFields);
        if(!$resOrder) {
            throw new Exception($APPLICATION->GetException());
        }

        $output = "Pay Success";

        $events = GetModuleEvents("dev2fun.stripepayment", "OnBeforeSuccessOutput", true);
        foreach ($events as $arEvent) {
            ExecuteModuleEventEx($arEvent, array(&$output,$arFields,$orderID));
        }

        if(!empty($SALE_CORRESPONDENCE['REDIRECT_SUCCESS']['VALUE'])){
            $url = Dev2funModuleStripeClass::GetRedirectUrl($SALE_CORRESPONDENCE['REDIRECT_SUCCESS']['VALUE'],$orderId,'success');
            if($url) {
                LocalRedirect($url);
            }
        }
    }

    \http_response_code(200);

} catch(\UnexpectedValueException $e) {
    // Invalid payload
    \http_response_code(400);
    $output = $e->getMessage();
} catch (\Exception $e) {
    \http_response_code(400);
    $output = $e->getMessage();
}

echo $output;
exit();