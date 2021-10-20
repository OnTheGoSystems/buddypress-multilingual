<?php

namespace WPML\BuddyPress;

use WPML\FP\Obj;
use WPML\FP\Fns;

class Groups {

	const FIELDS     = [ 'name', 'description' ];
	const TEXTDOMAIN = 'bpml';

	public function addHooks() {
		add_action( 'groups_group_after_save', [ $this, 'registerStrings' ], 10, 2 );

		foreach ( self::FIELDS as $field ) {
			add_filter( 'bp_get_group_' . $field, $this->translate( $field ), 10, 2 );
		}
	}

	/**
	 * @param BP_Groups_Group|array $group
	 */
	public function registerStrings( $group ) {
		$get = Obj::prop( Fns::__, $group );

		foreach ( self::FIELDS as $field ) {
			do_action(
				'wpml_register_single_string',
				self::TEXTDOMAIN,
				'Group #' . $get( 'id' ) . ' ' . $field,
				$get( $field )
			);
		}
	}

	/**
	 * @param string                $field
	 * @param string                $value
	 * @param BP_Groups_Group|array $group
	 */
	public function translate( $field ) {
		return function( $value, $group ) use ( $field ) {
			return apply_filters(
				'wpml_translate_single_string',
				$value,
				self::TEXTDOMAIN,
				'Group #' . Obj::prop( 'id', $group ) . ' ' . $field
			);
		};
	}

}
