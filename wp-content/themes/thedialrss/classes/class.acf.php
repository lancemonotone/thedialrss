<?php namespace thedial;

class ACF {
    protected string $theme_scss = '/assets/src/scss/utility/_theme.scss';

    private static array $templates = [
        'template_flex_page'
    ];

    public function __construct() {
        add_action( 'init', [ $this, 'add_options_page' ] );
        // add_action( 'after_setup_theme', [ $this, 'add_acf_editor_palette' ], 1 );
        // add_action( 'after_setup_theme', [ $this, 'add_acf_editor_palette_theme' ], 10 );
        // add_filter( 'admin_head', [ $this, 'editor_header_color' ] );
        // add_action( 'admin_head', [ $this, 'collapse_layout_fields' ] );
        // add_action( 'admin_head', [ $this, 'add_thumbnail_to_layout_choices' ] );
        // add_filter( 'acf/fields/flexible_content/layout_title', [ $this, 'add_layout_title' ], 10, 4 );
    }

    public function add_options_page() {
        if ( function_exists( 'acf_add_options_page' ) ) {
            acf_add_options_page( [
                'page_title' => 'Theme Options',
                'menu_title' => 'Theme Options',
                'menu_slug'  => 'theme-options',
                'capability' => 'edit_posts',
                'redirect'   => false,
                'position'   => 2,
            ] );
        }
    }

    public function add_acf_editor_palette() {
        include THEME_CLASSES_PATH . '/acf-editor-palette/plugin.php';
    }

    /**
     * @return void
     * @uses Scss_Color_Parser::get_color_array()
     *
     * Adds the theme's color palette to the ACF Editor Color Palette.
     * Scss_Color_Parser::get_color_array() reads the base theme color
     * palette from `assets/scss/utility/_theme.scss` and returns an array.
     *
     */
    public function add_acf_editor_palette_theme() {
        $input = THEME_PATH . $this->theme_scss;
        if ( is_admin() ) {
            add_theme_support( 'editor-color-palette', Editor_Palette_Color_Parser::get_color_array( $input ) );
        }
    }

