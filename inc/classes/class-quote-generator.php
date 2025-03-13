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
        add_action( 'woocommerce_thankyou', [ $this, 'generate_quote_pdf' ] );
        add_action( 'woocommerce_thankyou', [ $this, 'display_quote_pdf_on_thank_you_page' ] );
        add_action( 'woocommerce_admin_order_data_after_order_details', [ $this, 'display_quote_pdf_in_admin_order_page' ] );
    }

    /**
     * Générer un devis en PDF pour la commande et l'enregistrer dans /uploads/quotes/
     *
     * @param int $order_id Identifiant de la commande WooCommerce.
     */
    public function generate_quote_pdf( $order_id ) {
        if ( !$order_id ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( !$order ) {
            put_program_logs( 'ID de commande introuvable pour générer le devis PDF' );
            return;
        }

        $options = new Options();
        $options->set( 'isHtml5ParserEnabled', true );
        $options->set( 'defaultFont', 'Arial' );
        $dompdf = new Dompdf( $options );

        // Informations du client
        $customer_name  = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $customer_email = $order->get_billing_email();
        $customer_phone = $order->get_billing_phone();

        $billing_address  = $order->get_formatted_billing_address();
        $shipping_address = $order->get_formatted_shipping_address();

        // Générer les articles de la commande
        $order_items_html = '';
        $dropdowns_html   = '';

        foreach ( $order->get_items() as $item ) {
            $product_name  = $item->get_name();
            $product_price = number_format( $item->get_total(), 2 );

            $product_id = $item->get_product_id();

            $selected_color = get_post_meta( $product_id, '_selected_color', true );
            $selected_label = get_post_meta( $product_id, '_selected_label', true );

            $currency_symbol = get_woocommerce_currency_symbol();

            // put_program_logs( "ID du produit: {$product_id}, Couleur sélectionnée: {$selected_color}, Hauteur sélectionnée: {$selected_label}" );

            $order_items_html .= "
                <tr>
                    <td>{$product_name}</td>
                    <td>{$product_price} {$currency_symbol}</td>
                </tr>
            ";

            $dropdowns_html .= "
                <tr>
                    <td>Couleur</td>
                    <td>{$selected_color}</td>
                </tr>
                <tr>
                    <td>Hauteur</td>
                    <td>{$selected_label}</td>
                </tr>
            ";
        }

        // Contenu HTML mis à jour
        $html = <<<EOD
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; font-size: 14px; color: #333; }
                    h1 { text-align: center; color: #444; font-size: 18px; }
                    h3 { color: #555; border-bottom: 2px solid #ddd; padding-bottom: 5px; font-size: 16px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f4f4f4; }
                    
                    .customer-info { 
                        width: 100%; 
                        display: flex; 
                        justify-content: space-between; 
                        gap: 10px; 
                        margin-bottom: 15px; 
                    }
                    .customer-info div { 
                        flex: 1; 
                        background: #f9f9f9; 
                        padding: 15px; 
                        border-radius: 5px; 
                        box-sizing: border-box;
                    }
                    .customer-info p { margin: 5px 0; }

                    .order-items, .selected-options { margin-top: 20px; }

                    .footer-text { 
                        text-align: center; 
                        margin-top: 20px; 
                        font-size: 12px; 
                        color: #777;
                    }
                </style>
            </head>
            <body>
                <h1>Devis pour la commande #{$order_id}</h1>

                <div class="customer-info">
                    <div>
                        <h3>Informations du client</h3>
                        <p><strong>Nom:</strong> {$customer_name}</p>
                        <p><strong>Email:</strong> {$customer_email}</p>
                        <p><strong>Téléphone:</strong> {$customer_phone}</p>
                    </div>
                    <div>
                        <h3>Adresse de facturation</h3>
                        <p>{$billing_address}</p>
                    </div>
                    <div>
                        <h3>Adresse de livraison</h3>
                        <p>{$shipping_address}</p>
                    </div>
                </div>

                <h3>Articles de la commande</h3>
                <table class="order-items">
                    <tr>
                        <th>Nom du produit</th>
                        <th>Prix</th>
                    </tr>
                    {$order_items_html}
                </table>

                <h3>Options sélectionnées</h3>
                <table class="selected-options">
                    <tr>
                        <th>Nom du produit</th>
                        <th>Options sélectionnées</th>
                    </tr>
                    {$dropdowns_html}
                </table>

                <p class="footer-text">
                    Merci pour votre commande ! Si vous avez des questions, n'hésitez pas à nous contacter.
                </p>
            </body>
            </html>
        EOD;

        // Générer et enregistrer le PDF
        $dompdf->loadHtml( $html );
        $dompdf->render();

        $upload_dir = WP_CONTENT_DIR . "/uploads/quotes/";
        if ( !file_exists( $upload_dir ) ) {
            mkdir( $upload_dir, 0755, true );
        }

        $file_name = "commande_{$order_id}.pdf";
        $file_path = $upload_dir . $file_name;
        $file_url  = content_url( "/uploads/quotes/{$file_name}" );

        if ( file_put_contents( $file_path, $dompdf->output() ) ) {
            update_post_meta( $order_id, '_quote_pdf_url', $file_url );
        }
    }

    /**
     * Afficher le lien du devis PDF sur la page de remerciement WooCommerce.
     */
    public function display_quote_pdf_on_thank_you_page( $order_id ) {
        $pdf_url = get_post_meta( $order_id, '_quote_pdf_url', true );

        if ( $pdf_url ) {
            echo '<p><strong>Télécharger votre devis :</strong> <a href="' . esc_url( $pdf_url ) . '" target="_blank">Télécharger le PDF</a></p>';
        }
    }

    /**
     * Afficher le lien du devis PDF dans l'admin WooCommerce.
     */
    public function display_quote_pdf_in_admin_order_page( $order ) {
        $order_id = $order->get_id();
        $pdf_url  = get_post_meta( $order_id, '_quote_pdf_url', true );

        if ( $pdf_url ) {
            echo '<p><strong>Devis PDF :</strong> <a href="' . esc_url( $pdf_url ) . '" target="_blank">Télécharger le PDF</a></p>';
        }
    }
}
