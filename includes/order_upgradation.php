<?php
function downgrade_order($milestones,$milestones_names,$order_id,$o_user_id,$billing_email,$total_ar) {
	global $wpdb;
    $fee_updated = get_post_meta($order_id,'_order_fee_updated',true);
    
    $deposits_payment_schedule = get_post_meta($order_id,'_awcdp_deposits_payment_schedule',true);
    $payed_total = 0;
    
    foreach($deposits_payment_schedule as $payment => $schedule){
        if (isset($schedule['payed']) && $schedule['payed'] > 0) {
            $payed_total = $payed_total + $schedule['payed'];
        }
        $status = get_post_status($schedule['id']);
        $order_total = get_post_meta($schedule['id'],"_order_total",true);
        if($status == "wc-completed"){
            $payed_total = $payed_total + $order_total;
        }
    }
    $payed_total = get_post_meta($order_id,'_awcdp_deposits_deposit_amount',true);
    $remaining = $payed_total;

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

        while($new_query->have_posts() ) {
            $new_query->the_post();
            $id = get_the_ID();
            $status = get_post_status($id);
            $order_total = (isset($total_ar[$id]) ? $total_ar[$id] : get_post_meta($id,"_order_total",true) );
            // $order_total = get_post_meta($id,"_order_total",true);
            $price = isset($milestones[$s]) && $milestones[$s] > 0 ? $milestones[$s] : 0;
            $name = isset($milestones_names[$s]) && $milestones_names[$s] > 0 ? $milestones_names[$s] : 0;
            
            if($status == "wc-completed"){
                
                // $remaining = $remaining + ($order_total - $price);
            	$remaining = $remaining - $price;
                
                if ($s == 0) {
                    $milestone_ar['deposit'] = array(
                        "id" => $id,
                        "title" => $name,
                        "type" => "deposit",
                        "total" => $order_total,
                        "new_amount" => $price,
                        "old_amount" => $order_total,
                        "payed" => $order_total,
                        "remaining" => $remaining
                    );
                } else {
                	$milestone_ar['unlimited_'.$s] = array(
                        "id" => $id,
                        "title" => $name,
                        "type" => "second_payment_".$s,
                        "total" => $order_total,
                        "new_amount" => $price,
                        "old_amount" => $order_total,
                        "payed" => $order_total,
                        "remaining" => $remaining,
                    );
                }
            } else {

                $new_total = $price - $remaining;
                if ($new_total < 0) {
                    $new_total = 0;
                    $remaining = $remaining - $price;
                } else {
                    $new_total = round($new_total, wc_get_price_decimals());
                    $remaining = 0;
                }

                if ($s == 0) {
                    $milestone_ar['deposit'] = array(
                        "id" => $id,
                        "title" => $name,
                        "type" => "deposit",
                        "total" => $new_total,
                        "new_amount" => $price,
                        "old_amount" => $order_total,
                        "payed" => 0,
                        "remaining" => $remaining,
                    );
                } else {
                    $milestone_ar['unlimited_'.$s] = array(
                        "id" => $id,
                        "title" => $name,
                        "type" => "second_payment_".$s,
                        "total" => $new_total,
                        "new_amount" => $price,
                        "old_amount" => $order_total,
                        "payed" => 0,
                        "remaining" => $remaining,
                    );
                }

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

                    if ($new_total == 0) {
                        $update_post = array(
                            'post_type' => 'awcdp_payment',
                            'ID' => $id,
                            'post_status'    => 'wc-completed'
                        );
                        wp_update_post($update_post);
                    }
                }
            }
            $s++;
        }
        if ($remaining > 0) {
            $description= "Balance of order ".$order_id." downgrade";
            woo_wallet()->wallet->credit($o_user_id, $remaining, $description);

            $update_order = array(
                'post_type' => 'shop_order',
                'ID' => $order_id,
                'post_status'    => 'wc-completed'
            );
            wp_update_post($update_order);

            $order = new WC_Order($order_id );
            $new_amount_total = $order->get_total();
            update_post_meta( $order_id,'_awcdp_deposits_deposit_amount',$new_amount_total);
            update_post_meta( $order_id,'_awcdp_deposits_second_payment',0);
        }

        wp_reset_query();
        update_post_meta($order_id,"_awcdp_deposits_payment_schedule",$milestone_ar);
        update_post_meta($order_id,"_package_update","downgraded");
    }
}
function upgrade_order($milestones,$milestones_names,$order_id,$o_user_id,$billing_email,$fee = false) {
    global $wpdb;
    $new_args = array(
        'post_type'      => 'awcdp_payment',
        'order'          => 'ASC',
        'post_parent'    => $order_id,
        'post_status'    => 'any'
    );
    $new_query = new WP_Query( $new_args);
    
    if ($new_query->have_posts() ) {
        $remaining = 0;
        $milestone_ar = array();
        $s = 0;
        $balance_for_wallet = 0;
        $deposits_payment_schedule = get_post_meta($order_id,'_awcdp_deposits_payment_schedule',true);
        $payed_total = 0;

        foreach($deposits_payment_schedule as $payment => $schedule){
            if (isset($schedule['payed']) && $schedule['payed'] > 0) {
                $payed_total = $payed_total + $schedule['payed'];
            }
        }

        $payed_total = get_post_meta($order_id,'_awcdp_deposits_deposit_amount',true);
        
        $total_pay = $payed_total;
        while($new_query->have_posts() ) {
            $new_query->the_post();
            $id = get_the_ID();
            $status = get_post_status($id);
            $order_total = get_post_meta($id,"_order_total",true);
            $price = isset($milestones[$s]) && $milestones[$s] > 0 ? $milestones[$s] : 0;
            $name = isset($milestones_names[$s]) && $milestones_names[$s] > 0 ? $milestones_names[$s] : 0;
            
            if($status == "wc-completed"){
                $remain_pay = 0;
                if ($total_pay > $price) {
                    $total_pay = $total_pay - $price;
                    $total = $price;
                    $pending_payment = 0;
                } else {
                    $pending_payment = $price - $total_pay;
                    $total = $pending_payment;
                    $total_pay = 0;
                }
                $remaining = $total_pay;
                $total = round($total, wc_get_price_decimals());
                if ($s == 0) {
                    $milestone_ar['deposit'] = array(
                        "id" => $id,
                        "title" => $name,
                        "type" => "deposit",
                        "total" => $total,
                        "new_amount" => $price,
                        "old_amount" => $order_total,
                        "payed" => $order_total,
                        "remaining" => $total_pay
                    );
                } else {
                    $milestone_ar['unlimited_'.$s] = array(
                        "id" => $id,
                        "title" => $name,
                        "type" => "second_payment_".$s,
                        "total" => $total,
                        "new_amount" => $price,
                        "old_amount" => $order_total,
                        "payed" => $order_total,
                        "remaining" => $total_pay,
                    );
                }

                $res        = $wpdb->get_results(" SELECT * from ".$wpdb->prefix."woocommerce_order_items where order_id='".$id."' limit 0,10",ARRAY_A);
                if (is_array($res) && count($res) > 0 && isset($res[0])) {
                    $order_item = $res[0];
                    $order_item_id = $order_item['order_item_id'];
                    
                    $wpdb->query(" 
                                UPDATE `".$wpdb->prefix."woocommerce_order_itemmeta`
                                    SET `meta_value` = ".$total."
                                WHERE `order_item_id` = '".$order_item_id."' 
                                    AND `meta_key` = '_line_total' 
                                ");
                    update_post_meta($id,"_order_total",$total);
                    if ($pending_payment > 0) {
                        $update_post = array(
                            'post_type' => 'awcdp_payment',
                            'ID' => $id,
                            'post_status'    => 'wc-pending'
                        );
                        wp_update_post($update_post);
                    }
                }
            } else {
                if ($total_pay > $price) {
                    $total_pay = $total_pay - $price;
                    $total = $price;
                    $pending_payment = 0;
                } else {
                    $pending_payment = $price - $total_pay;
                    $total = $pending_payment;
                    $total_pay = 0;
                }

                $remaining = $total_pay;
                $total = round($total, wc_get_price_decimals());
                if ($s == 0) {
                    $milestone_ar['deposit'] = array(
                        "id" => $id,
                        "title" => $name,
                        "type" => "deposit",
                        "total" => $total,
                        "new_amount" => $price,
                        "old_amount" => $order_total,
                        "payed" => 0,
                        "remaining" => $total_pay,
                    );
                } else {
                    $milestone_ar['unlimited_'.$s] = array(
                        "id" => $id,
                        "title" => $name,
                        "type" => "second_payment_".$s,
                        "total" => $total,
                        "new_amount" => $price,
                        "old_amount" => $order_total,
                        "payed" => 0,
                        "remaining" => $total_pay,
                    );
                }

                $res        = $wpdb->get_results(" SELECT * from ".$wpdb->prefix."woocommerce_order_items where order_id='".$id."' limit 0,10",ARRAY_A);
                if (is_array($res) && count($res) > 0 && isset($res[0])) {
                    $order_item = $res[0];
                    $order_item_id = $order_item['order_item_id'];
                    
                    $wpdb->query(" 
                                UPDATE `".$wpdb->prefix."woocommerce_order_itemmeta`
                                    SET `meta_value` = ".$total."
                                WHERE `order_item_id` = '".$order_item_id."' 
                                    AND `meta_key` = '_line_total' 
                                ");
                    update_post_meta($id,"_order_total",$total);

                    if ($total_pay > 0 || $total == 0) {
                        $update_post = array(
                            'post_type' => 'awcdp_payment',
                            'ID' => $id,
                            'post_status'    => 'wc-completed'
                        );
                        wp_update_post($update_post);
                    }
                }
            }
            $s++;
        }

        wp_reset_query();
        update_post_meta($order_id,"_awcdp_deposits_payment_schedule",$milestone_ar);
        update_post_meta($order_id,"_package_update","upgraded");
    }
}

function upgrade_order_fee($milestones,$milestones_names,$order_id,$o_user_id,$billing_email) {
    global $wpdb;
    $deposits_payment_schedule = get_post_meta($order_id,'_awcdp_deposits_payment_schedule',true);
    $package_action = get_post_meta($order_id,'_package_update',true);
    if ($package_action == 'upgraded') {
        
        $payed_total = 0;
        foreach($deposits_payment_schedule as $payment => $schedule){
            $payed_total = $payed_total + $schedule['payed'];
        }
        $new_args = array(
            'post_type'      => 'awcdp_payment',
            'order'          => 'ASC',
            'post_parent'    => $order_id,
            'post_status'    => 'any'
        );
        $new_query = new WP_Query( $new_args);
        
        if ($new_query->have_posts() ) {
            $remaining = 0;
            $milestone_ar = array();
            $s = 0;
            $balance_for_wallet = 0;

            while($new_query->have_posts() ) {
                $new_query->the_post();
                $id = get_the_ID();
                $status = get_post_status($id);
                $order_total = get_post_meta($id,"_order_total",true);
                if($status == "wc-completed"){
                    $payed_total = $payed_total + $order_total;
                }
            }
            
            $payed_total = get_post_meta($order_id,'_awcdp_deposits_deposit_amount',true);


            $total_pay = $payed_total;

            while($new_query->have_posts() ) {
                $new_query->the_post();
                $id = get_the_ID();
                $status = get_post_status($id);
                $order_total = get_post_meta($id,"_order_total",true);
                $price = isset($milestones[$s]) && $milestones[$s] > 0 ? $milestones[$s] : 0;
                $name = isset($milestones_names[$s]) && $milestones_names[$s] > 0 ? $milestones_names[$s] : 0;
                
                if($status == "wc-completed"){
                    $remain_pay = 0;
                    if ($total_pay > $price) {
                        $total_pay = $total_pay - $price;
                        $total = $price;
                        $pending_payment = 0;
                    } else {
                        $pending_payment = $price - $total_pay;
                        $total = $pending_payment;
                        $total_pay = 0;
                    }
                    $remaining = $total_pay;
                    
                    if ($s == 0) {
                        $milestone_ar['deposit'] = array(
                            "id" => $id,
                            "title" => $name,
                            "type" => "deposit",
                            "total" => $total,
                            "new_amount" => $price,
                            "old_amount" => $order_total,
                            "payed" => $order_total,
                            "remaining" => $total_pay
                        );
                    } else {
                        $milestone_ar['unlimited_'.$s] = array(
                            "id" => $id,
                            "title" => $name,
                            "type" => "second_payment_".$s,
                            "total" => $total,
                            "new_amount" => $price,
                            "old_amount" => $order_total,
                            "payed" => $order_total,
                            "remaining" => $total_pay,
                        );
                    }

                    $res        = $wpdb->get_results(" SELECT * from ".$wpdb->prefix."woocommerce_order_items where order_id='".$id."' limit 0,10",ARRAY_A);
                    if (is_array($res) && count($res) > 0 && isset($res[0])) {
                        $order_item = $res[0];
                        $order_item_id = $order_item['order_item_id'];
                        
                        $wpdb->query(" 
                                    UPDATE `".$wpdb->prefix."woocommerce_order_itemmeta`
                                        SET `meta_value` = ".$total."
                                    WHERE `order_item_id` = '".$order_item_id."' 
                                        AND `meta_key` = '_line_total' 
                                    ");
                        update_post_meta($id,"_order_total",$total);
                        if ($pending_payment > 0) {
                            $update_post = array(
                                'post_type' => 'awcdp_payment',
                                'ID' => $id,
                                'post_status'    => 'wc-pending'
                            );
                            wp_update_post($update_post);
                        }
                    }
                } else {
                    if ($total_pay > $price) {
                        $total_pay = $total_pay - $price;
                        $total = $price;
                        $pending_payment = 0;
                    } else {
                        $pending_payment = $price - $total_pay;
                        $total = $pending_payment;
                        $total_pay = 0;
                    }

                    $remaining = $total_pay;

                    if ($s == 0) {
                        $milestone_ar['deposit'] = array(
                            "id" => $id,
                            "title" => $name,
                            "type" => "deposit",
                            "total" => $total,
                            "new_amount" => $price,
                            "old_amount" => $order_total,
                            "payed" => 0,
                            "remaining" => $total_pay,
                        );
                    } else {
                        $milestone_ar['unlimited_'.$s] = array(
                            "id" => $id,
                            "title" => $name,
                            "type" => "second_payment_".$s,
                            "total" => $total,
                            "new_amount" => $price,
                            "old_amount" => $order_total,
                            "payed" => 0,
                            "remaining" => $total_pay,
                        );
                    }

                    $res        = $wpdb->get_results(" SELECT * from ".$wpdb->prefix."woocommerce_order_items where order_id='".$id."' limit 0,10",ARRAY_A);
                    if (is_array($res) && count($res) > 0 && isset($res[0])) {
                        $order_item = $res[0];
                        $order_item_id = $order_item['order_item_id'];
                        
                        $wpdb->query(" 
                                    UPDATE `".$wpdb->prefix."woocommerce_order_itemmeta`
                                        SET `meta_value` = ".$total."
                                    WHERE `order_item_id` = '".$order_item_id."' 
                                        AND `meta_key` = '_line_total' 
                                    ");
                        update_post_meta($id,"_order_total",$total);

                        if ($total_pay > 0 || $total == 0) {
                            $update_post = array(
                                'post_type' => 'awcdp_payment',
                                'ID' => $id,
                                'post_status'    => 'wc-completed'
                            );
                            wp_update_post($update_post);
                        }
                    }
                }
                $s++;
            }

            wp_reset_query();
            // print_r($milestone_ar);
            update_post_meta($order_id,"_awcdp_deposits_payment_schedule",$milestone_ar);

            /*$subject = "Order package Upgraded";
            $body = "Your Package against $order_id is Upgraded";
            wp_mail( $billing_email , $subject, $body, array('Content-Type: text/html; charset=UTF-8'));*/
        }
    } else if ($package_action == 'downgraded') {
        
        $payed_total = 0;
        $new_args = array(
            'post_type'      => 'awcdp_payment',
            'order'          => 'ASC',
            'post_parent'    => $order_id,
            'post_status'    => 'any'
        );
        $new_query = new WP_Query( $new_args);
        
        if ($new_query->have_posts() ) {
            $remaining = 0;
            $milestone_ar = array();
            $s = 0;

            $payed_total = get_post_meta($order_id,'_awcdp_deposits_deposit_amount',true);

            $total_pay = $payed_total;

            while($new_query->have_posts() ) {
                $new_query->the_post();
                $id = get_the_ID();
                $status = get_post_status($id);
                $order_total = get_post_meta($id,"_order_total",true);
                $price = isset($milestones[$s]) && $milestones[$s] > 0 ? $milestones[$s] : 0;
                $name = isset($milestones_names[$s]) && $milestones_names[$s] > 0 ? $milestones_names[$s] : 0;
                
                if($status == "wc-completed"){
                    $remain_pay = 0;
                    if ($total_pay > $price) {
                        $total_pay = $total_pay - $price;
                        $total = $price;
                        $pending_payment = 0;
                    } else {
                        $pending_payment = $price - $total_pay;
                        $total = $pending_payment;
                        $total_pay = 0;
                    }
                    $remaining = $total_pay;
                    
                    if ($s == 0) {
                        $milestone_ar['deposit'] = array(
                            "id" => $id,
                            "title" => $name,
                            "type" => "deposit",
                            "total" => $total,
                            "new_amount" => $price,
                            "old_amount" => $order_total,
                            "payed" => $order_total,
                            "remaining" => $total_pay
                        );
                    } else {
                        $milestone_ar['unlimited_'.$s] = array(
                            "id" => $id,
                            "title" => $name,
                            "type" => "second_payment_".$s,
                            "total" => $total,
                            "new_amount" => $price,
                            "old_amount" => $order_total,
                            "payed" => $order_total,
                            "remaining" => $total_pay,
                        );
                    }

                    $res        = $wpdb->get_results(" SELECT * from ".$wpdb->prefix."woocommerce_order_items where order_id='".$id."' limit 0,10",ARRAY_A);
                    if (is_array($res) && count($res) > 0 && isset($res[0])) {
                        $order_item = $res[0];
                        $order_item_id = $order_item['order_item_id'];
                        
                        $wpdb->query(" 
                                    UPDATE `".$wpdb->prefix."woocommerce_order_itemmeta`
                                        SET `meta_value` = ".$total."
                                    WHERE `order_item_id` = '".$order_item_id."' 
                                        AND `meta_key` = '_line_total' 
                                    ");
                        update_post_meta($id,"_order_total",$total);
                        if ($pending_payment > 0) {
                            $update_post = array(
                                'post_type' => 'awcdp_payment',
                                'ID' => $id,
                                'post_status'    => 'wc-pending'
                            );
                            wp_update_post($update_post);
                        }
                    }
                } else {
                    if ($total_pay > $price) {
                        $total_pay = $total_pay - $price;
                        $total = $price;
                        $pending_payment = 0;
                    } else {
                        $pending_payment = $price - $total_pay;
                        $total = $pending_payment;
                        $total_pay = 0;
                    }

                    $remaining = $total_pay;

                    if ($s == 0) {
                        $milestone_ar['deposit'] = array(
                            "id" => $id,
                            "title" => $name,
                            "type" => "deposit",
                            "total" => $total,
                            "new_amount" => $price,
                            "old_amount" => $order_total,
                            "payed" => 0,
                            "remaining" => $total_pay,
                        );
                    } else {
                        $milestone_ar['unlimited_'.$s] = array(
                            "id" => $id,
                            "title" => $name,
                            "type" => "second_payment_".$s,
                            "total" => $total,
                            "new_amount" => $price,
                            "old_amount" => $order_total,
                            "payed" => 0,
                            "remaining" => $total_pay,
                        );
                    }

                    $res        = $wpdb->get_results(" SELECT * from ".$wpdb->prefix."woocommerce_order_items where order_id='".$id."' limit 0,10",ARRAY_A);
                    if (is_array($res) && count($res) > 0 && isset($res[0])) {
                        $order_item = $res[0];
                        $order_item_id = $order_item['order_item_id'];
                        
                        $wpdb->query(" 
                                    UPDATE `".$wpdb->prefix."woocommerce_order_itemmeta`
                                        SET `meta_value` = ".$total."
                                    WHERE `order_item_id` = '".$order_item_id."' 
                                        AND `meta_key` = '_line_total' 
                                    ");
                        update_post_meta($id,"_order_total",$total);

                        if ($total_pay > 0 || $total == 0) {
                            $update_post = array(
                                'post_type' => 'awcdp_payment',
                                'ID' => $id,
                                'post_status'    => 'wc-completed'
                            );
                            wp_update_post($update_post);
                        }
                    }
                }
                $s++;
            }

            wp_reset_query();
            // print_r($milestone_ar);
            update_post_meta($order_id,"_awcdp_deposits_payment_schedule",$milestone_ar);

            /*$subject = "Order package Upgraded";
            $body = "Your Package against $order_id is Upgraded";
            wp_mail( $billing_email , $subject, $body, array('Content-Type: text/html; charset=UTF-8'));*/
        }
    }
    
}
function downgrade_order_after_fee($milestones,$milestones_names,$order_id,$o_user_id,$billing_email) {
    global $wpdb;
    $fee_updated = get_post_meta($order_id,'_order_fee_updated',true);
    $new_args = array(
        'post_type'      => 'awcdp_payment',
        'order'          => 'ASC',
        'post_parent'    => $order_id,
        'post_status'    => 'any'
    );
    $new_query = new WP_Query( $new_args);

    $deposits_payment_schedule = get_post_meta($order_id,'_awcdp_deposits_payment_schedule',true);
    $payed_total = 0;

    foreach($deposits_payment_schedule as $payment => $schedule){
        if (isset($schedule['payed']) && $schedule['payed'] > 0) {
            $payed_total = $payed_total + $schedule['payed'];
        }
        $status = get_post_status($schedule['id']);
        $order_total = get_post_meta($schedule['id'],"_order_total",true);
        if($status == "wc-completed"){
            $payed_total = $payed_total + $order_total;
        }
    }

    $remaining = $payed_total;
    
    if ($new_query->have_posts() ) {
        $milestone_ar = array();
        $s = 0;

        while($new_query->have_posts() ) {
            $new_query->the_post();
            $id = get_the_ID();
            $status = get_post_status($id);
            $order_total = get_post_meta($id,"_order_total",true);
            $price = isset($milestones[$s]) && $milestones[$s] > 0 ? $milestones[$s] : 0;
            $name = isset($milestones_names[$s]) && $milestones_names[$s] > 0 ? $milestones_names[$s] : 0;
            
            if($status == "wc-completed"){
                $remaining = $remaining - $price;
                
                if ($s == 0) {
                    $milestone_ar['deposit'] = array(
                        "id" => $id,
                        "title" => $name,
                        "type" => "deposit",
                        "total" => $order_total,
                        "new_amount" => $price,
                        "old_amount" => $order_total,
                        "payed" => $order_total,
                        "remaining" => $remaining
                    );
                } else {
                    $milestone_ar['unlimited_'.$s] = array(
                        "id" => $id,
                        "title" => $name,
                        "type" => "second_payment_".$s,
                        "total" => $order_total,
                        "new_amount" => $price,
                        "old_amount" => $order_total,
                        "payed" => $order_total,
                        "remaining" => $remaining,
                    );
                }
            } else {
                
                $new_total = $price - $remaining;
                if ($new_total < 0) {
                    $new_total = 0;
                    $remaining = $remaining - $price;
                } else {
                    $remaining = 0;
                }

                if ($s == 0) {
                    $milestone_ar['deposit'] = array(
                        "id" => $id,
                        "title" => $name,
                        "type" => "deposit",
                        "total" => $new_total,
                        "new_amount" => $price,
                        "old_amount" => $order_total,
                        "payed" => 0,
                        "remaining" => $remaining,
                    );
                } else {
                    $milestone_ar['unlimited_'.$s] = array(
                        "id" => $id,
                        "title" => $name,
                        "type" => "second_payment_".$s,
                        "total" => $new_total,
                        "new_amount" => $price,
                        "old_amount" => $order_total,
                        "payed" => 0,
                        "remaining" => $remaining,
                    );
                }

                /*$res        = $wpdb->get_results(" SELECT * from ".$wpdb->prefix."woocommerce_order_items where order_id='".$id."' limit 0,10",ARRAY_A);
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

                    if ($new_total == 0) {
                        $update_post = array(
                            'post_type' => 'awcdp_payment',
                            'ID' => $id,
                            'post_status'    => 'wc-completed'
                        );
                        wp_update_post($update_post);
                    }
                }*/
            }
            $s++;
        }
        /*if ($remaining > 0) {
            $description= "Balance of order ".$order_id." downgrade";
            woo_wallet()->wallet->credit($o_user_id, $remaining, $description);

            $update_order = array(
                'post_type' => 'shop_order',
                'ID' => $order_id,
                'post_status'    => 'wc-completed'
            );
            wp_update_post($update_order);
        }*/
        wp_reset_query();
        print_r($milestone_ar);
        // update_post_meta($order_id,"_awcdp_deposits_payment_schedule",$milestone_ar);
        // update_post_meta($order_id,"_package_update","downgraded");

        /*$subject = "Order package Downgraded";
        $body = "Your Package against $order_id is Downgraded";
        wp_mail( $billing_email , $subject, $body, array('Content-Type: text/html; charset=UTF-8'));*/
    }
}
?>