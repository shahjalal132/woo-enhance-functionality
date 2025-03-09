<?php

function put_program_logs( $data ) {

    // Ensure the directory for logs exists
    $directory = PLUGIN_BASE_PATH . '/program_logs/';
    if ( !file_exists( $directory ) ) {
        mkdir( $directory, 0777, true );
    }

    // Construct the log file path
    $file_name     = $directory . 'program_logs.log';
    $file_name_txt = $directory . 'program_logs.txt';

    // Append the current datetime to the log entry
    $current_datetime = date( 'Y-m-d H:i:s' );
    $data             = $data . ' - ' . $current_datetime;

    // Write the log entry to the file
    file_put_contents( $file_name, $data . "\n\n", FILE_APPEND | LOCK_EX );
    // file_put_contents( $file_name_txt, $data . "\n\n", FILE_APPEND | LOCK_EX );
}


add_filter('template_include', function($template) {
    if (is_singular('product')) {
        put_program_logs("WooCommerce Single Product Template: " . $template);
    }
    return $template;
}, 99);

// hide product price
// add_action( 'woocommerce_single_product_summary', function() {
//     remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
// }, 1 );
