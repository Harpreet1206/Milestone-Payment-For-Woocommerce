<?php
include_once('order_upgradation.php');
include_once('test_package_update.php');
add_filter( 'woocommerce_price_trim_zeros', '__return_true' );
add_filter("woo_wallet_payment_is_available",function (){

    if(WC()->cart && is_checkout() && isset(WC()->cart->deposit_info['deposit_enabled']) && WC()->cart->deposit_info['deposit_enabled'] == true && is_user_logged_in())
    {
        $current_wallet_balance = woo_wallet()->wallet->get_wallet_balance( get_current_user_id(), 'edit' );
        if ( WC()->cart->total > 0 ) {
            foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                // $total_amount_with_tax = WC()->cart->get_total();
                // $total_amount_with_tax = (float) get_woowallet_cart_total();
                $total_amount_with_tax = WC()->cart->cart_contents_total + WC()->cart->tax_total;
                $selected_milestones = $cart_item['selected_milestones'];
                $selected_milestones = unserialize($selected_milestones);
                $percentage = (isset($selected_milestones[0]['percentage']) ? $selected_milestones[0]['percentage'] : 0);
                
                if ($percentage > 0) {
                    $deposit_amount = ($total_amount_with_tax / 100) * $percentage;
                    if ($current_wallet_balance >= $deposit_amount) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
                break;
            }
        } else {
            return false;
        }
    } else {
        return false;
    }
},1);


add_action( 'woocommerce_after_order_notes', 'wc_add_message' );
function wc_add_message () {


    
    /*if(WC()->cart && is_checkout() && isset(WC()->cart->deposit_info['deposit_enabled']) && WC()->cart->deposit_info['deposit_enabled'] == true && is_user_logged_in())
    {
        $current_wallet_balance = woo_wallet()->wallet->get_wallet_balance( get_current_user_id(), 'edit' );
        if ( WC()->cart->total > 0 ) {
            foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                // $total_amount_with_tax = WC()->cart->get_total();
                $total_amount_with_tax = (float) get_woowallet_cart_total();
                $selected_milestones = $cart_item['selected_milestones'];
                $selected_milestones = unserialize($selected_milestones);
                $percentage = (isset($selected_milestones[0]['percentage']) ? $selected_milestones[0]['percentage'] : 0);
                if ($percentage > 0) {
                    $deposit_amount = ($total_amount_with_tax / 100) * $percentage;
                    if ($current_wallet_balance >= $deposit_amount) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
                break;
            }
        } else {
            return false;
        }
    } else {
        return false;
    }*/

    if(isset($_GET['iamdev90']) and $_GET['iamdev90']!="ok")
    {

    }
    else
    {

    
        ?>
        <style>
            .wallet-pay-partial{ display: none !important; }
        </style>
        <?php
    }

}



add_action('wp_ajax_email_for_milestone_payment', 'email_for_milestone_payment');
function email_for_milestone_payment()
{
    $response = array();
    if (isset($_POST['price']) && $_POST['price'] > 0 && isset($_POST['order_id']) && $_POST['order_id'] > 0 and $_POST['link_for_payment']!="") {
        $body = "Pay Remaining Milestone";
        $order_id = $_POST['order_id'];
        $order = wc_get_order( $order_id );

        $billing_email  =   $order->get_billing_email();
        $link_for_pay   =   $_POST['link_for_payment'];
        $content = email_partial_payment_table($order_id);
        // Milestone Payment Email Body
        $email_content = '<h1>Please pay milestone of '.$_POST['price'].' by clicking <a href="'.$link_for_pay.'" target="_blank">here</a></h1></br>'.$content;
        
        if (!empty($billing_email)) {
            add_filter( 'wp_mail_from_name', 'my_mail_from_name' );
            wp_mail( $billing_email , 'Milestone Payment', $email_content, array('Content-Type: text/html; charset=UTF-8'));
            remove_filter('wp_mail_from_name', 'my_mail_from_name' ); 
            $response['message'] = 'Email has been sent successfully!';
        } else {
            $response['message'] = 'Email not found!';
        }
    } else {
        $response['message'] = 'Price not found!';
    }
    echo json_encode($response);
    die();
}



function msp_cart_item_name( $name, $cart_item, $cart_item_key ) {
    //ob_start();
    //$aa = ob_get_clean();

    if( isset( $cart_item['awcdp_deposit']['enable'] ) && $cart_item['awcdp_deposit']['enable'] ) {

        $milestones = unserialize(@$cart_item['selected_milestones']);
        $un_milestones = unserialize(@$cart_item['unselected_milestones']);
        
        $name .= '<br><span class="mil_price_table_span" style="font-weight:bold"></span><br>';
        $name .= '<table class="mil_price_table" cellpadding="0" cellspacing="0">
                    <tr>
                        <th>Milestone</th>
                    
                        <th>Percentage</th>
                        <th>Amount</th>
                    </tr>';
        foreach($milestones as $value){
            $name .= '<tr>
                        <td>'.$value['name'].'</td>
                        
                        <td>'.$value['percentage'].'%</td>
                        <td>'.get_woocommerce_currency_symbol().'&nbsp;'.$value['price'].'</td>

                    <tr>';
        }
        foreach($un_milestones as $value){
            $name .= '<tr>
                        <td>'.$value['name'].'</td>
                        
                        <td>'.$value['percentage'].'%</td>
                        <td>'.get_woocommerce_currency_symbol().'&nbsp;'.$value['price'].'</td>
                    <tr>';
        }
        $name .= '</table>';
    }
    return $name;
    
}
//add_filter( 'woocommerce_cart_item_name', 'msp_cart_item_name', 10, 3 );

add_filter("woocommerce_get_item_data","msp_product_add_on_display_cart",10,3);
function msp_product_add_on_display_cart($data, $cart_item)
{
    if( isset( $cart_item['awcdp_deposit']['enable'] ) && $cart_item['awcdp_deposit']['enable'] ) {

        $quantity = $cart_item['quantity'];
        $milestones = unserialize(@$cart_item['selected_milestones']);
        $un_milestones = unserialize(@$cart_item['unselected_milestones']);

        $cart_total = WC()->cart->total;
        $cart_total = $cart_total - WC()->cart->get_taxes_total();
        
        //$content = '<br><span class="mil_price_table_span" style="font-weight:bold"> Milestones:</span><br>';
        $content = '&nbsp;<br><br><table class="mil_price_table" cellpadding="0" cellspacing="0">';
        foreach($milestones as $value){
            $mile_price = 0;
            $mile_price = ($cart_total / 100) * $value['percentage'];
            $mile_price = round($mile_price, wc_get_price_decimals());
            $content .= '<tr>
                        <td>'.$value['name'].'</td>
                        <td>'.$value['percentage'].'%</td>
                        <td>'.get_woocommerce_currency_symbol().($mile_price ).'</td>
                    <tr>';
        }
        foreach($un_milestones as $value){
            $mile_price = 0;
            $mile_price = ($cart_total / 100) * $value['percentage'];
            $mile_price = round($mile_price, wc_get_price_decimals());
            $content .= '<tr>
                        <td>'.$value['name'].'</td>
                        <td>'.$value['percentage'].'%</td>
                        <td>'.get_woocommerce_currency_symbol().($mile_price ).'</td>
                    <tr>';
        }
        $content .= '</table>';
        /*ob_start();
        echo '<pre>';
        print_r($cart_item['aaaaaaaaaa']);
        $content = ob_get_clean();*/
        $data[] = [
            "name" => "Milestones",
            "value" => $content,
        ];
    }
    return $data;
}


/*add_action( 'add_meta_boxes', 'msp_add_meta_boxes' );
function msp_add_meta_boxes(){
    add_meta_box( 'mv_other_fields', __('Milestones','woocommerce'), 'msp_milestone_table_data', 'shop_order', 'normal', 'core' );
}
function msp_milestone_table_data(){

    global $post;
    $order = wc_get_order($post->ID);
    foreach ($order->get_items() as $key => $item) {
        $item_id = $key;
        $product_id = $item["product_id"];
        $item_meta_data = $item->get_meta_data();
        $formatted_meta_data = $item->get_formatted_meta_data( '_', true );
        
        $awcdp_deposit_meta = $item->get_meta('awcdp_deposit_meta');
        if (isset($awcdp_deposit_meta['enable']) && $awcdp_deposit_meta['enable']) {        
            $selected_milestones = unserialize($item->get_meta('_selected_milestones'));
            $unselected_milestones = unserialize($item->get_meta('_unselected_milestones'));








            ?>
            <table class="wp-list-table widefat fixed striped table-view-list posts">
                <thead>
                    <tr>
                        <th>Sr</th>
                        <th>Milestone</th>
                        <th>Price</th>
                        <th>Percentage</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 1;
                    if (is_array($selected_milestones)) {
                        foreach ($selected_milestones as $sm) {
                            ?>
                            <tr>
                                <td><?php echo $count; ?></td>
                                <td><?php echo $sm['name']; ?></td>
                                <td><?php echo get_woocommerce_currency_symbol().' '.$sm['price']; ?></td>
                                <td><?php echo $sm['percentage']; ?>%</td>
                                <td>Paid</td>
                                <td>&nbsp;</td>
                            </tr>
                            <?php
                            $count++;
                        }
                    }
                    if (is_array($unselected_milestones)) {
                        foreach ($unselected_milestones as $um) {
                            ?>
                            <tr>
                                <td><?php echo $count; ?></td>
                                <td><?php echo $um['name']; ?></td>
                                <td><?php echo get_woocommerce_currency_symbol().' '.$um['price']; ?></td>
                                <td><?php echo $um['percentage']; ?>%</td>
                                <td>Unpaid</td>
                                <td>
                                    <!-- <button type="button" class="button button-primary send-email" price="<?php echo $um['price']; ?>" name="<?php echo $um['name']; ?>" order_id="<?php echo $post->ID; ?>" item_id="<?php echo $item_id; ?>" product_id="<?php echo $product_id; ?>">
                                    Send Email
                                    </button> -->
                                </td>
                            </tr>
                            <?php
                            $count++;
                        }
                    } 
                    ?>
                </tbody>
            </table>
            <?php 
        }
    }
}*/

