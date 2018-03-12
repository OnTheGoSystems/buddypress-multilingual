<?php
/**
 * BuddyPress XProfile Classes.
 *
 * @package BuddyPress
 * @subpackage XProfileClasses
 * @since 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Language xprofile field type.
 *
 * @since 2.0.0
 */
class BP_XProfile_Field_Type_Language extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the selectbox field type.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->category = _x( 'Multi Fields', 'xprofile field type category', 'buddypress' );
		$this->name     = _x( 'Language Select Box', 'xprofile field type', 'buddypress' );

		$this->do_settings_section = true;

		$this->set_format( '/^.+$/', 'replace' );


		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_Language class.
		 *
		 * @since 2.0.0
		 *
		 * @param BP_XProfile_Field_Type_Language $this Current instance of
		 *                                               the field type select box.
		 */
		do_action( 'BP_XProfile_Field_Type_Language', $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since 2.0.0
	 *
	 * @param array $raw_properties Optional key/value array of
	 *                              {@link http://dev.w3.org/html5/markup/select.html permitted attributes}
	 *                              that you want to add.
	 */
	public function edit_field_html( array $raw_properties = array() ) {

		// User_id is a special optional parameter that we pass to
		// {@link bp_the_profile_field_options()}.
		if ( isset( $raw_properties['user_id'] ) ) {
			$user_id = (int) $raw_properties['user_id'];
			unset( $raw_properties['user_id'] );
		} else {
			$user_id = bp_displayed_user_id();
		} ?>

		<label for="<?php bp_the_profile_field_input_name(); ?>">
			<?php bp_the_profile_field_name(); ?>
			<?php bp_the_profile_field_required_label(); ?>
		</label>

		<?php

		/** This action is documented in bp-xprofile/bp-xprofile-classes */
		do_action( bp_get_the_profile_field_errors_action() ); ?>

		<select <?php echo $this->get_edit_field_html_elements( $raw_properties ); ?>>
			<?php bp_the_profile_field_options( array( 'user_id' => $user_id ) ); ?>
		</select>

		<?php
	}

	/**
	 * Get all child fields for this field ID.
	 *
	 * @since 1.2.0
	 *
	 * @global object $wpdb
	 *
	 * @param bool $for_editing Whether or not the field is for editing.
	 * @return array
	 */
	public function get_children( $for_editing = false ) {

		$children = icl_get_languages('skip_missing=N&orderby=KEY&order=DIR&link_empty_to=str');

		/**
		 * Filters the found children for a field.
		 *
		 * @since 1.2.5
		 *
		 * @param object $children    Found children for a field.
		 * @param bool   $for_editing Whether or not the field is for editing.
		 */
		return apply_filters( 'bp_xprofile_field_taxonomy_get_children', $children, $for_editing );
	}

	/**
	 * Output the edit field options HTML for this field type.
	 *
	 * BuddyPress considers a field's "options" to be, for example, the items in a selectbox.
	 * These are stored separately in the database, and their templating is handled separately.
	 *
	 * This templating is separate from {@link BP_XProfile_Field_Type::edit_field_html()} because
	 * it's also used in the wp-admin screens when creating new fields, and for backwards compatibility.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Optional. The arguments passed to {@link bp_the_profile_field_options()}.
	 */
	public function edit_field_options_html( array $args = array() ) {

		$original_option_values = maybe_unserialize( BP_XProfile_ProfileData::get_value_byid( $this->field_obj->id, $args['user_id'] ) );


		$options = $this->get_children();
		$empty_label = /* translators: no option picked in select box */ __( 'Choose your language', 'buddypress-multilingual');

		$html    = '<option value="">' . $empty_label . '</option>';

		if ( empty( $original_option_values ) && !empty( $_POST['field_' . $this->field_obj->id] ) ) {
			$original_option_values = sanitize_text_field(  $_POST['field_' . $this->field_obj->id] );
		}

		$option_values = ( $original_option_values ) ? (array) $original_option_values : array();
		foreach ( $options as $k => $option ) {

			$selected = '';

			// Check for updated posted values, but errors preventing them from
			// being saved first time.
			foreach( $option_values as $i => $option_value ) {
				if ( isset( $_POST['field_' . $this->field_obj->id] ) && $_POST['field_' . $this->field_obj->id] != $option_value ) {
					if ( ! empty( $_POST['field_' . $this->field_obj->id] ) ) {
						$option_values[$i] = sanitize_text_field( $_POST['field_' . $this->field_obj->id] );
					}
				}
			}

			// Run the allowed option name through the before_save filter, so
			// we'll be sure to get a match.
			$allowed_options = xprofile_sanitize_data_value_before_save( $option['code'], false, false );

			// First, check to see whether the user-entered value matches.
			if ( in_array( $allowed_options, $option_values ) ) {
				$selected = ' selected="selected"';
			}

			// Then, if the user has not provided a value, check for defaults.
			if ( ! is_array( $original_option_values ) && empty( $option_values ) && $option['active'] ) {
				$selected = ' selected="selected"';
			}

			/**
			 * Filters the HTML output for options in a select input.
			 *
			 * @since 1.1.0
			 *
			 * @param string $value    Option tag for current value being rendered.
			 * @param object $value    Current option being rendered for.
			 * @param int    $id       ID of the field object being rendered.
			 * @param string $selected Current selected value.
			 * @param string $k        Current index in the foreach loop.
			 */
			$html .= apply_filters( 'bp_get_the_profile_field_options_language', '<option' . $selected . ' value="' . esc_attr( stripslashes( $option['code'] ) ) . '">' . esc_html( stripslashes( $option['native_name'] ) ) . '</option>', $option, $this->field_obj->id, $selected, $k );
		}

		echo $html;
	}

	public static function display_filter( $field_value, $field_id = '' ) {

		$languages = icl_get_languages('skip_missing=N&orderby=KEY&order=DIR&link_empty_to=str');
		if ( ( $key = array_search( $field_value, wp_list_pluck( $languages, 'code' ) ) ) !== FALSE ) {
			$field_value = $languages[$key]['native_name'];
		}

		return $field_value;
	}

	/** Protected *************************************************************/


	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since 2.0.0
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 */
	public function admin_field_html( array $raw_properties = array() ) {
		?>

		<label for="<?php bp_the_profile_field_input_name(); ?>" class="screen-reader-text"><?php
			/* translators: accessibility text */
			esc_html_e( 'Select', 'buddypress' );
		?></label>
		<select <?php echo $this->get_edit_field_html_elements( $raw_properties ); ?>>
			<?php bp_the_profile_field_options(); ?>
		</select>

		<?php
	}

	/**
	 * Output HTML for this field type's children options on the wp-admin Profile Fields "Add Field" and "Edit Field" screens.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since 2.0.0
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string            $control_type  Optional. HTML input type used to render the current
	 *                                         field's child options.
	 */
	/**
	 * Output HTML for this field type's children options on the wp-admin Profile Fields "Add Field" and "Edit Field" screens.
	 *
	 * You don't need to implement this method for all field types. It's used in core by the
	 * selectbox, multi selectbox, checkbox, and radio button fields, to allow the admin to
	 * enter the child option values (e.g. the choices in a select box).
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since 2.0.0
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string            $control_type  Optional. HTML input type used to render the current
	 *                          field's child options.
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {

		// no options for this type
		return;

	}
}
