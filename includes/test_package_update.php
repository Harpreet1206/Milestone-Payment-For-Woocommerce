<?php
add_action("wp_loaded","test_pay_by_wallet");
function test_pay_by_wallet() {
    if (@$_GET['iamdev'] == "abc") {
        $response = array();
        $order_id = 24980;
        if (isset($order_id) && $order_id > 0) {
            // $order_id       = $_POST['order_id'];

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
            $wallet_balance = '787.53';
            echo $wallet_balance;
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
                                // woo_wallet()->wallet->debit($user_id, $order_total, $description);
                                $wallet_balance = $wallet_balance - $order_total;
                            }

                            /*$update_post = array(
                                'post_type' => 'awcdp_payment',
                                'ID' => $id,
                                'post_status'    => 'wc-completed'
                            );
                            wp_update_post($update_post);*/
                        } 
                        else {
                            echo "<br>".$wallet_balance."<br>";
                            echo $order_total."<br>";
                            $new_total = $order_total - $wallet_balance;
                            echo $new_total;

                            if ($new_total > 0) {
                                $description= "$title of order ".$order_id." payed";
                                // woo_wallet()->wallet->debit($user_id, $wallet_balance, $description);
                                $wallet_balance = 0;
                                $milestones_ar[$key]['total'] = $new_total;
                                
                                if (!isset($milestones_ar[$key]['new_amount'])) {
                                    $milestones_ar[$key]['new_amount'] = $order_total;
                                }

                                /*update_post_meta($order_id,"_awcdp_deposits_payment_schedule",$milestones_ar);
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
                                }*/
                            } else {
                                /*$update_post = array(
                                    'post_type' => 'awcdp_payment',
                                    'ID' => $id,
                                    'post_status'    => 'wc-completed'
                                );
                                wp_update_post($update_post);*/
                            }
                        }
                    }
                }



            }

            /*if ($order_id and $order_id > 0) {
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

                $wpdb->query("UPDATE ".$wpdb->prefix."postmeta  set 
                              meta_value='".$total_amount_paid."'  WHERE meta_key='_awcdp_deposits_deposit_amount' and `post_id` = '".$order_id."'", ARRAY_A);

                $wpdb->query("UPDATE ".$wpdb->prefix."postmeta  set 
                              meta_value='".$remain."'  WHERE meta_key='_awcdp_deposits_second_payment' and `post_id` = '".$order_id."'", ARRAY_A);

            }*/

            $response['success'] = 1;
            $response['msg'] = "Order Updated Successfully";
        } else {
            $response['error'] = 1;
            $response['msg'] = "Order ID not found";
        }
        echo json_encode($response);
        die();
    }
    if (@$_GET['iamdev'] == "tested") {
        global $wpdb;
        $order_id = 24976;
        $title = isset($_POST['updated_product_name']) ? $_POST['updated_product_name'] : '10 minutes extra';
        $price = isset($_POST['updated_package_price']) ? $_POST['updated_package_price'] : 1000;

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

                // $order->add_product( $product, 1);

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
                if ($package_updated == 1 && $fee_updated != 1) {
                    upgrade_order_fee($milestones,$milestones_names,$order_id,$o_user_id,$billing_email);
                } else if ($fee_updated != 1) {
                    $fee = true;
                    upgrade_order($milestones,$milestones_names,$order_id,$o_user_id,$billing_email,$fee);
                }
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
    else if (@$_GET['iamdev'] == "test") {
        echo '<pre>';

        $order_id = 24740;
        $order_id = 24752;
        $schedule = get_post_meta( $order_id, '_awcdp_deposits_payment_schedule', true);
        print_r($schedule);
        
        /*$sub_order_total_ar = array();
        global $wpdb;
        $res        = $wpdb->get_results(" SELECT * from ".$wpdb->prefix."posts where post_parent='".$order_id."' ",ARRAY_A);

        foreach($res as $rs){
            $id = $rs['ID'];
            $order_total = get_post_meta($id,'_order_total',true);
            $sub_order_total_ar[$id] = $order_total;
        }*/

        // print_r($sub_order_total_ar);
        exit();
    }
}

/*add_action( 'woocommerce_thankyou', function(){
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
            echo "Difference: $payed_total <br>";

            $order = new WC_Order($order_id );

            $billing_email  = $order->get_billing_email();
            $user_id        = $order->get_user_id();
            $total_amount = $order->get_total();

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
            // if ($total_amount < $total_amount_paid) {
            //     $total_amount_paid = $total_amount;
            // }
            echo "Total Paid: $total_amount_paid <br>";
            echo "Total Amount: $total_amount <br>";
            $remain =  $total_amount - $total_amount_paid;
            $remain = round($remain);
            
            echo "Total Remain: $remain <br>";

            $wpdb->query("UPDATE ".$wpdb->prefix."postmeta  set 
                          meta_value='".$total_amount_paid."'  WHERE meta_key='_awcdp_deposits_deposit_amount' and `post_id` = '".$order_id."'", ARRAY_A);

            $wpdb->query("UPDATE ".$wpdb->prefix."postmeta  set 
                          meta_value='".$remain."'  WHERE meta_key='_awcdp_deposits_second_payment' and `post_id` = '".$order_id."'", ARRAY_A);

        }

    }
}, 10);*/

?>