add_action('admin_head','custom_js_code');
function custom_js_code(){
    ?>
    <style type="text/css">
        .milestone_error_msg{
            background: #fff;
            border: 1px solid #c3c4c7;
            border-left-width: 4px;
            padding: 1px 12px;
            border-left-color: #d63638;
            margin-top: 10px;
        }
    </style>
    <script type="text/javascript">
        function remove_direct(elem) {
            elem.closest('tr').remove();
        }
        function add_row_more(a,variation_id=0) {
            jQuery('#table_append_'+variation_id).append(a);
        }
        function check_milestone_count() {
            if (jQuery('#_awcdp_deposit_enabled').val() == "yes") {

                var total = 0
                var temp = 0
                jQuery("input[name*='milestones[price_indian][]']").each(function () {
                    temp = jQuery(this).val()
                    total = parseFloat(total) + parseFloat(temp)
                })
                if (total != 100) {
                    jQuery('.milestone_error_msg').html('<p>Milestone percentage should be 100%</p>');
                    jQuery('.milestone_error_msg').show()
                    jQuery('#publish').attr('type','button')
                    return false
                } else {
                    jQuery('.milestone_error_msg').html('')
                    jQuery('.milestone_error_msg').hide()
                    jQuery('#publish').attr('type','submit')
                    return true
                }
            } else {
                jQuery('.milestone_error_msg').html('')
                jQuery('.milestone_error_msg').hide()
                jQuery('#publish').attr('type','submit')
                return true
            }
        }
        jQuery(document).ready(function() {
            if(jQuery("input[name*='milestones[price_indian][]']").length > 0){
                jQuery('#publish').addClass('milestone_prod_update_btn')
                check_milestone_count()
            }
            jQuery('.milestone_prod_update_btn[type=button]').click(function(event) {
                if(!check_milestone_count()){
                    alert('Milestone percentage should be 100%')
                }
            })
            jQuery("#_awcdp_deposit_enabled").change(function() {
                check_milestone_count()
            })
            // jQuery("input[name*='milestones[price_indian][]']").keyup(function() {
            // jQuery(document).on("keyup change","input[name*='milestones[price_indian][]']",function() {
            jQuery(document).on("keyup","input[name*='milestones[price_indian][]']",function() {
                check_milestone_count()
            })
            jQuery(".custom_update_package").on("click",function() {
                var name = jQuery('#updated_product_name').val();
                var price = jQuery('#updated_package_price').val();
                if(!name && !price){
                    alert('Line Item Name and Price is required');
                } else if(!name){
                    alert('Line Item Name is required');
                } else if (!price) {
                    alert('Line Item Price is required');
                } else if(confirm("Are you Really wants to add extra line item?")){

                    jQuery.ajax({
                        type:"POST",
                        url:"<?php echo admin_url( 'admin-ajax.php' ); ?>",
                        data:{
                            action: "custom_update_order_fee",
                            order_id:"<?php echo @$_GET['post']; ?>",
                            updated_product_name: name,
                            updated_package_price: price
                        },
                        success: function(data)
                        {
                            var response_data = JSON.parse(data)

                            alert(response_data.messages)
                            if(response_data.status == 1){
                                window.location.reload();
                            }
                        }
                    })
                }
            })

            let searchParams = new URLSearchParams(window.location.search);
            var post_id = searchParams.get('post');
            if (post_id > 0) {
                jQuery("#order_line_items .item").each(function(key,value){
                    if(key != 0){
                        var item_id = jQuery(this).attr('data-order_item_id');
                        jQuery(this).find(".wc-order-edit-line-item").append('<button type="button" onclick="delete_line_item('+post_id+','+item_id+')">Remove</button>');
                    }
                })
            }
        })
        function delete_line_item(post_id,item_id) {
            if(confirm("Are you Really wants to delete this line item?")){
                jQuery.ajax({
                    type:"POST",
                    url:"<?php echo admin_url( 'admin-ajax.php' ); ?>",
                    data:{
                        action: "custom_delete_order_fee",
                        order_id:"<?php echo @$_GET['post']; ?>",
                        item_id: item_id
                    },
                    success: function(data)
                    {
                        var response_data = JSON.parse(data)

                        alert(response_data.messages)
                        if(response_data.status == 1){
                            window.location.reload();
                        }
                    }
                })
            }
        }
    </script>
    <?php
}

add_action('wp_head','custom_css_code');
function custom_css_code(){
    ?>
    <style type="text/css">
        dt.variation-Milestones, 
        .woocommerce-mini-cart .variation-DepositAmount,
        .cart .variation-DepositAmount,
        .woocommerce-checkout .variation-DepositAmount,
        .woocommerce-mini-cart .variation-Milestones, 
        .woocommerce-mini-cart .variation-FuturePayments, 
        .woocommerce-checkout .variation-FuturePayments,
        .cart .variation-FuturePayments
        {
            display: none !important;
        }
        @media only screen and (max-width: 768px) {
            .awcdp-deposits-option{
                width: 100% !important;
            }
            .awcdp-radio-label{
                font-size: 12px !important;
            }
        }
    </style>
    <?php
    if (is_checkout()) { ?>
        <style type="text/css" media="screen">
            .mil_price_table td, .mil_price_table th {
                padding: 0px 3px !important;
                background-color: #fff !important;
                border: 0.5px solid black !important;
                font-size: 11px !important;
            }
            .mil_price_table, .mil_price_table_span ,dt.variation-Milestones, dd.variation-Milestones{
                display: none !important;
            }
            dt.variation-FuturePayments, dt.variation-DepositAmount,
            dd.variation-FuturePayments, dd.variation-DepositAmount,
            dd.variation-FuturePayments span, dd.variation-DepositAmount span
            {
                font-size: 12px !important;
            }
            .product-quantity{
                font-size: 13px !important;
            }
            .product-info small {
                margin-left: 6px !important;
            }
            .order-paid td span.woocommerce-Price-amount.amount {
                font-weight: bolder;
                font-size: 25px;
                color: #ef5537;
            }
        </style>
        <?php
    }
    if ( is_page( 'cart' ) || is_cart() ) { ?>
        <style type="text/css">
            @media only screen and (max-width: 768px) {
                .woocommerce table.shop_table_responsive tr td::before, .woocommerce-page table.shop_table_responsive tr td::before {
                    content: attr(data-title) !important;
                }
                .mil_price_table tr td, .mil_price_table tr th {
                    display: inline-block !important;
                    text-align: left !important;
                    padding: 8px 8px !important;
                    width: 100% !important;
                }
                .mil_price_table tr{
                    margin-bottom: -2px !important;
                }
                .mil_price_table tr td:first-child{
                    max-width: 63% !important;
                }
                .mil_price_table tr td:nth-child(2){
                    max-width: 15% !important;
                    margin-left: -4px !important;
                }
                .mil_price_table tr td:nth-child(3){
                    max-width: 22% !important;
                    margin-left: -4px !important;
                }
                .mil_price_table tbody{
                    text-align: left !important;
                }
                dd.variation-Milestones{
                    padding-left: 0 !important;
                }
                .mil_price_table{
                    width: 100% !important;
                }
            }
        </style>
        <?php
    }
}

add_filter( 'woocommerce_product_data_tabs', 'milestone_options_product_data_tab', 99 , 1 );
function milestone_options_product_data_tab( $product_data_tabs ) {
    $product_data_tabs['shipping-costs'] = array(
        'label' => 'Milestones', // translatable
        'target' => 'milestone_options', // translatable
    );
    return $product_data_tabs;
}

