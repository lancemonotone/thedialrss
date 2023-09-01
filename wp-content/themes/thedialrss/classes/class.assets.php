<?php namespace thedial;

class Assets {
    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueueScripts' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminScripts' ] );
    }

    /**
     * Enqueue scripts for pages using templates defined in
     * class ACF, which use new Vite build system.
     */
    public function enqueueScripts() {
        $file_path = THEME_BUILD_PATH . '/js/index.js';
        $file_uri  = THEME_BUILD_URI . '/js/index.js';

        if ( ! file_exists( $file_path ) ) {
            return;
        }

        $version = file_exists( $file_path ) ? filemtime( $file_path ) : wp_get_theme()->get( 'Version' );

        wp_enqueue_script( 'the-dial', $file_uri, null, $version, true );
        wp_localize_script( 'the-dial', 'ajax_object', [ 'ajax_url' => admin_url( 'admin-ajax.php' ) ] );
    }

    public function enqueueAdminScripts() {
        $file_path = THEME_BUILD_ADMIN_PATH . '/js/admin.js';
        $file_uri  = THEME_BUILD_ADMIN_URI . '/js/admin.js';

        if ( ! file_exists( $file_path ) ) {
            return;
        }

        $version   = file_exists( $file_path ) ? filemtime( $file_path ) : wp_get_theme()->get( 'Version' );

        wp_enqueue_script( 'the-dial-admin', $file_uri, null, $version, true );
        wp_localize_script( 'the-dial-admin', 'ajax_object', [ 'ajax_url' => admin_url( 'admin-ajax.php' ) ] );
    }


}

new Assets();
