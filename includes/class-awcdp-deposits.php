<?php

if (!defined('ABSPATH'))
    exit;

class AWCDP_Deposits
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

  public function __construct() {


    add_action('init', array($this, 'awcdp_register_post_types'));
    add_action('init', array($this, 'awcdp_register_order_status'));
    //add_filter('wc_order_statuses', array($this, 'awcdp_order_statuses'));

  }

    public static function instance($file = '', $version = '1.0.0') {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($file, $version);
        }
        return self::$_instance;
    }

    public function is_active() {
        return $this->_active !== false;
    }

    function awcdp_register_post_types(){

      $post_type = AWCDP_POST_TYPE;
      wc_register_order_type(
            $post_type,
            array(
                'labels' => array(
                    'name' => esc_html__('Payments', 'deposits-partial-payments-for-woocommerce'),
                    'singular_name' => esc_html__('Payment', 'deposits-partial-payments-for-woocommerce'),
                    'edit_item' => _x('Edit Payment', 'custom post type setting', 'deposits-partial-payments-for-woocommerce'),
                    'search_items' => esc_html__('Search Payments', 'deposits-partial-payments-for-woocommerce'),
                    'parent' => _x('Order', 'custom post type setting', 'deposits-partial-payments-for-woocommerce'),
                    'menu_name' => esc_html__('Payments', 'deposits-partial-payments-for-woocommerce'),
                ),
                'public' => false,
                'show_ui' => true,
                // 'show_ui' => false,
                'capability_type' => 'shop_order',
                'capabilities' => array(
                    'create_posts' => 'do_not_allow',
                ),
                'map_meta_cap' => true,
                'publicly_queryable' => false,
                'exclude_from_search' => true,
                'show_in_menu' =>  'woocommerce',
                'hierarchical' => false,
                'show_in_nav_menus' => false,
                'rewrite' => false,
                'query_var' => false,
                'supports' => array('title', 'comments', 'custom-fields'),
                'has_archive' => false,
                'exclude_from_orders_screen' => true,
                'add_order_meta_boxes' => true,
                'exclude_from_order_count' => true,
                'exclude_from_order_views' => true,
                'exclude_from_order_webhooks' => true,
                'exclude_from_order_reports' => true,
                'exclude_from_order_sales_reports' => true,
                'class_name' => 'AWCDP_Payment',
            )

        );




    }


    function awcdp_register_order_status() {

        register_post_status('wc-partially-paid', array(
            'label' => _x('Partially Paid', 'Order status', 'deposits-partial-payments-for-woocommerce'),
            'public' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'exclude_from_search' => false,
            'label_count' => _n_noop('Partially Paid <span class="count">(%s)</span>',
            'Partially Paid <span class="count">(%s)</span>', 'deposits-partial-payments-for-woocommerce')
        ));

    }

    /*
    function awcdp_order_statuses($order_statuses){
        $new_statuses = array();
        foreach ($order_statuses as $key => $value) {
            $new_statuses[$key] = $value;
            if ($key === 'wc-pending') {
                $new_statuses['partially-paid'] = esc_html__('Partially Paid', 'deposits-partial-payments-for-woocommerce');
            }
        }
        return $new_statuses;
    }
    */

    /* For templates */

    function awcdp_locate_template( $template_name, $template_path = '', $default_path = '' ) {

      // Set variable to search in the templates folder of theme.
      if ( ! $template_path ) :
        $template_path = 'AWCDP/';
      endif;

      // Set default plugin templates path.
      if ( ! $default_path ) :
        $default_path = AWCDP_PLUGIN_PATH . '/templates/'; // Path to the template folder
      endif;

      // Search template file in theme folder.
      $template = locate_template( array(
        $template_path . $template_name,
        $template_name
      ) );

      // Get plugins template file.
      if ( ! $template ) :
        $template = $default_path . $template_name;
      endif;

      return apply_filters( 'awcdp_locate_template', $template, $template_name, $template_path, $default_path );

    }

    function awcdp_get_template( $template_name, $args = array(), $tempate_path = '', $default_path = '' ) {

      if ( is_array( $args ) && isset( $args ) ) :
        $atts = $args;
        extract( $args );
      endif;

      $template_file = $this->awcdp_locate_template( $template_name, $tempate_path, $default_path );

      if ( ! file_exists( $template_file ) ) :
        _doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $template_file ), '1.0.0' );
        return;
      endif;

      ob_start();
      include( $template_file );
      return ob_get_clean();

    }

    /* For templates */


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