add_action( 'woocommerce_product_data_panels', 'milestone_options_product_data_fields' );
function milestone_options_product_data_fields() {
    global $post;
    $post_id = $post->ID;

    $milestones = unserialize(get_post_meta( $post_id, '_milestones', true ));
    $milestones_count = isset($milestones['milestone']) && is_array($milestones['milestone']) ? count($milestones['milestone']) : 0;
    ?>
    <style type="text/css">
        .table_1 input { width: 98% !important;  }
        .options_group{ padding: 12px; }
    </style>
    <div id='milestone_options' class='panel woocommerce_options_panel'>
        <div class='options_group'>
            <?php 
              woocommerce_wp_select(array(
                  'id' => '_awcdp_deposit_enabled',
                  'label' => esc_html__('Enable Milestone ', 'deposits-partial-payments-for-woocommerce'),
                  'options' => array(
                    'no' => esc_html__('No', 'deposits-partial-payments-for-woocommerce'),
                    'yes' => esc_html__('Yes', 'deposits-partial-payments-for-woocommerce'),
                  ),
                  'description' => esc_html__('Allow customers to pay a deposit for this product.', 'deposits-partial-payments-for-woocommerce'),
                  'desc_tip' => true,
                ));
            ?>
            <table style="padding: 0;">
                <tbody id="table_append_<?php echo $post_id; ?>" class="table_1">
                    <tr>
                        <th>Milestone</th>
                        <th>Percentage</th>
                      
                        <th>&nbsp;</th>
                    </tr>
                    <?php
                    for ($i = 0; $i < $milestones_count; $i++) {
                        ?>
                        <tr>
                            <td>
                                <input type="text" name="milestones[milestone][]" value="<?php echo @$milestones['milestone'][$i] ?>"/>
                            </td>
                            <td>
                                <input type="text" name="milestones[price_indian][]" value="<?php echo @$milestones['price_indian'][$i] ?>"/> 
                            </td>
                           
                            <td style="cursor: pointer;">
                                <span onclick="remove_direct(this)">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2 block mx-auto"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                </span>
                            </td>
                        </tr>
                        <?php
                    }
                    if ($milestones_count == 0) {
                        ?>
                        <tr>
                            <td><input type="text" name="milestones[milestone][]"/></td>
                            <td><input type="text" name="milestones[price_indian][]"/></td>
                       
                            <td style="cursor: pointer;">
                                <span onclick="remove_direct(this)">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2 block mx-auto"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                </span>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
            <?php ob_start(); ?>
                <tr>
                    <td>
                        <input type="text" name="milestones[milestone][]"/>
                    </td>
                    <td>
                        <input type="text" name="milestones[price_indian][]"/>
                    </td>
                   
                    <td>
                        <span onclick="remove_direct(this)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2 block mx-auto"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                        </span>
                    </td>
                </tr>
            <?php 
                $output = ob_get_clean(); 
                $output = str_replace(array("\r", "\n"), '', $output);
                $output = preg_replace( "/\r|\n/", "", trim($output)); 
                $output = str_replace(array("\r\n", "\r", "\n"), "", $output);
                $output = str_replace(array("\r\n", "\r", "\n"), "", $output); 
                $output=str_replace(chr(10),'',$output);
                $output=str_replace(chr(13),'',$output);
                $output=str_replace("\r\n",'',$output);
            ?>
            <button type="button" class="button-primary" onclick="add_row_more('<?php echo htmlentities(str_replace("'",'`',$output)); ?>','<?php echo $post_id; ?>')">Add Item</button>
            <div class="milestone_error_msg" style="display: none;"></div>
        </div>
    </div>
    <?php
}


add_action( 'woocommerce_process_product_meta', 'save_milestone_options_field' );

function save_milestone_options_field( $post_id ) {
    if (isset($_POST['milestones']) && is_array($_POST['milestones']) && isset($_POST['_awcdp_deposit_enabled']) && $_POST['_awcdp_deposit_enabled'] !== 'no') {
        $milestones = $_POST['milestones'];
        if (isset($milestones['price_indian']) && is_array($milestones['price_indian']) && count($milestones['price_indian']) > 0) {
            $price_ar = $milestones['price_indian'];
            $total = 0;
            foreach($price_ar as $val){
                $total = $total + $val;
            }
            if ($total != 100) {
                $error_message = 'Milestone percentage should be 100%';
                $_SESSION['my_admin_notices'] .= '<div class="error"><p>'.$error_message.'</p></div>';
                update_post_meta( $post_id, '_awcdp_deposit_enabled', 'no');
            }
        } else {
            update_post_meta( $post_id, '_awcdp_deposit_enabled', 'no');
        }
        update_post_meta( $post_id, '_milestones', serialize($milestones) );
    }
}

function my_admin_notices(){
    if(!empty($_SESSION['my_admin_notices'])){
        print  $_SESSION['my_admin_notices'];
    }
    unset ($_SESSION['my_admin_notices']);
}
add_action( 'admin_notices', 'my_admin_notices' );

add_action( 'add_meta_boxes', 'mp_update_order_package_meta_boxes' );
if ( ! function_exists( 'mp_update_order_package_meta_boxes' ) )
{
    function mp_update_order_package_meta_boxes()
    {
        add_meta_box( 'mv_other_fields', __('Update Package','woocommerce'), 'mp_add_prod_field_for_package_update', 'shop_order', 'side', 'core' );
        add_meta_box( 'mp_custom_quantity_update', __('Update Quantity','woocommerce'), 'mp_add_prod_field_for_quantity_update', 'shop_order', 'side', 'core' );
        add_meta_box( 'mp_custom_package_update', __('Update Custom Package','woocommerce'), 'mp_prod_field_for_custom_package_update', 'shop_order', 'side', 'core' );
    }
}

function mp_prod_field_for_custom_package_update()
{
    global $post;
    $order_id = $post->ID;
    // $updated_product_name = get_post_meta($order_id,'updated_product_name',true);
    // $updated_package_price = get_post_meta($order_id,'updated_package_price',true);
    //$already_updated = get_post_meta($order_id,'_order_package_updated',true);
    $already_updated = get_post_meta($order_id,'_order_fee_updated',true);

    $status = get_post_status($order_id);
    
    // if ($already_updated != 1 && $status !== 'wc-completed') {    
    // if ($status !== 'wc-completed') {    
        ?>
        <label for="updated_product_name">New Line Item Name</label></br>
        <input type="text" name="updated_product_name" id="updated_product_name" value=""></br>
        
        <label for="updated_package_price">New Line Item Price</label></br>
        <input type="text" name="updated_package_price" id="updated_package_price" value="">
        <button type="button" class="button button-primary custom_update_package" style="margin-top: 10px;">Custom Package Update</button>
        <?php
    // }
}

function mp_add_prod_field_for_quantity_update()
{
    global $post;
    $order_id = $post->ID;
    $update_package_varient_id = get_post_meta($order_id,'update_package_varient_id',true);
    $order = new WC_Order($order_id );
    $items = $order->get_items();
    $product_id = 0;
    $product_variation_id = 0;
    foreach ( $items as $item ) {
        $product_name = $item->get_name();
        $product_id = $item->get_product_id();
        $product_variation_id = $item->get_variation_id();
        break;
    }
    $already_updated = get_post_meta($order_id,'_order_package_updated',true);
    $status = get_post_status($order_id);
    $product = wc_get_product( $product_id );
    $available_variations = $product->get_available_variations();

    $sold_individually = get_post_meta( $product_id, '_sold_individually',true);
    
    if ($product_id != 0 && $product_variation_id != 0 && $already_updated != 1 && is_array($available_variations) && count($available_variations) && $status !== 'wc-completed') 
    {
        if ($sold_individually !== 'yes') {
            ?>
            <label for="updated_order_quantity">New Quantity</label></br>
            <input type="number" name="updated_order_quantity" id="updated_order_quantity">
            <?php
        }
    }

}

// Adding Meta field in the meta container admin shop_order pages
if ( ! function_exists( 'mp_add_prod_field_for_package_update' ) )
{
    function mp_add_prod_field_for_package_update()
    {
        global $post;
        $order_id = $post->ID;
        $update_package_varient_id = get_post_meta($order_id,'update_package_varient_id',true);
        $order = new WC_Order($order_id );
        $items = $order->get_items();
        $product_id = 0;
        $product_variation_id = 0;
        foreach ( $items as $item ) {
            $product_name = $item->get_name();
            $product_id = $item->get_product_id();
            $product_variation_id = $item->get_variation_id();
            break;
        }
        $already_updated = get_post_meta($order_id,'_order_package_updated',true);
        $status = get_post_status($order_id);
        $product = wc_get_product( $product_id );
        $available_variations = $product->get_available_variations();

        $sold_individually = get_post_meta( $product_id, '_sold_individually',true);
        
        if ($product_id != 0 && $product_variation_id != 0 && $already_updated != 1 && is_array($available_variations) && count($available_variations) && $status !== 'wc-completed') 
        {
            //print_r( $available_variations);
            ?>
            <label>Select Package</label>
            <select name="update_package_varient_id">
                <option value="">Select Variation</option>
                <?php 
                foreach($available_variations as $variation){ 
                    $selected = '';
                    /*if(!empty($update_package_varient_id)){
                        if($update_package_varient_id == $variation['variation_id']){
                            $selected = 'selected';
                        }
                    }
                    else if($product_variation_id == $variation['variation_id']){
                        $selected = 'selected';
                    }*/
                    if ($product_variation_id !== $variation['variation_id']) {
                        $attr = isset($variation['attributes']['attribute_pa_duration']) ? $variation['attributes']['attribute_pa_duration'] : (isset($variation['attributes']['attribute_packages']) ? $variation['attributes']['attribute_packages'] : '');
                        ?>
                        <option value="<?php echo $variation['variation_id']; ?>" <?php echo $selected; ?>><?php echo  $attr." (".get_woocommerce_currency_symbol().$variation['display_price'].")"; ?></option>
                        <?php
                    }
                }
                ?>
            </select>
            <?php
        }

    }
}

add_action( 'save_post', 'mv_save_wc_order_other_fields', 10, 1 );
function mv_save_wc_order_other_fields( $post_id ) {
    $package_update = get_post_meta($post_id,'_order_package_updated',true);
    if(isset($_POST['update_package_varient_id']) and !empty($_POST['update_package_varient_id']) and $package_update != 1){
        $order = wc_get_order($post_id);
        if( $order && $order->get_type() == 'shop_order'){
            $new_args = array(
                'post_type'      => 'awcdp_payment',
                'order'          => 'ASC',
                'post_parent'    => $post_id,
                'post_status'    => 'any'
            );
            $new_query = new WP_Query( $new_args);
            
            if ($new_query->have_posts() ) {
                wp_reset_query();

                // if($order->get_meta('update_package_varient_id', true) !== $_POST['update_package_varient_id']){
                    update_post_meta($post_id,'previous_package_varient_id',$order->get_meta('update_package_varient_id', true) );
                    update_post_meta($post_id,'update_package_varient_id',$_POST['update_package_varient_id']);
                    msp_update_order_package($post_id,$_POST['update_package_varient_id']);
                    update_post_meta($post_id,'_order_package_updated',1);
                // }
            }

        }
    }
    else if(isset($_POST['updated_order_quantity']) and $_POST['updated_order_quantity'] > 0 and $package_update != 1)
    {
        $order = wc_get_order($post_id);
        if( $order && $order->get_type() == 'shop_order'){
            $new_args = array(
                'post_type'      => 'awcdp_payment',
                'order'          => 'ASC',
                'post_parent'    => $post_id,
                'post_status'    => 'any'
            );
            $new_query = new WP_Query( $new_args);
            
            if ($new_query->have_posts() ) {
                wp_reset_query();

                update_post_meta($post_id,'updated_order_quantity',$_POST['updated_order_quantity']);
                msp_update_order_quantity($post_id,$_POST['updated_order_quantity']);
                update_post_meta($post_id,'_order_package_updated',1);
            }

        }
    }
}

function msp_update_order_package($order_id,$variation_id){
    global $wpdb;

    $sub_order_total_ar = array();
    $res        = $wpdb->get_results(" SELECT * from ".$wpdb->prefix."posts where post_parent='".$order_id."' ",ARRAY_A);

    foreach($res as $rs){
        $id = $rs['ID'];
        $order_total = get_post_meta($id,'_order_total',true);
        $sub_order_total_ar[$id] = $order_total;
    }
    $payed_total = get_post_meta($order_id,'_awcdp_deposits_deposit_amount',true);

    $variation = wc_get_product($variation_id); 
    $product_title = $variation->get_formatted_name();
    $duration = $variation->get_attribute( 'pa_duration' );
    $variation_price = get_post_meta($variation_id, '_price', true);
    $order = new WC_Order($order_id );
    $deposits_payment_schedule = $order->get_meta('_awcdp_deposits_payment_schedule');
    $mile_count = (is_array($deposits_payment_schedule) ? count($deposits_payment_schedule) : 0);
    $billing_email  =   $order->get_billing_email();
    $previous_total = ($order->get_total() > 0 ? $order->get_total() : 0);

    $o_user_id   = $order->get_user_id();

    if (!$o_user_id || $o_user_id <= 0) {

        $user = get_user_by("email",$billing_email);
        if (isset($user->data->ID)) {
            $o_user_id = $user->data->ID;
        } else {
            $o_user_id = register_user_using_email($billing_email);
        }
        // update_post_meta($order_id,'_customer_user',$o_user_id);
    }
    
    $product_id = 0;
    $new_amount_total         = $variation_price;
    $quanity = 1;
    $items = $order->get_items();
    foreach ( $items as $item ) {
        $item->update_meta_data('pa_duration', $duration);
        $product_id     = $item->get_product_id();
        
        $quanity        = $item->get_quantity();
        $quanity = $quanity > 0 ? $quanity : 1;
        $new_amount_total = $new_amount_total * $quanity;
        
        $item->set_name($product_title);
        $item->set_variation($variation_id);
        $item->update_meta_data('_variation_id', $variation_id);
        $item->set_subtotal($new_amount_total);
        // $previous_total = $item->get_total();
        
        
        $item->set_total($new_amount_total);
        break;
    }
    $order->calculate_taxes();
    $order->calculate_totals();
    $order->save();

    update_post_meta($order_id,"_awcdp_deposits_deposit_amount",$payed_total);
    // $tax_total = $order->get_total_tax();
    // $new_amount_total = $new_amount_total + $tax_total;
    $new_amount_total = $order->get_total();

    $milestones = msp_milestones_ar_fn($product_id,$new_amount_total);
    $milestones_names = msp_milestones_names_ar_fn($product_id);

    $currency = get_woocommerce_currency_symbol();

    if ($previous_total > $new_amount_total) {
        //Downgrade
        downgrade_order($milestones,$milestones_names,$order_id,$o_user_id,$billing_email,$sub_order_total_ar);
        $subject = "Order No. $order_id is Downgraded";
        $milestone_table = email_partial_payment_table($order_id);
        // Downgradation Email Body
        $email_content = "Your Order against Order No. $order_id is Downgraded from $currency $previous_total to $currency $new_amount_total. Your current Order Total is $currency $new_amount_total.</br>".$milestone_table;
        
        add_filter( 'wp_mail_from_name', 'my_mail_from_name' );
        wp_mail( $billing_email , $subject, $email_content, array('Content-Type: text/html; charset=UTF-8'));
        remove_filter('wp_mail_from_name', 'my_mail_from_name' ); 

        $note = __("Order is Downgraded from $currency$previous_total to $currency$new_amount_total.");
        $order->add_order_note( $note );

    } else if($previous_total < $new_amount_total){
        //upgrade
        upgrade_order($milestones,$milestones_names,$order_id,$o_user_id,$billing_email);

        $subject = "Order No. $order_id is Upgraded";
        $milestone_table = email_partial_payment_table($order_id);
        // Upgradation Email Body
        $email_content = "Your Order against Order No. $order_id is Upgraded from $currency $previous_total to $currency $new_amount_total. Your current Order Total is $currency $new_amount_total.</br>".$milestone_table;
        
        add_filter( 'wp_mail_from_name', 'my_mail_from_name' );
        wp_mail( $billing_email , $subject, $email_content, array('Content-Type: text/html; charset=UTF-8'));
        remove_filter('wp_mail_from_name', 'my_mail_from_name'  );
        
        $note = __("Order is Upgraded from $currency$previous_total to $currency$new_amount_total.");
        $order->add_order_note( $note );
    
    }
    wp_cache_flush();
}

function register_user_using_email($email) {
    $pswd = randomPassword();
    $user_id = wp_create_user($email,$pswd,$email);
    return $user_id;
}

function randomPassword() {
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < 8; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}

function my_mail_from_name( $name ){
    return get_option( 'blogname' );
}

function email_partial_payment_table($order_id) {
    $schedule = get_post_meta($order_id,"_awcdp_deposits_payment_schedule",true);
    ob_start();
    ?>
    <h2>Partial payment details</h2>
    <table cellspacing="0" cellpadding="6" style="color:#636363;border:1px solid #e5e5e5;vertical-align:middle;width:100%;margin-bottom: 40px;">
        <thead>
            <tr>
                <th style="border: 1px solid #e5e5e5;" >ID</th>
                <th style="border: 1px solid #e5e5e5;" >Payment</th>
                <th style="border: 1px solid #e5e5e5;" >Amount</th>
                <th style="border: 1px solid #e5e5e5;" >Paid</th>
                <th style="border: 1px solid #e5e5e5;" >Balance</th>
                <th style="border: 1px solid #e5e5e5;" >Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $package_update = get_post_meta( $order_id,'_package_update',true);
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
                    $payment_id = $payment_order ? $payment_order->get_order_number() : '-';
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
                            <td style="border: 1px solid #e5e5e5;"><?php echo $payment_id; ?></td>
                            <td style="border: 1px solid #e5e5e5;"><?php echo $date; ?></td>
                            <td style="border: 1px solid #e5e5e5;"><?php echo wc_price($amount, $price_args); ?></td>
                            <td style="border: 1px solid #e5e5e5;"><?php echo wc_price($payed); ?></td>
                            <td style="border: 1px solid #e5e5e5;"><?php echo wc_price($remaining_pay, $price_args); ?></td>
                            <td style="border: 1px solid #e5e5e5;"><?php echo $status; ?></td>
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
                            <td style="border: 1px solid #e5e5e5;"><?php echo $payment_id; ?></td>
                            <td style="border: 1px solid #e5e5e5;"><?php echo $gateway; ?></td>
                            <td style="border: 1px solid #e5e5e5;"><?php echo wc_price($new_amount, $price_args); ?></td>
                            <td style="border: 1px solid #e5e5e5;"><?php echo wc_price($payed); ?></td>
                            <td style="border: 1px solid #e5e5e5;"><?php echo wc_price($remain_pay, $price_args); ?></td>
                            <td style="border: 1px solid #e5e5e5;"><?php echo $status; ?></td>
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
                        ?>
                        <tr>
                            <td style="border: 1px solid #e5e5e5;"><?php echo $payment_id; ?></td>
                            <td style="border: 1px solid #e5e5e5;"><?php echo $date; ?></td>
                            <td style="border: 1px solid #e5e5e5;"><?php echo wc_price($total, $price_args); ?></td>
                            <td style="border: 1px solid #e5e5e5;"><?php echo wc_price($payed); ?></td>
                            <td style="border: 1px solid #e5e5e5;"><?php echo wc_price($remain_pay, $price_args); ?></td>
                            <td style="border: 1px solid #e5e5e5;"><?php echo $status; ?></td>
                        </tr>
                        <?php
                    }
                }
                ?>
        </tbody>
    </table>
    <?php
    return ob_get_clean();

}

function msp_update_order_quantity($order_id,$new_quanity){
    global $wpdb;

    $sub_order_total_ar = array();
    $res        = $wpdb->get_results(" SELECT * from ".$wpdb->prefix."posts where post_parent='".$order_id."' ",ARRAY_A);

    foreach($res as $rs){
        $id = $rs['ID'];
        $order_total = get_post_meta($id,'_order_total',true);
        $sub_order_total_ar[$id] = $order_total;
    }

    $order = new WC_Order($order_id );
    $deposits_payment_schedule = $order->get_meta('_awcdp_deposits_payment_schedule');

    $payed_total = get_post_meta($order_id,'_awcdp_deposits_deposit_amount',true);
    
    $billing_email  =   $order->get_billing_email();
    $o_user_id   = $order->get_user_id();

    if (!$o_user_id || $o_user_id <= 0) {

        $user = get_user_by("email",$billing_email);
        if (isset($user->data->ID)) {
            $o_user_id = $user->data->ID;
        } else {
            $o_user_id = register_user_using_email($billing_email);
        }
        // update_post_meta($order_id,'_customer_user',$o_user_id);
    }

    $previous_total = 0;
    $previous_total = ($order->get_total() > 0 ? $order->get_total() : 0);

    $new_quanity = $new_quanity > 0 ? $new_quanity : 1;
    $product_id = 0;
    $new_amount_total         = 0;
    $items = $order->get_items();
    
    foreach ( $items as $key => $item ) {
        $order_item_id  = $key;
        $product_id     = $item->get_product_id();
        $price_p = get_post_meta($product_id, '_price', true);

        $variation_id     = $item->get_variation_id();
        $price_v = get_post_meta($variation_id, '_price', true);
        if ($price_v > 0) {
            $price = $price_v;
        } else {
            $price = $price_p;
        }
        break;
    }
    $new_subtotal = ( int ) $new_quanity * $price;
    $items[$order_item_id]->set_quantity( $new_quanity );
    $items[$order_item_id]->set_subtotal( $new_subtotal );
    $items[$order_item_id]->set_total( $new_subtotal );


    $order->calculate_taxes();
    $order->calculate_totals();
    $order->save();

    update_post_meta($order_id,"_awcdp_deposits_deposit_amount",$payed_total);

    // $tax_total = $order->get_total_tax();
    // $new_amount_total = $new_amount_total + $tax_total;
    
    $new_amount_total = $order->get_total();

    $milestones = msp_milestones_ar_fn($product_id,$new_amount_total);
    $milestones_names = msp_milestones_names_ar_fn($product_id);

    if ($previous_total > $new_amount_total) {
        //Downgrade
        downgrade_order($milestones,$milestones_names,$order_id,$o_user_id,$billing_email,$sub_order_total_ar);
        $subject = "Order No. $order_id is Downgraded";
        $milestone_table = email_partial_payment_table($order_id);
        $body = "Your Order against Order No. $order_id is Downgraded from $currency $previous_total to $currency $new_amount_total. Your current Order Total is $currency $new_amount_total.</br>".$milestone_table;
        add_filter( 'wp_mail_from_name', 'my_mail_from_name' );
        wp_mail( $billing_email , $subject, $body, array('Content-Type: text/html; charset=UTF-8'));
        remove_filter('wp_mail_from_name', 'my_mail_from_name' ); 

        $note = __("Order is Downgraded from $currency$previous_total to $currency$new_amount_total.");
        $order->add_order_note( $note );

    } else if($previous_total < $new_amount_total){
        //upgrade
        upgrade_order($milestones,$milestones_names,$order_id,$o_user_id,$billing_email);

        $subject = "Order No. $order_id is Upgraded";
        $milestone_table = email_partial_payment_table($order_id);
        $body = "Your Order against Order No. $order_id is Upgraded from $currency $previous_total to $currency $new_amount_total. Your current Order Total is $currency $new_amount_total.</br>".$milestone_table;
        add_filter( 'wp_mail_from_name', 'my_mail_from_name' );
        wp_mail( $billing_email , $subject, $body, array('Content-Type: text/html; charset=UTF-8'));
        remove_filter('wp_mail_from_name', 'my_mail_from_name'  );
        
        $note = __("Order is Upgraded from $currency$previous_total to $currency$new_amount_total.");
        $order->add_order_note( $note );
    
    }
    wp_cache_flush();
}

function msp_milestones_ar_fn($product_id, $new_amount_total)
{
    $milestones_price_ar = array();

    $milestones = unserialize(get_post_meta( $product_id, '_milestones', true ));
    $milestones_count = isset($milestones['milestone']) && is_array($milestones['milestone']) ? count($milestones['milestone']) : 0;
    for ($i = 0; $i < $milestones_count; $i++) { 
        $percentage = $milestones['price_indian'][$i];
        $price = ($new_amount_total / 100) * $percentage;
        $price = round($price, wc_get_price_decimals());
        $milestones_price_ar[] = $price;
    }
    return $milestones_price_ar;
}

function msp_milestones_names_ar_fn($product_id)
{
    $milestones_name_ar = array();

    $milestones = unserialize(get_post_meta( $product_id, '_milestones', true ));
    $milestones_count = isset($milestones['milestone']) && is_array($milestones['milestone']) ? count($milestones['milestone']) : 0;
    for ($i = 0; $i < $milestones_count; $i++) { 
        $milestones_name_ar[] = $milestones['milestone'][$i];
    }
    return $milestones_name_ar;
}

add_action("init",function (){ 
    if (!session_id()) { 
        session_start(); 
    } 
});
add_action('wp_ajax_order_pay_by_wallet', 'order_pay_by_wallet_fn_test');
function order_pay_by_wallet_fn_test(){
    $response = array();
    if (isset($_POST['order_id']) && $_POST['order_id'] > 0) {
        $order_id       = $_POST['order_id'];

        global $wpdb;
        $order          = wc_get_order($order_id);

        $billing_email  = $order->get_billing_email();
        $user_id        = $order->get_user_id();

        if (!$user_id || $user_id <= 0) {
            $user = get_user_by("email",$billing_email);
            if (isset($user->data->ID)) {
                $user_id = $user->data->ID;
            } else {
                $user_id = register_user_using_email($billing_email);
            }
            // update_post_meta($order_id,'_customer_user',$user_id);
        }

        $wallet_balance = woo_wallet()->wallet->get_wallet_balance($user_id,"number");
        if ($wallet_balance > 0) {

            $milestones_ar = get_post_meta( $order_id, '_awcdp_deposits_payment_schedule', true);
            $milestone_ar = get_post_meta( $order_id, '_awcdp_deposits_payment_schedule', true);

            foreach ($milestone_ar as $key => $milestone) {
                $id = $milestone['id'];
                $title = $milestone['title'];
                $order_total = get_post_meta($id,"_order_total",true);
                $status = get_post_status($id);

                if($status != "wc-completed" && $wallet_balance > 0){
                    if ($wallet_balance >= $order_total) {
                        $description= "$title of order ".$order_id." payed";
                        if ($order_total > 0) {
                            $wallet_balance = $wallet_balance - $order_total;
                            woo_wallet()->wallet->debit($user_id, $order_total, $description);
                        }

                        $update_post = array(
                            'post_type' => 'awcdp_payment',
                            'ID' => $id,
                            'post_status'    => 'wc-completed'
                        );
                        wp_update_post($update_post);
                    } 
                    else {
                        $wallet_balance = $wallet_balance;
                        $new_total = $order_total - $wallet_balance;
                        if ($new_total > 0) {

                            $description= "$title of order ".$order_id." payed";
                            // woo_wallet()->wallet->debit($user_id, $wallet_balance, $description);
                            $wpdb->insert($wpdb->prefix.'woo_wallet_transactions', array(
                                'user_id' => $user_id,
                                'type' => 'debit',
                                'amount' => $wallet_balance,
                                'balance' => 0,
                                'currency' => 'USD',
                                'details' => $description,
                                'created_by' => $user_id
                            ));
                            update_user_meta( $user_id,'_current_woo_wallet_balance', 0);
                            
                            $wallet_balance = 0;
                            $milestones_ar[$key]['total'] = $new_total;
                            
                            if (!isset($milestones_ar[$key]['new_amount'])) {
                                $milestones_ar[$key]['new_amount'] = $order_total;
                            }

                            update_post_meta($order_id,"_awcdp_deposits_payment_schedule",$milestones_ar);
                            $res        = $wpdb->get_results(" SELECT * from ".$wpdb->prefix."woocommerce_order_items where order_id='".$id."' limit 0,10",ARRAY_A);
                            if (is_array($res) && count($res) > 0 && isset($res[0])) {
                                $order_item = $res[0];
                                $order_item_id = $order_item['order_item_id'];
                                
                                $wpdb->query(" 
                                            UPDATE `".$wpdb->prefix."woocommerce_order_itemmeta`
                                                SET `meta_value` = ".$new_total."
                                            WHERE `order_item_id` = '".$order_item_id."' 
                                                AND `meta_key` = '_line_total' 
                                            ");
                                update_post_meta($id,"_order_total",$new_total);
                            }
                        } else {
                            $update_post = array(
                                'post_type' => 'awcdp_payment',
                                'ID' => $id,
                                'post_status'    => 'wc-completed'
                            );
                            wp_update_post($update_post);
                        }
                    }
                }
            }



        }

        if ($order_id and $order_id > 0) {
            $order = wc_get_order(  $order_id );

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
            $total_amount = $order->get_total();
            $deposits_payment_schedule = $order->get_meta('_awcdp_deposits_payment_schedule');
            $tb =   $wpdb->prefix."posts";

            $res = $wpdb->get_results("SELECT * FROM ".$tb."  WHERE `post_parent` = '".$order_id."'", ARRAY_A);
            $total_amount_paid = $payed_total;
            foreach($res as $val)
            {
                $order_id2 = $val['ID'];

                $res2 = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."postmeta` WHERE `post_id` = '".$order_id2."' AND `meta_key` = '_order_total'", ARRAY_A);
                
                $amount22 = ($res2[0]['meta_value']);
                if($val['post_status']=="wc-completed"){
                    if ($action == 'upgraded' || $action == 'downgraded') {
                        foreach($deposits_payment_schedule as $payment => $schedule){
                            if (isset($schedule['id']) && isset($schedule['new_amount']) && $schedule['id'] == $order_id2 && $amount22 > $schedule['new_amount']) {
                                $total_amount_paid += $schedule['new_amount'];
                            } else if($schedule['id'] == $order_id2){
                                $total_amount_paid += $amount22;
                            }
                        }
                    } else {
                        $total_amount_paid += $amount22;
                    }
                }

            }
            
            $remain =  $total_amount-$total_amount_paid;                        

            $wpdb->query("UPDATE ".$wpdb->prefix."postmeta  set 
                          meta_value='".$total_amount_paid."'  WHERE meta_key='_awcdp_deposits_deposit_amount' and `post_id` = '".$order_id."'", ARRAY_A);

            $wpdb->query("UPDATE ".$wpdb->prefix."postmeta  set 
                          meta_value='".$remain."'  WHERE meta_key='_awcdp_deposits_second_payment' and `post_id` = '".$order_id."'", ARRAY_A);

        }
        wp_cache_flush();
        $response['success'] = 1;
        $response['msg'] = "Order Updated Successfully";
    } else {
        $response['error'] = 1;
        $response['msg'] = "Order ID not found";
    }
    echo json_encode($response);
    die();
}
function order_pay_by_wallet_fn()
{
    $response = array();
    if (isset($_POST['order_id']) && $_POST['order_id'] > 0) {

        $order_id       = $_POST['order_id'];
        $order          = wc_get_order($order_id);
        $user_id        = $order->get_user_id();
        $wallet_balance = woo_wallet()->wallet->get_wallet_balance($user_id,"number");
        $deposit        = 0;
        $milestone_ar   = get_post_meta($order_id,"_awcdp_deposits_payment_schedule",true);
        $main_total     = get_post_meta($order_id,"_order_total",true);

        //print_r($milestone_ar); exit;
        
        foreach ($milestone_ar as $key => $milestone) {

            $id = $milestone['id'];
            $title = $milestone['title'];
            $order_total = get_post_meta($id,"_order_total",true);
            $status = get_post_status($id);
                
            if($status != "wc-completed"){
                if ($wallet_balance > $order_total and $order_total > 0){
                    $update_post = array(
                        'post_type' => 'awcdp_payment',
                        'ID' => $id,
                        'post_status'    => 'wc-completed'
                    );
                    wp_update_post($update_post);
                    
                    $deposit = $deposit + $order_total;
                    $description= "$title of order ".$order_id." payed";
                    if ($order_total > 0) {
                        woo_wallet()->wallet->debit($user_id, $order_total, $description);
                        $wallet_balance = $wallet_balance - $order_total;
                    }
                }
                else if($order_total > 0 and $wallet_balance < $order_total )
                {
                    /*$update_post = array(
                        'post_type' => 'awcdp_payment',
                        'ID' => $id,
                        'post_status'    => 'wc-completed'
                    );
                    wp_update_post($update_post);*/

                    $new_amount = $order_total - $wallet_balance ;  
                    
                    $deposit = $new_amount + $order_total;
                    $description= "$title of order ".$order_id." payed";
                    if ($order_total > 0) {
                        woo_wallet()->wallet->debit($user_id, $new_amount, $description);
                    //    $wallet_balance = $wallet_balance - $new_amount;
                    }

                }


            } else {
                $deposit = $deposit + $order_total;
            }
        }
        if ($deposit > 0 ) {
            update_post_meta($order_id,"_awcdp_deposits_deposit_amount",$deposit);
            $second_pay = $main_total - $deposit;
            if ($second_pay <= 0) {
                update_post_meta($order_id,"_awcdp_deposits_second_payment",0);

                $update_order = array(
                    'post_type' => 'shop_order',
                    'ID' => $order_id,
                    'post_status'    => 'wc-completed'
                );
                wp_update_post($update_order);
            } else {
                update_post_meta($order_id,"_awcdp_deposits_second_payment",$second_pay);
            }
        }
        $response['success'] = 1;
        $response['msg'] = "Order Updated Successfully";
    } else {
        $response['error'] = 1;
        $response['msg'] = "Order ID not found";
    }
    echo json_encode($response);
    die();
}
add_action('wp_ajax_custom_update_order_fee', 'custom_update_order_fee_fn');
function custom_update_order_fee_fn()
{
    global $wpdb;
    $order_id = $_POST['order_id'];
    $title = isset($_POST['updated_product_name']) ? $_POST['updated_product_name'] : '';
    $price = isset($_POST['updated_package_price']) ? $_POST['updated_package_price'] : 0;

    $response_ar = array();
    if (!empty($order_id)) {
        if (!empty($title) && $price > 0) {
            
            $payed_total = get_post_meta($order_id,'_awcdp_deposits_deposit_amount',true);

            $product_id = 0;
            $previous_total = 0;

            $order = new WC_Order($order_id );
            $billing_email  =   $order->get_billing_email();
            $o_user_id   = $order->get_user_id();

            if (!$o_user_id || $o_user_id <= 0) {
                $user = get_user_by("email",$billing_email);
                if (isset($user->data->ID)) {
                    $o_user_id = $user->data->ID;
                } else {
                    $o_user_id = register_user_using_email($billing_email);
                }
                // update_post_meta($order_id,'_customer_user',$o_user_id);
            }

            $items = $order->get_items();
            foreach ( $items as $item ) {
                $product_id = $item->get_product_id();
                break;
            }
            $previous_total = $order->get_total();
            $new_amount_total         = $previous_total + $price;

            $product_id_new = (get_option('new_line_item_product_id') > 0 ? get_option('new_line_item_product_id') : $product_id);
            $product = wc_get_product( $product_id_new );
            $product->set_name($title);
            $product->set_price($price);

            $order->add_product( $product, 1);

            $order->calculate_taxes();
            $order->calculate_totals();
            $order->save();

            update_post_meta($order_id,"_awcdp_deposits_deposit_amount",$payed_total);

            $new_amount_total = $order->get_total();
            
            
            $milestones = msp_milestones_ar_fn($product_id,$new_amount_total);
            $milestones_names = msp_milestones_names_ar_fn($product_id);
            $package_updated = get_post_meta($order_id,'_order_package_updated',true);
            $package_update = get_post_meta($order_id,'_package_update',true);
            $fee_updated = get_post_meta($order_id,'_order_fee_updated',true);
            $fee_updated = 0;
            /*if ($package_updated == 1 && $fee_updated != 1) {
                upgrade_order_fee($milestones,$milestones_names,$order_id,$o_user_id,$billing_email);
            } else if ($fee_updated != 1) {
            }*/
            $fee = true;
            upgrade_order($milestones,$milestones_names,$order_id,$o_user_id,$billing_email,$fee);
            $currency = get_woocommerce_currency_symbol();
            $difference = $new_amount_total - $previous_total;

            $subject = "Order No. $order_id is Upgraded";
            $content = email_partial_payment_table($order_id);
            // Extra Fee Email Body
            $email_content = "Extra amount of $currency $difference is added.Your Order against Order No. $order_id is Upgraded from $currency $previous_total to $currency $new_amount_total. Your current Order Total is $currency $new_amount_total.</br>".$content;
            
            add_filter( 'wp_mail_from_name', 'my_mail_from_name' );
            wp_mail( $billing_email , $subject, $email_content, array('Content-Type: text/html; charset=UTF-8'));
            remove_filter('wp_mail_from_name', 'my_mail_from_name'  );


            $note = "Extra Fee of $currency$difference is added.";
            $order->add_order_note( $note );

            update_post_meta($order_id,'_order_fee_updated',1);

            /*$status = get_post_status($order_id);
            if($status == "wc-completed"){
                $update_post = array(
                    'post_type' => 'awcdp_payment',
                    'ID' => $order_id,
                    'post_status'    => 'wc-partially-paid'
                );
                wp_update_post($update_post);
            }*/

            wp_cache_flush();

            $response_ar['status'] = 1;
            $response_ar['messages'] = "Extra Fee is added";
        } else {
            $response_ar['status'] = 0;
            $response_ar['messages'] = "Credentials Missing";
        } 
    } else {
        $response_ar['status'] = 0;
        $response_ar['messages'] = "Order ID missing";
    }
    echo json_encode($response_ar);
    die();
}

