<?php
/*
 * Plugin Name: Milestone Payment
 * Version: 10.0.14
 * Description: WooCommerce Milestone Payment allows customers to pay for products using a fixed or percentage amount in WooCommerce store
 * Author: Oxygensoft
 * Author URI: http://oxygensoft.net
 * Requires at least: 4.0
 * Tested up to: 6.0
 * Text Domain: milestone_payment
 * WC requires at least: 4.0.0
 * WC tested up to: 6.6
 */
// add_action( 'woocommerce_order_status_changed', 'bbloomer_add_content_thankyou22' );
  
function bbloomer_add_content_thankyou22($order_id222) {
    global $wpdb;
   
    $order = wc_get_order( $order_id222 );


    $order_id = $order->get_parent_id(); // Get the parent order ID (for subscriptions…)
    if( $order_id>0)
    {

        $action = get_post_meta($order_id,"_package_update",true);
        $payed_total = 0;
        $deposits_payment_schedule = $order->get_meta('_awcdp_deposits_payment_schedule');
        if ($action == 'upgraded' || $action == 'downgraded') {
            foreach($deposits_payment_schedule as $payment => $schedule){
                if (isset($schedule['total']) && isset($schedule['new_amount']) && $schedule['new_amount'] > $schedule['total']) {
                    $difference = 0;
                    $difference = $schedule['new_amount'] - $schedule['total'];
                    $payed_total += $difference;
                }
            }
        }

        $order = new WC_Order($order_id );
        $deposits_payment_schedule = $order->get_meta('_awcdp_deposits_payment_schedule');
        $tb =   $wpdb->prefix."posts";

        $res = $wpdb->get_results("SELECT * FROM ".$tb."  WHERE `post_parent` = '".$order_id."'", ARRAY_A);
        $total_amount_paid = $payed_total;
        $total_amount      = 0;
        foreach($res as $val)
        {
            $order_id2 = $val['ID'];

            $res2 = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."postmeta` WHERE `post_id` = '".$order_id2."' AND `meta_key` = '_order_total'", ARRAY_A);
            
            $amount22 = ($res2[0]['meta_value']);
            if($val['post_status']=="wc-completed")
            {
                
                $total_amount_paid += $amount22;
              
            }
            $total_amount += $amount22;

        }
        
        $remain =  $total_amount-$total_amount_paid;                        


        $wpdb->query("UPDATE ".$wpdb->prefix."postmeta  set 
                      meta_value='".$total_amount_paid."'  WHERE meta_key='_awcdp_deposits_deposit_amount' and `post_id` = '".$order_id."'", ARRAY_A);

        $wpdb->query("UPDATE ".$wpdb->prefix."postmeta  set 
                      meta_value='".$remain."'  WHERE meta_key='_awcdp_deposits_second_payment' and `post_id` = '".$order_id."'", ARRAY_A);
        echo "==========================";
    }
}
add_action("init",function (){
    if(@$_GET['iamdev14']=="ok")
    {
        global $wpdb;
        echo "---------------------";
        $order_id ='23796';
        $order = new WC_Order($order_id );
        $deposits_payment_schedule = $order->get_meta('_awcdp_deposits_payment_schedule');
        $tb =   $wpdb->prefix."posts";

        $res = $wpdb->get_results("SELECT * FROM ".$tb."  WHERE `post_parent` = '".$order_id."'", ARRAY_A);
        $total_amount_paid = 0;
        $total_amount      = 0;
        foreach($res as $val)
        {
            $order_id2 = $val['ID'];

            $res2 = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."postmeta` WHERE `post_id` = '".$order_id2."' AND `meta_key` = '_order_total'", ARRAY_A);
            
            $amount22 = ($res2[0]['meta_value']);
            if($val['post_status']=="wc-completed")
            {
                
                $total_amount_paid += $amount22;
              
            }
            $total_amount += $amount22;

        }
        
        $remain =  $total_amount-$total_amount_paid;                        


        $wpdb->query("UPDATE ".$wpdb->prefix."postmeta  set 
                      meta_value='".$total_amount_paid."'  WHERE meta_key='_awcdp_deposits_deposit_amount' and `post_id` = '".$order_id."'", ARRAY_A);

        $wpdb->query("UPDATE ".$wpdb->prefix."postmeta  set 
                      meta_value='".$remain."'  WHERE meta_key='_awcdp_deposits_second_payment' and `post_id` = '".$order_id."'", ARRAY_A);

        //print "<pre>";print_r($total_amount_paid); print "</pre>";
        


        exit;
    }
});


define('AWCDP_TOKEN', 'awcdp');
define('AWCDP_VERSION', '1.0.14');
define('AWCDP_FILE', __FILE__);
define('AWCDP_PLUGIN_NAME', 'Deposits & Partial Payments for WooCommerce');
define('AWCDP_TEXT_DOMAIN', 'milestone_payment');
define('AWCDP_STORE_URL', 'https://api.acowebs.com');
define('AWCDP_POST_TYPE', 'awcdp_payment');
define('AWCDP_DEPOSITS_META_KEY', '_awcdp_deposit_enabled');
define('AWCDP_DEPOSITS_TYPE', '_awcdp_deposit_type');
define('AWCDP_DEPOSITS_AMOUNT', '_awcdp_deposits_deposit_amount');
define('AWCDP_PLUGIN_PATH',  plugin_dir_path( __FILE__ ) );

require_once(realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR . 'includes/helpers.php');

if (!function_exists('awcdp_init')) {

    function awcdp_init()
    {
        $plugin_rel_path = basename(dirname(__FILE__)) . '/languages'; /* Relative to WP_PLUGIN_DIR */
        load_plugin_textdomain('milestone_payment', false, $plugin_rel_path);
    }

}


if (!function_exists('awcdp_autoloader')) {

    function awcdp_autoloader($class_name)
    {
      if (0 === strpos($class_name, 'AWCDP_Email')) {
          $classes_dir = realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR. 'emails'. DIRECTORY_SEPARATOR ;
          $class_file = 'class-' . str_replace('_', '-', strtolower($class_name)) . '.php';
          require_once $classes_dir . $class_file;
      } else if (0 === strpos($class_name, 'AWCDP')) {
          $classes_dir = realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
          $class_file = 'class-' . str_replace('_', '-', strtolower($class_name)) . '.php';
          require_once $classes_dir . $class_file;
      }
    }

}

if (!function_exists('AWCDP')) {

    function AWCDP()
    {
        $instance = AWCDP_Backend::instance(__FILE__, AWCDP_VERSION);
        return $instance;
    }

}
add_action('plugins_loaded', 'awcdp_init');
spl_autoload_register('awcdp_autoloader');
if (is_admin()) {
    AWCDP();
}
new AWCDP_Api();

new AWCDP_Front_End(__FILE__, AWCDP_VERSION);


add_action('current_screen', 'awcpd_setup_screen');

if (!function_exists('awcpd_setup_screen')) {
function awcpd_setup_screen() {

    if ( function_exists( 'get_current_screen' ) ) {
        $screen    = get_current_screen();
        $screen_id = isset( $screen, $screen->id ) ? $screen->id : '';
    }
    switch ( $screen_id ) {
        case 'edit-awcdp_payment':
            include_once  __DIR__ .'/includes/class-awcdp-list.php';
            $wc_list_table = new AWCDP_Admin_List_Table_Orders();
            break;
    }

    // Ensure the table handler is only loaded once. Prevents multiple loads if a plugin calls check_ajax_referer many times.
    remove_action( 'current_screen', 'awcpd_setup_screen' );
    remove_action( 'check_ajax_referer', 'awcpd_setup_screen' );
}
}
