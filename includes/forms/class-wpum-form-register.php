<?php
/**
 * WP User Manager Forms
 *
 * @package     wp-user-manager
 * @author      Mike Jolley
 * @author      Alessandro Tesoro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WPUM_Form_Register Class
 *
 * @since 1.0.0
 */
class WPUM_Form_Register extends WPUM_Form {

	/**
	 * The name of the form
	 */
	public static $form_name = 'register';
	
	/**
	 * Password Method
	 */
	public static $random_password = true;

	/**
	 * Init the form.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public static function init() {

		add_action( 'wp', array( __CLASS__, 'process' ) );

		// Check for password field
		if(wpum_get_option('custom_passwords')) :
			
			self::$random_password = false;
			add_filter( 'wpum/form/validate=register', array( __CLASS__, 'validate_password_field' ), 10, 3 );

			// Add password meter field
			if( wpum_get_option('display_password_meter_registration') )
				add_action( 'wpum_after_single_password_field', array( __CLASS__, 'add_password_meter_field' ), 10, 2 );

			// Automatic login after registration
			if( wpum_get_option('login_after_registration') )
				add_action( 'wpum_after_registration', array( __CLASS__, 'do_login' ), 11, 3 );

		endif;

		// Validate Email Field
		add_filter( 'wpum/form/validate=register', array( __CLASS__, 'validate_email_field' ), 10, 3 );

		// Add honeypot spam field
		if( wpum_get_option('enable_honeypot') ) :
			add_action( 'wpum_get_registration_fields', array( __CLASS__, 'add_honeypot_field' ) );
			add_filter( 'wpum/form/validate=register', array( __CLASS__, 'validate_honeypot_field' ), 10, 3 );
		endif;

		// Add terms & conditions field
		if( wpum_get_option('enable_terms') ) :
			add_action( 'wpum_get_registration_fields', array( __CLASS__, 'add_terms_field' ) );
		endif;
		
		// Add Role selection if enabled
		if( wpum_get_option('allow_role_select') ) :
			add_action( 'wpum_get_registration_fields', array( __CLASS__, 'add_role_field' ) );
			add_filter( 'wpum/form/validate=register', array( __CLASS__, 'validate_role_field' ), 10, 3 );
			add_action( 'wpum_after_registration', array( __CLASS__, 'save_role' ), 10, 10 );
		endif;
		
		// Exclude usernames if enabled
		if( !empty( wpum_get_option('exclude_usernames') ) )
			add_filter( 'wpum/form/validate=register', array( __CLASS__, 'validate_username_field' ), 10, 3 );

		// Store uploaded avatar
		if( wpum_get_option('custom_avatars') && wpum_get_field_setting( 'user_avatar', 'show_on_signup' ) === true )
			add_action( 'wpum_after_registration', array( __CLASS__, 'add_avatar' ), 10, 3 );
	}

	/**
	 * Define registration fields
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public static function get_registration_fields() {

		if ( self::$fields ) {
			return;
		}

		self::$fields = array(
			'register' => wpum_get_registration_fields()
		);

	}

	/**
	 * Process the submission.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public static function process() {
		
		// Get fields
		self::get_registration_fields();

		// Get posted values
		$values = self::get_posted_fields();

		if ( empty( $_POST['wpum_submit_form'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'register' ) ) {
			return;
		}

		// Validate required
		if ( is_wp_error( ( $return = self::validate_fields( $values, self::$form_name ) ) ) ) {
			self::add_error( $return->get_error_message() );
			return;
		}

		// Let's do the registration
		//self::do_registration( $values['register']['username'], $values['register']['user_email'], $values );

	}

	/**
	 * Validate the password field.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public static function validate_password_field( $passed, $fields, $values ) {

		$pwd = $values['register']['password'];
		$pwd_strenght = wpum_get_option('password_strength');

		$containsLetter  = preg_match('/[A-Z]/', $pwd);
		$containsDigit   = preg_match('/\d/', $pwd);
		$containsSpecial = preg_match('/[^a-zA-Z\d]/', $pwd);

		if($pwd_strenght == 'weak') {
			if(strlen($pwd) < 8)
				return new WP_Error( 'password-validation-error', __( 'Password must be at least 8 characters long.' ) );
		}
		if($pwd_strenght == 'medium') {
			if( !$containsLetter || !$containsDigit || strlen($pwd) < 8 )
				return new WP_Error( 'password-validation-error', __( 'Password must be at least 8 characters long and contain at least 1 number and 1 uppercase letter.' ) );
		}
		if($pwd_strenght == 'strong') {
			if( !$containsLetter || !$containsDigit || !$containsSpecial || strlen($pwd) < 8 )
				return new WP_Error( 'password-validation-error', __( 'Password must be at least 8 characters long and contain at least 1 number and 1 uppercase letter and 1 special character.' ) );
		}

		return $passed;

	}

	/**
	 * Validate email field.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public static function validate_email_field( $passed, $fields, $values ) {

		$mail = $values['register'][ 'user_email' ];

		if( !is_email( $mail ) )
			return new WP_Error( 'email-validation-error', __( 'Please enter a valid email address.' ) );

		if( email_exists( $mail ) )
			return new WP_Error( 'email-validation-error', __( 'Email address already exists.' ) );

		return $passed;

	}

	/**
	 * Add password meter field.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public static function add_password_meter_field( $form, $field ) {
		echo '<span id="password-strength">'.__('Strength Indicator').'</span>';		
	}

	/**
	 * Add Honeypot field markup.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public static function add_honeypot_field( $fields ) {

		$fields[ 'comments' ] = array(
			'label'       => 'Comments',
			'type'        => 'textarea',
			'required'    => false,
			'placeholder' => '',
			'priority'    => 9999,
			'class'       => 'wpum-honeypot-field'
		);

		return $fields;

	}

	/**
	 * Validate the honeypot field.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public static function validate_honeypot_field( $passed, $fields, $values ) {

		$fake_field = $values['register'][ 'comments' ];

		if( $fake_field )
			return new WP_Error( 'honeypot-validation-error', __( 'Failed Honeypot validation' ) );

		return $passed;

	}

	/**
	 * Autologin.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public static function do_login( $user_id, $values ) {

		$userdata = get_userdata( $user_id );

		$data = array();
		$data['user_login']    = $userdata->user_login;
		$data['user_password'] = $values['register']['password'];
		$data['rememberme']    = true;

		$user_login = wp_signon( $data, false );

		wp_redirect( apply_filters( 'wpum_redirect_after_automatic_login', get_permalink(), $user_id ) );
		exit;

	}

	/**
	 * Add Terms field.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public static function add_terms_field( $fields ) {

		$fields[ 'terms' ] = array(
			'label'       => __('Terms &amp; Conditions'),
			'type'        => 'checkbox',
			'description' => sprintf(__('By registering to this website you agree to the <a href="%s" target="_blank">terms &amp; conditions</a>.'), get_permalink( wpum_get_option('terms_page') ) ),
			'required'    => true,
			'priority'    => 9999,
		);

		return $fields;

	}

	/**
	 * Add Role field.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public static function add_role_field( $fields ) {
		
		$fields[ 'role' ] = array(
			'label'       => __('Select Role'),
			'type'        => 'select',
			'required'    => true,
			'options'     => wpum_get_allowed_user_roles(),
			'description' => __('Select your user role'),
			'priority'    => 9999,
		);

		return $fields;

	}

	/**
	 * Validate the role field.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public static function validate_role_field( $passed, $fields, $values ) {

		$role_field = $values['register'][ 'role' ];
		$selected_roles = array_flip(wpum_get_option('register_roles'));

		if( !array_key_exists( $role_field , $selected_roles ) )
			return new WP_Error( 'role-validation-error', __( 'Select a valid role from the list.' ) );

		return $passed;

	}

	/**
	 * Save the role.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public static function save_role( $user_id, $values ) {

		$user = new WP_User( $user_id );
		$user->set_role( $values['register'][ 'role' ] );

	}

	/**
	 * Validate username field.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public static function validate_username_field( $passed, $fields, $values ) {

		$nickname = $values['register'][ 'username' ];

		if( wpum_get_option('exclude_usernames') && array_key_exists( $nickname , wpum_get_disabled_usernames() ) )
			return new WP_Error( 'nickname-validation-error', __( 'This nickname cannot be used.' ) );

		// Check for nicknames if permalink structure requires unique nicknames.
		if( get_option('wpum_permalink') == 'nickname'  ) :

			$current_user = wp_get_current_user();

			if( $username !== $current_user->user_nicename && wpum_nickname_exists( $username ) )
				return new WP_Error( 'username-validation-error', __( 'This nickname cannot be used.' ) );

		endif;

		return $passed;

	}

	/**
	 * Add avatar to user custom field.
	 * Also deletes previously selected avatar.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public static function add_avatar( $user_id, $values ) {

		$avatar_field = $values['register'][ 'user_avatar' ];

		if( !empty( $avatar_field ) && is_array( $avatar_field ) ) {

			update_user_meta( $user_id, "current_user_avatar", esc_url( $avatar_field['url'] ) );
			update_user_meta( $user_id, '_current_user_avatar_path', $avatar_field['path'] );
		}

	}

	/**
	 * Do registration.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public static function do_registration( $username, $email, $values ) {

		// Try registration
		if( self::$random_password ) {
			$do_user = register_new_user($username, $email);
		} else {
			$pwd = $values['register']['password'];
			$do_user = wp_create_user( $username, $pwd, $email );
		}

		// Check for errors
		if ( is_wp_error( $do_user ) ) {
			
			foreach ($do_user->errors as $error) {
				self::add_error( $error[0] );
			}
			return;

		} else {

			// Send notification if password is manually added by the user.
			if(!self::$random_password):
				wp_new_user_notification( $do_user, $pwd );
			endif;

			self::add_confirmation( apply_filters( 'wpum_registration_success_message', __( 'Registration complete.' ) ) );

			// Add ability to extend registration process.
			$user_id = $do_user;
			do_action('wpum_after_registration', $user_id, $values );

		}

	}

	/**
	 * Output the form.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public static function output( $atts = array() ) {
		
		// Get fields
		self::get_registration_fields();

		if( isset( $_POST['submit_wpum_register'] ) ) {
			// Show errors from fields
			self::show_errors();
			// Show confirmation messages
			self::show_confirmations();
		}

		// Display template
		if( !get_option( 'users_can_register' ) ) :

			// Display error message
			$message = array( 
				'id'   => 'wpum-registrations-disabled', 
				'type' => 'notice', 
				'text' => __( 'Registrations are currently disabled.' )
			);
			wpum_message( $message );

		elseif( is_user_logged_in() ) :

			get_wpum_template( 'already-logged-in.php', 
				array(
					'args' => $atts
				)
			);

		// Show register form if not logged in
		else :

			get_wpum_template( 'forms/registration-form.php', 
				array(
					'atts' => $atts,
					'form' => self::$form_name,
					'register_fields' => self::get_fields( 'register' ),
				)
			);

		endif;

	}

}