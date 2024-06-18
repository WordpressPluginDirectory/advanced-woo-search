<?php
/**
 * The7 theme support
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'AWS_The7' ) ) :

    /**
     * Class
     */
    class AWS_The7 {

        /**
         * Main AWS_The7 Instance
         *
         * Ensures only one instance of AWS_The7 is loaded or can be loaded.
         *
         * @static
         * @return AWS_The7 - Main instance
         */
        protected static $_instance = null;

        /**
         * Main AWS_The7 Instance
         *
         * Ensures only one instance of AWS_The7 is loaded or can be loaded.
         *
         * @static
         * @return AWS_The7 - Main instance
         */
        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Constructor
         */
        public function __construct() {

            if ( AWS()->get_settings( 'seamless' ) === 'true' ) {

                add_filter( 'aws_js_seamless_selectors', array( $this, 'js_seamless_selectors' ) );

                add_filter( 'aws_js_seamless_searchbox_markup', array( $this, 'aws_js_seamless_searchbox_markup' ), 1 );

            }

        }

        /*
         * Selector filter of js seamless
         */
        public function js_seamless_selectors( $selectors ) {
            $selectors[] = '.searchform.mini-widget-searchform .searchform-s';
            return $selectors;
        }

        /*
         * Change search form markup for js seamless
         */
        public function aws_js_seamless_searchbox_markup( $form_html ) {

            $form_html = str_replace( '<form', '<div', $form_html );
            $form_html = str_replace( '</form>', '</div>', $form_html );
            $form_html = str_replace( 'aws-search-field', 'aws-search-field field searchform-s', $form_html );
            $form_html = '<style>
                .mini-widget-searchform .aws-container.aws-js-seamless + .search-icon { display: none; } 
                .mini-widget-searchform .aws-container.aws-js-seamless .aws-search-field.field.searchform-s { padding: 6px; }
                .mini-widget-searchform .aws-container.aws-js-seamless .aws-form-btn { border-width: 0px; }
                .mini-widget-searchform .overlay-search-wrap { width: 400px; max-width: 100%; }
            </style>' . $form_html;

            return $form_html;

        }

    }

endif;

AWS_The7::instance();