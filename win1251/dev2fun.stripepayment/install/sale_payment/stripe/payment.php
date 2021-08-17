<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * @author darkfriend <hi@darkfriend.ru>
 * @copyright (c) 2019-2021, darkfriend
 * @version 1.3.9
 */

use \Bitrix\Main\Application;
use \Bitrix\Sale\Order;

global $SALE_CORRESPONDENCE, $USER, $APPLICATION;
\Bitrix\Main\Loader::registerAutoLoadClasses(
    "dev2fun.stripepayment",
    array(
        'dev2fun\StripeHelper' => 'sale_payment/stripe/lib/StripeHelper.php',
    )
);

\Bitrix\Main\Loader::includeModule('dev2fun.stripepayment');
$request = Application::getInstance()->getContext()->getRequest();

$orderKey = $SALE_CORRESPONDENCE['FIND_ORDER_ID']['VALUE'];
if(!$orderKey) {
    $orderKey = 'ORDER_ID';
}

$orderId = isset($_REQUEST[$orderKey]) ? $_REQUEST[$orderKey] : null;
if (!$orderId) $orderId = $request->get('ORDER_ID');
if (!$orderId) $orderId = $request->get('ID');
if (!$orderId) $orderId = $request->getPost('accountNumber');
if (!$orderId) {
    $orderId = \IntVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ID"]);
}

if(!$orderId) {
    ShowError('Order Id is not found!');
    return;
}

/** @var \Bitrix\Sale\Order $order */
$order = Order::load($orderId);
$arOrder = $order->getFieldValues();

$sum = $arOrder['PRICE'];
$sum = \number_format($sum, 2, '.', '');
if(!Dev2funModuleStripeClass::isSupportCurrency($arOrder['CURRENCY'])) {
    ShowError('Currency "'.$arOrder['CURRENCY'].'" is not support!');
    return;
}
//if ($arOrder['CURRENCY'] != 'EUR') {
//    $arOrder['PRICE_EUR'] = CCurrencyRates::ConvertCurrency(
//        $arOrder['PRICE'],
//        $arOrder['CURRENCY'],
//        'EUR'
//    );
//} else {
//    $arOrder['PRICE_EUR'] = $arOrder['PRICE'];
//}

//if (empty($arOrder['PRICE_EUR'])) {
//    $arOrder['PRICE_EUR'] = \number_format($arOrder['PRICE'], 2, '.', '');
//}

$orderID = $order->getId();

$events = GetModuleEvents("dev2fun.stripepayment", "OnBeforeShowStripe", true);
foreach ($events as $arEvent) {
    ExecuteModuleEventEx($arEvent, array(&$arOrder));
}

if (empty($SALE_CORRESPONDENCE['STRIPE_TEMPLATE']['VALUE'])) {
    $SALE_CORRESPONDENCE['STRIPE_TEMPLATE']['VALUE'] = 'CUSTOM';
}

if (isset($SALE_CORRESPONDENCE['LIVE_MODE']) && $SALE_CORRESPONDENCE['LIVE_MODE']['VALUE'] == 'Y') {
    $secretKey = $SALE_CORRESPONDENCE['LIVE_SECRET_KEY']['VALUE'];
    $publishKey = $SALE_CORRESPONDENCE['LIVE_PUBLISH_KEY']['VALUE'];
} else {
    $secretKey = $SALE_CORRESPONDENCE['TEST_SECRET_KEY']['VALUE'];
    $publishKey = $SALE_CORRESPONDENCE['TEST_PUBLISH_KEY']['VALUE'];
}

