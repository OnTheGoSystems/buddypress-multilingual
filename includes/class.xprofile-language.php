<?php
/**
 * Enables BP profile language switcher
 */
class BPML_XProfile_Language
{

    public function __construct() {

        add_action( 'template_redirect', array( $this, 'set_lang_from_profile' ) );
        add_filter( 'bp_xprofile_get_field_types', array( $this, 'xprofile_field_types' ) );
    }

    public function xprofile_field_types( $field_types ) {
        $field_types['language']  = 'BP_XProfile_Field_Type_Language';
        return $field_types;
    }

    public function get_language_xprofile_field_id() {
        global $wpdb, $bp;

        $ids = $wpdb->get_col( "SELECT id FROM {$bp->profile->table_name_fields} WHERE type = 'language'" );

        if ( empty( $ids ) )
            return false;

        return $ids[0];
    }

    public function get_profile_language( $user_id = 0 ) {
        global $sitepress;

        $language = $sitepress->get_current_language();

        if ( ! $user_id )
            $user_id = get_current_user_id();

        if ( ! $user_id )
            return $language;

        $field_id = $this->get_language_xprofile_field_id();

        if ( ! $field_id )
            return $language;

        $data = new BP_XProfile_ProfileData( $field_id, $user_id );

        if ( ! $data->exists() )
            return $language;

        return $data->value;
    }

    public function set_lang_from_profile() {
        global $sitepress;

        $profile_language = $this->get_profile_language();

        if ( is_admin() )
            return;

        if ( is_404() ) {
            return;
        }

        if ( $sitepress->get_current_language() != $profile_language ) {

            $is_translated = true;
            if ( is_singular() ) {
                $object = get_queried_object();
                $is_translated = apply_filters( 'wpml_element_has_translations', NULL, $object->ID , $object->post_type );
            }

            $old_url =  (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $url = $sitepress->convert_url( $old_url, $profile_language );

            if ( $url != $old_url && $is_translated ) {
                if ( wp_redirect( $url ) )
                    exit();
            }
        }
    }


}

new BPML_XProfile_Language();