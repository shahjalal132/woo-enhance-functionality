<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Credentials_Options;
use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Singleton;

class Metabox_Module {

    use Singleton;
    use Program_Logs;
    use Credentials_Options;

    public function __construct() {
        // $this->load_credentials_options();
        $this->setup_hooks();
    }

    public function setup_hooks() {
        add_action( 'init', [ $this, 'register_metabox_module_cpt' ] );

    }

    function register_metabox_module_cpt() {
        register_post_type( 'metabox_module', array(
            'label'     => 'Metabox Modules',
            'public'    => false,
            'show_ui'   => true,
            'supports'  => array( 'title' ),
            'menu_icon' => 'dashicons-list-view',
        ) );
    }
}
