<?php

namespace WPML\BuddyPress;

class Upgrade implements \IWPML_Backend_Action {

	const VERSION_KEY = 'bpml_version';

	const AVAILABLE_UPGRADES = [
		'1.6.2' => 'register_group_strings',
	];

	public function add_hooks() {
		add_action( 'init', [ $this, 'run' ] );
	}

	public function run() {
		$installedVersion = get_option( self::VERSION_KEY, '0.0.0' );
		if ( version_compare( BPML_VERSION, $installedVersion ) > 0 ) {
			foreach ( self::AVAILABLE_UPGRADES as $version => $method ) {
				if ( version_compare( $version, $installedVersion ) > 0 ) {
					call_user_func( [ $this, $method ] );
				}
			}
			update_option( self::VERSION_KEY, BPML_VERSION );
		}
	}

	public function register_group_strings() {
		if ( bp_is_active( 'groups' ) ) {
			$groups = \groups_get_groups( [ 'per_page' => -1 ] );
			foreach ( $groups['groups'] as $group ) {
				do_action_ref_array( 'groups_group_after_save', array( $group ) );
			}
		}
	}

}
