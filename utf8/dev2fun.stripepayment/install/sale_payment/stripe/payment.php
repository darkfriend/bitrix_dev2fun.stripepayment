<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * @author darkfriend <hi@darkfriend.ru>
 * @copyright (c) 2019-2023, darkfriend
 * @version 1.5.2
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

$orderId = (int)($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ID"] ?? 0);
if (!$orderId) {
    $orderId = isset($_REQUEST[$orderKey]) ? $_REQUEST[$orderKey] : null;
}
if (!$orderId) {
    $orderId = $request->get('ORDER_ID');
}
if (!$orderId) {
    $orderId = $request->get('ID');
}
if (!$orderId) {
    $orderId = $request->getPost('accountNumber');
}
if(!$orderId) {
    ShowError('Order Id is not found!');
    return;
}

/** @var \Bitrix\Sale\Order $order */
$order = Order::load($orderId);
if (!$order) {
    ShowError('Order is not found!');
    return;
}
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

if (isset($SALE_CORRESPONDENCE['LIVE_MODE']) && $SALE_CORRESPONDENCE['LIVE_MODE']['VALUE'] === 'Y') {
    $secretKey = $SALE_CORRESPONDENCE['LIVE_SECRET_KEY']['VALUE'];
    $publishKey = $SALE_CORRESPONDENCE['LIVE_PUBLISH_KEY']['VALUE'];
} else {
    $secretKey = $SALE_CORRESPONDENCE['TEST_SECRET_KEY']['VALUE'];
    $publishKey = $SALE_CORRESPONDENCE['TEST_PUBLISH_KEY']['VALUE'];
}

if (!empty($_REQUEST['sessionMode'])) {
    include __DIR__ . '/vendor/autoload.php';
//    \Stripe\Stripe::setApiKey($secretKey);
    $stripe = new \Stripe\StripeClient($secretKey);

    $arItems = [];
    foreach ($order->getBasket()->getBasketItems() as $basketItem) {
        $product = $basketItem->getFields();

        if (empty($product['QUANTITY'])) {
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
                'price_data' => [
                    'currency' => $product['CURRENCY'],
                    'unit_amount' => \number_format(($product['PRICE'] * 100), 0, '.', ''),
                    'product_data' => [
                        'name' => $product['NAME'],
                    ],
                ],
                'quantity' => (int) $product['QUANTITY'],
            ];
            continue;
        }

        $rsElement = \CIBlockElement::GetByID($product['PRODUCT_ID'])->GetNextElement();
        if (!$rsElement) {
            $arItems[] = [
                'name' => $product['NAME'],
                'unit_amount' => \number_format(($product['PRICE'] * 100), 0, '.', ''),
                'currency' => $product['CURRENCY'],
                'quantity' => (int) $product['QUANTITY'],
            ];
            continue;
        }
        $elementFields = $rsElement->GetFields();

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

        $item = [
            'price_data' => [
                'currency' => $product['CURRENCY'],
                'unit_amount' => \number_format(($product['PRICE'] * 100), 0, '.', ''),
                'product_data' => [
                    'name' => $product['NAME'],
                ],
            ],
            'quantity' => (int) $product['QUANTITY'],
        ];

        if ($pictureUrl) {
            $item['price_data']['product_data']['images'] = [$pictureUrl];
        }

        $arItems[] = $item;
    }

    $deliveryPrice = (float)$order->getDeliveryPrice();
//    var_dump($deliveryPrice); die();
    if ($deliveryPrice) {
        $arItems[] = [
//            'name' => 'Delivery',
//            'unit_amount' => \number_format(($deliveryPrice * 100), 0, '.', ''),
//            'currency' => $arOrder['CURRENCY'],
//            'quantity' => 1,
            'price_data' => [
                'currency' => $arOrder['CURRENCY'],
                'unit_amount' => \number_format(($deliveryPrice * 100), 0, '.', ''),
                'product_data' => [
                    'name' => 'Delivery',
                ],
            ],
            'quantity' => 1,
        ];
    }

//    var_dump($arOrder['TAX_VALUE']); die();
    $tax = (float)$arOrder['TAX_VALUE'];
    if ($tax) {
        $arItems[] = [
//            'name' => 'TAX',
//            'unit_amount' => \number_format(($arOrder['TAX_VALUE'] * 100), 0, '.', ''),
//            'currency' => $arOrder['CURRENCY'],
            'price_data' => [
                'currency' => $arOrder['CURRENCY'],
                'unit_amount' => \number_format($tax * 100, 0, '.', ''),
                'product_data' => [
                    'name' => 'TAX',
                ],
            ],
            'quantity' => 1,
        ];
    }

    $events = GetModuleEvents("dev2fun.stripepayment", "OnBeforeStripeCreateSession", true);
    foreach ($events as $arEvent) {
        ExecuteModuleEventEx($arEvent, array(&$arItems));
    }

    $response = [];
    try {
        $customer = $stripe->customers->create([
            "name" => "Customer for #{$orderId}",
            "description" => "Customer for #{$orderId}",
            "email" => $USER->GetEmail(),
            'metadata' => [
                'orderId' => $orderId,
            ],
        ]);
//        $customer = \Stripe\Customer::create([
//            "name" => "Customer for #$orderId",
//            "description" => "Customer for #$orderId",
//            "email" => $USER->GetEmail(),
//            'metadata' => [
//                'orderId' => $orderId,
//            ],
//        ]);

        $finalUrl = (\Bitrix\Main\Context::getCurrent()->getRequest()->isHttps() ? 'https' : 'http')
            . '://' . SITE_SERVER_NAME;

        if(!empty($SALE_CORRESPONDENCE['REDIRECT_SUCCESS']['VALUE'])) {
            $successUrl = $SALE_CORRESPONDENCE['REDIRECT_SUCCESS']['VALUE'];
            if(strpos($successUrl, '/') === 0) {
                $successUrl = "{$finalUrl}{$successUrl}";
            }
        } else {
            $successUrl = "{$finalUrl}/pay-success/";
        }

        if(!empty($SALE_CORRESPONDENCE['REDIRECT_FAIL']['VALUE'])) {
            $failUrl = $SALE_CORRESPONDENCE['REDIRECT_FAIL']['VALUE'];
            if(strpos($failUrl, '/') === 0) {
                $failUrl = "{$finalUrl}{$failUrl}";
            }
        } else {
            $failUrl = "{$finalUrl}/pay-error/";
        }

//        $paymentIntents = $stripe->paymentIntents->create([
//            'payment_method_types' => ['card'],
//            'line_items' => $arItems,
//            'customer' => $customer->id,
//            'success_url' => $successUrl,
//            'cancel_url' => $failUrl,
//            'metadata' => [
//                'orderId' => $orderId,
//            ],
//        ]);

//        echo '<pre>';
//        print_r($arItems);
//        die();

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [$arItems],
            'customer' => $customer->id,
            'success_url' => $successUrl,
            'cancel_url' => $failUrl,
            'metadata' => [
                'orderId' => $orderId,
            ],
        ]);

