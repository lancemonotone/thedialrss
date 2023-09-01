<?php namespace thedial;

class Constants {
    var string $assets = '/assets';
    var string $build = '/assets/build';
    var string $admin = '/assets/build-admin';
    var string $classes = '/classes';

    public function __construct() {
        add_action( 'after_setup_theme', [
            $this,
            'add_constants'
        ], 0 );
    }

    /**
     * Add constants to be used throughout the theme.
     */
    public function add_constants() {
        define( 'THEME_PATH', get_stylesheet_directory() );
        define( 'THEME_ASSETS_PATH', get_stylesheet_directory() . $this->assets );

        define( 'THEME_BUILD_PATH', get_stylesheet_directory() . $this->build );
        define( 'THEME_BUILD_URI', get_stylesheet_directory_uri() . $this->build );
        define( 'THEME_BUILD_ADMIN_PATH', get_stylesheet_directory() . $this->admin );
        define( 'THEME_BUILD_ADMIN_URI', get_stylesheet_directory_uri() . $this->admin );

        define( 'THEME_CLASSES_PATH', get_stylesheet_directory() . $this->classes );
        define( 'THEME_CLASSES_URI', get_stylesheet_directory_uri() . $this->classes );

        define( 'CURRENT_LANG', apply_filters( 'wpml_current_language', null ) ?: 'en' );
    }
}

new Constants();
