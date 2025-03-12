<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Credentials_Options;
use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Singleton;

// Include Composer's autoload to load Dompdf
require_once PLUGIN_BASE_PATH . '/vendor/autoload.php';

use \Dompdf\Dompdf;
use \Dompdf\Options;

class Quote_Generator {

    use Singleton;
    use Program_Logs;
    use Credentials_Options;

    public function __construct() {
        // Initialize hooks when the class is instantiated.
        $this->setup_hooks();
    }

    /**
     * Register WordPress hooks
     */
    public function setup_hooks() {
        // Hook into WooCommerce 'thank you' page to generate a quote after an order is placed.
        add_action( 'woocommerce_thankyou', [ $this, 'generate_quote_pdf' ] );
    }

    /**
     * Generate a PDF quote for the given order and save it in the /uploads/quotes/ directory.
     *
     * @param int $order_id WooCommerce Order ID.
     */
    public function generate_quote_pdf( $order_id ) {
        if ( !$order_id ) {
            return; // Exit if no order ID is provided.
        }

        $order = wc_get_order( $order_id );
        if ( !$order ) {
            put_program_logs( 'Product id not found to generate pdf quote' );
            return; // Exit if the order doesn't exist.
        }

        // Set up Dompdf options
        $options = new Options();
        $options->set( 'isHtml5ParserEnabled', true );
        $dompdf = new Dompdf( $options );

        // Generate HTML content for the PDF
        $html = '<h1>Quote for Order #' . esc_html( $order_id ) . '</h1>';
        $html .= '<h3>Customer Details:</h3>';
        $html .= '<p><strong>Name:</strong> ' . esc_html( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) . '</p>';
        $html .= '<p><strong>Email:</strong> ' . esc_html( $order->get_billing_email() ) . '</p>';
        $html .= '<p><strong>Phone:</strong> ' . esc_html( $order->get_billing_phone() ) . '</p>';
        $html .= '<h3>Order Items:</h3>';

        foreach ( $order->get_items() as $item ) {
            $html .= '<p>' . esc_html( $item->get_name() ) . ' - $' . esc_html( $item->get_total() ) . '</p>';
        }

        // Load HTML into Dompdf and render
        $dompdf->loadHtml( $html );
        $dompdf->render();

        // Define file path and ensure the directory exists
        $upload_dir = WP_CONTENT_DIR . "/uploads/quotes/";
        if ( !file_exists( $upload_dir ) ) {
            mkdir( $upload_dir, 0755, true ); // Create directory if it doesn't exist
        }

        $file_path = $upload_dir . "order_{$order_id}.pdf";

        // Save the generated PDF file
        if ( file_put_contents( $file_path, $dompdf->output() ) ) {
            put_program_logs( "Quote PDF generated successfully for Order #{$order_id}" );
        } else {
            put_program_logs( "Failed to save Quote PDF for Order #{$order_id}" );
        }
    }
}