//        $session = \Stripe\Checkout\Session::create([
//            'payment_method_types' => ['card'],
//            'line_items' => $arItems,
//            'customer' => $customer->id,
//            'success_url' => $successUrl,
//            'cancel_url' => $failUrl,
//            'metadata' => [
//                'orderId' => $orderId,
//            ],
//        ]);

        $response = $session->toArray();
//        $response = $session->toArray();
    } catch (\Throwable $e) {
        $response = [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];
    }

    $APPLICATION->RestartBuffer();
    ob_end_clean();
    ob_end_flush();
    ob_clean();
    if (!empty($_REQUEST['redirect']) && !empty($session)) {
        header("HTTP/1.1 303 See Other");
        header("Location: " . $session->url);
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    die();
}

if (empty($SALE_CORRESPONDENCE['STRIPE_TEMPLATE']['VALUE'])) {
    $fileTemplate = 'custom';
} else {
    $fileTemplate = $SALE_CORRESPONDENCE['STRIPE_TEMPLATE']['VALUE'];
}

$fileTemplate = Dev2funModuleStripeClass::GetPathTemplate($SALE_CORRESPONDENCE['STRIPE_TEMPLATE']['VALUE']);
$fileTemplate .= '/templates.php';

$error = false;

if ($fileTemplate && file_exists($fileTemplate)) {
    include $fileTemplate;
} else {
    ShowError('No template "' . $SALE_CORRESPONDENCE['STRIPE_TEMPLATE']['VALUE'] . '"');
}