if (!empty($_REQUEST['sessionMode'])) {
    include __DIR__ . '/vendor/autoload.php';
    \Stripe\Stripe::setApiKey($secretKey);

    $arItems = [];
    foreach ($order->getBasket()->getBasketItems() as $basketItem) {
        $product = $basketItem->getFields();

        if(empty($product['QUANTITY'])) {
            $product['QUANTITY'] = 1;
        }

//        if ($product['CURRENCY'] != 'EUR') {
//            $product['PRICE'] = CCurrencyRates::ConvertCurrency(
//                $product['PRICE'],
//                $product['CURRENCY'],
//                'EUR'
//            );
//        }

        if ($product['CUSTOM_PRICE'] === 'Y') {
            $arItems[] = [
                'name' => $product['NAME'],
                'amount' => \number_format(($product['PRICE'] * 100), 0, '.', ''),
                'currency' => $basketItem['CURRENCY'],
//                'currency' => 'eur',
                'quantity' => (int) $product['QUANTITY'],
            ];
            continue;
        }

        $rsElement = \CIBlockElement::GetByID($product['PRODUCT_ID'])->GetNextElement();
        if(!$rsElement) {
            $arItems[] = [
                'name' => $product['NAME'],
                'amount' => \number_format(($product['PRICE'] * 100), 0, '.', ''),
                'currency' => $product['CURRENCY'],
                'quantity' => (int) $product['QUANTITY'],
            ];
            continue;
        }
        $elementFields = $rsElement->GetFields();

//        $elementPropsConcert = [];
//        if (!empty($elementProps['DATE_CONCERT']['VALUE'])) {
//            $rsElementHall = \CIBlockElement::GetByID($elementProps['DATE_CONCERT']['VALUE'])->GetNextElement();
//            if ($rsElementHall) {
//                $elementPropsConcert = $rsElementHall->GetProperties();
//            }
//        }

        $pictureUrl = '';
        if (!empty($elementFields['PREVIEW_PICTURE'])) {
            $pictureUrl = (CMain::IsHTTPS() ? 'https' : 'http')
                . '://'
                . SITE_SERVER_NAME
                . CFile::GetPath($elementFields['PREVIEW_PICTURE']);
        } elseif (!empty($elementFields['DETAIL_PICTURE'])) {
            $pictureUrl = (CMain::IsHTTPS() ? 'https' : 'http')
                . '://'
                . SITE_SERVER_NAME
                . CFile::GetPath($elementFields['DETAIL_PICTURE']);
        }

//        $pictureUrl = '';
//        if (!empty($elementPropsConcert['CONCERT']['VALUE'])) {
//            $rsElementHall = \CIBlockElement::GetByID($elementPropsConcert['CONCERT']['VALUE'])->GetNextElement();
//            if ($rsElementHall) {
//                $elementConcert = $rsElementHall->GetFields();
//                if (empty($elementConcert['PREVIEW_PICTURE'])) {
//                    $pictureUrl = (CMain::IsHTTPS() ? 'https' : 'http')
//                        . '://'
//                        . SITE_SERVER_NAME
//                        . CFile::GetPath($elementConcert['PREVIEW_PICTURE']);
//                }
//            }
//        }

        $item = [
            'name' => $product['NAME'],
            'amount' => \number_format(($product['PRICE'] * 100), 0, '.', ''),
            'currency' => $product['CURRENCY'],
//            'currency' => 'eur',
            'quantity' => (int) $product['QUANTITY'],
        ];

        if ($pictureUrl) {
            $item['images'] = [$pictureUrl];
        }

        $arItems[] = $item;
    }

    $deliveryPrice = (float)$order->getDeliveryPrice();
    if($deliveryPrice) {
        $arItems[] = [
            'name' => 'Delivery',
            'amount' => \number_format(($deliveryPrice * 100), 0, '.', ''),
            'currency' => $arOrder['CURRENCY'],
            'quantity' => 1,
        ];
    }

    if(!empty($arOrder['TAX_VALUE'])) {
        $arItems[] = [
            'name' => 'TAX',
            'amount' => \number_format(($arOrder['TAX_VALUE'] * 100), 0, '.', ''),
            'currency' => $arOrder['CURRENCY'],
            'quantity' => 1,
        ];
    }

    $events = GetModuleEvents("dev2fun.stripepayment", "OnBeforeStripeCreateSession", true);
    foreach ($events as $arEvent) {
        ExecuteModuleEventEx($arEvent, array(&$arItems));
    }

    $finalUrl = (CMain::IsHTTPS() ? 'https' : 'http') . '://' . SITE_SERVER_NAME;

    $response = [];
    try {
        $customer = \Stripe\Customer::create([
            "name" => "Customer for #$orderId",
            "description" => "Customer for #$orderId",
            "email" => $USER->GetEmail(),
            'metadata' => [
                'orderId' => $orderId,
            ],
        ]);

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $arItems,
            'customer' => $customer->id,
            'success_url' => $finalUrl . '/pay-success/',
            'cancel_url' => $finalUrl . '/pay-error/',
            'metadata' => [
                'orderId' => $orderId,
            ],
        ]);

        $response = $session->toArray();
    } catch (\Throwable $e) {
        $response = [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];
    }

    $APPLICATION->RestartBuffer();
    \ob_end_clean();
    \ob_end_flush();
    \ob_clean();
    \header('Content-Type: application/json');
    echo \json_encode($response);
    die();

//    header('Content-Type: application/json');
//    ob_end_clean();
//    ob_end_flush();
//    ob_clean();
//    die(json_encode($session));
}

$fileTemplate = Dev2funModuleStripeClass::GetPathTemplate($SALE_CORRESPONDENCE['STRIPE_TEMPLATE']['VALUE']);

$error = false;

$fileTemplate = __DIR__ . '/templates/custom';
if ($fileTemplate && file_exists($fileTemplate)) {
    include_once $fileTemplate . '/templates.php';
} else {
    ShowError('No template "' . $SALE_CORRESPONDENCE['STRIPE_TEMPLATE']['VALUE'] . '"');
}
