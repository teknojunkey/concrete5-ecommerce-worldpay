<?php  
$form = Loader::helper('form');
?>
<div>
<div><?php echo t("Please configure the following options in your WorldPay account:"); ?></div>
<table class="entry-form">
<tr>
<td class="subheader"><?php echo t("Option"); ?></td>
<td class="subheader"><?php echo t("Value"); ?></td>
<td class="subheader"><?php echo t("Comment"); ?></td>
</tr>
<tr>
<td><b><?php echo t("Payment Response URL"); ?></b></td>
<td>&lt;wpdisplay item=MC_callback&gt;</td>
<td><em><?php echo t("Copy the value entirely, including &lt; and &gt;"); ?></em></td>
</tr>
<tr>
<td><b><?php echo t("Payment Response enabled?"); ?></b></td>
<td><?php echo t("YES"); ?></td>
<td>&nbsp;</td>
</tr>
<tr>
<td><b><?php echo t("Enable the Shopper Response"); ?></b></td>
<td><?php echo t("YES"); ?></td>
<td>&nbsp;</td>
</tr>
</table>
</div>

<table class="entry-form" cellspacing="1" cellpadding="0">

<tr>
	<td class="subheader"><?php
	echo t('WorldPay Installation ID');
	?> <span class="ccm-required">*</span></td>
</tr>
<tr>
	<td><?php
	echo $form->text('PAYMENT_METHOD_WORLDPAY_INSTID', $PAYMENT_METHOD_WORLDPAY_INSTID);
	?></td>
</tr>

<tr>
	<td class="subheader"><?php
	echo t('Currency');
	?> <span class="ccm-required">*</span></td>
</tr>
<tr>
	<td><?php
	echo $form->select('PAYMENT_METHOD_WORLDPAY_CURRENCY', $worldpay_currencies, $PAYMENT_METHOD_WORLDPAY_CURRENCY);
	?></td>
</tr>

<tr>
	<td class="subheader"><?php
	echo t('Payment Response password');
	?></td>
</tr>
<tr>
	<td><?php
	echo $form->text('PAYMENT_METHOD_WORLDPAY_RESPONSE_PW', $PAYMENT_METHOD_WORLDPAY_RESPONSE_PW);
	?></td>
</tr>

<tr>
	<td class="subheader"><?php
	echo t('MD5 secret for transactions');
	?></td>
</tr>
<tr>
	<td><?php
	echo $form->text('PAYMENT_METHOD_WORLDPAY_MD5_SECRET', $PAYMENT_METHOD_WORLDPAY_MD5_SECRET);
	echo '<div>';
	echo t("Note: <em>in your WorldPay account settings, leave SignatureFields empty or set to <b>amount:currency:cartId</b></em>");
	echo '</div>';
	?></td>
</tr>

<tr>
	<td class="subheader"><?php   echo t('Test mode'); ?></td>
</tr>
<tr>
	<td><?php
	echo $form->checkbox("PAYMENT_METHOD_WORLDPAY_TESTMODE", "Y", $PAYMENT_METHOD_WORLDPAY_TESTMODE);
	echo '<div id="worldpay_testmode_options"';
	if ($PAYMENT_METHOD_WORLDPAY_TESTMODE != "Y")
		echo ' style="display: none;"';
	echo '>';
	echo t("Transaction result: ");
	echo $form->select('PAYMENT_METHOD_WORLDPAY_TESTMODE_RESULT', $testmode_options, $PAYMENT_METHOD_WORLDPAY_TESTMODE_RESULT);
	echo '</div>';
	?>
	</td>
</tr>

</table>

<script type="text/javascript">
<!-- 
$().ready(function() {
	$('input[name="PAYMENT_METHOD_WORLDPAY_TESTMODE"]').change(function() {
		if ($(this).attr("checked")) {
			$('#worldpay_testmode_options').css('display', 'block');
		} else {
			$('#worldpay_testmode_options').css('display', 'none');
		}
	});
});
// -->
</script>