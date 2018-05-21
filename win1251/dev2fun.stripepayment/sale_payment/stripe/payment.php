<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
/**
 * @author darkfriend <hi@darkfriend.ru>
 * @copyright (c) 2018, darkfriend
 * @version 1.0.2
 */
use \Bitrix\Main\Application;
use \Bitrix\Sale\Order;
global $SALE_CORRESPONDENCE, $USER, $APPLICATION;
\Bitrix\Main\Loader::includeModule('dev2fun.stripepayment');
$request = Application::getInstance()->getContext()->getRequest();

$orderId = $request->get('ORDER_ID');
if(!$orderId) $orderId = $request->getPost('accountNumber');
if(!$orderId) $orderId = $request->get('ID');

/** @var \Bitrix\Sale\Order $order */
$order = Order::load($orderId);
$arOrder = $order->getFieldValues();
// echo '<pre>';
// print_r(Dev2funModuleStripeClass::GetRedurectUrl($SALE_CORRESPONDENCE['REDIRECT_SUCCESS']['VALUE'],$orderId,'success'));
// echo '</pre>';
// die();

$events = GetModuleEvents("dev2fun.stripepayment", "OnBeforeShowStripe", true);
foreach ($events as $arEvent) {
	ExecuteModuleEventEx($arEvent, array(&$arOrder));
}
$sum = $arOrder['PRICE'];
$sum = number_format($sum, 2, '.', '');
$orderID = $order->getId();
if(isset($SALE_CORRESPONDENCE['LIVE_MODE']) && $SALE_CORRESPONDENCE['LIVE_MODE']['VALUE']=='Y'){
	$secretKey = $SALE_CORRESPONDENCE['LIVE_SECRET_KEY']['VALUE'];
	$publishKey = $SALE_CORRESPONDENCE['LIVE_PUBLISH_KEY']['VALUE'];
} else {
	$secretKey = $SALE_CORRESPONDENCE['TEST_SECRET_KEY']['VALUE'];
	$publishKey = $SALE_CORRESPONDENCE['TEST_PUBLISH_KEY']['VALUE'];
}
$error = false;
if($payToken = $request->getPost('stripeToken')) {
	try
	{
		if(!class_exists('Stripe')) {
			include __DIR__ . '/stripe-php/init.php';
		}
		\Stripe\Stripe::setApiKey($secretKey);
		$token = $request->getPost('stripeToken');
		$customer = \Stripe\Customer::create(array(
			"email" => $request->getPost('stripeEmail'),
			"source" => $token,
		));

		$arCreateFields = array(
			"amount" => ($sum*100),
			"currency" => $arOrder['CURRENCY'],
			"description" => $request->getPost('stripeEmail'),
			"customer" => $customer->id,
			"metadata" => array("order_id" => $orderID),
		);
		$events = GetModuleEvents("dev2fun.stripepayment", "OnBeforeStripeCharge", true);
		foreach ($events as $arEvent) {
			ExecuteModuleEventEx($arEvent, array(&$arCreateFields,$customer));
		}

		$charge = \Stripe\Charge::create($arCreateFields);

		if(in_array($charge->status,['succeeded','paid'])) {
			$arFields = array(
				"PAYED"=>"Y",
				"DATE_PAYED" => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG))),
				"USER_ID" => $order->getUserId(),
				"EMP_PAYED_ID" => $USER->GetID(),
				"PS_SUM" => ($charge->amount/100),
				"PS_CURRENCY" => $charge->currency,
				"PS_STATUS " => "Y",
			);
			$events = GetModuleEvents("dev2fun.stripepayment", "OnBeforeUpdateOrder", true);
			foreach ($events as $arEvent) {
				ExecuteModuleEventEx($arEvent, array(&$arFields, $charge, $orderID));
			}
			$saleOrder = new CSaleOrder;
			$resOrder = $saleOrder->Update($orderID, $arFields);
			if($resOrder) {
				$output = "Pay Success";
				$events = GetModuleEvents("dev2fun.stripepayment", "OnBeforeSuccessOutput", true);
				foreach ($events as $arEvent) {
					ExecuteModuleEventEx($arEvent, array(&$output,$arFields,$orderID));
				}
				if(!empty($SALE_CORRESPONDENCE['REDIRECT_SUCCESS']['VALUE'])){
					$url = Dev2funModuleStripeClass::GetRedurectUrl($SALE_CORRESPONDENCE['REDIRECT_SUCCESS']['VALUE'],$orderId,'success');
					if($url) {
						LocalRedirect($url);
					}
				}
				echo $output;
				return;
			} else {
				throw new Exception($APPLICATION->GetException());
			}
		} else {
			//var_dump($charge);die();
			throw new Exception('NO Pay!');
		}
	} catch (Exception $e) {
		$error = $e->getMessage();
	}
	if($error!==false) {
		$errorText = $error;
		$error = "Pay Error! ".$error;
		$events = GetModuleEvents("dev2fun.stripepayment", "OnBeforeErrorOutput", true);
		foreach ($events as $arEvent) {
			ExecuteModuleEventEx($arEvent, array(&$error,$errorText,$arFields,$orderID));
		}
		if(!empty($SALE_CORRESPONDENCE['REDIRECT_FAIL']['VALUE'])){
			$url = Dev2funModuleStripeClass::GetRedurectUrl($SALE_CORRESPONDENCE['REDIRECT_FAIL']['VALUE'],$orderId,'fail');
			if($url) {
				LocalRedirect($url);
			}
		}
		echo $error;
	}
}

if(empty($SALE_CORRESPONDENCE['STRIPE_TEMPLATE']['VALUE'])) {
	$SALE_CORRESPONDENCE['STRIPE_TEMPLATE']['VALUE'] = 'CUSTOM';
}

$fileTemplate = Dev2funModuleStripeClass::GetPathTemplate($SALE_CORRESPONDENCE['STRIPE_TEMPLATE']['VALUE']);
if($fileTemplate && file_exists($fileTemplate)) {
	include_once $fileTemplate;
} else {
	ShowError('No template "'.$SALE_CORRESPONDENCE['STRIPE_TEMPLATE']['VALUE'].'"');
}
?>