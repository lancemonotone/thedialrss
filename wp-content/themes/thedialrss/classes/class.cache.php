<?php namespace thedial;

// Create a class which adds a button to the toolbar which clears a set of
// transients starting with the prefix 'thedial_rss_'.
class Cache {
    public function __construct() {
        if ( current_user_can( 'edit_posts' ) ) {
            add_action( 'admin_bar_menu', [ $this, 'addClearCacheButton' ], 999 );
            add_action( 'wp_ajax_clear_cache', [ $this, 'clearCache' ] );
            add_action( 'wp_ajax_nopriv_clear_cache', [ $this, 'clearCache' ] );
            add_action ('acf/options_page/save', [ $this, 'clearCache' ], 10, 3);
        }
    }

    public function addClearCacheButton( $wp_admin_bar ) {
        $args = [
            'id'    => 'clear-rss-cache',
            'title' => 'Clear RSS Cache',
            'href'  => '#',
            'meta'  => [
                'class' => 'clear-rss-cache',
            ],
        ];
        $wp_admin_bar->add_node( $args );
    }

    // Clear all transients starting with 'thedial_rss_'.
    public static function clearCache() {
        global $wpdb;
        $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient%thedial_rss_%'" );
    }
}

new Cache();
