<?php
/**
 * Customer Deposit Payment email
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AWCDP_Email_Deposit_Paid' ) ) {

	class AWCDP_Email_Deposit_Paid extends WC_Email {

		public function __construct() {
			$this->id          = 'awcdp_deposit_paid';
			$this->title       = __( 'Acowebs Deposit Payment Received', 'deposits-partial-payments-for-woocommerce' );
			$this->description = __( 'This emails will be sent to the customer when an order deposit payment is received', 'deposits-partial-payments-for-woocommerce' );
      		$this->customer_email = true;

			// $awwlm_es = get_option('woocommerce_awcdp_deposit_paid_settings');
			//
			// $pp_subject = ( isset($awwlm_es['subject']) && $awwlm_es['subject'] != '' ) ? $awwlm_es['subject'] : __( 'Your {site_title} order receipt from {order_date}', 'deposits-partial-payments-for-woocommerce' );
			// $pp_heading = ( isset($awwlm_es['heading']) && $awwlm_es['heading'] != '' ) ? $awwlm_es['heading'] : __( 'Thank you for your order', 'deposits-partial-payments-for-woocommerce' );
			//
			// $this->heading = $pp_heading;
			// $this->subject = $pp_subject;

			$this->template_html  = 'emails/customer-order-deposit-paid.php';
			$this->template_plain = 'emails/plain/customer-order-deposit-paid.php';

			// Triggers for this email.
			add_action('woocommerce_order_status_changed', array($this, 'trigger'), 10, 1);
			// add_action('woocommerce_order_status_pending_to_partially-paid_notification', array($this, 'trigger'));
			add_action('woocommerce_order_status_failed_to_partially-paid_notification', array($this, 'trigger'));
			/*add_action('woocommerce_order_status_completed_notification',array($this,'maybe_trigger'));*/

			//add_action('woocommerce_order_status_on-hold_to_partially-paid_notification', array($this, 'trigger'));

			// Call parent constructor.
			parent::__construct();
			$this->template_base = AWCDP_PLUGIN_PATH.'/templates/';

			// Other settings.


		}


		function trigger( $order_id, $order = false ){
			
			$status = get_post_status($order_id);
			if ($status == 'wc-partially-paid') {
				if( $order_id && !is_a($order, 'WC_Order') ){
					$order = wc_get_order($order_id);
				}

				if (is_a($order, 'WC_Order')) {
					$this->object = $order;
					$this->placeholders['{order_date}'] = wc_format_datetime($this->object->get_date_created());
					$this->placeholders['{order_number}'] = $this->object->get_order_number();

					$awcdp_as = get_option('awcdp_advanced_settings');
		      		$remaining_payable = (isset($awcdp_as['remaining_payable']) && $awcdp_as['remaining_payable'] == 1) ? 'no' : 'yes';

					if($this->object->get_status() == 'partially-paid' && $remaining_payable == 'yes'){

						$awcdp_ts = get_option('awcdp_text_settings');
			      		$payment_link_text = (isset($awcdp_ts['pay_link_text']) && $awcdp_ts['pay_link_text'] != '') ? $awcdp_ts['pay_link_text'] : esc_html__('Payment Link', 'deposits-partial-payments-for-woocommerce');

						$this->placeholders['{awcdp_payment_link}'] = '<a href="' . esc_url($this->object->get_checkout_payment_url()) . '">' . $payment_link_text . '</a>';
					} else {
						$this->placeholders['{awcdp_payment_link}'] = '';
					}
				}

				$this->recipient = $this->object->get_billing_email();
				if (!$this->is_enabled() || !$this->get_recipient()) {
					return;
				}

				$this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
			}

		}

		function maybe_trigger($order_id, $order = false){
			if ($order_id && !is_a($order, 'WC_Order')) {
				$order = wc_get_order($order_id);
				if( $order && $order->get_type() == AWCDP_POST_TYPE && $order->get_meta('_awcdp_deposits_payment_type', true) == 'deposit' ){
					$parent = wc_get_order($order->get_parent_id());
					$this->trigger($parent,$parent->get_id());
				}
			}
		}

		function get_email_text(){
			$text = $this->get_option('email_text', $this->get_default_email_text());
			return $this->format_string($text);
		}

		function get_payment_text(){
			$text = $this->get_option('payment_text', $this->get_default_payment_text());
			return $this->format_string($text);
		}

		function get_content_html(){

			ob_start();
			wc_get_template(
					$this->template_html ,
					array(
						'order' => $this->object,
						'email_heading' => $this->get_heading(),
						'additional_content' => version_compare( WOOCOMMERCE_VERSION, '3.7.0' ,'<') ?'' : $this-> get_additional_content(),
			            'email_text' => $this->get_email_text(),
			            'payment_text' => $this->get_payment_text(),
						'plain_text' => false,
						'sent_to_admin' => false,
						'email' => $this
					),
					'',
					$this->template_base
				);
				return ob_get_clean();
		}

		public function get_default_heading(){
    	return esc_html__('Thank you for your order', 'deposits-partial-payments-for-woocommerce');
    }

		public function get_default_subject(){
    	return esc_html__('Your {site_title} order receipt from {order_date}', 'deposits-partial-payments-for-woocommerce');
    }

		public function get_default_payment_text(){
			return esc_html__('To pay the remaining amount, please visit following link {awcdp_payment_link}', 'deposits-partial-payments-for-woocommerce');
		}

		public function get_default_email_text(){
    	return esc_html__("Your deposit has been received and your order is now being processed.", 'deposits-partial-payments-for-woocommerce');
    }

		function get_content_plain(){

			ob_start();
			wc_get_template(
				$this->template_plain,
				array(
					'order' => $this->object,
					'email_heading' => $this->get_heading(),
					'additional_content' => version_compare( WOOCOMMERCE_VERSION, '3.7.0' ,'<') ?'' : $this->get_additional_content(),
					'email_text' => $this->get_email_text(),
          			'payment_text' => $this->get_payment_text(),
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
				'email_text' => array(
					'title' => esc_html__('Email text', 'deposits-partial-payments-for-woocommerce'),
					'type' => 'textarea',
					'placeholder' => $this->get_default_email_text(),
					'default' => $this->get_default_email_text(),
					'css' => 'width:400px; height: 50px;',
				),
			    'payment_text' => array(
					'title' => esc_html__('Payment text', 'deposits-partial-payments-for-woocommerce'),
					'type' => 'textarea',
					'description' => esc_html__('Text that appear with payment link', 'deposits-partial-payments-for-woocommerce') . ' ' . $placeholder_text,
					'placeholder' => $this->get_default_payment_text(),
					'default' => $this->get_default_payment_text(),
					'css' => 'width:400px; height: 50px;',
			    ),
			    'additional_content' => array(
					'title' => esc_html__('Additional content', 'deposits-partial-payments-for-woocommerce'),
					'type' => 'textarea',
					'description' => esc_html__('Text to appear below the main email content.', 'deposits-partial-payments-for-woocommerce') . ' ' . $placeholder_text,
					'placeholder' => esc_html__('N/A', 'deposits-partial-payments-for-woocommerce'),
					'default' => $this->get_default_additional_content(),
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


return new AWCDP_Email_Deposit_Paid();
