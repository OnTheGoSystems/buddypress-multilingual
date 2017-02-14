<?php

class Buddypress_Multilingual_User_Language_Redirection {

	public function add_hooks() {
		add_action( 'wp_login', array( $this, 'redirect' ), 10, 2 );
	}

	public function redirect( $user_login, $user ) {
		$user_language  = get_user_meta( $user->ID, '_buddypress_language', true );
		$wpml_permalink = apply_filters( 'wpml_permalink',
			bp_get_activity_directory_permalink(), $user_language );
		wp_redirect( $wpml_permalink );
		exit;
	}
}