<?php
defined('C5_EXECUTE') or die(_("Access Denied."));

class WorldpayPackage extends Package {

	protected $pkgHandle = 'worldpay';
	protected $appVersionRequired = '5.4.0.5';
	protected $pkgVersion = '1.0';

	public function getPackageDescription() {
		return t("WorldPay payment method for concrete5 eCommerce.");
	}

	public function getPackageName() {
		return t("WorldPay");
	}

	public function install() {
		$pkg = parent::install();
		Loader::model('payment/method', 'core_commerce');
		CoreCommercePaymentMethod::add('worldpay', t('WorldPay'), 1, NULL, $pkg);
	}
}