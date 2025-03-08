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
        add_action( 'woocommerce_before_add_to_cart_form', [ $this, 'custom_product_fields' ], 15 );
        add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_custom_data_to_cart' ], 10, 2 );
        add_filter( 'woocommerce_get_item_data', [ $this, 'display_custom_data_in_cart' ], 10, 2 );
        add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'add_custom_data_to_order' ], 10, 4 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

        add_action( 'wp_ajax_custom_add_to_cart', [ $this, 'custom_add_to_cart_handler' ] );
        add_action( 'wp_ajax_nopriv_custom_add_to_cart', [ $this, 'custom_add_to_cart_handler' ] );

        add_action( 'wp_ajax_proceed_to_checkout', [ $this, 'proceed_to_checkout_handler' ] );
        add_action( 'wp_ajax_nopriv_proceed_to_checkout', [ $this, 'proceed_to_checkout_handler' ] );
    }

    function custom_add_to_cart_handler() {
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

    public function proceed_to_checkout_handler(){
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

    public function custom_product_fields() {
        if ( !is_product() ) {
            return;
        }

        $product_id    = get_the_ID();
        $product_title = get_the_title( $product_id );

        $formatted_height = '';
        if ( preg_match( '/(?:H[auteur]*[:\s]*)?([\d,.]+)m/i', $product_title, $matches ) ) {
            $height           = str_replace( ',', '.', $matches[1] );
            $formatted_height = preg_replace( '/(\d+)\.(\d+)/', '$1m$2', $height );
        }

        $dropdowns = get_post_meta( $product_id, '_dropdowns', true );

        ?>
        <div class="custom-product-options">

            <div class="dropdown-wrapper">
                <?php foreach ( $dropdowns['outer_dropdown_repeater'] as $dropdown ) : ?>
                    <div class="dropdown-group">
                        <label><?php echo esc_html( $dropdown['outer_dropdown_name'] ); ?></label>
                        <select
                            name="custom_dropdown[<?php echo esc_attr( sanitize_title( $dropdown['outer_dropdown_name'] ) ); ?>]">
                            <!-- Initial "Select" option -->
                            <option value=""><?php esc_html_e( 'Select', 'your-text-domain' ); ?></option>

                            <?php foreach ( $dropdown['inner_dropdown_items'] as $item ) : ?>
                                <option value="<?php echo esc_attr( $item['inner_dropdown_name'] ); ?>">
                                    <?php echo esc_html( $item['inner_dropdown_name'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="quantity-wrapper">
                <label>Unit Measurements</label>
                <input type="text" id="unit-measurements" name="unit-measurements" placeholder="Unit Measurements">

                <label>Quantity</label>
                <input type="number" id="wef-quantity" name="wef-quantity" placeholder="Quantity">
            </div>

            <div class="product-unit-wrapper text-center">
                <p>Panneau de <?php echo esc_html( $formatted_height ); ?></p>
            </div>

            <button id="custom-add-to-cart" data-product-id="<?php echo esc_attr( $product_id ); ?>"
                class="button alt">
                <span class="text-center">Añadir a la cesta</span>
                <span class="add-to-cart-spinner-loader-wrapper"></span>
            </button>
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
            $dropdown_text   = 'Dropdowns Data'; // Label text
            $dropdown_values = [];

            foreach ( $cart_item['custom_dropdown'] as $name => $value ) {
                // Extract dropdown name and remove "custom_dropdown[" prefix
                $name_clean        = preg_replace( '/custom_dropdown\[|\]/', '', $name );
                $name_clean        = ucwords( str_replace( '_', ' ', $name_clean ) ); // Format name
                $dropdown_values[] = $name_clean . ': ' . esc_html( $value );
            }

            if ( !empty( $dropdown_values ) ) {
                $item_data[] = array(
                    'name'  => $dropdown_text,
                    'value' => implode( ', ', $dropdown_values ), // Combine dropdowns into one line
                );
            }
        }

        if ( !empty( $cart_item['unit_measurements'] ) ) {
            $item_data[] = array(
                'name'  => 'Unit Measurements',
                'value' => esc_html( $cart_item['unit_measurements'] ),
            );
        }

        if ( !empty( $cart_item['quantity'] ) ) {
            $item_data[] = array(
                'name'  => 'Quantity',
                'value' => esc_html( $cart_item['quantity'] ),
            );
        }

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
