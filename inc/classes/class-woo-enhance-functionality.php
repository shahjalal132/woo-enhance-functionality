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
        ?>
        <div class="custom-product-options">

            <div class="dropdown-wrapper">
                <!-- Dropdown Field -->
                <select name="custom_dropdown">
                    <option value="option1">Option 1</option>
                    <option value="option2">Option 2</option>
                    <option value="option3">Option 3</option>
                </select>
                
                <select name="custom_dropdown">
                    <option value="option1">Option 1</option>
                    <option value="option2">Option 2</option>
                    <option value="option3">Option 3</option>
                </select>
            </div>

            <div class="quantity-wrapper">
                <!-- Number Input -->
                <input type="number" id="custom-quantity" name="custom_quantity" min="1" value="1">
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
