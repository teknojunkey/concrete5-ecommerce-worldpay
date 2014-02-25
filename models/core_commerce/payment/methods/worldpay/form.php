<?php   $form = Loader::helper('form'); ?>

<?php   foreach($fields as $key => $value) { ?>

	<input type="hidden" name="<?php  echo $key?>" value="<?php  echo $value?>" />

<?php   } ?>

 <?php  // t("Click 'Next' to proceed to WorldPay to finish your order."); ?> 