<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AWS_Option_Vars' ) ) :

    /**
     * Class to get main plugin variables and conditions
     */
    class AWS_Option_Vars {

        /**
         * @var AWS_Option_Vars Array of custom data
         */
        public $data = array();

        /*
         * Initialize a new instance of the WordPress license class
         */
        public function __construct() {
        }

        /*
         * Get plugin version
         * @return int
         */
        public function get_plugin_version() {

            if ( isset( $this->data['plugin_version'] ) ) {
                return $this->data['plugin_version'];
            }

            $plugin_version = get_option( 'aws_plugin_ver' );

            $this->data['plugin_version'] = $plugin_version;

            return $plugin_version;

        }

        /*
         * Get index table version
         * @return int
         */
        public function get_index_table_version() {

            if ( isset( $this->data['index_table_version'] ) ) {
                return $this->data['index_table_version'];
            }

            $index_table_version = get_option( 'aws_index_table_version' );

            $this->data['index_table_version'] = $index_table_version;

            return $index_table_version;

        }

        /*
         * Get table reindex version
         * @return int
         */
        public function get_reindex_version() {

            if ( isset( $this->data['reindex_version'] ) ) {
                return $this->data['reindex_version'];
            }

            $reindex_version = get_option( 'aws_reindex_version' );

            $this->data['reindex_version'] = $reindex_version;

            return $reindex_version;

        }

        /*
         * Is index table exists
         * @return bool
         */
        public function is_index_table_not_exists() {

            if ( isset( $this->data['is_index_table_not_exists'] ) ) {
                return $this->data['is_index_table_not_exists'];
            }

            global $wpdb;

            $table_name = $wpdb->prefix . AWS_INDEX_TABLE_NAME;

            $result = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );

            $is_not_exists = empty( $result );

            $this->data['is_index_table_not_exists'] = $is_not_exists;

            return $is_not_exists;

        }

        /*
         * Is cache table exists
         * @return bool
         */
        public function is_cache_table_not_exists() {

            if ( isset( $this->data['is_cache_table_not_exists'] ) ) {
                return $this->data['is_cache_table_not_exists'];
            }

            global $wpdb;

            $table_name = $wpdb->prefix . AWS_CACHE_TABLE_NAME;

            $result = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );

            $is_not_exists = empty( $result );

            $this->data['is_cache_table_not_exists'] = $is_not_exists;

            return $is_not_exists;

        }

    }

endif;