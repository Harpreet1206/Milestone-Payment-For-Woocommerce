<?php
/**
 * Order details Summary
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!$order = wc_get_order($order_id)) {
    return;
}

?>

<p><?php esc_html_e('Partial payment details', 'deposits-partial-payments-for-woocommerce') ?></p>
<div style="overflow: auto;">
  <table class="woocommerce-table  awcdp_deposits_summary">
    <thead>
      <tr>
        <th><?php esc_html_e('ID', 'deposits-partial-payments-for-woocommerce'); ?> </th>
        <th><?php esc_html_e('Payment', 'deposits-partial-payments-for-woocommerce'); ?> </th>
        <th><?php esc_html_e('Payment method', 'deposits-partial-payments-for-woocommerce'); ?> </th>
        <th><?php esc_html_e('Amount', 'deposits-partial-payments-for-woocommerce'); ?> </th>
        <th><?php esc_html_e('Paid', 'deposits-partial-payments-for-woocommerce'); ?> </th>
        <th><?php esc_html_e('Balance', 'deposits-partial-payments-for-woocommerce'); ?> </th>
        <th><?php esc_html_e('Status', 'deposits-partial-payments-for-woocommerce'); ?> </th>
      </tr>
    </thead>
    <tbody>
      <?php
      $order_iddd = $order->get_id();
      $package_update = get_post_meta( $order_iddd,'_package_update',true);
      // update_post_meta( $order_iddd,'_package_update','upgraded');

      $s = 0;
      foreach ($schedule as $timestamp => $payment) {
        $date = '';
        if (isset($payment['title'])) {
          $date = $payment['title'];
        } else {
          if (!is_numeric($timestamp)) {
            $date = '-';
          } else {
            $date = date_i18n(wc_date_format(), $timestamp);
          }
        }
        $date = apply_filters('awcdp_partial_payment_title', $date, $payment);
        $payment_order = false;
        if (isset($payment['id']) && !empty($payment['id'])) $payment_order = wc_get_order($payment['id']);
        if (!$payment_order) continue;
        $gateway = $payment_order ? $payment_order->get_payment_method_title() : '-';
        $payment_id = $payment_order ? '<a href="' . esc_url($payment_order->get_edit_order_url()) . '">' . $payment_order->get_order_number() . '</a>' : '-';
        $status = $payment_order ? wc_get_order_status_name($payment_order->get_status()) : '-';
        $amount =  $payment_order->get_total() - $payment_order->get_total_refunded();
        $price_args = array('currency' => $payment_order->get_currency());
        $remaining =  (isset($payment['remaining']) ? $payment['remaining'] : 0);
        if ($status != "Completed") {
          $remaining_pay = $amount + $remaining;
          $payed = 0;
        } else {
          $payed = $amount;
          $remaining_pay = 0;
          $gateway = (!empty($gateway) ? $gateway : "Wallet Payment");
        }
        if (empty($package_update)) {
          ?>
          <tr>
            <td><?php echo wp_kses_post( $payment_id); ?></td>
            <td><?php echo wp_kses_post( $date); ?></td>
            <td><?php echo wp_kses_post( $gateway); ?></td>
            <td><?php echo wp_kses_post( wc_price($amount, $price_args)); ?></td>
            <td><?php echo wp_kses_post( wc_price($payed)); ?></td>
            <td><?php echo wp_kses_post( wc_price($remaining_pay, $price_args)); ?></td>
            <td><?php echo wp_kses_post( $status); ?></td>
          </tr>
          <?php
        } else if($package_update == "downgraded"){
          $total      =  (isset($payment['total']) ? $payment['total'] : 0);
          $new_amount =  (isset($payment['new_amount']) ? $payment['new_amount'] : 0);
          $old_amount =  (isset($payment['old_amount']) ? $payment['old_amount'] : 0);
          $remaining  =  (isset($payment['remaining']) ? $payment['remaining'] : 0);
          if ($remaining > 0) {
            $payed = $new_amount;
            $remain_pay = 0;
          }
          if ($remaining == 0) {
            $payed = $new_amount - $total;
            $remain_pay = $total;
          }
          if ($status != "Completed") {
            $remain_pay = $new_amount - $payed;
          }
          if ($status == "Completed") {
            $payed = $new_amount;
            $remain_pay = 0;
          }
          ?>
          <tr>
            <td><?php echo wp_kses_post( $payment_id); ?></td>
            <td><?php echo wp_kses_post( $date); ?></td>
            <td><?php echo wp_kses_post( $gateway); ?></td>
            <td><?php echo wp_kses_post( wc_price($new_amount, $price_args)); ?></td>
            <td><?php echo wp_kses_post( wc_price($payed)); ?></td>
            <td><?php echo wp_kses_post( wc_price($remain_pay, $price_args)); ?></td>
            <td><?php echo wp_kses_post( $status); ?></td>
          </tr>
          <?php
        } else if($package_update == "upgraded"){
          $total      =  (isset($payment['total']) ? $payment['total'] : 0);
          $new_amount =  (isset($payment['new_amount']) ? $payment['new_amount'] : 0);
          $old_amount =  (isset($payment['old_amount']) ? $payment['old_amount'] : 0);
          $remaining  =  (isset($payment['remaining']) ? $payment['remaining'] : 0);

          if ($status !== "Completed" && $new_amount != $total) {
            $payed = $new_amount - $total;
            $remain_pay = $total;
            $total = $new_amount;
          } else {
            $remain_pay = $total;
          }
          if ($status == "Completed"){
            $total = $new_amount;
            $payed = $new_amount;
            $remain_pay = 0;
          }

          /*if ($remaining > 0) {
            $total = $total + ($new_amount - $old_amount);
            $remain_pay = $remaining;
          }
          else{
            $remain_pay = $total;
            $total = $new_amount;
          }*/

          ?>
          <tr>
            <td><?php echo wp_kses_post( $payment_id); ?></td>
            <td><?php echo wp_kses_post( $date); ?></td>
            <td><?php echo wp_kses_post( $gateway); ?></td>
            <td><?php echo wp_kses_post( wc_price($total, $price_args)); ?></td>
            <td><?php echo wp_kses_post( wc_price($payed)); ?></td>
            <td><?php echo wp_kses_post( wc_price($remain_pay, $price_args)); ?></td>
            <td><?php echo wp_kses_post( $status); ?></td>
          </tr>
          <?php
        }
      }
      ?>
    </tbody>
  </table>
</div>


		  <?php 
		  $balance_text = esc_html__('Make balance payment :', 'deposits-partial-payments-for-woocommerce');
		  $balance_text = apply_filters('awcdp_balance_payment_text',$balance_text);
		  
		  $actions = wc_get_account_orders_actions( $order );
			if ( ! empty( $actions ) ) {
				foreach ( $actions as $key => $action ) {
					if( $key == 'pay' ){
						echo '<div class="awcdp_balance_pay ">';
						echo '<p>' . $balance_text . '<a href="' . esc_url( $action['url'] ) . '" class="button ' . sanitize_html_class( $key ) . '">' . esc_html( $action['name'] ) . '</a> </p>';
						echo '</div>';
					}
				}
			}
		  ?> 

