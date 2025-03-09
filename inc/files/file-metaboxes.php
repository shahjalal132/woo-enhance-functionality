<?php

if ( !defined( 'ABSPATH' ) ) {
    die;
} // Cannot access directly.

if ( class_exists( 'CSF' ) ) {

    // Prefix
    $prefix = "_dropdowns";

    // Create metabox
    CSF::createMetabox( $prefix, array(
        'title'        => 'Dropdown Configurations',
        'post_type'    => 'product',
        'show_restore' => true,
    ) );

    CSF::createSection( $prefix, array(
        'title'  => 'Manage Dropdowns',
        'icon'   => '',
        'fields' => array(

            // Repeater field
            array(
                'id'     => 'outer_dropdown_repeater',
                'type'   => 'repeater',
                'title'  => 'Dropdowns',
                'fields' => array(
                    // dropdown name key field
                    array(
                        'id'          => 'outer_dropdown_name',
                        'type'        => 'text',
                        'title'       => 'Dropdown Name',
                        'placeholder' => 'Dropdown Name',
                    ),
                    // dropdown options
                    array(
                        'id' => 'inner_dropdown_items',
                        'type' => 'repeater',
                        'title' => 'Dropdown Items',
                        'fields' => array(
                            // dropdown name key field
                            array(
                                'id'          => 'inner_dropdown_name',
                                'type'        => 'text',
                                'title'       => 'Item Name',
                                'placeholder' => 'Item Name',
                            ),
                            array(
                                'id'          => 'inner_dropdown_value',
                                'type'        => 'text',
                                'title'       => 'Item Value',
                                'placeholder' => 'Item Value',
                            ),
                        ),
                    )
                ),
            ),
        ),
    ) );
}