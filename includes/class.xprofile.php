<?php
/**
 * Translates group and profile field labels.
 */
class BPML_XProfile implements \IWPML_Backend_Action, \IWPML_Frontend_Action {

    protected $_context = \WPML\BuddyPress\Groups::TEXTDOMAIN;
    protected $_field_string_prefix = 'profile field ';
    protected $_group_string_prefix = 'profile group ';

    const PRIORITY_BEFORE_NAME_REPLACE = 9;
    const FIELD_TYPES_WITH_OPTIONS     = [ 'radio', 'checkbox', 'selectbox', 'multiselectbox' ];

    public function add_hooks() {

        add_action( 'bp_init', array($this, 'bp_init') );

        // AJAX string registration
        add_action( 'wp_ajax_bpml_register_fields', array( $this, 'ajax_register' ) );
        
        // Register actions
        add_action( 'xprofile_fields_saved_field', array( $this, 'saved_field_action' ) );
        add_action( 'xprofile_fields_deleted_field', array( $this, 'deleted_field_action' ) );
        add_action( 'xprofile_groups_saved_group', array( $this, 'saved_group_action' ) );
        add_action( 'xprofile_groups_deleted_group', array( $this, 'deleted_group_action' ) );
        add_action( 'update_xprofile_field_meta', array( $this, 'update_alternate_name' ), 10, 4 );
        add_action( 'add_xprofile_field_meta', array( $this, 'add_alternate_name' ), 10, 3 );
        add_action( 'delete_xprofile_field_meta', array( $this, 'delete_alternate_name' ), 10, 3 );

        // Translation filters
        add_filter( 'bp_get_the_profile_field_name', array($this, 'translate_name'), self::PRIORITY_BEFORE_NAME_REPLACE );
        add_filter( 'bp_get_the_profile_field_alternate_name', array($this, 'translate_alternate_name') );
        add_filter( 'bp_get_the_profile_field_description', array($this, 'translate_description') );
        add_filter( 'bp_xprofile_field_get_children', array($this, 'translate_options') );
        add_filter( 'bp_get_the_profile_field_options_checkbox', array($this, 'translate_checkbox'), 0, 5 );
        add_filter( 'bp_get_the_profile_field_options_radio', array($this, 'translate_radio'), 0, 5 );
        add_filter( 'bp_get_the_profile_field_options_multiselect', array($this, 'translate_multiselect_option'), 0, 5 );
        add_filter( 'bp_get_the_profile_field_options_select', array($this, 'translate_select_option'), 0, 5 );
        add_filter( 'bp_get_the_profile_field_value', array($this, 'translate_value_profile_view'), 9, 2 );
        add_filter( 'bp_get_the_profile_group_name', array($this, 'translate_group_name') );
        add_filter( 'bp_get_member_profile_data', array($this, 'translate_data'), 10, 2 );
    }

    public function bp_init() {
        // BP Profile Fields admin screen
        if ( isset( $_GET['page'] ) && $_GET['page'] == 'bp-profile-setup' ) {
            // Scan needed check
            if ( $this->_scan_needed() ) {
                add_action( 'admin_notices', array( $this, 'scan_needed_warning' ) );
            }
            wp_enqueue_script( 'bpml', BPML_RELPATH . '/js/admin.js', array('jquery'), BPML_VERSION, true );
        }
    }

    public function register_fields() {
        if ( $groups = bp_xprofile_get_groups( array('fetch_fields' => true) ) ) {
            foreach ( $groups as $group ) {
                $this->saved_group_action( $group );
                if ( !empty( $group->fields ) && is_array( $group->fields ) ) {
                    foreach ( $group->fields as $field ) {
                        $this->saved_field_action( $field );
                        $meta = bp_xprofile_get_meta( $field->id, 'field', 'alternate_name', true );
                        if ( $meta ) {
                            $this->add_alternate_name( $field->id, 'alternate_name', $meta );
                        }
                    }
                }
            }
        }
    }

