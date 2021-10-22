<?php

namespace WPML\BuddyPress;

use WPML\FP\Obj;
use WPML\FP\Fns;

class Groups implements \IWPML_Backend_Action, \IWPML_Frontend_Action {

	const FIELDS     = [ 'name', 'description' ];
	const TEXTDOMAIN = 'bpml';

	public function add_hooks() {
		add_action( 'groups_group_after_save', [ $this, 'registerStrings' ] );

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
				self::getName( $get( 'id' ), $field ),
				$get( $field )
			);
		}
	}

	/**
	 * @param string $field
	 *
	 * @return \Closure (string, BP_Groups_Group|array) -> string
	 */
	public function translate( $field ) {
		return function( $value, $group ) use ( $field ) {
			return apply_filters(
				'wpml_translate_single_string',
				$value,
				self::TEXTDOMAIN,
				self::getName( Obj::prop( 'id', $group ), $field )
			);
		};
	}

	/**
	 * @param int    $id
	 * @param string $field
	 *
	 * @return string
	 */
	public static function getName( $id, $field ) {
		return sprintf( 'Group #%d %s', $id, $field );
	}

}
