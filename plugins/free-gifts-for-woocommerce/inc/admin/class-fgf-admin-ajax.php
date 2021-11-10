<?php

/**
 * Admin Ajax.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}
if ( ! class_exists( 'FGF_Admin_Ajax' ) ) {

	/**
	 * FGF_Admin_Ajax Class.
	 */
	class FGF_Admin_Ajax {

		/**
		 * Class initialization.
		 */
		public static function init() {

			$actions = array(
				'json_search_products_and_variations' => false ,
				'json_search_products'                => false ,
				'json_search_customers'               => false ,
				'json_search_coupons'                 => false ,
				'create_gift_order'                   => false ,
				'master_log_info_popup'               => false ,
				'gift_products_pagination'            => true ,
				'drag_rules_list'                     => false ,
				'reset_rule_usage_count'              => false ,
				'add_gift_product'                    => true ,
				'update_gift_products_content'        => true ,
					) ;

			foreach ( $actions as $action => $nopriv ) {
				add_action( 'wp_ajax_fgf_' . $action , array( __CLASS__ , $action ) ) ;

				if ( $nopriv ) {
					add_action( 'wp_ajax_nopriv_fgf_' . $action , array( __CLASS__ , $action ) ) ;
				}
			}
		}

		/**
		 * Search for products.
		 */
		public static function json_search_products( $term = '', $include_variations = false ) {
			check_ajax_referer( 'search-products' , 'fgf_security' ) ;

			try {

				if ( empty( $term ) && isset( $_GET[ 'term' ] ) ) {
					$term = isset( $_GET[ 'term' ] ) ? wc_clean( wp_unslash( $_GET[ 'term' ] ) ) : '' ;
				}

				if ( empty( $term ) ) {
					throw new exception( esc_html__( 'No Products found' , 'free-gifts-for-woocommerce' ) ) ;
				}

				if ( ! empty( $_GET[ 'limit' ] ) ) {
					$limit = absint( $_GET[ 'limit' ] ) ;
				} else {
					$limit = absint( apply_filters( 'woocommerce_json_search_limit' , 30 ) ) ;
				}

				$data_store = WC_Data_Store::load( 'product' ) ;
				$ids        = $data_store->search_products( $term , '' , ( bool ) $include_variations , false , $limit ) ;

				$product_objects = fgf_filter_readable_products( $ids ) ;
				$products        = array() ;

				$exclude_global_variable = isset( $_GET[ 'exclude_global_variable' ] ) ? wc_clean( wp_unslash( $_GET[ 'exclude_global_variable' ] ) ) : 'no' ; // @codingStandardsIgnoreLine.
				foreach ( $product_objects as $product_object ) {
					if ( 'yes' == $exclude_global_variable && $product_object->is_type( 'variable' ) ) {
						continue ;
					}

					$products[ $product_object->get_id() ] = rawurldecode( $product_object->get_formatted_name() ) ;
				}

				wp_send_json( apply_filters( 'woocommerce_json_search_found_products' , $products ) ) ;
			} catch ( Exception $ex ) {
				wp_die() ;
			}
		}

		/**
		 * Search for product variations.
		 */
		public static function json_search_products_and_variations( $term = '', $include_variations = false ) {
			self::json_search_products( '' , true ) ;
		}

		/**
		 * Customers search.
		 */
		public static function json_search_customers() {
			check_ajax_referer( 'fgf-search-nonce' , 'fgf_security' ) ;

			try {
				$term = isset( $_GET[ 'term' ] ) ? wc_clean( wp_unslash( $_GET[ 'term' ] ) ) : '' ; // @codingStandardsIgnoreLine.

				if ( empty( $term ) ) {
					throw new exception( esc_html__( 'No Customer found' , 'free-gifts-for-woocommerce' ) ) ;
				}

				$exclude = isset( $_GET[ 'exclude' ] ) ? wc_clean( wp_unslash( $_GET[ 'exclude' ] ) ) : '' ; // @codingStandardsIgnoreLine.
				$exclude = ! empty( $exclude ) ? array_map( 'intval' , explode( ',' , $exclude ) ) : array() ;

				$found_customers = array() ;
				$customers_query = new WP_User_Query(
						array(
					'fields'         => 'all' ,
					'orderby'        => 'display_name' ,
					'search'         => '*' . $term . '*' ,
					'search_columns' => array( 'ID' , 'user_login' , 'user_email' , 'user_nicename' ) ,
						)
						) ;
				$customers       = $customers_query->get_results() ;

				if ( fgf_check_is_array( $customers ) ) {
					foreach ( $customers as $customer ) {
						if ( ! in_array( $customer->ID , $exclude ) ) {
							$found_customers[ $customer->ID ] = $customer->display_name . ' (#' . $customer->ID . ' &ndash; ' . sanitize_email( $customer->user_email ) . ')' ;
						}
					}
				}

				wp_send_json( $found_customers ) ;
			} catch ( Exception $ex ) {
				wp_die() ;
			}
		}

		/**
		 * Coupon search.
		 */
		public static function json_search_coupons() {
			check_ajax_referer( 'fgf-search-nonce' , 'fgf_security' ) ;

			try {
				$term = isset( $_GET[ 'term' ] ) ? wc_clean( wp_unslash( $_GET[ 'term' ] ) ) : '' ; // @codingStandardsIgnoreLine.

				if ( empty( $term ) ) {
					throw new exception( esc_html__( 'No Coupon found' , 'free-gifts-for-woocommerce' ) ) ;
				}

				global $wpdb ;
				$like = '%' . $wpdb->esc_like( $term ) . '%' ;

				$search_results = array_filter( $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT ID as id, post_title as name FROM {$wpdb->posts}
			WHERE post_type='shop_coupon' AND post_status IN('publish')
                        AND (post_title LIKE %s) ORDER BY post_title ASC" , $like ) , ARRAY_A ) ) ;

				$found_coupons = array() ;

				if ( fgf_check_is_array( $search_results ) ) {
					foreach ( $search_results as $search_result ) {
						$found_coupons[ $search_result[ 'id' ] ] = $search_result[ 'name' ] . ' (#' . $search_result[ 'id' ] . ')' ;
					}
				}

				wp_send_json( $found_coupons ) ;
			} catch ( Exception $ex ) {
				wp_die() ;
			}
		}

		/**
		 * Create order for selected user with gift products.
		 */
		public static function create_gift_order() {
			check_ajax_referer( 'fgf-manual-gift-nonce' , 'fgf_security' ) ;

			try {
				if ( ! isset( $_POST ) ) {
					throw new exception( esc_html__( 'Invalid Request' , 'free-gifts-for-woocommerce' ) ) ;
				}

				if ( ! isset( $_POST[ 'user' ] ) || empty( absint( $_POST[ 'user' ] ) ) ) { // @codingStandardsIgnoreLine.
					throw new exception( esc_html__( 'Please select a User' , 'free-gifts-for-woocommerce' ) ) ;
				}

				if ( ! isset( $_POST[ 'products' ] ) || empty( wc_clean( wp_unslash( ( $_POST[ 'products' ] ) ) ) ) ) { // @codingStandardsIgnoreLine.
					throw new exception( esc_html__( 'Please select atleast one Product' , 'free-gifts-for-woocommerce' ) ) ;
				}

				// Sanitize post values
				$user_id      = ! empty( $_POST[ 'user' ] ) ? absint( $_POST[ 'user' ] ) : 0 ; // @codingStandardsIgnoreLine.
				$products     = ! empty( $_POST[ 'products' ] ) ? wc_clean( wp_unslash( ( $_POST[ 'products' ] ) ) ) : array() ; // @codingStandardsIgnoreLine.
				$order_status = ! empty( $_POST[ 'status' ] ) ? wc_clean( wp_unslash( ( $_POST[ 'status' ] ) ) ) : '' ; // @codingStandardsIgnoreLine.
				// Create order for selected user with gift products
				$order_id     = FGF_Manual_Gift_Order_Handler::create_free_gift_order( $user_id , $products , $order_status ) ;

				$msg = esc_html__( 'Free Gift has been sent successfully' , 'free-gifts-for-woocommerce' ) ;

				wp_send_json_success( array( 'msg' => $msg ) ) ;
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) ) ;
			}
		}

		/**
		 * Display Gift Products based on pagination.
		 */
		public static function gift_products_pagination() {
			check_ajax_referer( 'fgf-gift-products-pagination' , 'fgf_security' ) ;

			try {
				if ( ! isset( $_POST ) || ! isset( $_POST[ 'page_number' ] ) ) { // @codingStandardsIgnoreLine.
					throw new exception( esc_html__( 'Invalid Request' , 'free-gifts-for-woocommerce' ) ) ;
				}

				// Sanitize post values
				$current_page = ! empty( $_POST[ 'page_number' ] ) ? absint( $_POST[ 'page_number' ] ) : 0 ; // @codingStandardsIgnoreLine.
				$page_url     = ! empty( $_POST[ 'page_url' ] ) ? wc_clean( wp_unslash( $_POST[ 'page_url' ] ) ) : '' ; // @codingStandardsIgnoreLine.

				$per_page = fgf_get_free_gifts_per_page_column_count() ;
				$offset   = ( $current_page - 1 ) * $per_page ;

				// Get gift products based on per page count
				$gift_products = FGF_Rule_Handler::get_manual_gift_products() ;
				$gift_products = array_slice( $gift_products , $offset , $per_page ) ;

				// Get gift products table body content
				$html = fgf_get_template_html(
						'gift-products.php' , array(
					'gift_products' => $gift_products ,
					'permalink'     => esc_url( $page_url ) ,
						)
						) ;

				wp_send_json_success( array( 'html' => $html ) ) ;
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) ) ;
			}
		}

		/**
		 * Display master log gift products information as Popup.
		 */
		public static function master_log_info_popup() {
			check_ajax_referer( 'fgf-master-log-info-nonce' , 'fgf_security' ) ;

			try {
				if ( ! isset( $_POST ) || ! isset( $_POST[ 'master_log_id' ] ) ) { // @codingStandardsIgnoreLine.
					throw new exception( esc_html__( 'Invalid Request' , 'free-gifts-for-woocommerce' ) ) ;
				}

				// Sanitize post values
				$master_log_id = ! empty( $_POST[ 'master_log_id' ] ) ? absint( $_POST[ 'master_log_id' ] ) : 0 ; // @codingStandardsIgnoreLine.

				$master_log_object = fgf_get_master_log( $master_log_id ) ;

				// Get master log popup content
				ob_start() ;
				include_once 'menu/views/master-log-popup.php' ;
				$popup = ob_get_clean() ;

				wp_send_json_success( array( 'popup' => $popup ) ) ;
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) ) ;
			}
		}

		/**
		 * Drag Rules.
		 */
		public static function drag_rules_list() {
			check_ajax_referer( 'fgf-rules-drag-nonce' , 'fgf_security' ) ;

			try {
				if ( ! isset( $_POST ) || ! isset( $_POST[ 'sort_order' ] ) ) { // @codingStandardsIgnoreLine.
					throw new exception( esc_html__( 'Invalid Request' , 'free-gifts-for-woocommerce' ) ) ;
				}

				$sort_ids            = array() ;
				// Sanitize post values
				$post_sort_order_ids = ! empty( $_POST[ 'sort_order' ] ) ? wc_clean( wp_unslash( ( $_POST[ 'sort_order' ] ) ) ) : array() ; // @codingStandardsIgnoreLine.
				// prepare sort order post ids
				foreach ( $post_sort_order_ids as $key => $post_id ) {
					$sort_ids[ $key + 1 ] = str_replace( 'post-' , '' , $post_id ) ;
				}

				// update sort order post ids
				foreach ( $sort_ids as $menu_order => $post_id ) {
					wp_update_post(
							array(
								'ID'         => $post_id ,
								'menu_order' => $menu_order ,
							)
					) ;
				}

				wp_send_json_success() ;
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) ) ;
			}
		}

		/**
		 * Reset rule usage count.
		 */
		public static function reset_rule_usage_count() {
			check_ajax_referer( 'fgf-rules-nonce' , 'fgf_security' ) ;

			try {
				if ( ! isset( $_POST ) || ! isset( $_POST[ 'rule_id' ] ) ) { // @codingStandardsIgnoreLine.
					throw new exception( esc_html__( 'Invalid Request' , 'free-gifts-for-woocommerce' ) ) ;
				}

				// Sanitize post values
				$rule_id = absint( $_POST[ 'rule_id' ] ) ; // @codingStandardsIgnoreLine.
				// Reset rule usage count
				update_post_meta( $rule_id , 'fgf_rule_usage_count' , 0 ) ;

				wp_send_json_success( array( 'msg' => esc_html__( 'Order usage count reset successfully' , 'free-gifts-for-woocommerce' ) ) ) ;
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) ) ;
			}
		}

		/**
		 * Add a manual gift product to the cart.
		 */
		public static function add_gift_product() {
			check_ajax_referer( 'fgf-gift-product' , 'fgf_security' ) ;

			try {
				if ( ! isset( $_POST ) ) {
					throw new exception( esc_html__( 'Cannot process action' , 'free-gifts-for-woocommerce' ) ) ;
				}

				// Sanitize post values
				$product_id = ! empty( $_POST[ 'product_id' ] ) ? absint( $_POST[ 'product_id' ] ) : 0 ;
				$rule_id    = ! empty( $_POST[ 'rule_id' ] ) ? absint( $_POST[ 'rule_id' ] ) : 0 ;

				if ( empty( $product_id ) || empty( $rule_id ) ) {
					throw new exception( esc_html__( 'Cannot process action' , 'free-gifts-for-woocommerce' ) ) ;
				}

				// Return if cart object is not initialized.
				if ( ! is_object( WC()->cart ) ) {
					throw new exception( esc_html__( 'Cannot process action' , 'free-gifts-for-woocommerce' ) ) ;
				}

				// return if cart is empty
				if ( WC()->cart->get_cart_contents_count() == 0 ) {
					throw new exception( esc_html__( 'Cannot process action' , 'free-gifts-for-woocommerce' ) ) ;
				}

				// Restrict Adding gift product if gift products per order count exists
				if ( FGF_Rule_Handler::check_per_order_count_exists() ) {
					throw new exception( esc_html__( 'Cannot process action' , 'free-gifts-for-woocommerce' ) ) ;
				}

				$gift_products = FGF_Rule_Handler::get_manual_gift_products() ;
				if ( ! fgf_check_is_array( $gift_products ) ) {
					throw new exception( esc_html__( 'Cannot process action' , 'free-gifts-for-woocommerce' ) ) ;
				}

				$rule    = fgf_get_rule( $rule_id ) ;
				$product = wc_get_product( $product_id ) ;

				// return if product id is not proper product
				if ( ! $product ) {
					throw new exception( esc_html__( 'Cannot process action' , 'free-gifts-for-woocommerce' ) ) ;
				}

				// return if rule id is not proper rule
				if ( ! $rule->exists() ) {
					throw new exception( esc_html__( 'Cannot process action' , 'free-gifts-for-woocommerce' ) ) ;
				}

				$cart_item_data = array(
					'fgf_gift_product' => array(
						'mode'       => 'manual' ,
						'rule_id'    => $rule_id ,
						'product_id' => $product_id ,
						'price'      => apply_filters( 'fgf_manual_gift_product_price' , 0 , $rule_id , $product_id ) ,
					) ,
						) ;

				// Add a gift product in the cart.
				WC()->cart->add_to_cart( $product_id , '1' , 0 , array() , $cart_item_data ) ;

				$notice = get_option( 'fgf_settings_free_gift_success_message' ) ;
				if ( ! wc_has_notice( $notice ) ) {
					// Success Notice.
					wc_add_notice( $notice ) ;
				}

				wp_send_json_success() ;
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) ) ;
			}
		}

		/**
		 * Update the gift products content.
		 */
		public static function update_gift_products_content() {
			check_ajax_referer( 'fgf-gift-product' , 'fgf_security' ) ;

			// Return if data args does not exists.
			$data_args = FGF_Gift_Products_Handler::get_gift_product_data() ;
			if ( ! empty( $data_args ) ) {
				$data_args[ 'mode' ] = 'popup' ;
				// Display the gift products layout.
				fgf_get_template( $data_args[ 'template' ] , $data_args ) ;
			}

			wp_die() ;
		}

	}

	FGF_Admin_Ajax::init() ;
}