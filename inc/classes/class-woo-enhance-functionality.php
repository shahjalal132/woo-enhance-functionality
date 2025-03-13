<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Credentials_Options;
use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Singleton;

class Woo_Enhance_Functionality {

    use Singleton;
    use Program_Logs;
    use Credentials_Options;

    public function __construct() {
        $this->setup_hooks();
    }

    public function setup_hooks() {
        // custom product fields
        add_action( 'woocommerce_before_add_to_cart_form', [ $this, 'frontend_custom_product_fields' ], 15 );
        // add custom data to cart
        // add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_custom_data_to_cart' ], 10, 2 );
        // add custom data to order
        add_filter( 'woocommerce_get_item_data', [ $this, 'display_custom_data_in_cart' ], 10, 2 );
        // add custom data to order
        add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'add_custom_data_to_order' ], 10, 4 );
        // enqueue scripts
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

        // add to cart ajax handler
        add_action( 'wp_ajax_custom_add_to_cart', [ $this, 'handle_custom_add_to_cart_handler' ] );
        add_action( 'wp_ajax_nopriv_custom_add_to_cart', [ $this, 'handle_custom_add_to_cart_handler' ] );

        // proceed to checkout ajax handler
        add_action( 'wp_ajax_proceed_to_checkout', [ $this, 'handle_proceed_to_checkout_handler' ] );
        add_action( 'wp_ajax_nopriv_proceed_to_checkout', [ $this, 'handle_proceed_to_checkout_handler' ] );

        // handle height values ajax handler
        add_action( 'wp_ajax_handle_save_height_dropdown_value', [ $this, 'handle_save_height_dropdown_value_handler' ] );
        add_action( 'wp_ajax_nopriv_handle_save_height_dropdown_value', [ $this, 'handle_save_height_dropdown_value_handler' ] );

        // handle color values ajax handler
        add_action( 'wp_ajax_handle_save_color_dropdown_value', [ $this, 'handle_save_color_dropdown_value_handler' ] );
        add_action( 'wp_ajax_nopriv_handle_save_color_dropdown_value', [ $this, 'handle_save_color_dropdown_value_handler' ] );
    }

    public function handle_save_color_dropdown_value_handler() {
        // get product id and others values
        $productId     = isset( $_POST['productId'] ) ? intval( $_POST['productId'] ) : 0;
        $selectedColor = isset( $_POST['selectedColor'] ) ? sanitize_text_field( $_POST['selectedColor'] ) : '';

        if ( empty( $productId ) || empty( $selectedColor ) ) {
            wp_send_json_error( "Invalid data" );
        }

        // update post meta
        update_post_meta( $productId, '_selected_color', $selectedColor );

        // return success response
        wp_send_json_success( "Saved successfully" );
    }

    public function handle_save_height_dropdown_value_handler() {
        // get product id and others values
        $productId     = isset( $_POST['productId'] ) ? intval( $_POST['productId'] ) : 0;
        $selectedPrice = isset( $_POST['selectedPrice'] ) ? sanitize_text_field( $_POST['selectedPrice'] ) : '';
        // replace , to . to make it float
        $selectedPrice = str_replace( ',', '.', $selectedPrice );
        $selectedLabel = isset( $_POST['selectedLabel'] ) ? sanitize_text_field( $_POST['selectedLabel'] ) : '';

        if ( empty( $productId ) || empty( $selectedPrice ) || empty( $selectedLabel ) ) {
            wp_send_json_error( "Invalid data" );
        }

        // update post meta
        update_post_meta( $productId, '_selected_price', $selectedPrice );
        update_post_meta( $productId, '_selected_label', $selectedLabel );
        update_post_meta( $productId, '_price', $selectedPrice );

        // return success response
        wp_send_json_success( "Saved successfully" );
    }

    public function handle_custom_add_to_cart_handler() {
        $product_id        = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
        $custom_dropdown   = isset( $_POST['custom_dropdown'] ) ? $_POST['custom_dropdown'] : [];
        $unit_measurements = isset( $_POST['unit_measurements'] ) ? sanitize_text_field( $_POST['unit_measurements'] ) : '';
        $quantity          = isset( $_POST['quantity'] ) ? intval( $_POST['quantity'] ) : 1;

        $cart_item_data = [
            'custom_dropdown'   => $custom_dropdown,
            'unit_measurements' => $unit_measurements,
            'quantity'          => $quantity,
        ];

        WC()->cart->add_to_cart( $product_id, $quantity, 0, [], $cart_item_data );

        wp_send_json_success( [ 'cart_url' => wc_get_cart_url() ] );
    }

    public function handle_proceed_to_checkout_handler() {
        // get checkout page url
        $checkout_url = wc_get_checkout_url();

        // return success response
        wp_send_json_success( [ 'checkout_url' => $checkout_url ] );
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'woo-enhance-js', PLUGIN_PUBLIC_ASSETS_URL . "/js/woo-enhance.js", [ 'jquery' ], '1.0', true );
        wp_localize_script( 'woo-enhance-js', 'wooEnhanceParams', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'cart_url' => wc_get_cart_url(),
        ] );
    }

    public function frontend_custom_product_fields() {
        if ( !is_product() ) {
            return;
        }

        // get the product id
        $product_id = get_the_ID();
        // get the product title
        $product_title = get_the_title( $product_id );

        // get the product height
        $formatted_height = '';
        if ( preg_match( '/(?:H[auteur]*[:\s]*)?([\d,.]+)m/i', $product_title, $matches ) ) {
            $height           = str_replace( ',', '.', $matches[1] );
            $formatted_height = preg_replace( '/(\d+)\.(\d+)/', '$1m$2', $height );
        }

        $dropdowns = get_post_meta( $product_id, '_dropdowns', true );
        // put_program_logs( json_encode( $dropdowns ) );

        // get the excerpt
        $excerpt = get_post_field( 'post_excerpt', $product_id );

        // get the product price
        $product = wc_get_product( $product_id );
        // get the product price
        $price = $product->get_price();

        // Define placeholders based on product categories or specific products
        $unit_placeholder = '';
        if ( has_term( 'grillage-rigide', 'product_cat', $product->get_id() ) ) {
            $unit_placeholder = 'Panneau de 2m50 de longueur';
        } elseif ( has_term( 'kit-occultant', 'product_cat', $product->get_id() ) ) {
            $unit_placeholder = 'Kit pour 1 Panneau de 2m50 de longueur';
        }

        // define under quantity text
        // $under_quantity_text = !empty( $dropdowns ) ? "Panneau de 2m50 de longueur" : "Rouleau de 25M";
        $under_quantity_text = "Rouleau de 25M";
        // get the currency symbol
        $currency_symbol = get_woocommerce_currency_symbol();

        // put_program_logs( $unit_placeholder );

        ?>
        <div class="custom-product-options">

            <div class="excerpt-price-wrapper">

                <!-- hidden input to store product id -->
                <input type="hidden" name="product_id" id="current_product_id" value="<?php echo esc_attr( $product_id ); ?>">

                <div class="dropdown-not-selected-state">
                    <?php
                    if ( !empty( $dropdowns ) ) {
                        echo '<p>Sélectionnez une couleur et une hauteur ci-dessous pour afficher le prix</p>';
                    } else {
                        $main_price = $price . $currency_symbol;
                        echo "<h3 class='selected-price'> $main_price </h3>";
                    }
                    ?>
                </div>

                <!-- start: dropdown selected state -->
                <div class="dropdown-selected-state wef-d-none">
                    <div>
                        <h3 class="selected-price"><?php echo wc_price( $price ); ?></h3>
                    </div>

                    <div class="meta-description">
                        <p><?php echo wp_kses_post( $excerpt ); ?></p>
                    </div>
                </div>
                <!-- end: dropdown selected state -->
            </div>

            <div class="dropdown-wrapper">
                <?php
                if ( !empty( $dropdowns ) && is_array( $dropdowns ) ) {
                    foreach ( $dropdowns['outer_dropdown_repeater'] as $dropdown ) : ?>
                        <div class="dropdown-group">
                            <label><?php echo esc_html( $dropdown['outer_dropdown_name'] ); ?></label>
                            <select
                                name="custom_dropdown[<?php echo esc_attr( sanitize_title( $dropdown['outer_dropdown_name'] ) ); ?>]"
                                id="custom_dropdown[<?php echo esc_attr( sanitize_title( $dropdown['outer_dropdown_name'] ) ); ?>]">
                                <!-- Initial "Select" option -->
                                <option value=""><?php esc_html_e( 'Sélectionner une hauteur', 'wef' ); ?></option>

                                <?php foreach ( $dropdown['inner_dropdown_items'] as $item ) : ?>
                                    <option value="<?php echo esc_attr( $item['inner_dropdown_value'] ); ?>">
                                        <?php echo esc_html( $item['inner_dropdown_name'] ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach;
                }
                ?>
            </div>

            <div class="unit-measurement-wrapper wef-d-none">
                <label for="unit_measurement"><?php esc_html_e( 'Unité de mesure', 'wef' ); ?></label>
                <input type="text" id="unit_measurements" name="unit_measurements"
                    placeholder="<?php echo esc_attr( $unit_placeholder ); ?>">
            </div>

            <div class=" quantity-wrapper">

                <div class="quantity-field">
                    <label>Quantité</label>
                    <input type="number" class="text-center" id="wef-quantity" name="wef-quantity" value="1">

                    <div class="product-unit-wrapper text-center">
                        <p class="wef-d-none">Panneau de <span
                                class="replace-to-formatted-height"><?php echo esc_html( $formatted_height ); ?></span></p>
                        <p><?php echo $under_quantity_text; ?></p>
                    </div>
                </div>

                <div class="add-to-cart-button-wrapper">
                    <button id="custom-add-to-cart" data-product-id="<?php echo esc_attr( $product_id ); ?>" class="button alt">
                        <span class="text-center">Ajouter à mon devis</span>
                        <span class="add-to-cart-spinner-loader-wrapper"></span>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    public function add_custom_data_to_cart( $cart_item_data, $product_id ) {
        if ( isset( $_POST['custom_dropdown'] ) ) {
            $cart_item_data['custom_dropdown'] = wc_clean( $_POST['custom_dropdown'] );
        }
        if ( !empty( $_POST['unit_measurements'] ) ) {
            $cart_item_data['unit_measurements'] = sanitize_text_field( $_POST['unit_measurements'] );
        }
        if ( !empty( $_POST['quantity'] ) ) {
            $cart_item_data['quantity'] = intval( $_POST['quantity'] );
        }
        return $cart_item_data;
    }

    public function display_custom_data_in_cart( $item_data, $cart_item ) {
        if ( !empty( $cart_item['custom_dropdown'] ) ) {
            $dropdown_text   = 'Données déroulantes'; // Label text
            $dropdown_values = [];

            foreach ( $cart_item['custom_dropdown'] as $name => $value ) {
                // Extract dropdown name and remove "custom_dropdown[" prefix
                $name_clean        = preg_replace( '/custom_dropdown\[|\]/', '', $name );
                $name_clean        = ucwords( str_replace( '_', ' ', $name_clean ) ); // Format name
                $dropdown_values[] = $name_clean . ': ' . esc_html( $value );
            }

            if ( !empty( $dropdown_values ) ) {

                // get the product id and update post meta for dropdowns data
                // get product id
                $product_id = get_the_ID();
                // join dropdowns values
                $dropdowns_values = implode( ', ', $dropdown_values );
                // update post meta
                update_post_meta( $product_id, '_selected_dropdowns', $dropdowns_values );

                $item_data[] = array(
                    'name'  => $dropdown_text,
                    'value' => implode( ', ', $dropdown_values ), // Combine dropdowns into one line
                );
            }
        }

        if ( !empty( $cart_item['unit_measurements'] ) ) {
            $item_data[] = array(
                'name'  => 'Unités de mesure',
                'value' => esc_html( $cart_item['unit_measurements'] ),
            );
        }

        /* if ( !empty( $cart_item['quantity'] ) ) {
            $item_data[] = array(
                'name'  => 'Quantité',
                'value' => esc_html( $cart_item['quantity'] ),
            );
        } */

        return $item_data;
    }

    public function add_custom_data_to_order( $item, $cart_item_key, $values, $order ) {
        if ( !empty( $values['custom_dropdown'] ) ) {
            $item->add_meta_data( 'Dropdown Selections', $values['custom_dropdown'] );
        }
        if ( !empty( $values['unit_measurements'] ) ) {
            $item->add_meta_data( 'Unit Measurements', $values['unit_measurements'] );
        }
        if ( !empty( $values['quantity'] ) ) {
            $item->add_meta_data( 'Quantity', $values['quantity'] );
        }
    }
}
