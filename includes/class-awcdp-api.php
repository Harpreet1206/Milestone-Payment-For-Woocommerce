<?php

if (!defined('ABSPATH'))
    exit;

class AWCDP_Api
{

    /**
     * @var    object
     * @access  private
     * @since    1.0.0
     */
    private static $_instance = null;

    /**
     * The version number.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_version;
    private $_active = false;

    public function __construct()
    {
        add_action('rest_api_init', function () {

            register_rest_route('awcdp/v1', '/awcdp_general_settings/', array(
                'methods' => 'POST',
                'callback' => array($this, 'awcdp_general_settings'),
                'permission_callback' => array($this, 'get_permission')
            ));
            register_rest_route('awcdp/v1', '/awcdp_general_settings/(?P<id>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'awcdp_general_settings'),
                'permission_callback' => array($this, 'get_permission')
            ));

            register_rest_route('awcdp/v1', '/awcdp_text_and_labels/', array(
                'methods' => 'POST',
                'callback' => array($this, 'awcdp_text_and_labels'),
                'permission_callback' => array($this, 'get_permission')
            ));
            register_rest_route('awcdp/v1', '/awcdp_text_and_labels/(?P<id>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'awcdp_text_and_labels'),
                'permission_callback' => array($this, 'get_permission')
            ));





        });
    }

    /**
     *
     * Ensures only one instance of AWDP is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @see WordPress_Plugin_Template()
     * @return Main AWDP instance
     */
    public static function instance($file = '', $version = '1.0.0')
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($file, $version);
        }
        return self::$_instance;
    }

    /**
     * @param $data
     * @return WP_REST_Response
     * @throws Exception
     */

        function awcdp_general_settings($data){

          if( ! $data['id'] ) {

              $data = $data->get_params();

              $awcdp_general_settings = array(
                'enable_deposits' => isset($data['awcdp_enable_deposits']) ? $data['awcdp_enable_deposits'] : 0,
                'require_login' => isset($data['awcdp_require_login']) ? $data['awcdp_require_login'] : 0,
                'deposit_type' => isset($data['awcdp_deposit_type']) ? $data['awcdp_deposit_type'] : '',
                'deposit_amount' => isset($data['awcdp_deposit_amount']) ? $data['awcdp_deposit_amount'] : '',
                'default_selected' => isset($data['awcdp_default_selected']) ? $data['awcdp_default_selected'] : '',
                'fully_paid_status' => isset($data['awcdp_fully_paid_status']) ? $data['awcdp_fully_paid_status'] : '',
                'reduce_stock' => isset($data['awcdp_reduce_stock']) ? $data['awcdp_reduce_stock'] : '',
                'disable_gateways' => isset($data['awcdp_disable_gateways']) ? $data['awcdp_disable_gateways'] : '',
              );

              if ( false === get_option('awcdp_general_settings') ){
                    add_option('awcdp_general_settings', $awcdp_general_settings, '', 'yes');
              }  else {
                    update_option('awcdp_general_settings', $awcdp_general_settings);
              }

          }

            $result['awcdp_general_settings'] = get_option('awcdp_general_settings') ? get_option('awcdp_general_settings') : '';

            return new WP_REST_Response($result, 200);
        }



      function awcdp_text_and_labels($data){

        if( ! $data['id'] ) {
            $data = $data->get_params();

            $awcdp_text_settings = array(
              'pay_deposit_text' => isset($data['awcdp_pay_deposit_text']) ? $data['awcdp_pay_deposit_text'] : '',
              'pay_full_text' => isset($data['awcdp_pay_full_text']) ? $data['awcdp_pay_full_text'] : '',
              'deposit_text' => isset($data['awcdp_deposit_text']) ? $data['awcdp_deposit_text'] : '',
              'to_pay_text' => isset($data['awcdp_to_pay_text']) ? $data['awcdp_to_pay_text'] : '',
              'future_payment_text' => isset($data['awcdp_future_payment_text']) ? $data['awcdp_future_payment_text'] : '',
              'deposit_amount_text' => isset($data['awcdp_deposit_amount_text']) ? $data['awcdp_deposit_amount_text'] : '',
            );

            if ( false === get_option('awcdp_text_settings') ){
                  add_option('awcdp_text_settings', $awcdp_text_settings, '', 'yes');
            } else {
                  update_option('awcdp_text_settings', $awcdp_text_settings);
            }



        }

          $result['awcdp_text_settings'] = get_option('awcdp_text_settings') ? get_option('awcdp_text_settings') : '';

          return new WP_REST_Response($result, 200);
      }








    /**
     * Permission Callback
     **/
    public function get_permission()
    {
        if (current_user_can('administrator') || current_user_can('manage_woocommerce')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

}
