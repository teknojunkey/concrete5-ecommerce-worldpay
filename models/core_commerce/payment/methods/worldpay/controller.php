<?php
defined('C5_EXECUTE') or die(_("Access Denied."));
Loader::library('payment/controller', 'core_commerce');

class CoreCommerceWorldpayPaymentMethodController extends CoreCommercePaymentController {

	const DEBUG_MODE = true;

	public function method_form() {
		$pkg = Package::getByHandle('core_commerce');
		$eh = Loader::helper('encryption');

		// installation ID
		$this->set('PAYMENT_METHOD_WORLDPAY_INSTID', $pkg->config('PAYMENT_METHOD_WORLDPAY_INSTID'));

		// currency
		$currency = $pkg->config('PAYMENT_METHOD_WORLDPAY_CURRENCY');
		if (empty($currency))
			$currency = "GBP";
		$this->set('PAYMENT_METHOD_WORLDPAY_CURRENCY', $currency);
		$this->set("worldpay_currencies", $this->getCurrencies());

		// payment response password
		$response_pw = $pkg->config('PAYMENT_METHOD_WORLDPAY_RESPONSE_PW');
		if (!empty($response_pw)) {
			$response_pw = $eh->decrypt($response_pw);
		}
		$this->set('PAYMENT_METHOD_WORLDPAY_RESPONSE_PW', $response_pw);

		// md5 secret for transactions
		$md5_secret = $pkg->config('PAYMENT_METHOD_WORLDPAY_MD5_SECRET');
		if (!empty($md5_secret)) {
			$md5_secret = $eh->decrypt($md5_secret);
		}
		$this->set('PAYMENT_METHOD_WORLDPAY_MD5_SECRET', $md5_secret);

		// test/live mode
		$this->set('PAYMENT_METHOD_WORLDPAY_TESTMODE', $pkg->config('PAYMENT_METHOD_WORLDPAY_TESTMODE'));

		// test mode result
		$tres = $pkg->config("PAYMENT_METHOD_WORLDPAY_TESTMODE_RESULT");
		if (empty($tres))
			$tres = "AUTHORISED";
		$this->set("PAYMENT_METHOD_WORLDPAY_TESTMODE_RESULT", $tres);
		$this->set("testmode_options", $this->getTestModeOptions());
	}

	public function validate() {
		$e = parent::validate();
		$ve = Loader::helper('validation/strings');

		if ($this->post('PAYMENT_METHOD_WORLDPAY_INSTID') == '') {
			$e->add(t('You must specify your Installation ID'));
		}

		return $e;
	}

	private function validateStatusRequest() {
		$pkg = Package::getByHandle('core_commerce');
		$eh = Loader::helper('encryption');

		$result = false;

		$response_pw = $pkg->config('PAYMENT_METHOD_WORLDPAY_RESPONSE_PW');
		if (!empty($response_pw)) {
			$response_pw = $eh->decrypt($response_pw);
		} else {
			$result = true;
		}

		if ($_POST["callbackPW"] == $response_pw) {
			$result = true;
		}

		return $result;
	}

