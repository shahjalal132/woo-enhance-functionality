<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Credentials_Options;
use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Singleton;

class APIS {

    use Singleton;
    use Program_Logs;
    use Credentials_Options;

    public function __construct() {
        // $this->load_credentials_options();
        $this->setup_hooks();
    }

    public function setup_hooks() {
        // Register REST API action
        add_action( 'rest_api_init', [ $this, 'register_apis_route' ] );
    }

    public function register_apis_route() {
        register_rest_route( 'api/v1', '/get-product-ids', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_selected_product_ids' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'cid' => [
                    'required'          => true,
                    'validate_callback' => function ($param) {
                        return is_numeric( $param ) && $param > 0;
                    },
                ],
            ],
        ] );
    }

    public function get_selected_product_ids( $request ) {
        // Get category ID from request
        $category_id = intval( $request->get_param( 'cid' ) );

        if ( !$category_id ) {
            return new \WP_Error( 'invalid_category', 'Invalid or missing category ID', [ 'status' => 400 ] );
        }

        // Query for products in the given category
        $args = [
            'post_type'      => 'product',
            'posts_per_page' => -1, // Get all products
            'fields'         => 'ids', // Only get product IDs
            'tax_query'      => [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $category_id, // Dynamic category ID
                ],
            ],
        ];

        $product_ids = get_posts( $args );

        if ( empty( $product_ids ) ) {
            return new \WP_Error( 'no_products', 'No products found in this category.', [ 'status' => 404 ] );
        }

        put_program_logs( 'Products ids for category ' . $category_id . ' are: ' . json_encode( $product_ids ) );
        return rest_ensure_response( [ 'product_ids' => $product_ids ] );
    }
}