add_action('wp_ajax_custom_delete_order_fee', 'custom_delete_order_fee_fn');
function custom_delete_order_fee_fn()
{
    global $wpdb;
    $order_id = isset($_POST['order_id']) ? $_POST['order_id'] : 0;
    $item_id = isset($_POST['item_id']) ? $_POST['item_id'] : 0;

    $response_ar = array();
    if (!empty($order_id)) {
        $sub_order_total_ar = array();
        $res        = $wpdb->get_results(" SELECT * from ".$wpdb->prefix."posts where post_parent='".$order_id."' ",ARRAY_A);

        foreach($res as $rs){
            $id = $rs['ID'];
            $order_total = get_post_meta($id,'_order_total',true);
            $sub_order_total_ar[$id] = $order_total;
        }

        if ($item_id > 0) {

            $product_id = 0;
            $previous_total = 0;
            
            $payed_total = get_post_meta($order_id,'_awcdp_deposits_deposit_amount',true);

            $order = new WC_Order($order_id);
            $billing_email  =   $order->get_billing_email();
            $o_user_id   = $order->get_user_id();
            $items = $order->get_items();
            $s = 0;
            foreach ( $items as $id => $item ) {
                $product_id = $item->get_product_id();
                break;
            }
            $previous_total = $order->get_total();

            $order->remove_item($item_id);

            $order->calculate_taxes();
            $order->calculate_totals();
            $order->save();

            update_post_meta($order_id,"_awcdp_deposits_deposit_amount",$payed_total);

            $new_amount_total = $order->get_total();
            
            $milestones = msp_milestones_ar_fn($product_id,$new_amount_total);
            $milestones_names = msp_milestones_names_ar_fn($product_id);

            downgrade_order($milestones,$milestones_names,$order_id,$o_user_id,$billing_email,$sub_order_total_ar);
            $currency = get_woocommerce_currency_symbol();

            $difference = $previous_total - $new_amount_total;

            $subject = "Order No. $order_id is Downgraded";
            $content = email_partial_payment_table($order_id);
            // Extra Fee Email Body
            $email_content = "Extra amount of $currency $difference is excluded.Your Order against Order No. $order_id is Downgraded from $currency $previous_total to $currency $new_amount_total. Your current Order Total is $currency $new_amount_total.</br>".$content;
            
            add_filter( 'wp_mail_from_name', 'my_mail_from_name' );
            wp_mail( $billing_email , $subject, $email_content, array('Content-Type: text/html; charset=UTF-8'));
            remove_filter('wp_mail_from_name', 'my_mail_from_name'  );

            $note = "Extra Fee of $currency$difference removed successfully.";
            $order->add_order_note( $note );

            wp_cache_flush();

            $response_ar['status'] = 1;
            $response_ar['messages'] = "Extra item removed successfully";
        } else {
            $response_ar['status'] = 0;
            $response_ar['messages'] = "Item ID Not found";
        } 
    } else {
        $response_ar['status'] = 0;
        $response_ar['messages'] = "Order ID missing";
    }
    echo json_encode($response_ar);
    die();
}