	public function action_notify_complete() {
		$success = false;
		$result = "";
        Loader::model('order/model', 'core_commerce');
		$pkg = Package::getByHandle('core_commerce');
		$eh = Loader::helper('encryption');

		if (self::DEBUG_MODE) {
			Log::addEntry(print_r($_POST, true), "worldpay");
		}

		if ($this->validateStatusRequest()) {

			$orderID = intval($_POST["cartId"]);

			$o = CoreCommerceOrder::getByID($orderID);
			if ($o) {

				$order_total = $o->getOrderTotal();
				$x100_amount = $_POST["amount"] * 100;
				$x100_total = $order_total * 100;

				if ($_POST["transStatus"] == "C") {
					$success = false;
					$result = "cancelled";
					//$o->setStatus(CoreCommerceOrder::STATUS_CANCELLED); // Commented out - we don't want the cart to be cleared
				} elseif ($x100_amount."" === $x100_total."") {
					if ($_POST['transStatus'] == 'Y') {
						$o->setStatus(CoreCommerceOrder::STATUS_AUTHORIZED);
						// CJD 20131104: Set transId
						$o->setAttribute('worldpay_transaction_id', $_POST['transId']);
						parent::finishOrder($o, 'WorldPay');
						$success = true;
						$result = "complete";
					} else {
						Log::addEntry('Unable to set status. Status code received: ' . $_POST['transStatus']);
					}
				} else {
					Log::addEntry('Invalid payment. Requested '.$pkg->config('CURRENCY_SYMBOL')." ".$order_total.', got '.$pkg->config('CURRENCY_SYMBOL').$_POST['amount']);
					$floatval = floatval($_POST["amount"]);
					$order_total_float = floatval($order_total);

					$debug = array(
						"condition1" => (floatval($_POST["amount"]) >= $order_total),
						"condition2" => (floatval($_POST["amount"]) >= $o->getOrderTotal()),
						"condition3" => ($floatval >= $order_total),
						"condition4" => ($order_total <= $floatval),
						"condition5" => ($order_total == $floatval),
						"condition6" => ($order_total >= $floatval),
						"condition7" => ($order_total > $floatval),
						"condition8" => ($floatval >= $order_total_float),
						"condition9" => ($x100_amount >= $x100_total),
						"condition10" => $x100_amount."" === $x100_total."",
						"x100_amount" => $x100_amount,
						"x100_total" => $x100_total,
						"floatval" => $floatval,
						"amount_posted" => $_POST["amount"],
						"amount_posted_float" => floatval($_POST["amount"]),
						"amount_order" => $o->getOrderTotal(),
						"order_total" => $order_total,
						"order_total_float" => $order_total_float,
					);
					Log::addEntry(var_export($debug, true), "worldpay");
				}

			} else {
				Log::addEntry('Received order notification with unknown order: '.$orderID);
			}
		} else {
			Log::addEntry("Invalid callback", "worldpay");
		}

		$title = t("Payment response processing error");
		if ($result == "cancelled") {
			$title = t("Order cancelled");
		} elseif ($result == "complete") {
			$title = t("Order complete");
		}

		Loader::packageElement("payment_result/header", "worldpay", array(
			"title" => $title,
			"result" => $result,
		));

		echo "<wpdisplay item=banner><br />\n"; // THIS PART IS REQUIRED BY WorldPay API

		Loader::packageElement("payment_result/footer", "worldpay", array(
			"result" => $result,
		));

		exit;
	}

	public function form() {
		$pkg = Package::getByHandle('core_commerce');
		$o = CoreCommerceCurrentOrder::get();
		$checkouth = Loader::helper('checkout/step', 'core_commerce');

		$next_checkout_step = $checkouth->getNextCheckoutStep();
		$payment_checkout_step = $checkouth->getCheckoutStep();

		$eh = Loader::helper('encryption');

		# WorldPay fields
		$fields = array();

		// instId - mandatory
		$fields["instId"] = $pkg->config('PAYMENT_METHOD_WORLDPAY_INSTID');

		// cartId - mandatory
		$fields["cartId"] = $o->getOrderID();

		// amount - mandatory
		$fields["amount"] = rtrim(rtrim( sprintf("%.2f", $o->getOrderTotal()), "0"), "."); // 5.00 => 5. => 5

		// currency - mandatory
		$fields["currency"] = $pkg->config('PAYMENT_METHOD_WORLDPAY_CURRENCY') == "" ? "GBP" : $pkg->config('PAYMENT_METHOD_WORLDPAY_CURRENCY');

		// desc - optional
		$fields["desc"] = "Order #" . $o->getOrderID(). " at ". BASE_URL;

		// email - optional (shopper's email)
		$fields["email"] = $o->getOrderEmail();

		// name - optional
		$fields["name"] = $o->getAttribute('billing_first_name') . " " . $o->getAttribute('billing_last_name');

		// address1
		$fields['address1'] = $o->getAttribute('billing_address')->getAddress1();
		// address2
		$fields['address2'] = $o->getAttribute('billing_address')->getAddress2();
		// address3

		// town
		$fields["town"] = $o->getAttribute('billing_address')->getCity();

		// region
		$fields["region"] = $o->getAttribute('billing_address')->getStateProvince();

		// postcode
		$fields["postcode"] = $o->getAttribute('billing_address')->getPostalCode();

		// country
		$fields["country"] = $o->getAttribute('billing_address')->getCountry();

		$fields["fixContact"] = 1;
		$fields["hideContact"] = 1;

		// authMode
		$fields["authMode"] = "A";

		// MC_callback - callback URL
		$fields["MC_callback"] = $this->action('notify_complete');

		// setting action URL (different for live & test mode)
		if ($pkg->config('PAYMENT_METHOD_WORLDPAY_TESTMODE') == 'Y') {
			$this->set('action', 'https://secure-test.worldpay.com/wcc/purchase');
			$fields["testMode"] = "100"; // should be above zero
			$fields["name"] = $pkg->config("PAYMENT_METHOD_WORLDPAY_TESTMODE_RESULT");
		} else {
			$this->set('action', 'https://secure.worldpay.com/wcc/purchase');
		}

		// signatureFields
		// signature
		$md5_secret = $pkg->config('PAYMENT_METHOD_WORLDPAY_MD5_SECRET');
		if (!empty($md5_secret)) {
			$md5_secret = $eh->decrypt($md5_secret);
		}

		if (!empty($md5_secret)) {
			$fields["signatureFields"] = 'amount:currency:cartId';
			$fields["signature"] = md5(implode(":", array(
				$md5_secret,
				$fields["amount"],
				$fields["currency"],
				$fields["cartId"],
			)));
		}

		$this->set('fields', $fields);
	}

