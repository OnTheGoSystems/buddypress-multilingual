<?php

class Buddypress_Multilingual_User_Language_Form {
	public function add_hooks() {
		add_action( 'bp_setup_nav', array( $this, 'add_bp_tabs' ), 100 );
		add_action( 'init', array( $this, 'save_user_language' ) );
	}

	function add_bp_tabs() {
		bp_core_new_subnav_item( array(
			'name'            => __( 'Language', 'sitepress' ),
			'slug'            => 'language',
			'parent_url'      => trailingslashit( bp_displayed_user_domain() . 'settings' ),
			'parent_slug'     => 'settings',
			'screen_function' => array( $this, 'language_screen' ),
			'position'        => 100,
			'user_has_access' => bp_is_my_profile()
		) );
	}


	function language_screen() {
		add_action( 'bp_template_title', array( $this, 'language_screen_title' ) );
		add_action( 'bp_template_content', array( $this, 'language_screen_content' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	function language_screen_title() {
		_e( 'Language', 'sitepress' );
	}

	function language_screen_content() {
		$languages     = apply_filters( 'wpml_active_languages', array() );
		$user_language = get_user_meta( get_current_user_id(), '_buddypress_language', true );

		$html = new DOMDocument;
		$form = $html->createElement( 'form' );
		$form->setAttribute( 'method', 'post' );

		foreach ( $languages as $language_code => $data ) {
			$label = $html->createElement( 'label', $data['native_name'] . '&nbsp;' );
			$input = $html->createElement( 'input' );
			$input->setAttribute( 'type', 'radio' );
			$input->setAttribute( 'name', 'user_language' );
			$input->setAttribute( 'value', $language_code );
			if ( $user_language == $language_code ) {
				$input->setAttribute( 'checked', 'checked' );
			}
			$label->insertBefore( $input );
			$form->appendChild( $label );
		}

		$submit = $html->createElement( 'input' );
		$submit->setAttribute( 'type', 'submit' );
		$submit->setAttribute( 'name', 'submit' );
		$submit->setAttribute( 'value', __( 'Save Changes', 'buddypress' ) );
		$form->appendChild( $submit );

		$wpnonce = $html->createElement( 'input' );
		$wpnonce->setAttribute( 'type', 'hidden' );
		$wpnonce->setAttribute( 'name', 'bpml_save_user_language' );
		$wpnonce->setAttribute( 'value', wp_create_nonce( 'bpml_save_user_language' ) );
		$form->appendChild( $wpnonce );

		$html->appendChild( $form );
		echo $html->saveHTML();
	}

	public function save_user_language() {
		if ( isset( $_POST['bpml_save_user_language'] )
		     && wp_verify_nonce( $_POST['bpml_save_user_language'], 'bpml_save_user_language' )
		) {
			update_user_meta( get_current_user_id(), '_buddypress_language',
				sanitize_text_field( $_POST['user_language'] ) );
		}
	}
}