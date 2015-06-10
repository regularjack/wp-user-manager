<?php
/**
 * WPUM Template: Default Registration Form Template.
 *
 * Displays login form.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2015, Alessandro Tesoro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */
?>
<div id="wpum-form-register-<?php echo esc_attr( $atts['form_id'] );?>" class="wpum-registration-form-wrapper">

	<?php do_action( "wpum/templates/before/form={$form}", $atts ); ?>

	<form action="#" method="post" id="wpum-register-<?php echo esc_attr( $atts['form_id'] );?>" class="wpum-registration-form" name="wpum-register-<?php echo esc_attr( $atts['form_id'] );?>" enctype="multipart/form-data">

		<?php do_action( "wpum/templates/top/inside/form={$form}", $atts ); ?>

		<?php foreach ( $register_fields as $key => $field ) : ?>
			<fieldset class="fieldset-<?php esc_attr_e( $key ); ?>" data-type="<?php echo esc_attr( $field['type'] );?>" data-label="<?php echo esc_attr( $field['label'] );?>" data-required="<?php echo esc_attr( $field['required'] );?>" data-name="<?php esc_attr_e( $key ); ?>">
				<label for="<?php esc_attr_e( $key ); ?>"><?php echo $field['label']; ?></label>
				<div class="field <?php echo $field['required'] ? 'required-field' : ''; ?>">
					<?php do_action( "wpum/form/{$form}/before/field={$key}", $field ); ?>
					<?php get_wpum_template( 'form-fields/' . $field['type'] . '-field.php', array( 'key' => $key, 'field' => $field ) ); ?>
					<?php do_action( "wpum/form/{$form}/after/field={$key}", $field ); ?>
				</div>
			</fieldset>
		<?php endforeach; ?>

		<?php do_action( "wpum/templates/bottom/inside/form={$form}", $atts ); ?>

		<?php wp_nonce_field( $form ); ?>

		<p>
			<input type="hidden" name="wpum_submit_form" value="<?php echo $form; ?>" />
			<input type="submit" id="submit_wpum_register" name="submit_wpum_register" class="button" value="<?php _e( 'Register' ); ?>" />
		</p>

	</form>

	<?php do_action( "wpum/templates/after/form={$form}", $atts ); ?>

</div>
