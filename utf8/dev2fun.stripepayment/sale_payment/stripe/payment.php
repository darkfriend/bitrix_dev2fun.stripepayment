<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * @author darkfriend <hi@darkfriend.ru>
 * @copyright (c) 2019, darkfriend
 * @version 1.2.0
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

$orderId = $request->get('ORDER_ID');
if(!$orderId) $orderId = $request->getPost('accountNumber');
if(!$orderId) $orderId = $request->get('ID');


/** @var \Bitrix\Sale\Order $order */
$order = Order::load($orderId);
$arOrder = $order->getFieldValues();

$sum = $arOrder['PRICE'];
$sum = number_format($sum, 2, '.', '');
if($arOrder['CURRENCY']!='EUR') {
	$arOrder['PRICE_EUR'] = CCurrencyRates::ConvertCurrency(
		$arOrder['PRICE'],
		$arOrder['CURRENCY'],
		'EUR'
	);
} else {
	$arOrder['PRICE_EUR'] = $arOrder['PRICE'];
}

if(empty($arOrder['PRICE_EUR']))
	$arOrder['PRICE_EUR'] = number_format($arOrder['PRICE'], 2, '.', '');
$orderID = $order->getId();

$events = GetModuleEvents("dev2fun.stripepayment", "OnBeforeShowStripe", true);
foreach ($events as $arEvent) {
	ExecuteModuleEventEx($arEvent, array(&$arOrder));
}

if(empty($SALE_CORRESPONDENCE['STRIPE_TEMPLATE']['VALUE'])) {
	$SALE_CORRESPONDENCE['STRIPE_TEMPLATE']['VALUE'] = 'CUSTOM';
}

$fileTemplate = Dev2funModuleStripeClass::GetPathTemplate($SALE_CORRESPONDENCE['STRIPE_TEMPLATE']['VALUE']);

if(isset($SALE_CORRESPONDENCE['LIVE_MODE']) && $SALE_CORRESPONDENCE['LIVE_MODE']['VALUE']=='Y'){
	$secretKey = $SALE_CORRESPONDENCE['LIVE_SECRET_KEY']['VALUE'];
	$publishKey = $SALE_CORRESPONDENCE['LIVE_PUBLISH_KEY']['VALUE'];
} else {
	$secretKey = $SALE_CORRESPONDENCE['TEST_SECRET_KEY']['VALUE'];
	$publishKey = $SALE_CORRESPONDENCE['TEST_PUBLISH_KEY']['VALUE'];
}

$error = false;

$stripeSourceToken = $request->getPost('stripeToken');
$type = $request->getPost('type');
if(!$stripeSourceToken) {
	$stripeSourceToken = $request->get('source');
	$type = $request->get('type');
}

if($stripeSourceToken) {
	try
	{
		if(!$sum) throw new \Exception('Product price is null!');
		if(!class_exists('Stripe'))
			include __DIR__ . '/stripe-php/init.php';
		\Stripe\Stripe::setApiKey($secretKey);

//		$stripeSourceToken = $request->getPost('stripeToken');
//		if(!$stripeSourceToken)
//			throw new \Exception('Stripe token is not found!');

//		$userEmail = $request->getPost('stripeEmail');
		$arCreateFields = array(
			"amount" => ($sum*100),
			"currency" => $arOrder['CURRENCY'],
			//			"description" => $request->getPost('stripeEmail'),
//			"customer" => $customer->id,
			"metadata" => array("orderId" => $orderID),
		);
//		$stripeSource = '';
		$fieldsSource = [];
//		$type = $request->getPost('type');
		if(!$type) $type = 'card';
		switch ($type) {
			case 'card':
				$userEmail = $USER->GetEmail();
			case 'sepa':
				if($request->getPost('owner'))
					$userEmail = $request->getPost('owner')['email'];
				if($type=='sepa') {
					$arCreateFields['source'] = $stripeSourceToken;
					$arCreateFields['amount'] = ($arOrder['PRICE_EUR']*100);
					$arCreateFields['currency'] = 'eur';
				}

				if(!$userEmail)
					throw new \Exception('User email is not found!');

				$customer = \Stripe\Customer::create(array(
					"email" => $userEmail,
					"source" => $stripeSourceToken,
				));

				if(!$customer)
					throw new \Exception('Customer is not found!');

				$arCreateFields['customer'] = $customer->id;
//				$stripeIban = $request->getPost('stripeIban');
//				$stripeOwnerName = $request->getPost('stripeOwnerName');
//				if(!$stripeIban)
//					throw new \Exception('IBAN is not found!');
//				if(!$stripeOwnerName)
//					throw new \Exception('Owner Name is not found!');
//				$fieldsSource = [
//					"type" => "sepa_debit",
//					"sepa_debit" => ["iban" => $stripeIban],
//					"currency" => "eur",
//					"owner" => [
//						"name" => $stripeOwnerName,
//					],
//				];
				break;
			case 'sofort':
			case 'giropay':
				$arCreateFields['currency'] = 'eur';
				$arCreateFields['source'] = $stripeSourceToken;
				$arCreateFields['amount'] = ($arOrder['PRICE_EUR']*100);
				break;
		}



//		$arCreateFields = array(
//			"amount" => ($sum*100),
//			"currency" => $arOrder['CURRENCY'],
////			"description" => $request->getPost('stripeEmail'),
//			"customer" => $customer->id,
//			"metadata" => array("order_id" => $orderID),
//		);

//		switch ($type) {
//			case 'card':
//				break;
//			case 'sepa':
//				$arCreateFields['source'] = $stripeSourceToken;
//				break;
//			case 'sofort':
//				break;
//			case 'giropay':
//				break;
//		}

		$events = GetModuleEvents("dev2fun.stripepayment", "OnBeforeStripeCharge", true);
		foreach ($events as $arEvent) {
			ExecuteModuleEventEx($arEvent, array(&$arCreateFields,$type));
		}

		$charge = \Stripe\Charge::create($arCreateFields);

//		var_dump($charge); die();

		if(in_array($charge->status,array('succeeded','paid','chargeable'))) {
			$arFields = array(
				"PAYED"=>"Y",
				"DATE_PAYED" => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG))),
				"USER_ID" => $order->getUserId(),
				"EMP_PAYED_ID" => $USER->GetID(),
				"PS_SUM" => ($charge->amount/100),
				"PS_CURRENCY" => $charge->currency,
				"PS_STATUS " => "Y",
			);
			if(!empty($SALE_CORRESPONDENCE['PAYED_ORDER_STATUS']['VALUE']))
				$arFields['STATUS_ID'] = $SALE_CORRESPONDENCE['PAYED_ORDER_STATUS']['VALUE'];
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
				if($fileTemplate && file_exists($fileTemplate)) {
					include_once $fileTemplate.'/success.php';
				} else {
					echo $output;
				}
				return;
			} else {
				throw new Exception($APPLICATION->GetException());
			}
		} else {
			if($charge->status=='pending') {
				$output = 'Pay is pending';
				if($fileTemplate && file_exists($fileTemplate)) {
					include_once $fileTemplate.'/pending.php';
				} else {
					echo $output;
				}
				return;
			}
			throw new Exception('NO Pay! Please repeat.');
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
		if($fileTemplate && file_exists($fileTemplate)) {
			include_once $fileTemplate.'/error.php';
		} else {
			echo $error;
		}
	}
}

if($fileTemplate && file_exists($fileTemplate)) {
	include_once $fileTemplate.'/templates.php';
} else {
	ShowError('No template "'.$SALE_CORRESPONDENCE['STRIPE_TEMPLATE']['VALUE'].'"');
}
?>