<?php
/**
 * @author darkfriend <hi@darkfriend.ru>
 * @copyright (c) 2020-2023, darkfriend
 * @version 1.5.0
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

    // Set your secret key: remember to change this to your live secret key in production
    // See your keys here: https://dashboard.stripe.com/account/apikeys
//    \Stripe\Stripe::setApiKey($secretKey);


//    $event = \Stripe\Event::constructFrom(
//        json_decode($payload, true),
//    );

    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $webhookToken
    );

    $stripe = new \Stripe\StripeClient($secretKey);

    switch ($event->type) {
//        case 'payment_intent.created':
//            $paymentIntent = $event->data->object;
//            break;
//        case 'payment_intent.pending': break;
//        case 'payment_intent.processing': break;
//        case 'payment_intent.succeeded': break;
//        case 'payment_intent.canceled': break;
//        case 'payment_intent.payment_failed': break;
//        case 'payment_intent.requires_action': break;

        case 'source.chargeable':
            /** @var \Stripe\Charge $charge */
            $charge = $event->data->object;
            $finalCharge = $stripe->charges->create([
                'amount' => $charge->amount,
                'currency' => $charge->currency,
                'source' => $charge->id,
            ]);
//            \http_response_code(200);
//            if(\is_string($finalCharge)) {
//                \http_response_code(580);
//                die($finalCharge);
//            }
//            if($charge->type!=='sofort') {
//                die('OK');
//            } else {
            $orderId = $charge->metadata->toArray()['orderId'] ?? null;
            if (!$orderId) {
                throw new \Exception('$orderId is not found!');
            }
//            }
            break;
        case 'charge.succeeded':
            /** @var \Stripe\Charge $charge */
            $charge = $event->data->object;
            if ($charge->status!=='succeeded') {
                throw new \Exception('Status is not succeeded');
            }
            $orderId = $charge->source->metadata->toArray()['orderId'] ?? null;
            if (!$orderId) {
                throw new \Exception('$orderId is not found!');
            }
            break;

        case 'checkout.session.completed':
            /** @var \Stripe\Charge $charge */
            $charge = $event->data->object;
            $customer = $stripe->customers->retrieve($charge->customer->id);
            $orderId = $customer->metadata->toArray()['orderId'] ?? null;
            if (!$orderId) {
                throw new \Exception('OrderId is not found!');
            }
//            $orderId = $customer->metadata->orderId;
            break;

        default:
            throw new \Exception("Received unknown event type {$event->type}");
    }

//    $event = \Stripe\Webhook::constructEvent(
//        $payload, $sig_header, $webhookToken
//    );
//    $charge = $event['data']['object'];

//    if(\in_array($event['type'], [
//        'checkout.session.completed',
//        'source.chargeable',
////        'charge.succeeded',
//    ])) {
//
//        if($event['type'] === 'source.chargeable') {
//            $finalCharge = \Stripe\Charge::create([
//                'amount' => $charge['amount'],
//                'currency' => $charge['currency'],
//                'source' => $charge['id'],
//            ]);
//            \http_response_code(200);
//            //      var_dump($finalCharge);
//            if(\is_string($finalCharge)) {
//                \http_response_code(580);
//                die($finalCharge);
//            }
//            if($charge['type']!=='sofort') {
//                die('OK');
//            } else {
//                $orderId = $charge['metadata']['orderId'];
//                if(!$orderId) {
//                    die('$orderId is not found!');
//                }
//            }
//        }
//
//        if($event['type']==='charge.succeeded') {
//            //      http_response_code(500);
//            if($charge['status']!=='succeeded') {
//                die('Status is not succeeded');
//            }
//            $orderId = $charge['source']['metadata']['orderId'];
//        }
//
//        if($event['type']==='checkout.session.completed') {
//            $customer = \Stripe\Customer::retrieve($charge['customer']);
//            if(empty($customer->metadata->orderId)) {
//                throw new \Exception('OrderId is not found!');
//            }
//            $orderId = $customer->metadata->orderId;
//        }

        //    http_response_code(403);
        //    die();

        //    $orderID = $customer->metadata->orderId;

    /** @var \Bitrix\Sale\Order $order */
    $order = Order::load($orderId);
    if(empty($order)) {
        throw new \Exception('Order is not found!');
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
//    }

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