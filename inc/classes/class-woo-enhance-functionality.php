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
        // $this->load_credentials_options();
        $this->setup_hooks();
    }

    public function setup_hooks() {
        add_action( 'woocommerce_before_add_to_cart_form', [ $this, 'custom_product_fields' ], 15 );
    }

    public function custom_product_fields() {
        // Ensure we are on a single product page
        if ( !is_product() ) {
            return;
        }

        // Get the product ID
        $product_id = get_the_ID();

        // Get the product title
        $product_title = get_the_title( $product_id );

        // Extract height value in different possible formats
        $formatted_height = '';
        if ( preg_match( '/(?:H[auteur]*[:\s]*)?([\d,.]+)m/i', $product_title, $matches ) ) {
            $height           = str_replace( ',', '.', $matches[1] ); // Replace comma with dot for uniformity
            $formatted_height = preg_replace( '/(\d+)\.(\d+)/', '$1m$2', $height );
        }

        // Get _dropdowns from post meta
        $dropdowns = get_post_meta( $product_id, '_dropdowns', true );

        if ( empty( $dropdowns['outer_dropdown_repeater'] ) ) {
            return; // If no dropdowns are set, exit.
        }

        ?>
        <div class="custom-product-options">

            <div class="dropdown-wrapper">
                <?php foreach ( $dropdowns['outer_dropdown_repeater'] as $dropdown ) : ?>
                    <div class="dropdown-group">
                        <label><?php echo esc_html( $dropdown['outer_dropdown_name'] ); ?></label>
                        <select
                            name="custom_dropdown[<?php echo esc_attr( sanitize_title( $dropdown['outer_dropdown_name'] ) ); ?>]">
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
                <!-- Unit Measurements -->
                <label>Unit Measurements</label>
                <input type="text" id="unit-measurements" name="unit-measurements" placeholder="Unit Measurements">

                <!-- Quantity -->
                <label>Quantity</label>
                <input type="number" id="wef-quantity" name="wef-quantity" placeholder="Quantity">
            </div>

            <div class="product-unit-wrapper text-center">
                <p>Panneau de <?php echo esc_html( $formatted_height ); ?></p>
            </div>

            <!-- Custom Add to Cart Button -->
            <button id="custom-add-to-cart" class="button alt">Añadir a la cesta</button>
        </div>
        <?php
    }

}