    public function saved_field_action( $field ) {
        // Happens that new field has no accesible 'id' property
        if ( empty( $field->id ) ) {
            if ( $field_id = xprofile_get_field_id_from_name( $field->name ) ) {
                $field->id = $field_id;
            } else {
                return;
            }
        }
        // Register name
        if ( !empty( $field->name ) ) {
	        do_action( 'wpml_register_single_string', $this->_context,
                    "{$this->_field_string_prefix}{$field->id} name", stripslashes( $field->name ) );
        }
        // Register description
        if ( !empty( $field->description ) ) {
	        do_action( 'wpml_register_single_string', $this->_context,
                    "{$this->_field_string_prefix}{$field->id} description", stripslashes( $field->description ) );
        }
        // Register options
        if ( in_array( $field->type, self::FIELD_TYPES_WITH_OPTIONS ) ) {
            $bp_field = xprofile_get_field( $field->id );
            $options = $bp_field->get_children();
            foreach ( $options as $option ) {
                if ( !empty( $option->name ) ) {
	                do_action( 'wpml_register_single_string', $this->_context,
                            $this->sanitize_option_basename( $option, $field->id ) . ' name',
		                    stripslashes( $option->name ) );
                }
                if ( !empty( $option->description ) ) {
	                do_action( 'wpml_register_single_string', $this->_context,
                            $this->sanitize_option_basename( $option, $field->id ) . ' description',
		                    stripslashes( $option->description ) );
                }
            }
        }
    }

    public function deleted_field_action( $field ) {
	    if ( function_exists( 'icl_unregister_string' ) ) {
		    // Unregister name
		    if ( ! empty( $field->name ) ) {
			    icl_unregister_string( $this->_context,
				    "{$this->_field_string_prefix}{$field->id} name", $field->name );
		    }
		    // Unregister description
		    if ( ! empty( $field->description ) ) {
			    icl_unregister_string( $this->_context,
				    "{$this->_field_string_prefix}{$field->id} description", $field->description );
		    }
		    // Unregister options
		    if ( in_array( $field->type, self::FIELD_TYPES_WITH_OPTIONS ) ) {
			    $bp_field = xprofile_get_field( $field->id );
			    $options  = $bp_field->get_children();
			    foreach ( $options as $option ) {
				    if ( ! empty( $option->name ) ) {
					    icl_unregister_string( $this->_context,
						    $this->sanitize_option_basename( $option, $field->id ) . ' name',
						    $option->name );
				    }
				    if ( ! empty( $option->description ) ) {
					    icl_unregister_string( $this->_context,
						    $this->sanitize_option_basename( $option, $field->id ) . ' description',
						    $option->description );
				    }
			    }
		    }
	    }
    }

    public function saved_group_action( $group ) {
        // Register name
        if ( !empty( $group->name ) ) {
	        do_action( 'wpml_register_single_string', $this->_context,
                    "{$this->_group_string_prefix}{$group->id} name", $group->name );
        }
        // Register description
        if ( !empty( $group->description ) ) {
	        do_action( 'wpml_register_single_string', $this->_context,
                    "{$this->_group_string_prefix}{$group->id} description", $group->description );
        }
    }

    public function deleted_group_action( $group ) {
	    if ( function_exists( 'icl_unregister_string' ) ) {
		    // Unregister name
		    if ( ! empty( $group->name ) ) {
			    icl_unregister_string( $this->_context,
				    "{$this->_group_string_prefix}{$group->id} name", $group->name );
		    }
		    // Unregister description
		    if ( ! empty( $group->description ) ) {
			    icl_unregister_string( $this->_context,
				    "{$this->_group_string_prefix}{$group->id} description", $group->description );
		    }
	    }
    }

    /**
     * @param int    $meta_id
     * @param int    $field_id
     * @param string $key
     * @param string $value
     */
    public function update_alternate_name( $meta_id, $field_id, $key, $value ) {
        $this->add_alternate_name( $field_id, $key, $value );
    }

    /**
     * @param int    $field_id
     * @param string $key
     * @param string $value
     */
    public function add_alternate_name( $field_id, $key, $value ) {
        if ( 'alternate_name' === $key && $value ) {
	        do_action( 'wpml_register_single_string', $this->_context,
                    "{$this->_field_string_prefix}{$field_id} alternate name", stripslashes( $value ) );
        }
    }

