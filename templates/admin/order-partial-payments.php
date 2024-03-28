<?php
if (!defined('ABSPATH')) {
    exit;
}

//oxygensoft email

if ($order && $order->get_type() != AWCDP_POST_TYPE ) {
    $payment_schedule = $order->get_meta('_awcdp_deposits_payment_schedule', true);
    if (!is_array($payment_schedule) || empty($payment_schedule)) {
      ?>
      <div>
        <h4><?php esc_html_e('No payment schedule found.', 'deposits-partial-payments-for-woocommerce'); ?></h4>
      </div>
      <?php
    } else {
      ?>
      <table style="width:100%; text-align:left;" >
        <thead>
          <tr>
            <th><?php esc_html_e('ID', 'deposits-partial-payments-for-woocommerce'); ?> </th>
            <th><?php esc_html_e('Payment', 'deposits-partial-payments-for-woocommerce'); ?> </th>
            <th><?php esc_html_e('Payment method', 'deposits-partial-payments-for-woocommerce'); ?> </th>
            <th><?php esc_html_e('Amount', 'deposits-partial-payments-for-woocommerce'); ?> </th>
            <th><?php esc_html_e('Paid', 'deposits-partial-payments-for-woocommerce'); ?> </th>
            <th><?php esc_html_e('Balance', 'deposits-partial-payments-for-woocommerce'); ?> </th>
            <th><?php esc_html_e('Status', 'deposits-partial-payments-for-woocommerce'); ?> </th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php
          $order_iddd = $order->get_id();
          $package_update = get_post_meta( $order_iddd,'_package_update',true);
          // update_post_meta( $order_iddd,'_package_update','upgraded');

          $s = 0;
          foreach ($payment_schedule as $timestamp => $payment) {
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
                <?php
                if(isset($payment['new_amount'])){
                  $new_amount = $payment['new_amount'];
                  ?>
                  <td><?php echo wp_kses_post( wc_price($new_amount, $price_args)); ?></td>
                  <td><?php echo wp_kses_post( wc_price($new_amount - $amount)); ?></td>
                  <?php
                } else {
                  ?>
                  <td><?php echo wp_kses_post( wc_price($amount, $price_args)); ?></td>
                  <td><?php echo wp_kses_post( wc_price($payed)); ?></td>
                  <?php
                }
                ?>
                <td><?php echo wp_kses_post( wc_price($remaining_pay, $price_args)); ?></td>
                <td><?php echo wp_kses_post( $status); ?></td>
                <td>
              
                  <a href="<?php echo esc_url($payment_order->get_edit_order_url()); ?>" class="button" > <?php esc_html_e('View', 'deposits-partial-payments-for-woocommerce'); ?> </a>

                  <?php 
                  if($status=="Pending payment" and $s==0){
                    ?>
                    <a href="javascript:void(0);" class="button button-primary send-email" 
                      order_id="<?php echo $order->get_id(); ?>"
                      price = "<?php echo $amount; ?>"
                      link_for_payment = ""
                    > <?php esc_html_e('Send Email', 'deposits-partial-payments-for-woocommerce'); ?> </a>
                    <?php
                    $s++;
                  }
                  
                  ?>
                  

                </td>
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
                <td>
              
                  <a href="<?php echo esc_url($payment_order->get_edit_order_url()); ?>" class="button" > <?php esc_html_e('View', 'deposits-partial-payments-for-woocommerce'); ?> </a>

                  <?php 
                  if($status=="Pending payment" and $s==0){
                    ?>
                    <a href="javascript:void(0);" class="button button-primary send-email" 
                      order_id="<?php echo $order->get_id(); ?>"
                      price = "<?php echo $amount; ?>"
                      link_for_payment = ""
                    > <?php esc_html_e('Send Email', 'deposits-partial-payments-for-woocommerce'); ?> </a>
                    <?php
                    $s++;
                  }
                  
                  ?>
                  

                </td>
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
                <td>
              
                  <a href="<?php echo esc_url($payment_order->get_edit_order_url()); ?>" class="button" > <?php esc_html_e('View', 'deposits-partial-payments-for-woocommerce'); ?> </a>

                  <?php 
                  if($status=="Pending payment" and $s==0){
                    ?>
                    <a href="javascript:void(0);" class="button button-primary send-email" 
                      order_id="<?php echo $order->get_id(); ?>"
                      price = "<?php echo $amount; ?>"
                      link_for_payment = ""
                    > <?php esc_html_e('Send Email', 'deposits-partial-payments-for-woocommerce'); ?> </a>
                    <?php
                    $s++;
                  }
                  
                  ?>
                  

                </td>
              </tr>
              <?php
            }
          }
          ?>
        </tbody>
      </table>
      <?php
      $user_id = $order->get_user_id();
      $billing_email  =   $order->get_billing_email();
      if (!$user_id || $user_id <= 0) {
        $user = get_user_by("email",$billing_email);
        $user_id = isset($user->data->ID) ? $user->data->ID : 0;
      }
      $wallet_balance = woo_wallet()->wallet->get_wallet_balance($user_id,"number");
      $status = get_post_status($order_iddd);
      if (count($payment_schedule) > 0 || $status == 'wc-partially-paid') {
        if ($user_id > 0 && $wallet_balance > '0.01') { ?>
          <button type="button" class="button button-primary pay_by_wallet_btn" order_id="<?php echo $order_iddd; ?>">Pay by Wallet (<?php echo wc_price($wallet_balance, $price_args); ?>)</button>
          &nbsp;
          <?php
        }
        ?>
        <button type="button" class="button button-primary recalculate_order" order_id="<?php echo $order_iddd; ?>">Recalculate Order</button>
        <?php
      }
    }
}


