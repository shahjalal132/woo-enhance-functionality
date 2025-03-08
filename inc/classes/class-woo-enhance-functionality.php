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

        // Get _dropdowns from post meta
        $dropdowns = get_post_meta( $product_id, '_dropdowns', true );

        // put_program_logs( 'Dropdowns: ' . json_encode( $dropdowns ) );

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
                <p>Panneau de 10m03</p>
            </div>

            <!-- Custom Add to Cart Button -->
            <button id="custom-add-to-cart" class="button alt">Add to Cart</button>
        </div>
        <?php
    }

}