    /**
     * @param array $meta_ids
     * @param int   $field_id
     * @param string $key
     * @param string $value
     */
    public function delete_alternate_name( $meta_ids, $field_id, $key ) {
	    if ( function_exists( 'icl_unregister_string' ) ) {
		    if ( 'alternate_name' === $key ) {
			    icl_unregister_string( $this->_context,
				    "{$this->_field_string_prefix}{$field_id} alternate name" );
		    }
	    }
    }

    /**
     * @param string $value
     * @param string $name
     *
     * @return string
     */
    private function translate( $value, $name ) {
        return stripslashes( apply_filters( 'wpml_translate_single_string', $value, $this->_context, $name ) );
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function translate_name( $name ) {
        global $field;

        return $this->translate( $name, $this->_field_string_prefix . $field->id . ' name' );
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function translate_alternate_name( $name ) {
        global $field;

        return $this->translate( $name, $this->_field_string_prefix . $field->id . ' alternate name' );
    }

    /**
     * @param string $description
     *
     * @return string
     */
    public function translate_description( $description ) {
        global $field;

        return $this->translate( $description, $this->_field_string_prefix . $field->id . ' description' );
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function translate_options( $options ) {
        global $field;

        foreach ( $options as &$option ) {
            // Just translate description. Name can messup forms.
            if ( !empty( $option->description ) ) {
                $option->description = $this->translate( $option->description, $this->sanitize_option_basename( $option, $field->id ) . ' description' );
            }
        }

        return $options;
    }

    /**
     * @param object $option
     * @param int    $field_id
     *
     * @return string
     */
    private function translate_option_name( $option, $field_id ) {
        if ( ! empty( $option->name ) ) {
            return $this->translate( $option->name, $this->sanitize_option_basename( $option, $field_id ) . ' name' );
        }

        return isset( $option->name ) ? $option->name : '';
    }

    /**
     * @param object $option
     * @param int    $field_id
     *
     * @return string
     */
    private function sanitize_option_basename( $option, $field_id ) {
        $sanitized_string = bpml_sanitize_string_name( $option->name, 30 );

        return $this->_field_string_prefix . $field_id . " - option '" . $sanitized_string . "'";
    }

    /**
     * Adjusts HTML output for radio field.
     */
    public function translate_radio( $html, $option, $field_id, $selected, $k ) {
        $label = $this->translate_option_name( $option, $field_id );

        return preg_replace( "/\>{$option->name}\<\/label\>/", ">{$label}</label>", $html, 1 );
    }

    /**
     * Adjusts HTML output for checkbox field.
     */
    public function translate_checkbox( $html, $option, $field_id, $selected, $k ) {
        return $this->translate_radio( $html, $option, $field_id, $selected, $k );
    }

    /**
     * Adjusts HTML output for select field.
     */
    public function translate_select_option( $html, $option, $field_id, $selected, $k ) {
        $label = $this->translate_option_name( $option, $field_id );
        return preg_replace( '/"\>(.*)\<\/option\>/', "\">{$label}</option>", $html );
    }

    /**
     * Adjusts HTML output for multiselect field.
     */
    public function translate_multiselect_option( $html, $option, $field_id, $selected, $k ) {
        return $this->translate_select_option( $html, $option, $field_id, $selected, $k );
    }

    /**
     * Filters field values on profile view template.
     */
    public function translate_value_profile_view( $value, $field_type ) {
        global $field;

        // Only for fields with options
        if ( in_array( $field_type, self::FIELD_TYPES_WITH_OPTIONS ) ) {
            $bp_field = xprofile_get_field( $field->id );
            $options = $bp_field->get_children();
            switch ( $field_type ) {
                case 'radio':
                case 'selectbox':
                    $_value = false;
                    foreach ( $options as $option ) {
                        if ( isset($option->name) && $option->name == $field->data->value ) {
                            $_value = $this->translate_option_name( $option, $field->id );
                        }
                    }
                    if ( $_value ) {
                        // Expected format is search link
                        $value = str_replace( ">{$field->data->value}</a>", ">{$_value}</a>", $value, $count );
                        if ( ! $count ) {
                            $value = $_value;
                        }
                    }
                    break;

                case 'multiselectbox':
                case 'checkbox':
                    foreach ( $options as $option ) {
                        $_value = $this->translate_option_name( $option, $field->id );
                        // Expected format is search link
                        $value = str_replace( ">{$option->name}</a>", ">{$_value}</a>", $value, $count );
                        // CSV list
                        if ( ! $count && strpos( $value, $option->name ) !== false ) {
                            $_ex_values = explode( ',', $value );
                            if ( !empty( $_ex_values ) ) {
                                foreach ( $_ex_values as &$v ) {
                                    if ( trim( $v ) == $option->name ) {
                                        $v = $_value;
                                    }
                                }
                                $value = implode( ', ', $_ex_values );
                            }
                        }
                    }
                    break;

                default:
                    break;
            }
        }

        return $value;
    }

    /**
     * @param string $group_name
     *
     * @return string
     */
    public function translate_group_name( $group_name ) {
        $cache_key = 'bpml_xprofile_group_id_by_name_' . md5( $group_name );
        $group_id = wp_cache_get( $cache_key );
        if ( false === $group_id ) {
            global $wpdb, $bp;
            $sql = $wpdb->prepare( "SELECT id FROM {$bp->profile->table_name_groups} WHERE name=%s", $group_name );
            $group_id = $wpdb->get_var( $sql );
            wp_cache_set( $cache_key, $group_id );
        }
        
        return $group_id ? $this->translate( $group_name, $this->_group_string_prefix . $group_id . ' name' ) : $group_name;
    }

    /**
     * Translates radio/checkbox values in member profile data.
     *
     * @param array $data
     * @param array $args
     *
     * @return array
     */
    public function translate_data( $data, $args ) {
        $field_id = xprofile_get_field_id_from_name( $args['field'] );
        if ( $field_id ) {
            $field = xprofile_get_field( $field_id, null, false );
            if ( $field && in_array( $field->type_obj->field_obj->type, self::FIELD_TYPES_WITH_OPTIONS ) ) {
                $data = $this->translate_option_name( (object) [ 'name' => $data ], $field_id );
            }
        }

        return $data;
    }

    protected function verify_nonce() {
        if ( !wp_verify_nonce( $_POST['nonce'], 'bpml-xprofile' ) ) {
            die('0');
        }
        return true;
    }

    protected function _scan_needed() {
        if ( function_exists( 'icl_st_is_registered_string' )
             && $groups = bp_xprofile_get_groups( array('fetch_fields' => true) ) ) {
            foreach ( $groups as $group ) {
                $is_registered = icl_st_is_registered_string($this->_context,
                    "{$this->_group_string_prefix}{$group->id} name");
                if ( !$is_registered ) {
                    return true;
                }
                if ( !empty( $group->fields ) && is_array( $group->fields ) ) {
                    foreach ( $group->fields as $field ) {
                        $is_registered = icl_st_is_registered_string( $this->_context,
                            "{$this->_field_string_prefix}{$field->id} name" );
                        if ( !$is_registered ) {
                            return true;
                        }
                        $meta = bp_xprofile_get_meta( $field->id, 'field', 'alternate_name', true );
                        if ( $meta ) {
                            $is_registered = icl_st_is_registered_string( $this->_context,
                                "{$this->_field_string_prefix}{$field->id} alternate name" );
                            if ( ! $is_registered ) {
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    public function scan_needed_warning() {
        echo '<div class="updated error"><p>'
                . __('Buddypress Multilingual: some profile fields are not registered for translation','bpml')
                . '&nbsp;<a class="button edit js-bpml-register-fields" href="javascript:void(0)" data-bpml="nonce='
                . wp_create_nonce( 'bpml-xprofile' )
                . '&action=bpml_register_fields">'
                . __('Register fields','bpml') . '</a>'
                . '</p></div>';
    }

    public function ajax_register() {
        $response = '0';
        if ( $this->verify_nonce() ) {
            $this->register_fields();
            $response = __( 'Fields registered', 'bpml' );
        }
        die( $response );
    }

}
