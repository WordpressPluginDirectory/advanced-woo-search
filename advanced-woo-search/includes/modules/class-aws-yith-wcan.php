<?php
/**
 * YITH WooCommerce Ajax Product Filter plugin support
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'AWS_YITH_WCAN' ) ) :

    /**
     * Class
     */
    class AWS_YITH_WCAN {

        /**
         * Main AWS_YITH_WCAN Instance
         *
         * Ensures only one instance of AWS_YITH_WCAN is loaded or can be loaded.
         *
         * @static
         * @return AWS_YITH_WCAN - Main instance
         */
        protected static $_instance = null;

        private $data = array();

        /**
         * Main AWS_YITH_WCAN Instance
         *
         * Ensures only one instance of AWS_YITH_WCAN is loaded or can be loaded.
         *
         * @static
         * @return AWS_YITH_WCAN - Main instance
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

            add_filter( 'yith_wcan_query_supported_parameters', array( $this, 'yith_wcan_query_supported_parameters' ) );

            add_filter( 'yith_wcan_suppress_cache', array( $this, 'yith_wcan_suppress_cache' ) );

            add_filter( 'aws_search_page_filters', array( $this, 'aws_search_page_filters' ), 10, 2 );

        }

        /*
         * Remove search query for query vars
         */
        public function yith_wcan_query_supported_parameters( $params ) {
            if ( isset( $_GET['type_aws'] ) ) {
                $index = array_search('s', $params);
                if ( $index !== false ) {
                    unset( $params[$index] );
                }
            }
            return $params;
        }

        /*
         * Disable cache
         */
        public function yith_wcan_suppress_cache( $r ) {
            if ( isset( $_GET['type_aws'] ) ) {
                $r = true;
            }
            return $r;
        }

        /*
         * Fix tax filters
         */
        public function aws_search_page_filters( $filters, $query ) {

            if ( $query && $query->query_vars && $query->query_vars && isset( $query->query_vars['yith_wcan_query'] ) ) {

                foreach ( $query->query_vars['yith_wcan_query'] as $taxonomy => $q_param ) {

                    if ( in_array( $taxonomy, array( 'product_cat', 'product_tag' ) ) ) {

                        $operator = strpos( $q_param, '+' ) !== false ? 'AND' : 'OR';
                        $explode_char = strpos( $q_param, '+' ) !== false ? '+' : ',';
                        $terms_arr = explode( $explode_char, $q_param );

                        if ( preg_match( '/[a-z]/', $q_param ) ) {
                            $new_terms_arr = array();
                            foreach ( $terms_arr as $term_slug ) {
                                $term = get_term_by('slug', $term_slug, $taxonomy );
                                if ( $term ) {
                                    $new_terms_arr[] = $term->term_id;
                                }
                            }
                            if ( $new_terms_arr ) {
                                $terms_arr = $new_terms_arr;
                            }
                        }

                        $filters['tax'][$taxonomy] = array(
                            'terms' => $terms_arr,
                            'operator' => $operator,
                            'include_parent' => false,
                        );

                    }

                }

            }

            return $filters;

        }
        
    }

endif;


AWS_YITH_WCAN::instance();