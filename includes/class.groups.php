<?php

namespace WPML\BuddyPress;

use WPML\FP\Obj;

class Groups {

	const FIELDS = [ 'name', 'description' ];

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
		foreach ( self::FIELDS as $field ) {
			do_action(
				'wpml_register_single_string',
				'buddypress-multilingual',
				'Group #' . Obj::prop( 'id', $group ) . ' ' . $field,
				Obj::prop( $field, $group )
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
				'buddypress-multilingual',
				'Group #' . Obj::prop( 'id', $group ) . ' ' . $field
			);
		};
	}

}
