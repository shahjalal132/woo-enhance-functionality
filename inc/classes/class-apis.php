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
        // Initialize hooks when the class is instantiated
        $this->setup_hooks();
    }

    public function setup_hooks() {
        // Register custom REST API routes
        add_action( 'rest_api_init', [ $this, 'register_apis_route' ] );
    }

    public function register_apis_route() {
        // Register API endpoint to fetch product IDs based on category ID
        register_rest_route( 'api/v1', '/get-product-ids', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_selected_product_ids' ],
            'permission_callback' => '__return_true', // Allow public access
            'args'                => [
                'cid' => [
                    'required'          => true,
                    'validate_callback' => function ($param) {
                        return is_numeric( $param ) && $param > 0;
                    },
                ],
            ],
        ] );

        // Register API endpoint to set dropdown values based on category ID
        register_rest_route( 'api/v1', '/set-dropdowns', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'set_dropdowns' ],
            'permission_callback' => '__return_true', // Allow public access
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
        // Get category ID from request parameters
        $category_id = intval( $request->get_param( 'cid' ) );

        if ( !$category_id ) {
            return new \WP_Error( 'invalid_category', 'Invalid or missing category ID', [ 'status' => 400 ] );
        }

        // Query for product IDs in the given category
        $args = [
            'post_type'      => 'product',
            'posts_per_page' => -1, // Retrieve all products
            'fields'         => 'ids', // Fetch only product IDs
            'tax_query'      => [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $category_id, // Filter by selected category ID
                ],
            ],
        ];

        $product_ids = get_posts( $args );

        if ( empty( $product_ids ) ) {
            return new \WP_Error( 'no_products', 'No products found in this category.', [ 'status' => 404 ] );
        }

        // Log retrieved product IDs for debugging
        put_program_logs( 'Product IDs for category ' . $category_id . ' are: ' . json_encode( $product_ids ) );

        return rest_ensure_response( [ 'product_ids' => $product_ids ] );
    }

    public function set_dropdowns( $request ) {
        // Get category ID from request parameters
        $category_id = intval( $request->get_param( 'cid' ) );

        if ( !$category_id ) {
            return new \WP_Error( 'invalid_category', 'Invalid or missing category ID', [ 'status' => 400 ] );
        }

        // Query for product IDs in the given category
        $args = [
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $category_id,
                ],
            ],
        ];

        $product_ids = get_posts( $args );

        if ( empty( $product_ids ) ) {
            return new \WP_Error( 'no_products', 'No products found in this category.', [ 'status' => 404 ] );
        }

        // Define file paths for dropdown values based on category ID
        $category_grillage_rigide_dropdowns_file_path = PLUGIN_BASE_PATH . '/data/category-grillage-rigide-metabox.json';
        $category_kit_occultant_dropdowns_file_path   = PLUGIN_BASE_PATH . '/data/category-kit-occultant-metabox.json';
        $category_portillons_dropdowns_file_path      = PLUGIN_BASE_PATH . '/data/category-portillons-metabox.json';
        $dropdowns_file_path                          = null;

        // Assign the correct dropdown file based on category ID
        if ( $category_id == 39 ) {
            $dropdowns_file_path = $category_grillage_rigide_dropdowns_file_path;
        } else if ( $category_id == 36 ) {
            $dropdowns_file_path = $category_kit_occultant_dropdowns_file_path;
        } else if ( $category_id == 50 ) {
            $dropdowns_file_path = $category_portillons_dropdowns_file_path;
        }

        if ( !empty( $product_ids ) && is_array( $product_ids ) ) {
            // Load dropdown values from the selected JSON file
            $dropdowns = json_decode( file_get_contents( $dropdowns_file_path ), true );

            foreach ( $product_ids as $product_id ) {
                // Store dropdown values as post meta for each product
                update_post_meta( $product_id, '_dropdowns', $dropdowns );
            }
        }

        // Send success response
        wp_send_json_success( [ 'message' => 'Dropdowns set successfully.' ] );
    }
}