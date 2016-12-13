<?php
/**
 * Enables BP multilingual components on frontend using various filters.
 */
class BPML_Filters
{
    protected $_icl_ls_languages, $bp_current_page_id;

    public function __construct() {
        // Filter BP AJAX URL (add query args 'lang' and '_bpml_ac')
        add_filter( 'bp_core_ajax_url', array($this, 'core_ajax_url_filter') );
        // Filter language switcher
        add_filter( 'icl_ls_languages', array($this, 'icl_ls_languages_filter'), 99 );
        // Adjust BP pages IDs
        add_filter( 'bp_core_get_directory_page_ids', array($this, 'filter_page_ids'), 0 );
        // WPML Convert URL
        add_filter( 'bp_core_get_root_domain', array($this, 'core_get_root_domain_filter'), 0 );
        add_filter( 'bp_uri', array($this, 'uri_filter'), 0 );
        // Remove WPML post availability
        add_action( 'bp_ready', array($this, 'remove_wpml_post_availability_hook') );
	    add_action( 'bp_init', function(){
		    if ( bp_is_activity_component() ) {
			    add_filter( 'parse_query', array( $this, 'wpml_fix_activity_redirection' ), 5 );
		    }
	    }, 999 );
    }

    /**
     * Filters site_url() calls.
     */
    public function site_url_filter( $url ) {
        global $sitepress;
        return rtrim( $sitepress->convert_url( $url ), '/' );
    }

    /**
     * Filters BuddyPress root domain.
     */
    public function core_get_root_domain_filter( $url ) {
        return $this->site_url_filter( $url );
    }

    /**
     * Filters bp_uri.
     *
     * This URI is important for BuddyPress.
     * By that it determines some components and actions.
     * We remove language component so BP can determine things right.
     * 
     * @todo Review regex.
     */
    public function uri_filter( $url ) {
        if ( bpml_is_language_per_domain() ) {
            return $url;
        }
        $language = apply_filters( 'wpml_current_language', null );
        return preg_replace('/(\/|^)' . $language . '\//', '$1', $url, 1);
    }

    /**
     * WPML language switcher filter.
     * 
     * Appends BP URI components to language switcher base URLs
     * and fixes bug with $wp_query->queried_object_id provoked by BP.
     */
    public function icl_ls_languages_filter( $languages ) {

        if ( !bp_is_blog_page() ) {

            if ( !is_null( $this->_icl_ls_languages ) ) {
                return $this->_icl_ls_languages;
            }

            global $sitepress, $bp, $wp_query;

            // Set page
            if ( !empty( $wp_query->queried_object_id )
                    && get_post_type( $wp_query->queried_object_id ) == 'page' ) {
                $page = get_post( $wp_query->queried_object_id );
            } else {
                $pagename = $bp->unfiltered_uri[$bp->unfiltered_uri_offset];
                $args = array(
                    'name' => $pagename,
                    'post_type' => 'page',
                    'posts_per_page' => 1
                );
                $posts = get_posts( $args );
                $page = array_shift( $posts );
            }

            if ( !empty( $page->ID ) ) {
                /*
                 * If languages are empty (WPML failed in setting language switcher data)
                 * re-create language switcher data.
                 * 
                 * Only case so far known is when WP_Query queried_object is messed up by BP.
                 * BP sets queried object to be BP content type, but it's fake WP_Post without ID.
                 */
	            // @todo Add persistent message for admin to report and mark as deprecated
                if ( empty( $languages )
                        && method_exists( $sitepress, 'set_wp_query' )
                        && method_exists( $sitepress, 'get_ls_languages' ) ) {

                    // Clone original $wp_query
                    $_wp_query = clone $wp_query;
                    // Fix query
                    $wp_query->queried_object_id = $page->ID;
                    $wp_query->queried_object = $page;
                    $sitepress->set_wp_query();
                    remove_filter( 'icl_ls_languages', array($this, 'icl_ls_languages_filter') );
                    // Re-create language switcher data
                    $languages = $sitepress->get_ls_languages();
                    add_filter( 'icl_ls_languages', array($this, 'icl_ls_languages_filter') );
                    // Restore $wp_query
                    unset( $wp_query );
                    global $wp_query;
                    $wp_query = clone $_wp_query;
                    unset( $_wp_query );
                    $sitepress->set_wp_query();
                }
                /*
                 * Append all URI components after base component.
                 * For example member screen:
                 * {http://localhost/es/miembros}/{keir/profile/view/}
                 */
                if ( is_array( $languages ) && get_option( 'permalink_structure' ) != '' ) {
                    $unfiltered_uri = $bp->unfiltered_uri;
                    $offset = intval( $bp->unfiltered_uri_offset ) + 1;
                    $append_array = array_slice( $unfiltered_uri, $offset );
                    $append = implode( '/', $append_array );
                    $current_language = apply_filters( 'wpml_current_language', null );
                    foreach ( $languages as $code => &$language ) {
                        $translated_page_id = apply_filters( 'wpml_object_id', $page->ID, 'page', false, $code );
                        if ( $translated_page_id ) {
                            do_action( 'wpml_switch_language', $code );
                            $page_permalink = untrailingslashit( get_permalink( $translated_page_id ) );
                            do_action( 'wpml_switch_language', $current_language );
                            $language['url'] = "{$page_permalink}/{$append}";
                        }
                    }
                }
            }
            $this->_icl_ls_languages = $languages;
        }

        return $languages;
    }

    public function remove_wpml_post_availability_hook() {
        if ( !bp_is_blog_page() ) {
            global $icl_language_switcher;
            remove_filter( 'the_content',
                    array($icl_language_switcher, 'post_availability'), 100 );
        }
    }

    public function core_ajax_url_filter( $url ){
        $url = add_query_arg( array(
            'lang' => apply_filters( 'wpml_current_language', null ),
            'bpml_filter' => 'true',
            ), $url );
        return $url;
    }

    public function filter_page_ids( $page_ids = array() ){
        foreach( $page_ids as $k => &$page_id ){
            $page_id = apply_filters( 'wpml_object_id', $page_id, 'page', true );
        }
        return $page_ids;
    }

    public function wpml_fix_activity_redirection( $q ){
	    if ( empty( $q->get( 'page_id' ) ) && !empty( $q->get( 'pagename' ) ) ) {
		    if ( is_null( $this->bp_current_page_id ) ) {
			    $bp_pages = bp_core_get_directory_pages();
			    $bp_current_component = bp_current_component();
			    if ( isset( $bp_pages->members->id ) ) {
				    $this->bp_current_page_id = $bp_pages->members->id;
			    } else if ( isset( $bp_pages->{$bp_current_component}->id ) ) {
				    $this->bp_current_page_id = $bp_pages->{$bp_current_component}->id;
			    } else {
				    // Failed - add BuddyPress main page(s)
				    $this->bp_current_page_id = 0;
			    }
		    }
		    $q->set( 'page_id', $this->bp_current_page_id ) ;
	    }
	    return $q;
    }

}

new BPML_Filters();