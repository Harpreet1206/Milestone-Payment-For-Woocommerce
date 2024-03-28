<?php
if (!defined('ABSPATH')) {
    exit;
}


    if ( $schedule ) {
      ?>
      <h2><?php esc_html_e('Partial payment details', 'deposits-partial-payments-for-woocommerce') ?></h2>
      <table cellspacing="0" cellpadding="6" style="color:#636363;border:1px solid #e5e5e5;vertical-align:middle;width:100%;margin-bottom: 40px;"  >
        <thead>
          <tr>
            <th style="border: 1px solid #e5e5e5;" ><?php esc_html_e('ID', 'deposits-partial-payments-for-woocommerce'); ?> </th>
            <th style="border: 1px solid #e5e5e5;" ><?php esc_html_e('Payment', 'deposits-partial-payments-for-woocommerce'); ?> </th>
            <th style="border: 1px solid #e5e5e5;" ><?php esc_html_e('Payment method', 'deposits-partial-payments-for-woocommerce'); ?> </th>
            <th style="border: 1px solid #e5e5e5;" ><?php esc_html_e('Amount', 'deposits-partial-payments-for-woocommerce'); ?> </th>
            <th style="border: 1px solid #e5e5e5;" ><?php esc_html_e('Status', 'deposits-partial-payments-for-woocommerce'); ?> </th>
          </tr>
        </thead>
        <tbody>
          <?php
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
            $payment_id = $payment_order ? $payment_order->get_order_number() : '-';
            $status = $payment_order ? wc_get_order_status_name($payment_order->get_status()) : '-';
            $amount =  $payment_order->get_total() - $payment_order->get_total_refunded();
            $price_args = array('currency' => $payment_order->get_currency());
            ?>
            <tr>
              <td style="border: 1px solid #e5e5e5;" ><?php echo wp_kses_post( $payment_id); ?></td>
              <td style="border: 1px solid #e5e5e5;" ><?php echo wp_kses_post( $date); ?></td>
              <td style="border: 1px solid #e5e5e5;" ><?php echo wp_kses_post( $gateway); ?></td>
              <td style="border: 1px solid #e5e5e5;" ><?php echo wp_kses_post( wc_price($amount, $price_args)); ?></td>
              <td style="border: 1px solid #e5e5e5;" ><?php echo wp_kses_post( $status); ?></td>
            </tr>
            <?php
          }
          ?>
        </tbody>
      </table>
      <?php
    }