	public function save() {
		$pkg = Package::getByHandle('core_commerce');
		$eh = Loader::helper('encryption');

		// installation ID
		$pkg->saveConfig("PAYMENT_METHOD_WORLDPAY_INSTID", $this->post('PAYMENT_METHOD_WORLDPAY_INSTID'));

		// test/live mode
		$pkg->saveConfig("PAYMENT_METHOD_WORLDPAY_TESTMODE", $this->post('PAYMENT_METHOD_WORLDPAY_TESTMODE'));

		// payment response password
		$pkg->saveConfig("PAYMENT_METHOD_WORLDPAY_RESPONSE_PW", $eh->encrypt($this->post('PAYMENT_METHOD_WORLDPAY_RESPONSE_PW')));

		// md5 secret for transactions
		$pkg->saveConfig("PAYMENT_METHOD_WORLDPAY_MD5_SECRET", $eh->encrypt($this->post('PAYMENT_METHOD_WORLDPAY_MD5_SECRET')));

		// test mode result
		// optional values: REFUSED, AUTHORISED, ERROR, CAPTURED
		$testmode_result = $this->post('PAYMENT_METHOD_WORLDPAY_TESTMODE_RESULT');
		if (!in_array($testmode_result, $this->getTestModeOptions())) {
			$testmode_result = "AUTHORIZED";
		}
		$pkg->saveConfig("PAYMENT_METHOD_WORLDPAY_TESTMODE_RESULT", $testmode_result);
	}

	private function getTestModeOptions() {
		return array(
			"REFUSED" => "REFUSED",
			"AUTHORISED" => "AUTHORISED",
			"ERROR" => "ERROR",
			"CAPTURED" => "CAPTURED",
			);
	}

	private function getCurrencies() {
		return array(
			"ARS" => "Nuevo Argentine Peso",
			"AUD" => "Australian Dollar",
			"BRL" => "Brazilian Real",
			"CAD" => "Canadian Dollar",
			"CHF" => "Swiss Franc",
			"CLP" => "Chilean Peso",
			"CNY" => "Yuan Renminbi",
			"COP" => "Colombian Peso",
			"CZK" => "Czech Koruna",
			"DKK" => "Danish Krone",
			"EUR" => "Euro",
			"GBP" => "Pound Sterling",
			"HKD" => "Hong Kong Dollar",
			"HUF" => "Hungarian Forint",
			"IDR" => "Indonesian Rupiah",
			"JPY" => "Japanese Yen",
			"KES" => "Kenyan Shilling",
			"KRW" => "South-Korean Won",
			"MXP" => "Mexican Peso",
			"MYR" => "Malaysian Ringgit",
			"NOK" => "Norwegian Krone",
			"NZD" => "New Zealand Dollar",
			"PHP" => "Philippine Peso",
			"PLN" => "New Polish Zloty",
			"PTE" => "Portugese Escudo",
			"SEK" => "Swedish Krone",
			"SGD" => "Singapore Dollar",
			"SKK" => "Slovak Koruna",
			"THB" => "Thai Baht",
			"TWD" => "New Taiwan Dollar",
			"USD" => "US Dollars",
			"VND" => "Vietnamese New Dong",
			"ZAR" => "South African Rand",
		);
	}

}