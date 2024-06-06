<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AWS_Order' ) ) :

    /**
     * Class for products sorting
     */
    class AWS_Order {

        /**
         * @var AWS_Order Array of products
         */
        private $products = array();

        /**
         * Constructor
         */
        public function __construct( $products, $query ) {

            $this->products = array_map( array($this, 'get_product_id'), $products );

            // Filter
            $this->filter_results( $query );

            // Order
            if ( $query->query && ( isset( $query->query['orderby'] ) || isset( $query->query_vars['orderby'] ) || isset( $_GET['orderby'] ) ) ) {
                $this->order( $query );
            }

        }

        /*
         * Filter search results
         */
        private function filter_results( $query ) {

            $new_products = array();
            $filters = array();
            $attr_filter = array();

            if ( isset( $query->query_vars['meta_query'] ) ) {
                $meta_query = $query->query_vars['meta_query'];

                if ( isset( $meta_query['price_filter'] ) && isset( $meta_query['price_filter']['value'] ) ) {
                    $price_values = $meta_query['price_filter']['value'];
                    if ( isset( $price_values[0] ) && isset( $price_values[1] ) ) {
                        $filters['price_min'] = $price_values[0];
                        $filters['price_max'] = $price_values[1];
                    }
                }

            }

            if ( ! isset( $filters['on_sale'] ) && isset( $_GET['on_sale'] ) ) {
                $filters['on_sale'] = in_array( sanitize_text_field( $_GET['on_sale'] ), array( '1', 'true', true, 'yes' ) );
            }

            if ( ! isset( $filters['in_status'] ) && isset( $_GET['in_stock'] ) ) {
                $filters['in_status'] = in_array( sanitize_text_field( $_GET['in_stock'] ), array( '1', 'true', true, 'yes', 'instock', 'in_stock' ) );
            }

            if ( ! isset( $filters['price_min'] ) && isset( $_GET['min_price'] ) ) {
                $filters['price_min'] = sanitize_text_field( $_GET['min_price'] );
            }

            if ( ! isset( $filters['price_max'] ) && isset( $_GET['max_price'] ) ) {
                $filters['price_max'] = sanitize_text_field( $_GET['max_price'] );
            }

            if ( isset( $filters['price_min'] ) && ! isset( $filters['price_max'] ) ) {
                $filters['price_max'] = 99999;
            }
            if ( ! isset( $filters['price_min'] ) && isset( $filters['price_max'] ) ) {
                $filters['price_min'] = 0;
            }

            if ( ! isset( $filters['rating'] ) ) {
                $ratingf = false;
                if ( isset( $_GET['rating_filter'] ) && $_GET['rating_filter'] ) {
                    $ratingf = $_GET['rating_filter'];
                } elseif ( isset( $_GET['rating'] ) && $_GET['rating'] ) {
                    $ratingf = $_GET['rating'];
                }
                if ( $ratingf ) {
                    $filters['rating'] = explode( ',', sanitize_text_field( $ratingf ) );
                }
            }

            if ( isset( $_GET['filtering'] ) && $_GET['filtering'] && isset( $_GET['filter_product_brand'] ) ) {
                $filters['brand'] = explode( ',', sanitize_text_field( $_GET['filter_product_brand'] ) );
            }

            if ( isset( $query->query_vars['tax_query'] ) ) {
                $tax_query = $query->query_vars['tax_query'];

                if ( $tax_query && is_array( $tax_query ) && ! empty( $tax_query ) ) {
                    foreach( $tax_query as $taxonomy_query ) {
                        if ( is_array( $taxonomy_query ) ) {
                            if ( isset( $taxonomy_query['taxonomy'] ) && strpos( $taxonomy_query['taxonomy'], 'pa_' ) === 0 ) {
                                $tax_name = $taxonomy_query['taxonomy'];
                                $attr_filter[$tax_name] = $taxonomy_query;
                            }
                        }
                    }
                }

            }

            if ( empty( $attr_filter ) && class_exists('WC_Query') && method_exists( 'WC_Query', 'get_layered_nav_chosen_attributes' ) && count( WC_Query::get_layered_nav_chosen_attributes() ) > 0  ) {
                foreach ( WC_Query::get_layered_nav_chosen_attributes() as $taxonomy => $data ) {
                    $attr_filter[$taxonomy] = $data;
                }
            }


            /**
             * Filter available search page filters before apply
             * @since 2.04
             * @param array $filters Filters
             * @param object $query Current query ( since 3.08 )
             */
            $filters = apply_filters( 'aws_search_page_filters', $filters, $query );


            foreach( $this->products as $product_id ) {

                if ( isset( $filters['in_status'] ) ) {
                    $f_stock = 'outofstock' !== get_post_meta( $product_id, '_stock_status', true );
                    if ( $f_stock !== $filters['in_status'] ) {
                        continue;
                    }
                }

                if ( isset( $filters['on_sale'] ) ) {

                    $regular_price = get_post_meta( $product_id, '_regular_price', true );
                    $sale_price = get_post_meta( $product_id, '_sale_price', true );

                    $is_on_sale = false;
                    if ( '' !== (string) $sale_price && $regular_price > $sale_price ) {
                        $is_on_sale = true;
                    }

                    if ( $is_on_sale !== $filters['on_sale'] ) {
                        continue;
                    }

                }

                if ( isset( $filters['price_min'] ) && isset( $filters['price_max'] ) ) {
                    $price = get_post_meta( $product_id, '_price', true );
                    if ( $price ) {
                        if ( $price > $filters['price_max'] || $price < $filters['price_min'] ) {
                            continue;
                        }
                    }
                }

                if ( isset( $filters['rating'] ) && is_array( $filters['rating'] ) ) {
                    $average_rating = get_post_meta( $product_id, '_wc_average_rating', true );
                    if ( $average_rating ) {
                        if ( array_search( floor( $average_rating ), $filters['rating'] ) === false ) {
                            continue;
                        }
                    }
                }

                if ( isset( $filters['brand'] ) && is_array( $filters['brand'] ) ) {

                    $parent_id = wp_get_post_parent_id( $product_id );
                    if ( ! $parent_id ) {
                        $parent_id = $product_id;
                    }

                    $skip = true;
                    $p_brands = get_the_terms( $parent_id, 'product_brand' );

                    if ( ! is_wp_error( $p_brands ) && ! empty( $p_brands ) ) {
                        foreach ( $p_brands as $p_brand ) {
                            if ( in_array( $p_brand->term_id, $filters['brand'] ) ) {
                                $skip = false;
                                break;
                            }
                        }
                    }

                    if ( $skip ) {
                        continue;
                    }

                }

                if ( isset( $filters['tax'] ) && is_array( $filters['tax'] ) ) {

                    $skip = true;

                    foreach( $filters['tax'] as $taxonomy => $taxonomy_terms ) {

                        $parent_id = wp_get_post_parent_id( $product_id );
                        if ( ! $parent_id ) {
                            $parent_id = $product_id;
                        }

                        $terms = get_the_terms( $parent_id, $taxonomy );
                        $operator = isset( $taxonomy_terms['operator'] ) ? $taxonomy_terms['operator'] : 'OR';
                        $include_parent = isset( $taxonomy_terms['include_parent'] ) ? $taxonomy_terms['include_parent'] : false;
                        $term_arr = array();

                        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                            foreach ( $terms as $term ) {
                                $term_arr[] = $term->term_id;
                                if ( $include_parent && $term->parent ) {
                                    $term_arr[] = $term->parent;
                                    $term_parent = get_term( $term->parent, $taxonomy );
                                    while ( ! is_wp_error( $term_parent ) && ! empty( $term_parent->parent ) && ! in_array( $term_parent->parent, $term_arr, true ) ) {
                                        $term_arr[]  = (int) $term_parent->parent;
                                        $term_parent = get_term( $term_parent->parent, $taxonomy );
                                    }
                                }
                            }
                        } elseif( strpos( $taxonomy, 'pa_' ) !== 0 ) {

                            if ( $parent_id !== $product_id && class_exists( 'WC_Product_Variation' ) ) {
                                $terms = array();
                                $variation_product = new WC_Product_Variation( $product_id );
                                if ( $variation_product && method_exists( $variation_product, 'get_attributes' ) ) {
                                    $variation_attr = $variation_product->get_attributes();
                                    if ( $variation_attr && is_array( $variation_attr ) ) {
                                        foreach( $variation_attr as $variation_p_att => $variation_p_text ) {
                                            if ( strpos( $variation_p_att, 'pa_' ) === 0 ) {
                                                $attr_term = get_term_by( 'slug', $variation_p_text, $variation_p_att );
                                                if ( ! is_wp_error( $attr_term ) && $attr_term && $attr_term->name ) {
                                                    $terms[] = $attr_term;
                                                }
                                            }
                                        }
                                    }
                                }
                            } else {
                                $terms = get_the_terms( $product_id, 'pa_' . $taxonomy );
                            }

                            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                                foreach ( $terms as $term ) {
                                    $term_arr[] = $term->term_id;
                                    if ( $include_parent && $term->parent ) {
                                        $term_arr[] = $term->parent;
                                        $term_parent = get_term( $term->parent, $taxonomy );
                                        while ( ! is_wp_error( $term_parent ) && ! empty( $term_parent->parent ) && ! in_array( $term_parent->parent, $term_arr, true ) ) {
                                            $term_arr[]  = (int) $term_parent->parent;
                                            $term_parent = get_term( $term_parent->parent, $taxonomy );
                                        }
                                    }
                                }
                            }
                        }

                        $skip = AWS_Helpers::page_filter_tax( $term_arr, $taxonomy_terms['terms'], $operator );

                        if ( $skip ) {
                            break;
                        }

                    }

                    if ( $skip ) {
                        continue;
                    }

                }

                if ( $attr_filter && ! empty( $attr_filter ) && is_array( $attr_filter ) ) {

                    $product = wc_get_product( $product_id );
                    $attributes = $product->get_attributes();
                    $product_terms_array = array();
                    $skip = true;

                    if ( $attributes && ! empty( $attributes ) ) {

                        foreach( $attributes as $attr_name => $attribute_object ) {
                            if ( $attribute_object ) {
                                if ( ( is_object( $attribute_object ) && method_exists( $attribute_object, 'is_taxonomy' ) && $attribute_object->is_taxonomy() ) ||
                                    ( is_array( $attribute_object ) && isset( $attribute_object['is_taxonomy'] ) && $attribute_object['is_taxonomy'] )
                                ) {
                                    if ( isset( $attr_filter[$attr_name] ) ) {
                                        $product_terms = wp_get_object_terms( $product_id, $attr_name );

                                        if ( ! is_wp_error( $product_terms ) && ! empty( $product_terms ) ) {
                                            foreach ( $product_terms as $product_term ) {
                                                $product_terms_array[] = ! empty( $attr_filter[$attr_name]['terms'] ) && preg_match( '/[a-z\-\.\,]/i', $attr_filter[$attr_name]['terms'][0] ) ? $product_term->slug : $product_term->term_id;
                                            }
                                        }

                                    }
                                }
                            }
                        }

                        if ( $product_terms_array ) {

                            foreach( $attr_filter as $attr_filter_name => $attr_filter_object ) {

                                $operator = isset( $attr_filter_object['operator'] ) ? $attr_filter_object['operator'] : ( isset( $attr_filter_object['query_type'] ) ? $attr_filter_object['query_type'] : 'OR' ) ;
                                $attr_filter_terms = $attr_filter_object['terms'];

                                $skip = AWS_Helpers::page_filter_tax( $product_terms_array, $attr_filter_terms, $operator );

                                if ( $skip ) {
                                    break;
                                }

                            }

                        }

                    }

                    if ( $skip ) {
                        continue;
                    }

                }

                $new_products[] = $product_id;

            }

            /**
             * Filter search results after search page filters applied
             * @since 3.08
             * @param array $new_products Array of products IDs
             */
            $this->products = apply_filters( 'aws_search_page_products_filtered', $new_products );

        }

        /*
         * Sort products
         */
        private function order( $query ) {

            if ( isset( $_GET['orderby'] ) && $_GET['orderby'] ) {

                $order_by = sanitize_text_field( $_GET['orderby'] );

            } elseif ( isset( $query->query['orderby'] ) ) {

                $order_by = $query->query['orderby'];

            } else {

                $order_by = $query->query_vars['orderby'];

                if ( $order_by === 'meta_value_num' ) {
                    $order_by = 'price';
                }

                if ( is_array( $order_by ) ) {
                    $order_by = isset( $order_by[0] ) ? $order_by[0] : '';
                }

                if ( $order_by && isset( $query->query_vars['order'] ) ) {
                    $order_by = $order_by . '-' . strtolower( $query->query_vars['order'] );
                }

            }

            /**
             * Filter order by value
             * @since 2.13
             * @param string $order_by Order by value
             * @param object $query Page query
             */
            $order_by = apply_filters( 'aws_products_order_by', $order_by, $query );

            switch( $order_by ) {

                case 'price':
                case 'price-asc':
                case 'low_high':

                    usort( $this->products, array( $this, 'compare_price_asc' ) );

                    break;

                case 'price-desc':
                case 'high_low':

                    usort( $this->products, array( $this, 'compare_price_desc' ) );

                    break;

                case 'date':
                case 'date-desc':

                    usort( $this->products, array( $this, 'compare_date' ) );

                    break;

                case 'date-asc':

                    usort( $this->products, array( $this, 'compare_date_asc' ) );

                    break;

                case 'rating':
                case 'rating-desc':

                    usort( $this->products, array( $this, 'compare_rating' ) );

                    break;

                case 'rating-asc':

                    usort( $this->products, array( $this, 'compare_rating_asc' ) );

                    break;

                case 'popularity':
                case 'popularity-desc':

                    usort( $this->products, array( $this, 'compare_reviews' ) );

                    break;

                case 'popularity-asc':

                    usort( $this->products, array( $this, 'compare_reviews_asc' ) );

                    break;

                case 'title':
                case 'title-desc':
                case 'za':

                    usort( $this->products, array( $this, 'compare_title' ) );

                    break;

                case 'title-asc':
                case 'az':

                    usort( $this->products, array( $this, 'compare_title' ) );
                    $this->products = array_reverse($this->products);

                    break;

                case 'stock_quantity-asc':

                    usort( $this->products, array( $this, 'compare_f_quantity_asc' ) );

                    break;

                case 'stock_quantity-desc':

                    usort( $this->products, array( $this, 'compare_f_quantity_desc' ) );

                    break;

            }

            /**
             * Filter search results after ordering
             * @since 3.08
             * @param array $this->products Array of products IDs
             * @param string $order_by Order by value
             */
            $this->products = apply_filters( 'aws_search_page_products_order', $this->products, $order_by );

        }

        /*
         * Compare price values asc
         */
        private function compare_price_asc( $a, $b ) {

            $price_a = get_post_meta( $a, '_price', true );
            $price_b = get_post_meta( $b, '_price', true );

            $price_a = intval( $price_a ) * 100;
            $price_b = intval( $price_b ) * 100;

            if ( ! is_numeric( $price_a ) || ! is_numeric( $price_b ) ) {
                return 0;
            }

            if ($price_a == $price_b) {
                return 0;
            }

            return ($price_a < $price_b) ? -1 : 1;

        }

        /*
         * Compare price values desc
         */
        private function compare_price_desc( $a, $b ) {

            $price_a = get_post_meta( $a, '_price', true );
            $price_b = get_post_meta( $b, '_price', true );

            $price_a = intval( $price_a ) * 100;
            $price_b = intval( $price_b ) * 100;

            if ( ! is_numeric( $price_a ) || ! is_numeric( $price_b ) ) {
                return 0;
            }

            if ($price_a == $price_b) {
                return 0;
            }

            return ($price_a < $price_b) ? 1 : -1;

        }

        /*
         * Compare date
         */
        private function compare_date( $a, $b ) {

            $post_a = get_post( $a );
            $post_b = get_post( $b );

            if ( ! $post_a || ! $post_b ) {
                return 0;
            }

            $date_a = strtotime( $post_a->post_date );
            $date_b = strtotime( $post_b->post_date );

            if ($date_a == $date_b) {
                return 0;
            }

            return ($date_a < $date_b) ? 1 : -1;

        }

        /*
         * Compare date desc
         */
        private function compare_date_asc( $a, $b ) {

            $post_a = get_post( $a );
            $post_b = get_post( $b );

            if ( ! $post_a || ! $post_b ) {
                return 0;
            }

            $date_a = strtotime( $post_a->post_date );
            $date_b = strtotime( $post_b->post_date );

            if ($date_a == $date_b) {
                return 0;
            }

            return ($date_a < $date_b) ? -1 : 1;

        }

        /*
         * Compare rating
         */
        private function compare_rating( $a, $b ) {

            $rating_a = get_post_meta( $a, '_wc_average_rating', true );
            $rating_b = get_post_meta( $b, '_wc_average_rating', true );

            if ( ! $rating_a || ! $rating_b ) {
                return 0;
            }

            $rating_a = intval( $rating_a * 100 );
            $rating_b = intval( $rating_b * 100 );

            if ($rating_a == $rating_b) {
                return 0;
            }

            return ($rating_a < $rating_b) ? 1 : -1;

        }

        /*
         * Compare rating asc
         */
        private function compare_rating_asc( $a, $b ) {

            $rating_a = get_post_meta( $a, '_wc_average_rating', true );
            $rating_b = get_post_meta( $b, '_wc_average_rating', true );

            if ( ! $rating_a || ! $rating_b ) {
                return 0;
            }

            $rating_a = intval( $rating_a * 100 );
            $rating_b = intval( $rating_b * 100 );

            if ($rating_a == $rating_b) {
                return 0;
            }

            return ($rating_a < $rating_b) ? -1 : 1;

        }

        /*
         * Compare popularity
         */
        private function compare_reviews( $a, $b ) {

            $reviews_a = get_post_meta( $a, '_wc_review_count', true );
            $reviews_b = get_post_meta( $b, '_wc_review_count', true );

            if ( ! $reviews_a || ! $reviews_b ) {
                return 0;
            }

            $reviews_a = intval( $reviews_a * 100 );
            $reviews_b = intval( $reviews_b * 100 );

            if ($reviews_a == $reviews_b) {
                return 0;
            }

            return ($reviews_a < $reviews_b) ? 1 : -1;

        }

        /*
         * Compare rating asc
         */
        private function compare_reviews_asc( $a, $b ) {

            $reviews_a = get_post_meta( $a, '_wc_review_count', true );
            $reviews_b = get_post_meta( $b, '_wc_review_count', true );

            if ( ! $reviews_a || ! $reviews_b ) {
                return 0;
            }

            $reviews_a = intval( $reviews_a * 100 );
            $reviews_b = intval( $reviews_b * 100 );

            if ($reviews_a == $reviews_b) {
                return 0;
            }

            return ($reviews_a < $reviews_b) ? -1 : 1;

        }

        /*
         * Compare titles
         */
        private function compare_title( $a, $b ) {

            $title_a = get_the_title( $a );
            $title_b = get_the_title( $b );

            $res = strcasecmp( $title_a, $title_b );

            return $res;

        }

        /*
         * Compare quantity values asc
         */
        private function compare_f_quantity_asc( $a, $b ) {

            $product_a = wc_get_product( $a );
            $product_b = wc_get_product( $b );

            if ( ! is_a( $product_a, 'WC_Product' ) || ! is_a( $product_b, 'WC_Product' ) ) {
                return 0;
            }

            $a_val = AWS_Helpers::get_quantity( $product_a );
            $b_val = AWS_Helpers::get_quantity( $product_b );

            if ($a_val == $b_val) {
                return 0;
            }

            return ($a_val < $b_val) ? -1 : 1;

        }

        /*
         * Compare quantity values desc
         */
        private function compare_f_quantity_desc( $a, $b ) {

            $product_a = wc_get_product( $a );
            $product_b = wc_get_product( $b );

            if ( ! is_a( $product_a, 'WC_Product' ) || ! is_a( $product_b, 'WC_Product' ) ) {
                return 0;
            }

            $a_val = AWS_Helpers::get_quantity( $product_a );
            $b_val = AWS_Helpers::get_quantity( $product_b );

            if ($a_val == $b_val) {
                return 0;
            }

            return ($a_val > $b_val) ? -1 : 1;

        }

        /*
         * Check that products array contains only IDs
         */
        public function get_product_id( $pr ) {

            $product_id = $pr;

            if ( is_array( $pr ) ) {

                if ( isset( $pr['id'] ) ) {
                    $product_id = $pr['id'];
                } else {
                    $product_id = 0;
                }

            }

            return $product_id;

        }

        /*
         * Return array of sorted products
         */
        public function result() {

            return $this->products;

        }

    }

endif;