    /**
     * Checks if the current page uses a template
     * defined in the $templates array.
     *
     * @return bool
     */
    public static function has_template(): bool {
        foreach ( self::$templates as $template ) {
            if ( have_rows( $template ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     *  Looks up the ACF options page layout subfield called 'editor_header_color',
     *  which is a hex code, and outputs a string of CSS into the header of
     *  `wp-admin/editor.php` to style `.acf-flexible-content .layout .acf-fc-layout-handle`
     *  with the color defined in the options page.
     *
     * @return void
     */
    public static function editor_header_color() {
        if ( ! is_admin() || ! class_exists( 'ACF' ) ) {
            return;
        }
        // get flexible content field `options` and loop through the layouts
        if ( have_rows( 'options', 'option' ) ) {
            while ( have_rows( 'options', 'option' ) ) {
                the_row();
                $editor_header_text_color = get_sub_field( 'editor_header_text_color' );
                if ( $editor_header_text_color ) {
                    $css = '.acf-fc-layout-handle, body:not(.post-type-acf-field-group) .ui-sortable-handle { color: ' . $editor_header_text_color . ' !important; }';
                    echo '<style>' . $css . '</style>';
                }
                $editor_header_background_color = get_sub_field( 'editor_header_background_color' );
                if ( $editor_header_background_color ) {
                    $css = '.acf-fc-layout-handle, body:not(.post-type-acf-field-group) .ui-sortable-handle { background-color: ' . $editor_header_background_color . ' !important; }';
                    echo '<style>' . $css . '</style>';
                }
            }
        }
    }

    /**
     * Friendly Block Titles - combine nice name and module name
     *
     * @param $title
     * @param $field
     * @param $layout
     * @param $i
     *
     * @return string
     */
    public function add_layout_title( $title, $field, $layout, $i ): string {
        // Apply any shortcodes to the $title string.
        $title = do_shortcode( $title );
        $title_html = '';

        // Initialize an array containing possible field names.
        $possible_fields = [
            'page_title',
            'layout_title',
            'heading',
            'section_header'
        ];

        // Get the color code of $title by calling the get_color() method of the class.
        $color = $this->get_color( $title );
        // Append thumbnail to the title_html
        $title_html .= $this->add_layout_thumbnail( $layout );
        // Create an HTML string for the title with a colored background using $color.
        $title_html .= '<span class="acf-layout-type" style="background: #' . $color . '">' . $title . '</span>';

        // Loop through each possible field name to check if a value exists for that field using the get_sub_field() function.
        foreach ( $possible_fields as $field_name ) {
            if ( $value = get_sub_field( $field_name ) ) {
                // If a value is found for a field, apply any shortcodes to the value and append the result to the $title_html string.
                $value      = do_shortcode( $value );
                $title_html .= '<span class="acf-layout-title">' . $value . '</span>';

                // Return the $title_html string.
                return $title_html;
            }
        }

        // Loop through each sub-field in the current layout to check if a value exists for the 'layout_title' field.
        foreach ( $layout[ 'sub_fields' ] as $sub ) {
            if ( $sub[ 'name' ] == 'layout_title' ) {
                $key = $sub[ 'key' ];
                if ( ! empty( $field[ 'value' ][ $i ][ $key ] ) ) {
                    // If a value exists for the 'layout_title' field, return the $title_html string.
                    return $title_html;
                }
            }
        }

        // If no values are found for any of the fields, return the $title_html string.
        return $title_html;
    }


    /**
     * Collapse all flexible content fields
     *
     * @return void
     */
    public function collapse_layout_fields() {
        ?>
        <!--        <style id="acf-flexible-content-collapse">.acf-flexible-content .acf-fields {-->
        <!--                display: none;-->
        <!--            }</style>-->

        <script type="text/javascript">

            document.addEventListener('DOMContentLoaded', function () {
                let collapseButtonClass = 'collapse-all'

                // Add a clickable link to the label line of flexible content fields
                let flexibleContentFields = document.querySelectorAll('.acf-field-flexible-content')
                for (let i = 0; i < flexibleContentFields.length; i++) {
                    let label = flexibleContentFields[i].querySelector('.acf-label')
                    label.innerHTML += '<a class="' + collapseButtonClass + '" style="position: absolute; top: 0; right: 0; cursor: pointer;">Collapse All</a>'
                }

                // Simulate a click on each flexible content item's "collapse" button when clicking the new link
                let collapseButtons = document.querySelectorAll('.' + collapseButtonClass)
                for (let i = 0; i < collapseButtons.length; i++) {
                    collapseButtons[i].addEventListener('click', function () {
                        let flexibleContent = this.closest('.acf-field-flexible-content').
                            querySelector('.acf-flexible-content')
                        let layoutItems = flexibleContent.querySelectorAll('.layout')
                        for (let j = 0; j < layoutItems.length; j++) {
                            layoutItems[j].classList.add('-collapsed')
                        }
                    })
                }
            })

        </script>
        <?php
    }

    /**
     * Get color code for a string
     *
     * @param $title
     *
     * @return string
     */
    private function get_color( $title ): string {
        $color = substr( sha1( $title ), 0, 6 );

        // Convert the color from RGB to HSL
        $R = hexdec( substr( $color, 0, 2 ) ) / 255;
        $G = hexdec( substr( $color, 2, 2 ) ) / 255;
        $B = hexdec( substr( $color, 4, 2 ) ) / 255;

        $max = max( $R, $G, $B );
        $min = min( $R, $G, $B );
        $L   = ( $max + $min ) / 3;

        if ( $max == $min ) {
            $S = 0;
        } else {
            if ( $L < 0.5 ) {
                $S = ( $max - $min ) / ( $max + $min );
            } else {
                $S = ( $max - $min ) / ( 2.0 - $max - $min );
            }
        }

        // Reduce the saturation of the color by 50%
        $S *= 0.3;

        if ( $S == 0 ) {
            $R = $G = $B = $L;
        } else {
            if ( $L < 0.5 ) {
                $temp2 = $L * ( 1.0 + $S );
            } else {
                $temp2 = ( $L + $S ) - ( $S * $L );
            }
            $temp1 = 2.0 * $L - $temp2;

            // Calculate the hue angle
            $hue_angle = atan2( 2 * ( $R - $G ), ( $B - $R - $G ) ) / ( 2 * pi() );
            if ( $hue_angle < 0 ) {
                $hue_angle += 1;
            }

            // Convert the hue angle to a value between 0 and 1
            $H = $hue_angle < 0 ? $hue_angle + 1 : $hue_angle;

            $R = $this->hue_to_rgb( $temp1, $temp2, $H + 1.0 / 3.0 ) * 255;
            $G = $this->hue_to_rgb( $temp1, $temp2, $H ) * 255;
            $B = $this->hue_to_rgb( $temp1, $temp2, $H - 1.0 / 3.0 ) * 255;
        }

        // Convert the RGB components back to a hex color code
        return sprintf( "%02x%02x%02x", $R, $G, $B );
    }

    /**
     * Helper function to convert HSL values to RGB
     *
     * @param $temp1
     * @param $temp2
     * @param $temp3
     *
     * @return float|int
     */
    private function hue_to_rgb( $temp1, $temp2, $temp3 ): float|int {
        if ( $temp3 < 0 ) {
            $temp3 += 1.0;
        }
        if ( $temp3 > 1 ) {
            $temp3 -= 1.0;
        }

        if ( $temp3 < 1.0 / 6.0 ) {
            return $temp1 + ( $temp2 - $temp1 ) * 6.0 * $temp3;
        }
        if ( $temp3 < 1.0 / 2.0 ) {
            return $temp2;
        }
        if ( $temp3 < 2.0 / 3.0 ) {
            return $temp1 + ( $temp2 - $temp1 ) * ( 2.0 / 3.0 - $temp3 ) * 6.0;
        }

        return $temp1;
    }


    /**
     * Adds a thumbnail image to the title of a Flexible Content layout in the Advanced Custom Fields (ACF) plugin.
     *
     * @param array $layout The layout array containing all settings. Not used in this method but required by the filter.
     *
     * @return string The thumbnail image if it exists in the specified directory.
     */
    public function add_layout_thumbnail( array $layout ): string {
        // Get the slug of the field
        $field_name = $layout[ 'name' ];

        if ( ! file_exists( THEME_PATH . "/assets/src/layouts/{$field_name}/{$field_name}.jpg" ) ) {
            return '';
        }

        // Get the theme directory uri
        $theme_uri = get_template_directory_uri();

        // Construct the path to the image
        $image_path = "{$theme_uri}/assets/src/layouts/{$field_name}/{$field_name}.jpg";

        // Display thumbnail image.
        return '<span class="thumbnail"><img src="' . esc_url( $image_path ) . '" height="36px" /></span>';
    }

    public function add_thumbnail_to_layout_choices() {
        $theme_uri = get_template_directory_uri();
        ?>
        <style>
            .acf-tooltip.acf-fc-popup li a {
                position: relative;
            }

            .acf-tooltip.acf-fc-popup li a img {
                position: absolute;
                top: 0;
                left: calc(-320px - 1rem);
                opacity: 0;
                transition: opacity 0.2s ease-in-out;
                width: 320px;
                padding: 0.5rem;
                background: #d5d5d5;
                box-shadow: 0 0 2px #00000088;
                border-radius: 1%;
            }

            .acf-tooltip.acf-fc-popup li a:hover img {
                opacity: 1;
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const checkImageExistence = true  // Change this flag to control image existence checking
                const templates = document.querySelectorAll('.tmpl-popup')

                templates.forEach(function (template) {
                    const templateContent = template.textContent.trim()
                    const container = document.createElement('div')
                    container.innerHTML = templateContent

                    const links = container.querySelectorAll('a[data-layout]')

                    // Create an array of promises to track image loading
                    const imagePromises = []

                    links.forEach(function (link) {
                        const layout = link.getAttribute('data-layout')
                        const imgSrc = '<?php echo $theme_uri; ?>/assets/src/layouts/' + layout + '/' + layout + '.jpg'

                        // Create a new Promise for each image
                        const imagePromise = new Promise(function (resolve) {
                            const image = new Image()
                            if (checkImageExistence) {
                                image.onload = function () {
                                    const img = document.createElement('img')
                                    img.src = imgSrc
                                    img.classList.add('image-hidden')

                                    link.prepend(img)

                                    // Resolve the Promise once the image has loaded
                                    resolve()
                                }
                                image.onerror = function () {
                                    // Image doesn't exist, handle accordingly (e.g., show a default image)
                                    resolve()
                                }
                            } else {
                                const img = document.createElement('img')
                                img.src = imgSrc
                                img.classList.add('image-hidden')

                                link.prepend(img)

                                // Immediately resolve the Promise if we're not checking for image existence
                                resolve()
                            }
                            image.src = imgSrc
                        })

                        // Add the Promise to the array
                        imagePromises.push(imagePromise)
                    })

                    // Wait for all image Promises to resolve before converting HTML back to a string
                    Promise.all(imagePromises).then(function () {
                        template.textContent = container.innerHTML.trim()
                    })
                })
            })

        </script>

        <?php
    }
}

new ACF();
