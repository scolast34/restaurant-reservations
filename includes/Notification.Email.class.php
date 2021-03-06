<?php
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'rtbNotificationEmail' ) ) {
/**
 * Class to handle an email notification for Restaurant Reservations
 *
 * This class extends rtbNotification and must implement the following methods:
 *	prepare_notification() - set up and validate data
 *	send_notification()
 *
 * @since 0.0.1
 */
class rtbNotificationEmail extends rtbNotification {

	/**
	 * Recipient email
	 * @since 0.0.1
	 */
	public $to_email;

	/**
	 * From email
	 * @since 0.0.1
	 */
	public $from_email;

	/**
	 * From name
	 * @since 0.0.1
	 */
	public $from_name;

	/**
	 * Email subject
	 * @since 0.0.1
	 */
	public $subject;

	/**
	 * Email message body
	 * @since 0.0.1
	 */
	public $message;

	/**
	 * Email headers
	 * @since 0.0.1
	 */
	public $headers;

	/**
	 * Prepare and validate notification data
	 *
	 * @return boolean if the data is valid and ready for transport
	 * @since 0.0.1
	 */
	public function prepare_notification() {
	
		// Check if notifications are disabled
		if ( empty( $this->booking->send_notifications ) ) {
			return false;
		}

		$this->set_to_email();
		$this->set_from_email();
		$this->set_subject();
		$this->set_headers();
		$this->set_message();

		// Return false if we're missing any of the required information
		if ( 	empty( $this->to_email) ||
				empty( $this->from_email) ||
				empty( $this->from_name) ||
				empty( $this->subject) ||
				empty( $this->headers) ||
				empty( $this->message) ) {
			return false;
		}
		
		return true;
	}

	/**
	 * Set to email
	 * @since 0.0.1
	 */
	public function set_to_email() {

		if ( $this->target == 'user' ) {
			$this->to_email = empty( $this->booking->email ) ? null : $this->booking->email;

		} else {
			global $rtb_controller;
			$this->to_email = $rtb_controller->settings->get_setting( 'admin-email-address' );
		}

	}

	/**
	 * Set from email
	 * @since 0.0.1
	 */
	public function set_from_email() {

		global $rtb_controller;

		if ( $this->target == 'user' ) {
			$this->from_email = $rtb_controller->settings->get_setting( 'reply-to-address' );
			$this->from_name = $rtb_controller->settings->get_setting( 'reply-to-name' );
		} else {
			$this->from_email = $this->booking->email;
			$this->from_name = $this->booking->name;
		}

	}

	/**
	 * Set email subject
	 * @since 0.0.1
	 */
	public function set_subject() {

		global $rtb_controller;

		if( $this->event == 'new_submission' ) {
			if ( $this->target == 'admin' ) {
				$this->subject = $this->process_subject_template( $rtb_controller->settings->get_setting( 'subject-booking-admin' ) );
			} elseif ( $this->target == 'user' ) {
				$this->subject = $this->process_subject_template( $rtb_controller->settings->get_setting( 'subject-booking-user' ) );
			}

		} elseif ( $this->event == 'pending_to_confirmed' ) {
			$this->subject = $this->process_subject_template( $rtb_controller->settings->get_setting( 'subject-confirmed-user' ) );

		} elseif ( $this->event == 'pending_to_closed' ) {
			$this->subject = $this->process_subject_template( $rtb_controller->settings->get_setting( 'subject-rejected-user' ) );
		}

	}

	/**
	 * Set email headers
	 * @since 0.0.1
	 */
	public function set_headers( $headers = null ) {

		global $rtb_controller;

		$headers = "From: " . stripslashes_deep( html_entity_decode( $rtb_controller->settings->get_setting( 'reply-to-name' ), ENT_COMPAT, 'UTF-8' ) ) . " <" . apply_filters( 'rtb_notification_email_header_from_email', get_option( 'admin_email' ) ) . ">\r\n";
		$headers .= "Reply-To: " . stripslashes_deep( html_entity_decode( $this->from_name, ENT_COMPAT, 'UTF-8' ) ) . " <" . $this->from_email . ">\r\n";
		$headers .= "Content-Type: text/html; charset=utf-8\r\n";
		$this->headers = apply_filters( 'rtb_notification_email_headers', $headers, $this );

	}

	/**
	 * Set email message body
	 * @since 0.0.1
	 */
	public function set_message() {

		$settings = get_option( 'rtb-settings' );

		if ( $this->event == 'new_submission' ) {
			if ( $this->target == 'user' ) {
				$template = 'template-booking-user';
			} elseif ( $this->target == 'admin' ) {
				$template = 'template-booking-admin';
			}

		} elseif ( $this->event == 'pending_to_confirmed' ) {
			if ( $this->target == 'user' ) {
				$template = 'template-confirmed-user';
			}

		} elseif ( $this->event == 'pending_to_closed' ) {
			if ( $this->target == 'user' ) {
				$template = 'template-rejected-user';
			}
		}

		if ( !isset( $template ) ) {
			$this->message = '';
		} else {
			$this->message = wpautop( $this->process_template( $this->get_template( $template ) ) );
		}

	}

	/**
	 * Process template tags for email subjects
	 * @since 0.0.1
	 */
	public function process_subject_template( $subject ) {

		$template_tags = array(
			'{user_name}'		=> $this->booking->name,
			'{party}'			=> $this->booking->party,
			'{date}'			=> $this->booking->format_date( $this->booking->date )
		);

		$template_tags = apply_filters( 'rtb_notification_email_subject_template_tags', $template_tags, $this );

		return str_replace( array_keys( $template_tags ), array_values( $template_tags ), $subject );

	}

	/**
	 * Send notification
	 * @since 0.0.1
	 */
	public function send_notification() {
		wp_mail( $this->to_email, $this->subject, $this->message, $this->headers );
	}
}
} // endif;
