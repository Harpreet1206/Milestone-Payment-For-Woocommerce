<?php
if (!defined('ABSPATH')) {
    exit;
}

do_action('woocommerce_email_header', $email_heading, $email);
?>

<p><?php echo wp_kses_post( wpautop( wptexturize( __( $email_text, 'deposits-partial-payments-for-woocommerce' )))); ?></p>

<?php
$awcdp_gs = get_option('awcdp_general_settings');
$remaining_payable = (isset($awcdp_gs['remaining_payable']) && $awcdp_gs['remaining_payable'] == 1) ? 'no' : 'yes';

if ($order->has_status('partially-paid') && $remaining_payable == 'yes'){
    ?>
    <p><?php echo wp_kses_post( wpautop( wptexturize( __( $payment_text, 'deposits-partial-payments-for-woocommerce' ) ) ) ); ?></p>
    <?php
}

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
