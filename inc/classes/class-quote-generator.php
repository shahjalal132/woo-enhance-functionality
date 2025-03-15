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

        // Send the quote PDF via email when the "Thank You" page is displayed.
        add_action( 'woocommerce_thankyou', [ $this, 'send_quote_pdf_email' ] );

        // create a shortcode to send test static email
        add_shortcode( 'test_send_email', [ $this, 'send_test_email' ] );

    }

    public function send_test_email() {
        $to      = "rjshahjalal132@gmail.com";
        $subject = "Test email sending";
        $message = "Test Email sending";
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        if ( wp_mail( $to, $subject, $message, $headers ) ) {
            return "Email sent successfully";
        } else {
            return "Email not sent";
        }
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
     * Send the Quote PDF via Email to the Customer and the Company.
     * This function is now triggered by the woocommerce_thankyou hook.
     *
     * @param int $order_id The ID of the WooCommerce order.
     */
    public function send_quote_pdf_email( $order_id ) {
        if ( !$order_id ) {
            return;
        }

        // Get the order object
        $order = wc_get_order( $order_id );
        if ( !$order ) {
            put_program_logs( 'ID de commande introuvable pour envoyer le devis PDF par email' );
            return;
        }

        // Get PDF file path & URL
        $pdf_url   = get_post_meta( $order_id, '_quote_pdf_url', true );
        $file_path = WP_CONTENT_DIR . '/uploads/quotes/commande_' . $order_id . '.pdf';

        if ( !file_exists( $file_path ) ) {
            put_program_logs( 'Fichier PDF introuvable pour la commande #' . $order_id );
            return;
        }

        // Get customer details
        $customer_name    = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $customer_email   = $order->get_billing_email();
        $customer_phone   = $order->get_billing_phone();
        $billing_address  = $order->get_formatted_billing_address();
        $shipping_address = $order->get_formatted_shipping_address();

        // Company email (change this to your company email)
        $company_email = get_option( 'admin_email' );

        // Email subject
        $subject = "Votre devis pour la commande #{$order_id}";

        // Email body with simple design
        $message = "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Devis pour la commande #{$order_id}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    color: #333;
                    margin: 0;
                    padding: 0;
                }
                .email-container {
                    max-width: 600px;
                    margin: 20px auto;
                    background-color: #ffffff;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                }
                .email-header {
                    text-align: center;
                    padding-bottom: 20px;
                    border-bottom: 1px solid #ddd;
                }
                .email-header h1 {
                    color: #444;
                    font-size: 24px;
                    margin: 0;
                }
                .email-body {
                    padding: 20px 0;
                }
                .email-body h3 {
                    color: #555;
                    font-size: 18px;
                    margin-bottom: 10px;
                }
                .email-body p {
                    font-size: 14px;
                    line-height: 1.6;
                    margin: 0 0 10px;
                }
                .email-footer {
                    text-align: center;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    font-size: 12px;
                    color: #777;
                }
                .button {
                    display: inline-block;
                    padding: 10px 20px;
                    margin: 20px 0;
                    background-color: #0073e6;
                    color: #ffffff !important;
                    text-decoration: none;
                    border-radius: 5px;
                    font-size: 16px;
                }
                .button:hover {
                    background-color: #005bb5;
                }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='email-header'>
                    <h1>Devis pour la commande #{$order_id}</h1>
                </div>
                <div class='email-body'>
                    <p>Bonjour <strong>{$customer_name}</strong>,</p>
                    <p>Merci pour votre commande. Vous trouverez votre devis en pièce jointe.</p>
    
                    <h3>Détails du client :</h3>
                    <p><strong>Nom :</strong> {$customer_name}</p>
                    <p><strong>Email :</strong> {$customer_email}</p>
                    <p><strong>Téléphone :</strong> {$customer_phone}</p>
                    <p><strong>Adresse de facturation :</strong> {$billing_address}</p>
                    <p><strong>Adresse de livraison :</strong> {$shipping_address}</p>
    
                    <p>Vous pouvez également télécharger votre devis ici :</p>
                    <a href='{$pdf_url}' class='button'>Télécharger le PDF</a>
                </div>
                <div class='email-footer'>
                    <p>Merci pour votre confiance. Si vous avez des questions, n'hésitez pas à nous contacter.</p>
                    <p>Cordialement,<br><strong>L'équipe de votre entreprise</strong></p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Set email headers
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: Votre Entreprise <' . $company_email . '>',
            'Reply-To: ' . $company_email,
        ];

        // Attach the PDF
        $attachments = [ $file_path ];

        // Send email to customer
        if ( wp_mail( $customer_email, $subject, $message, $headers, $attachments ) ) {
            // put_program_logs( 'Email envoyé avec succès au client pour la commande #' . $order_id );
        } else {
            // put_program_logs( 'Échec de l\'envoi de l\'email au client pour la commande #' . $order_id );
        }

        // Send email to the company
        if ( wp_mail( $company_email, "Copie du devis pour la commande #{$order_id}", $message, $headers, $attachments ) ) {
            // put_program_logs( 'Email envoyé avec succès à l\'entreprise pour la commande #' . $order_id );
        } else {
            // put_program_logs( 'Échec de l\'envoi de l\'email à l\'entreprise pour la commande #' . $order_id );
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