function add_to_cart_validation( $passed ) { 
    $count = WC()->cart->get_cart_contents_count();
    if ($count >= 1) {
        wc_add_notice( __( 'You can add only one product in cart.', 'woocommerce' ), 'error' );
        $passed = false;
    }
    return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'add_to_cart_validation', 10, 5 ); 

add_action('wp_ajax_recalculate_order_amounts', 'recalculate_order_amounts_fn');
function recalculate_order_amounts_fn()
{
    $response_ar = array();
    $order_id = (isset($_POST['order_id']) ? $_POST['order_id'] : '');
    if ($order_id and $order_id > 0) {
        global $wpdb;
        $order = wc_get_order(  $order_id );

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
        $total_amount = $order->get_total();
        $deposits_payment_schedule = $order->get_meta('_awcdp_deposits_payment_schedule');
        $tb =   $wpdb->prefix."posts";

        $res = $wpdb->get_results("SELECT * FROM ".$tb."  WHERE `post_parent` = '".$order_id."'", ARRAY_A);
        $total_amount_paid = $payed_total;
        foreach($res as $val)
        {
            $order_id2 = $val['ID'];

            $res2 = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."postmeta` WHERE `post_id` = '".$order_id2."' AND `meta_key` = '_order_total'", ARRAY_A);
            
            $amount22 = ($res2[0]['meta_value']);
            if($val['post_status']=="wc-completed"){
                if ($action == 'upgraded' || $action == 'downgraded') {
                    foreach($deposits_payment_schedule as $payment => $schedule){
                        if (isset($schedule['id']) && isset($schedule['new_amount']) && $schedule['id'] == $order_id2 && $amount22 > $schedule['new_amount']) {
                            $total_amount_paid += $schedule['new_amount'];
                        } else if($schedule['id'] == $order_id2){
                            $total_amount_paid += $amount22;
                        }
                    }
                } else {
                    $total_amount_paid += $amount22;
                }
            }
        }
        
        $remain =  $total_amount-$total_amount_paid;                        

        $wpdb->query("UPDATE ".$wpdb->prefix."postmeta  set 
                      meta_value='".$total_amount_paid."'  WHERE meta_key='_awcdp_deposits_deposit_amount' and `post_id` = '".$order_id."'", ARRAY_A);

        $wpdb->query("UPDATE ".$wpdb->prefix."postmeta  set 
                      meta_value='".$remain."'  WHERE meta_key='_awcdp_deposits_second_payment' and `post_id` = '".$order_id."'", ARRAY_A);

        $response_ar['status'] = 1;
        $response_ar['messages'] = "Recalculation done successfully";

    } else {
        $response_ar['status'] = 0;
        $response_ar['messages'] = "Order ID missing";
    }
    echo json_encode($response_ar);
    die();
}

add_action( 'woocommerce_thankyou', function(){
    wp_cache_flush();
    global $wp;
    global $wpdb;

    if ( isset($wp->query_vars['order-received']) ) {
        $sub_order_id = absint($wp->query_vars['order-received']); // The order ID
        $sub_order    = wc_get_order( $sub_order_id ); // The WC_Order object
        $amount_paid = $sub_order->get_total();
        $order_id = wp_get_post_parent_id($sub_order_id);
        if ($order_id and $order_id > 0) {
            $order = wc_get_order(  $order_id );
            $note = __("Milestone of ".get_woocommerce_currency_symbol()."$amount_paid is paid");
            $order->add_order_note( $note );



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
            $total_amount = $order->get_total();
            $billing_email  = $order->get_billing_email();
            $user_id        = $order->get_user_id();

            if (!$user_id || $user_id <= 0) {
                $user = get_user_by("email",$billing_email);
                if (isset($user->data->ID)) {
                    $user_id = $user->data->ID;
                } else {
                    $user_id = register_user_using_email($billing_email);
                }
                // update_post_meta($order_id,'_customer_user',$user_id);
            }

            $deposits_payment_schedule = $order->get_meta('_awcdp_deposits_payment_schedule');
            $tb =   $wpdb->prefix."posts";

            $res = $wpdb->get_results("SELECT * FROM ".$tb."  WHERE `post_parent` = '".$order_id."'", ARRAY_A);
            $total_amount_paid = $payed_total;
            foreach($res as $val)
            {
                $order_id2 = $val['ID'];

                $res2 = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."postmeta` WHERE `post_id` = '".$order_id2."' AND `meta_key` = '_order_total'", ARRAY_A);
                
                $amount22 = ($res2[0]['meta_value']);
                if($val['post_status']=="wc-completed"){
                    if ($action == 'upgraded' || $action == 'downgraded') {
                        foreach($deposits_payment_schedule as $payment => $schedule){
                            if (isset($schedule['id']) && isset($schedule['new_amount']) && $schedule['id'] == $order_id2 && $amount22 > $schedule['new_amount']) {
                                $total_amount_paid += $schedule['new_amount'];
                            } else if($schedule['id'] == $order_id2){
                                $total_amount_paid += $amount22;
                            }
                        }
                    } else {
                        $total_amount_paid += $amount22;
                    }
                }
            }
            
            $remain =  $total_amount-$total_amount_paid;                        

            $wpdb->query("UPDATE ".$wpdb->prefix."postmeta  set 
                          meta_value='".$total_amount_paid."'  WHERE meta_key='_awcdp_deposits_deposit_amount' and `post_id` = '".$order_id."'", ARRAY_A);

            $wpdb->query("UPDATE ".$wpdb->prefix."postmeta  set 
                          meta_value='".$remain."'  WHERE meta_key='_awcdp_deposits_second_payment' and `post_id` = '".$order_id."'", ARRAY_A);

        }

    }
}, 1, 1);

add_action('woocommerce_order_status_pending_to_partially-paid_notification', 'status_change_calculation_fn',1);
add_action('woocommerce_order_status_changed', 'status_change_calculation_fn', 1, 1);

function status_change_calculation_fn($order_id) {
    if ($order_id and $order_id > 0) {
        global $wpdb;
        global $wp;

        $order = wc_get_order(  $order_id );

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
        $total_amount = $order->get_total();
        $deposits_payment_schedule = $order->get_meta('_awcdp_deposits_payment_schedule');
        $tb =   $wpdb->prefix."posts";

        $res = $wpdb->get_results("SELECT * FROM ".$tb."  WHERE `post_parent` = '".$order_id."'", ARRAY_A);
        $total_amount_paid = $payed_total;
        foreach($res as $val)
        {
            $order_id2 = $val['ID'];

            $res2 = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."postmeta` WHERE `post_id` = '".$order_id2."' AND `meta_key` = '_order_total'", ARRAY_A);
            
            $amount22 = ($res2[0]['meta_value']);
            if($val['post_status']=="wc-completed"){
                if ($action == 'upgraded' || $action == 'downgraded') {
                    foreach($deposits_payment_schedule as $payment => $schedule){
                        if (isset($schedule['id']) && isset($schedule['new_amount']) && $schedule['id'] == $order_id2 && $amount22 > $schedule['new_amount']) {
                            $total_amount_paid += $schedule['new_amount'];
                        } else if($schedule['id'] == $order_id2){
                            $total_amount_paid += $amount22;
                        }
                    }
                } else {
                    $total_amount_paid += $amount22;
                }
            }
        }
        
        $remain =  $total_amount-$total_amount_paid;                        

        $wpdb->query("UPDATE ".$wpdb->prefix."postmeta  set 
                      meta_value='".$total_amount_paid."'  WHERE meta_key='_awcdp_deposits_deposit_amount' and `post_id` = '".$order_id."'", ARRAY_A);

        $wpdb->query("UPDATE ".$wpdb->prefix."postmeta  set 
                      meta_value='".$remain."'  WHERE meta_key='_awcdp_deposits_second_payment' and `post_id` = '".$order_id."'", ARRAY_A);

    }
}

// woocommerce_loaded
/*add_action( 'wp_loaded', function(){
    if($_GET['iamdevssss'] == "ok"){
        global $wp;
        global $wpdb;
        $sub_order_id = 24734;
        if ( isset($sub_order_id) ) {
            // $sub_order_id = absint($wp->query_vars['order-received']); // The order ID
            $sub_order    = wc_get_order( $sub_order_id ); // The WC_Order object
            $amount_paid = $sub_order->get_total();
            $order_id = wp_get_post_parent_id($sub_order_id);
            if ($order_id and $order_id > 0) {
                $order = wc_get_order(  $order_id );
                // $note = __("Milestone of ".get_woocommerce_currency_symbol()." $amount_paid is paid");
                // $order->add_order_note( $note );



                $action = get_post_meta($order_id,"_package_update",true);
                $payed_total = 0;
                $deposits_payment_schedule = $order->get_meta('_awcdp_deposits_payment_schedule');
                // if ($action == 'upgraded' || $action == 'downgraded') {
                //     foreach($deposits_payment_schedule as $payment => $schedule){
                //         if (isset($schedule['total']) && isset($schedule['new_amount']) && $schedule['new_amount'] > $schedule['total']) {
                //             $difference = 0;
                //             $difference = $schedule['new_amount'] - $schedule['total'];
                //             $payed_total += $difference;
                //         }
                //     }
                // }

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
                    if($val['post_status']=="wc-completed"){
                        $total_amount_paid += $amount22;
                    }
                    $total_amount += $amount22;

                }
                
                $remain =  $total_amount-$total_amount_paid;  
                echo "total_amount = ".$total_amount;
                echo "total_amount_paid = ".$total_amount_paid;

                // $wpdb->query("UPDATE ".$wpdb->prefix."postmeta  set 
                //               meta_value='".$total_amount_paid."'  WHERE meta_key='_awcdp_deposits_deposit_amount' and `post_id` = '".$order_id."'", ARRAY_A);

                // $wpdb->query("UPDATE ".$wpdb->prefix."postmeta  set 
                //               meta_value='".$remain."'  WHERE meta_key='_awcdp_deposits_second_payment' and `post_id` = '".$order_id."'", ARRAY_A);
                echo 'aaaaaaaaaaaaa';
                exit();
            }

        }
    }
});*/

add_action( 'admin_init', function() {
    add_option( 'new_line_item_product_id', '');
    register_setting( 'mileSetting_options_group', 'new_line_item_product_id', 'mileSetting_callback' );
});

add_action('admin_menu', function() {
    add_options_page('Milestone Settings', 'Milestone Settings', 'manage_options', 'mileSetting', 'mileSetting_options_page');
});

function mileSetting_options_page() {
    ?>
    <div>
        <h2>Milestone Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields( 'mileSetting_options_group' ); ?>
            <table class="form-table">
                <tr>
                    <th>
                        <label for="new_line_item_product_id">Product ID</label>
                    </th>
                    <td>
                        <input type="number" id="new_line_item_product_id" name="new_line_item_product_id" value="<?php echo get_option('new_line_item_product_id'); ?>" >
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

add_action('init','log_entry_wp_fn');
function log_entry_wp_fn() {
    if (@$_GET['iamdev55'] == 'ok') {
        $txt = 'string';
        $myfile = file_put_contents('log22.html', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
        exit();
    }
}
add_action('init','test_function_test');
function test_function_test() {
    if (@$_GET['iamdev99'] == 'ok') {

        echo '<pre>';
        global $wpdb;
        $order_id = 23749;
        $amount = 100;
        $partial_payment_name = __('Extra Payment for previous milestone', 'deposits-partial-payments-for-woocommerce');

        $order = new WC_Order($order_id );
        $user_id = $order->get_user_id();
        $is_vat_exempt = $order->get_meta('is_vat_exempt');
        $user_ip_address = $order->get_customer_ip_address();
        $user_agent = $order->get_customer_user_agent();
        // print_r($user_ip_address);
        // print_r(get_class_methods($order));
        exit;

        $partial_payment = new AWCDP_Order();
        $partial_payment->set_customer_id(apply_filters('woocommerce_checkout_customer_id', $user_id));
        $item = new WC_Order_Item_Fee();
        $item->set_props(
            array(
                'total' => $amount
            )
        );
        $item->set_name($partial_payment_name);
        $partial_payment->add_item($item);
        do_action('awcdp_deposits_do_partial_payment_meta', $partial_payment );
        $partial_payment->set_parent_id($order->get_id());
        $partial_payment->add_meta_data('is_vat_exempt', $is_vat_exempt);
        $partial_payment->add_meta_data('_awcdp_deposits_payment_type', 'deposit');
        $partial_payment->set_currency(get_woocommerce_currency());
        $partial_payment->set_prices_include_tax('yes' === get_option('woocommerce_prices_include_tax'));
        $partial_payment->set_customer_ip_address($user_ip_address);
        $partial_payment->set_customer_user_agent($user_agent);
        $partial_payment->set_total($amount);
        $partial_payment->save();
        
        exit;


        $product_id = 0;
        $previous_amount_total    = 75;
        $new_amount_total         = 150;
        
        $items = $order->get_items();
        foreach ( $items as $item ) {
            $product_id = $item->get_product_id();
            $item->set_subtotal($new_amount_total);
            $item->set_total($new_amount_total);
            break;
        }
        $order->calculate_taxes();
        $order->calculate_totals();
        $order->save();

        $milestones = msp_milestones_ar_fn($product_id,$new_amount_total );

        
        $post_tb    = $wpdb->prefix."posts";
        $res        = $wpdb->get_results("select * from ".$post_tb." where post_parent='".$order_id."' limit 0,10",ARRAY_A);
        $new_args = array(
            'post_type'      => 'awcdp_payment',
            'order'          => 'ASC',
            'post_parent'    => $order_id,
            'post_status'    => 'any'
        );
        $new_query = new WP_Query( $new_args);
        
        if ($new_query->have_posts() ) {
            $s = 0;
            $mile_price = 0;
            while($new_query->have_posts() ) {

                $new_query->the_post();
                $id = get_the_ID();
                $status = get_post_status($id);
                $price = isset($milestones[$s]) && $milestones[$s] > 0 ? $milestones[$s] : 0;
                if($status == "wc-completed"){
                    $paid = get_post_meta($id,"_order_total",true);
                    $remaining = $price - $paid;
                    $mile_price = $remaining;
                } else {
                    $price = $mile_price + $price;
                    $mile_price = 0;

                    $res        = $wpdb->get_results(" SELECT * from ".$wpdb->prefix."woocommerce_order_items where order_id='".$id."' limit 0,10",ARRAY_A);
                    if (is_array($res) && count($res) > 0 && isset($res[0])) {
                        $order_item = $res[0];
                        $order_item_id = $order_item['order_item_id'];
                        
                        $wpdb->query(" 
                                    UPDATE `".$wpdb->prefix."woocommerce_order_itemmeta`
                                        SET `meta_value` = ".$price."
                                    WHERE `order_item_id` = '".$order_item_id."' 
                                        AND `meta_key` = '_line_total' 
                                    ");
                        update_post_meta($id,"_order_total",$price);
                    }
                }
                $s++;
            }
            wp_reset_query();
        }

        /*foreach($res as $key => $val)
        {
            if($val['post_status']=="wc-pending" && $extra_remaining > 0)
            {
                $milestone_order_id = $val['ID'];
                $res        = $wpdb->get_results("select * from ".$wpdb->prefix."woocommerce_order_items where order_id='".$milestone_order_id."' limit 0,10",ARRAY_A);
                if (is_array($res) && count($res) > 0 && isset($res[0])) {
                    $order_item = $res[0];
                    $order_item_id = $order_item['order_item_id'];
                    
                    $wpdb->query(" 
                                UPDATE `".$wpdb->prefix."woocommerce_order_itemmeta`
                                    SET `meta_value` = `meta_value` + ".$extra_remaining."
                                WHERE `order_item_id` = '".$order_item_id."' 
                                    AND `meta_key` = '_line_total' 
                                ");

                    $wpdb->query(" 
                                UPDATE `".$wpdb->prefix."postmeta`
                                    SET `meta_value` = `meta_value` + ".$extra_remaining."
                                WHERE `post_id` = '".$milestone_order_id."' 
                                    AND `meta_key` = '_order_total' 
                                ");

                    print_r($order_item_id);
                    print_r("aaaaaaabb");
                }
                break;
            }
        }*/

      
        
        exit;

        $order_id = 23708;
        $variation_id = 22766;
        $variation_price = get_post_meta($variation_id, '_price', true);
        $order = new WC_Order($order_id );
       
        $items = $order->get_items();
        foreach ( $items as $item ) {
            $item->set_variation_id($variation_id);
            $item->set_subtotal($variation_price);
            $item->set_total($variation_price);
            break;
        }
        $order->calculate_taxes();
        $order->calculate_totals();
        $order->save();
        $paid       = get_post_meta($order_id,'_awcdp_deposits_deposit_amount',true);
        $remaining  = $variation_price - $paid;
        // $remaining = get_post_meta($order_id,'_awcdp_deposits_second_payment',true);
        update_post_meta($order_id,'_awcdp_deposits_second_payment',$remaining);
        
        // update_post_meta($order_id, '_awcdp_deposits_second_payment', '82.8');
        // $new_args = array(
        //     'post_type'      => 'awcdp_payment',
        //     'order'          => 'ASC',
        //     'post_parent'    => $order_id,
        //     'post_status'    => 'wc-pending'
        // );
        // $new_query = new WP_Query( $new_args);
        // //echo $new_query->request;
        // if ($new_query->have_posts() ) {
        //     while($new_query->have_posts() ) {
        //         $new_query->the_post();
        //         $id = get_the_ID();
        //         echo $id.'<br>';
        //         $sub_order = new WC_Order($order_id);
        //         /*$subitems = $sub_order->get_items();
        //         foreach ( $subitems as $subitem ) {
        //             $subitem->set_subtotal('32.40');
        //             $subitem->set_total('32.40');
        //         }
        //         $sub_order->calculate_totals();
        //         $sub_order->save();
        //         break;
        //     }
        //     wp_reset_query();
        // }
        //

        exit();
    }
}

?>