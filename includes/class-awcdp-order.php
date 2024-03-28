<?php
class AWCDP_Order extends WC_Order {

  function get_type(){
      return AWCDP_POST_TYPE;
  }

  function get_created_via($context = 'view'){
    return 'admin';
  }

  function get_customer_id($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent){
      return $parent->get_customer_id($context);
    }
    return $this->get_prop('customer_id', $context);
  }

  function get_user_id($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent){
      return $parent->get_user_id($context);
    }
    return $this->get_prop('customer_id', $context);
  }

  function get_user(){
    $parent = wc_get_order($this->get_parent_id());
    if($parent){
      return $parent->get_user();
    }
    return $this->get_user_id() ? get_user_by('id', $this->get_user_id()) : false;
  }

  function get_address_prop($prop, $address = 'billing', $context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent){
      return $parent->get_address_prop($prop, $address = 'billing', $context = 'view');
    }
    $value = null;
    if (array_key_exists($prop, $this->data[$address])) {
      $value = isset($this->changes[$address][$prop]) ? $this->changes[$address][$prop] : $this->data[$address][$prop];
      if ('view' === $context) {
        $value = apply_filters($this->get_hook_prefix() . $address . '_' . $prop, $value, $this);
      }
    }
    return $value;
  }

  function get_billing_first_name($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent){
      return $parent->get_billing_first_name($context);
    }
    return $this->get_address_prop('first_name', 'billing', $context);
  }

  function get_billing_last_name($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent){
      return $parent->get_billing_last_name($context);
    }
    return $this->get_address_prop('last_name', 'billing', $context);
  }

  function get_billing_company($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_billing_company($context);
    }
    return $this->get_address_prop('company', 'billing', $context);
  }

  function get_billing_address_1($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_billing_address_1($context);
    }
    return $this->get_address_prop('address_1', 'billing', $context);
  }

  function get_billing_address_2($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_billing_address_2($context);
    }
    return $this->get_address_prop('address_2', 'billing', $context);
  }

  function get_billing_city($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_billing_city($context);
    }
    return $this->get_address_prop('city', 'billing', $context);
  }

  function get_billing_state($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_billing_state($context);
    }
    return $this->get_address_prop('state', 'billing', $context);
  }

  function get_billing_postcode($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_billing_postcode($context);
    }
    return $this->get_address_prop('postcode', 'billing', $context);
  }

  function get_billing_country($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_billing_country($context);
    }
    return $this->get_address_prop('country', 'billing', $context);
  }

  function get_billing_email($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_billing_email($context);
    }
    return $this->get_address_prop('email', 'billing', $context);
  }

  function get_billing_phone($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_billing_phone($context);
    }
    return $this->get_address_prop('phone', 'billing', $context);
  }

  function get_shipping_first_name($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_shipping_first_name($context);
    }
    return $this->get_address_prop('first_name', 'shipping', $context);
  }

  function get_shipping_last_name($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_shipping_last_name($context);
    }
    return $this->get_address_prop('last_name', 'shipping', $context);
  }

  function get_shipping_company($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_shipping_company($context);
    }
    return $this->get_address_prop('company', 'shipping', $context);
  }

  function get_shipping_address_1($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_shipping_address_1($context);
    }
    return $this->get_address_prop('address_1', 'shipping', $context);
  }

  function get_shipping_address_2($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_shipping_address_2($context);
    }
    return $this->get_address_prop('address_2', 'shipping', $context);
  }

  function get_shipping_city($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_shipping_city($context);
    }
    return $this->get_address_prop('city', 'shipping', $context);
  }

  function get_shipping_state($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_shipping_state($context);
    }
    return $this->get_address_prop('state', 'shipping', $context);
  }

  function get_shipping_postcode($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_shipping_postcode($context);
    }
    return $this->get_address_prop('postcode', 'shipping', $context);
  }

  function get_shipping_country($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_shipping_country($context);
    }
    return $this->get_address_prop('country', 'shipping', $context);
  }

  function get_customer_note($context = 'view'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_customer_note($context);
    }
    return $this->get_prop('customer_note', $context);
  }

  function get_customer_ip_address($context = 'view'){
    return $this->get_prop('customer_ip_address', $context);
  }

  function get_customer_user_agent($context = 'view'){
    return $this->get_prop('customer_user_agent', $context);
  }

  function get_order_number(){
    if(is_order_received_page() && did_action('woocommerce_before_thankyou') && !did_action('woocommerce_thankyou')){
      return (string) apply_filters( 'woocommerce_order_number', $this->get_parent_id(), $this );
    }
    return (string) apply_filters( 'woocommerce_order_number', $this->get_id(), $this );
  }

  function get_shipping_address_map_url(){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_shipping_address_map_url();
    }
    $address = $this->get_address('shipping');
    // Remove name and company before generate the Google Maps URL.
    unset($address['first_name'], $address['last_name'], $address['company']);
    $address = apply_filters('woocommerce_shipping_address_map_url_parts', $address, $this);
    return apply_filters('woocommerce_shipping_address_map_url', 'https://maps.google.com/maps?&q=' . rawurlencode(implode(', ', $address)) . '&z=16', $this);
  }

  function is_editable(){
    return apply_filters('awcdp_deposits_is_wcdp_payment_editable',false,$this->get_id());
  }

  function get_address($type = 'billing'){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_address($type);
    }
    return apply_filters('woocommerce_get_order_address', array_merge($this->data[$type], $this->get_prop($type, 'view')), $type, $this);
  }

  function get_formatted_billing_full_name(){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_formatted_billing_full_name();
    }
    /* translators: 1: first name 2: last name */
    return sprintf(_x('%1$s %2$s', 'full name', 'woocommerce'), $this->get_billing_first_name(), $this->get_billing_last_name());
  }

  function get_formatted_shipping_full_name(){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_formatted_shipping_full_name();
    }
    /* translators: 1: first name 2: last name */
    return sprintf(_x('%1$s %2$s', 'full name', 'woocommerce'), $this->get_shipping_first_name(), $this->get_shipping_last_name());
  }

  function get_formatted_billing_address($empty_content = ''){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_formatted_billing_address($empty_content);
    }
    $address = apply_filters('woocommerce_order_formatted_billing_address', $this->get_address('billing'), $this);
    $address = WC()->countries->get_formatted_address($address);
    return $address ? $address : $empty_content;
  }

  function get_formatted_shipping_address($empty_content = ''){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->get_formatted_shipping_address($empty_content);
    }
    $address = '';
    if ($this->has_shipping_address()) {
      $address = apply_filters('woocommerce_order_formatted_shipping_address', $this->get_address('shipping'), $this);
      $address = WC()->countries->get_formatted_address($address);
    }
    return $address ? $address : $empty_content;
  }

  function has_billing_address(){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->has_billing_address();
    }
    return $this->get_billing_address_1() || $this->get_billing_address_2();
  }

  function has_shipping_address(){
    $parent = wc_get_order($this->get_parent_id());
    if($parent) {
      return $parent->has_shipping_address();
    }
    return $this->get_shipping_address_1() || $this->get_shipping_address_2();
  }

  function get_order_item_totals($tax_display = ''){
    if (did_action('woocommerce_order_details_after_order_table_items')) {
      return array($total_rows['order_total'] = array(
          'label' => __('Partial Payment amount:', 'deposits-partial-payments-for-woocommerce'),
          'value' => $this->get_formatted_order_total($tax_display),
      ));
    }
    $tax_display = $tax_display ? $tax_display : get_option('woocommerce_tax_display_cart');
    $total_rows = array();
    $this->add_order_item_totals_fee_rows($total_rows, $tax_display);
    $this->add_order_item_totals_total_row($total_rows, $tax_display);
    return apply_filters('woocommerce_get_order_item_totals', $total_rows, $this, $tax_display);
  }

  function get_customer_first_name() {
    if ( $this->get_user_id() ) {
      return get_user_meta( $this->get_user_id(), 'first_name', true );
    }

    if ( $this->get_billing_first_name( 'edit' ) !== '' ) {
      return $this->get_billing_first_name( 'edit' );
    } else {
      return $this->get_shipping_first_name( 'edit' );
    }
  }


}


?>