?>

<script>
jQuery(document).ready(function () {
    jQuery('.pay_by_wallet_btn').click(function() {
      var order_id = jQuery(this).attr("order_id");
      if (order_id > 0) {
        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            data: {action:"order_pay_by_wallet",order_id:order_id},
            type: 'POST',
            success: function (response) {
              var data = JSON.parse(response);
              if (data.success == 1) {
                alert(data.msg);
                window.location.reload();
              }
            }
        });
      }
    })
    jQuery('.recalculate_order').click(function() {
      var order_id = jQuery(this).attr("order_id");
      if (order_id > 0) {
        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            data: {action:"recalculate_order_amounts",order_id:order_id},
            type: 'POST',
            success: function (response) {
              var data = JSON.parse(response);
              if (data.status == 1) {
                alert(data.messages);
                window.location.reload();
              }
            }
        });
      }
    })
    var my_link  = jQuery(".wc-order-status a").attr('href');
    if(my_link!="")
    {
      jQuery(".send-email").attr('link_for_payment',my_link);
    }
 

    function reload_payments_metabox() {
        jQuery('#awcdp_deposits_partial_payments').block({
          message: null,
          overlayCSS: { background: '#fff', opacity: 0.6 }
        });

        var data = {
          action: 'awcdp_reload_payments_metabox',
          order_id: woocommerce_admin_meta_boxes.post_id,
          security: '<?php echo wp_create_nonce('awcdp-deposits-partial-payments-refresh'); ?>'
        };

        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            data: data,
            type: 'POST',
            success: function (response) {
              if (response.success) {
                jQuery('#awcdp_deposits_partial_payments div.inside').empty().append(response.data.html);
                jQuery('#woocommerce-order-items').unblock();
                jQuery('#awcdp_deposits_partial_payments').unblock();
              }
            }
        });
    }

    jQuery(document.body).on ('order-totals-recalculate-complete', function(){
      window.setTimeout(function(){
        reload_payments_metabox();
      },1500);
    });

});
</script>


<script type="text/javascript">
jQuery(document).ready(function() {
  jQuery('.send-email').on('click',function(){
      var price = jQuery(this).attr('price');
     
      var order_id = jQuery(this).attr('order_id');
    
      var link_for_payment = jQuery(this).attr('link_for_payment');
      jQuery.ajax({
          type: "POST",
          url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
          data: { 
              action: 'email_for_milestone_payment',
              price: price,
              order_id: order_id,
              link_for_payment: link_for_payment
          },
          cache: false,
          success: function(data)
          {
              var response = JSON.parse(data);
              if (response.message != undefined && response.message != 'undefined' && response.message != '' && response.message != null) {
                  alert(response.message)
              }
          }
      });
  })
})
</script>
