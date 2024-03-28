<?php

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

        ob_start();
        echo 'Please pay milestone of '.$_POST['price'].' by clicking <a href="'.$link_for_pay.'" target="_blank">here</a>';
        $items = $order->get_items();
        $item  = $items[0];
        foreach ($order->get_items() as $key => $item) {
            $item_id = $key;
            $product_id = $item["product_id"];
            $item_meta_data = $item->get_meta_data();
            $formatted_meta_data = $item->get_formatted_meta_data( '_', true );
            
            $payment_type = $item->get_meta('_payment_type');
       
            $selected_milestones = unserialize($item->get_meta('_selected_milestones'));
            $unselected_milestones = unserialize($item->get_meta('_unselected_milestones'));
            ?>
            <table class="wp-list-table widefat fixed striped table-view-list posts" border="1">
                <thead>
                    <tr>
                        <th>Sr</th>
                        <th>Milestone</th>
                        <th>Price</th>
                        <th>Percentage</th>
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
                                <td><?php echo get_woocommerce_currency_symbol().$sm['price']; ?></td>
                                <td><?php echo $sm['percentage']; ?>%</td>
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
                                <td>$<?php echo $um['price']; ?></td>
                                <td><?php echo $um['percentage']; ?>%</td>
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
        $output = ob_get_clean();
        if (!empty($billing_email)) {

          //  $billing_email = 'purepunjabi276@gmail.com';
            wp_mail( $billing_email , 'Milestone Payment', $output, array('Content-Type: text/html; charset=UTF-8'));
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
                        <td>'.$value['name'].' :To be pay</td>
                        
                        <td>'.$value['percentage'].'%</td>
                        <td>'.get_woocommerce_currency_symbol().'&nbsp;'.$value['price'].'</td>

                    <tr>';
        }
        foreach($un_milestones as $value){
            $name .= '<tr>
                        <td>'.$value['name'].'</td>
                        
                        <td>'.$value['percentage'].' %</td>
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

        $milestones = unserialize(@$cart_item['selected_milestones']);
        $un_milestones = unserialize(@$cart_item['unselected_milestones']);
        
        //$content = '<br><span class="mil_price_table_span" style="font-weight:bold"> Milestones:</span><br>';
        $content = '&nbsp;<br><br><table class="mil_price_table" cellpadding="0" cellspacing="0">';
        foreach($milestones as $value){
            $content .= '<tr>
                        <td>'.$value['name'].' :To be pay</td>
                        <td>'.$value['percentage'].'%</td>
                        <td>'.get_woocommerce_currency_symbol().'&nbsp;'.$value['price'].'</td>
                    <tr>';
        }
        foreach($un_milestones as $value){
            $content .= '<tr>
                        <td>'.$value['name'].'</td>
                        <td>'.$value['percentage'].' %</td>
                        <td>$ '.$value['price'].'</td>
                    <tr>';
        }
        $content .= '</table>';
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
    <script type="text/javascript">
        function remove_direct(elem) {
            elem.closest('tr').remove();
        }
        function add_row_more(a,variation_id=0) {
            jQuery('#table_append_'+variation_id).append(a);
        }
        jQuery(document).ready(function() {
            jQuery(".custom_update_package").on("click",function() {
                if(confirm("Are you Really wants to add extra line item?")){

                    jQuery.ajax({
                        type:"POST",
                        url:"<?php echo admin_url( 'admin-ajax.php' ); ?>",
                        data:{
                            action: "custom_update_order_fee",
                            order_id:"<?php echo @$_GET['post']; ?>",
                            updated_product_name: jQuery('#updated_product_name').val(),
                            updated_package_price: jQuery('#updated_package_price').val()
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
        })
    </script>
    <?php
}

add_action('wp_head','custom_css_code');
function custom_css_code(){
    ?>
    <style type="text/css">
        dt.variation-Milestones{
            display: none !important;
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
                    'yes' => esc_html__('Yes', 'deposits-partial-payments-for-woocommerce'),
                    'no' => esc_html__('No', 'deposits-partial-payments-for-woocommerce'),
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
        </div>
    </div>
    <?php
}


add_action( 'woocommerce_process_product_meta', 'save_milestone_options_field' );

function save_milestone_options_field( $post_id ) {
    $milestones = $_POST['milestones'];
    update_post_meta( $post_id, '_milestones', serialize($milestones) );
}

add_action( 'add_meta_boxes', 'mp_update_order_package_meta_boxes' );
if ( ! function_exists( 'mp_update_order_package_meta_boxes' ) )
{
    function mp_update_order_package_meta_boxes()
    {
        add_meta_box( 'mv_other_fields', __('Update Package','woocommerce'), 'mp_add_prod_field_for_package_update', 'shop_order', 'side', 'core' );
        add_meta_box( 'mp_custom_package_update', __('Update Custom Package','woocommerce'), 'mp_prod_field_for_custom_package_update', 'shop_order', 'side', 'core' );
    }
}

function mp_prod_field_for_custom_package_update()
{
    global $post;
    $order_id = $post->ID;
    $updated_product_name = get_post_meta($order_id,'updated_product_name',true);
    $updated_package_price = get_post_meta($order_id,'updated_package_price',true);
    ?>
    <label for="updated_product_name">New Line Item Name</label></br>
    <input type="text" name="updated_product_name" id="updated_product_name" value="<?php echo $updated_product_name; ?>"></br>
    
    <label for="updated_package_price">New Line Item Price</label></br>
    <input type="text" name="updated_package_price" id="updated_package_price" value="<?php echo $updated_package_price; ?>">
    <button type="button" class="button button-primary custom_update_package" style="margin-top: 10px;">Custom Package Update</button>
    <?php
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
        if ($product_id != 0 && $product_variation_id != 0) {
            $product = wc_get_product( $product_id );
            $available_variations = $product->get_available_variations();
            //print_r( $available_variations);
            ?>

            <label>Select Package</label>
            <select name="update_package_varient_id">
                <?php 
                foreach($available_variations as $variation){ 
                    $selected = '';
                    if(!empty($update_package_varient_id)){
                        if($update_package_varient_id == $variation['variation_id']){
                            $selected = 'selected';
                        }
                    }
                    else if($product_variation_id == $variation['variation_id']){
                        $selected = 'selected';
                    }
                    ?>
                    <option value="<?php echo $variation['variation_id']; ?>" <?php echo $selected; ?>><?php echo $variation['attributes']['attribute_pa_duration']." (".get_woocommerce_currency_symbol().$variation['display_price'].")"; ?></option>
                    <?php
                }
                ?>
            </select>
            <?php
        }

    }
}

add_action( 'save_post', 'mv_save_wc_order_other_fields', 10, 1 );
function mv_save_wc_order_other_fields( $post_id ) {
    if(isset($_POST['update_package_varient_id'])){
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

                if($order->get_meta('update_package_varient_id', true) !== $_POST['update_package_varient_id']){
                    update_post_meta($post_id,'previous_package_varient_id',$order->get_meta('update_package_varient_id', true) );
                    update_post_meta($post_id,'update_package_varient_id',$_POST['update_package_varient_id']);
                    msp_update_order_package($post_id,$_POST['update_package_varient_id']);
                    pay_from_wallet($post_id);
                }
            }

        }
    }
}

function msp_update_order_package($order_id,$variation_id){
    global $wpdb;
    $variation = wc_get_product($variation_id);
    $product_title = $variation->get_formatted_name();
    $duration = $variation->get_attribute( 'pa_duration' );
    $variation_price = get_post_meta($variation_id, '_price', true);
    $order = new WC_Order($order_id );
    $deposits_payment_schedule = $order->get_meta('_awcdp_deposits_payment_schedule');
    $mile_count = (is_array($deposits_payment_schedule) ? count($deposits_payment_schedule) : 0);
    $billing_email  =   $order->get_billing_email();
    $previous_total = 0;

    $product_id = 0;
    $new_amount_total         = $variation_price;
    
    $items = $order->get_items();
    foreach ( $items as $item ) {
        $item->update_meta_data('pa_duration', $duration);
        $product_id = $item->get_product_id(); 
        $item->set_name($product_title);
        $item->set_variation($variation_id);
        $item->update_meta_data('_variation_id', $variation_id);
        $item->set_subtotal($new_amount_total);
        $previous_total = $item->get_total();
        $item->set_total($new_amount_total);
        break;
    }
    $order->calculate_taxes();
    $order->calculate_totals();
    $order->save();

    $milestones = msp_milestones_ar_fn($product_id,$new_amount_total);

    
    $post_tb    = $wpdb->prefix."posts";
    $res        = $wpdb->get_results("select * from ".$post_tb." where post_parent='".$order_id."' limit 0,10",ARRAY_A);

    $order       = wc_get_order( $order_id );

    $o_user_id   = $order->get_user_id();


    $new_args = array(
        'post_type'      => 'awcdp_payment',
        'order'          => 'ASC',
        'post_parent'    => $order_id,
        'post_status'    => 'any'
    );
    $new_query = new WP_Query( $new_args);
    
    if ($new_query->have_posts() ) {
        $milestone_ar = array();
        $s = 0;
        $mile_price = 0;
        $balance_for_wallet = 0;
        while($new_query->have_posts() ) {
            $new_query->the_post();
            $id = get_the_ID();
            $status = get_post_status($id);
            $order_total = get_post_meta($id,"_order_total",true);
            $price = isset($milestones[$s]) && $milestones[$s] > 0 ? $milestones[$s] : 0;
            if($status == "wc-completed"){
                $remaining = $price - $order_total;
                $mile_price = $remaining;
                $balance_for_wallet =  ($balance_for_wallet+$order_total) -$price;
                if ($mile_price > 0) {
                    $mile_order_id = msp_extra_milestone_order($order_id,$mile_price);
                    if ($s == 0) {
                        $milestone_ar['deposit'] = array(
                            "id" => $id,
                            "title" => "Milestone ".$s+1,
                            "type" => "deposit",
                            "total" => $order_total,
                            "remaining" => $remaining,
                        );
                        $milestone_ar['unlimited_'.$mile_count] = array(
                            "id" => $mile_order_id,
                            "title" => "Adjustment Payment for Milestone ".$s + 1,
                            "type" => "second_payment_".$mile_count,
                            "total" => $mile_price
                        );
                    }
                    $mile_price = 0;
                }
                else {
                    if ($s == 0) {
                        $milestone_ar['deposit'] = array(
                            "id" => $id,
                            "title" => "Milestone ".$s+1,
                            "type" => "deposit",
                            "total" => $order_total,
                            "remaining" => $remaining,
                        );
                    }
                }
            } else {


                $balance_for_wallet = $balance_for_wallet-$price;

                $price = $mile_price + $price;

                


                if ($price < 0) {
                    $mile_price = $price;
                    $price = 0;
                } else {
                    $mile_price = 0;
                }

                if ($s == 0) {
                    $milestone_ar['deposit'] = array(
                        "id" => $id,
                        "title" => "Milestone ".$s+1,
                        "type" => "deposit",
                        "total" => $price
                    );
                } else {
                    $milestone_ar['unlimited_'.$s] = array(
                        "id" => $id,
                        "title" => "Milestone ".$s+1,
                        "type" => "second_payment_".$s,
                        "total" => $price
                    );
                }

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

        if($balance_for_wallet>0)
        {
            $description= "Balance of order ".$order_id." downgrade";
            woo_wallet()->wallet->credit($o_user_id, $balance_for_wallet, $description);
        }

        wp_reset_query();
        update_post_meta($order_id,"_awcdp_deposits_payment_schedule",$milestone_ar);

        $subject = "Order package ";
        if ($previous_total > $new_amount_total) {
            $action = "Downgraded";
        } else {
            $action = "Upgraded";
        }

        $subject = "Order package ".$action;
        $body = "Your Package is $action from ".get_woocommerce_currency_symbol().$previous_total." To ".get_woocommerce_currency_symbol().$new_amount_total.". Your current package is ".$product_title;
        wp_mail( $billing_email , $subject, $body, array('Content-Type: text/html; charset=UTF-8'));
    }
}

function msp_extra_milestone_order($order_id,$amount) {
    $partial_payment_name = __('Extra Payment for previous milestone', 'deposits-partial-payments-for-woocommerce');
    $order = new WC_Order($order_id );
    $user_id = $order->get_user_id();
    $is_vat_exempt = $order->get_meta('is_vat_exempt');
    $user_ip_address = $order->get_customer_ip_address();
    $user_agent = $order->get_customer_user_agent();

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
    return $partial_payment->get_id();
}

function msp_milestones_ar_fn($product_id, $new_amount_total)
{
    $milestones_price_ar = array();

    $milestones = unserialize(get_post_meta( $product_id, '_milestones', true ));
    $milestones_count = isset($milestones['milestone']) && is_array($milestones['milestone']) ? count($milestones['milestone']) : 0;
    for ($i = 0; $i < $milestones_count; $i++) { 
        $percentage = $milestones['price_indian'][$i];
        $price = ($new_amount_total / 100) * $percentage;
        $price = round($price,2);
        $milestones_price_ar[] = $price;
    }
    return $milestones_price_ar;
}
//add_action("wp_loaded","pay_from_wallet");
function pay_from_wallet($order_id) {
    $order = wc_get_order($order_id);
    $user_id = $order->get_user_id();
    $wallet_balance = woo_wallet()->wallet->get_wallet_balance($user_id,"number");
    $deposit = 0;
    $milestone_ar = get_post_meta($order_id,"_awcdp_deposits_payment_schedule",true);
    $main_total = get_post_meta($order_id,"_order_total",true);
    foreach ($milestone_ar as $key => $milestone) {
        $id = $milestone['id'];
        $title = $milestone['title'];
        $order_total = get_post_meta($id,"_order_total",true);
        $status = get_post_status($id);
        if($status != "wc-completed"){
            if ($wallet_balance > $order_total){
                $update_post = array(
                    'post_type' => 'awcdp_payment',
                    'ID' => $id,
                    'post_status'    => 'wc-completed'
                );
                wp_update_post($update_post);
                $deposit = $deposit + $order_total;
                $description= "$title of order ".$order_id." payed";
                woo_wallet()->wallet->debit($user_id, $order_total, $description);
                $wallet_balance = $wallet_balance - $order_total;
            }
        } else {
            $deposit = $deposit + $order_total;
        }
    }
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

function get_balance_from_milestone()
{

}
add_action("init",function (){
    if(@$_GET['iamdev144']=="ok")
    {
        global $wpdb;
   
    $milestones = msp_milestones_ar_fn("2942","175");
    get_balance_from_milestone($milestones,);

    echo "-----------eee22----------";
    $order_id ='23871';
    
    print "<pre>";print_r($milestones); print "</pre>";

   
    $post_tb    = $wpdb->prefix."posts";
    $res        = $wpdb->get_results("select * from ".$post_tb." where post_parent='".$order_id."' limit 0,10",ARRAY_A);

    $order       = wc_get_order( $order_id );

    $o_user_id   = $order->get_user_id();

  
  

    $new_args = array(
        'post_type'      => 'awcdp_payment',
        'order'          => 'ASC',
        'post_parent'    => $order_id,
        'post_status'    => 'any'
    );
    $new_query = new WP_Query( $new_args);
    
    if ($new_query->have_posts() ) {
        $milestone_ar = array();
        $s = 0;
        $mile_price = 0;
        $balance_for_wallet = 0;
        while($new_query->have_posts() ) {
            $new_query->the_post();
            $id = get_the_ID();
            $status = get_post_status($id);
            $order_total = get_post_meta($id,"_order_total",true);
            echo "---->>>>".$order_total."<br>";
            $price = isset($milestones[$s]) && $milestones[$s] > 0 ? $milestones[$s] : 0;
            if($status == "wc-completed"){

                $remaining = $price - $order_total;
                $mile_price = $remaining;
                $balance_for_wallet =  ($balance_for_wallet+$order_total) -$price;

                echo "=====Price".$price.'<br>';
             
                echo "=====remaining".$balance_for_wallet.'<br>';


                if ($mile_price > 0) {
                    $mile_order_id = msp_extra_milestone_order($order_id,$mile_price);
                    if ($s == 0) {
                        $milestone_ar['deposit'] = array(
                            "id" => $id,
                            "title" => "Milestone ".$s+1,
                            "type" => "deposit",
                            "total" => $order_total,
                            "remaining" => $remaining,
                        );
                        $milestone_ar['unlimited_'.$mile_count] = array(
                            "id" => $mile_order_id,
                            "title" => "Adjustment Payment for Milestone ".$s + 1,
                            "type" => "second_payment_".$mile_count,
                            "total" => $mile_price
                        );
                    }
                    $mile_price = 0;
                }
            } else {

                echo "<br><br><br>";
                echo "------Price".$price.'<br>';
                
             
                $balance_for_wallet = $balance_for_wallet-$price;
               
                $price = $mile_price + $price;


                echo "=====balance_for_wallet".$balance_for_wallet.'<br>';


                if ($price < 0) {
                    $mile_price = $price;
                    $price = 0;
                } else {
                    $mile_price = 0;
                }

                if ($s == 0) {
                    $milestone_ar['deposit'] = array(
                        "id" => $id,
                        "title" => "Milestone ".$s+1,
                        "type" => "deposit",
                        "total" => $price
                    );
                } else {
                    $milestone_ar['unlimited_'.$s] = array(
                        "id" => $id,
                        "title" => "Milestone ".$s+1,
                        "type" => "second_payment_".$s,
                        "total" => $price
                    );
                }
            
             
                

                $res        = $wpdb->get_results(" SELECT * from ".$wpdb->prefix."woocommerce_order_items where order_id='".$id."' limit 0,10",ARRAY_A);
                if (is_array($res) && count($res) > 0 && isset($res[0])) {
                    $order_item = $res[0];
                    $order_item_id = $order_item['order_item_id'];
                    
                    // $wpdb->query(" 
                    //             UPDATE `".$wpdb->prefix."woocommerce_order_itemmeta`
                    //                 SET `meta_value` = ".$price."
                    //             WHERE `order_item_id` = '".$order_item_id."' 
                    //                 AND `meta_key` = '_line_total' 
                    //             ");
                    //update_post_meta($id,"_order_total",$price);
                }
            }
            $s++;
        }
        echo "=====Total Balance for the wallet".$balance_for_wallet.'<br>';
         print "<pre>";print_r($milestone_ar); print "</pre>";
        if($balance_for_wallet>0)
        {
            $description= "Balance of order ".$order_id." downgrad";
            woo_wallet()->wallet->credit($o_user_id, $balance_for_wallet, $description);
        }
        wp_reset_query();
        
      //  update_post_meta($order_id,"_awcdp_deposits_payment_schedule",$milestone_ar);

        $subject = "Order package ";
        if ($previous_total > $new_amount_total) {
            $action = "Downgraded";
        } else {
            $action = "Upgraded";
        }


    }

}
});
add_action('wp_ajax_custom_update_order_fee', 'custom_update_order_fee_fn');
function custom_update_order_fee_fn()
{
    global $wpdb;
    $response_ar = array();
    if (!empty($_POST['order_id'])) {
        if (!empty($_POST['updated_product_name']) && !empty($_POST['updated_package_price'])) {
            $order_id = $_POST['order_id'];
            $title = $_POST['updated_product_name'];
            $price = $_POST['updated_package_price'];

            $product_id = 0;
            $previous_total = 0;

            $order = new WC_Order($order_id );
            $items = $order->get_items();
            foreach ( $items as $item ) {
                $product_id = $item->get_product_id(); 
                $previous_total = $item->get_total();
                break;
            }
            $new_amount_total         = $previous_total + $price;
            $item = new WC_Order_Item_Fee();
            $item->set_props(
                array(
                    'total' => $price
                )
            );
            
            $item->set_name($title);
            $order->add_item($item);
            $order->calculate_taxes();
            $order->calculate_totals();
            $order->save();
            
            $milestones = msp_milestones_ar_fn($product_id,$new_amount_total);
            
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
                $milestone_ar = array();
                $s = 0;
                $mile_price = 0;
                while($new_query->have_posts() ) {
                    $new_query->the_post();
                    $id = get_the_ID();
                    $status = get_post_status($id);
                    $order_total = get_post_meta($id,"_order_total",true);
                    $price = isset($milestones[$s]) && $milestones[$s] > 0 ? $milestones[$s] : 0;
                    if($status == "wc-completed"){
                        $remaining = $price - $order_total;
                        $mile_price = $remaining;
                        if ($mile_price > 0) {
                            $mile_order_id = msp_extra_milestone_order($order_id,$mile_price);
                            if ($s == 0) {
                                $milestone_ar['deposit'] = array(
                                    "id" => $id,
                                    "title" => "Milestone ".$s+1,
                                    "type" => "deposit",
                                    "total" => $order_total,
                                    "remaining" => $remaining,
                                );
                                $milestone_ar['unlimited_'.$mile_count] = array(
                                    "id" => $mile_order_id,
                                    "title" => "Adjustment Payment for Milestone ".$s + 1,
                                    "type" => "second_payment_".$mile_count,
                                    "total" => $mile_price
                                );
                            }
                            $mile_price = 0;
                        }
                    } else {

                        $price = $mile_price + $price;
                        if ($price < 0) {
                            $mile_price = $price;
                            $price = 0;
                        } else {
                            $mile_price = 0;
                        }

                        if ($s == 0) {
                            $milestone_ar['deposit'] = array(
                                "id" => $id,
                                "title" => "Milestone ".$s+1,
                                "type" => "deposit",
                                "total" => $price
                            );
                        } else {
                            $milestone_ar['unlimited_'.$s] = array(
                                "id" => $id,
                                "title" => "Milestone ".$s+1,
                                "type" => "second_payment_".$s,
                                "total" => $price
                            );
                        }

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
                update_post_meta($order_id,"_awcdp_deposits_payment_schedule",$milestone_ar);
            }
            update_post_meta($order_id,"updated_product_name",$_POST['updated_product_name']);
            update_post_meta($order_id,"updated_package_price",$_POST['updated_package_price']);

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