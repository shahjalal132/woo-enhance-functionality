<?php
if ( !defined( 'ABSPATH' ) ) {
    die;
} // Cannot access directly.

if ( class_exists( 'CSF' ) ) {

    // Step 1: Register a Metabox for Managing Global Dropdown Modules
    CSF::createOptions( '_global_dropdowns', array(
        'menu_title'      => 'Dropdown Modules',
        'menu_slug'       => 'dropdown-modules',
        'framework_title' => 'Dropdown Modules Settings',
    ) );

    CSF::createSection( '_global_dropdowns', array(
        'title'  => 'Dropdown Modules',
        'fields' => array(
            array(
                'id'     => 'dropdown_modules',
                'type'   => 'repeater',
                'title'  => 'Create Dropdown Modules',
                'fields' => array(
                    array(
                        'id'          => 'module_name',
                        'type'        => 'text',
                        'title'       => 'Module Name',
                        'placeholder' => 'Enter module name',
                    ),
                    array(
                        'id'     => 'module_dropdowns',
                        'type'   => 'repeater',
                        'title'  => 'Dropdowns',
                        'fields' => array(
                            array(
                                'id'          => 'dropdown_name',
                                'type'        => 'text',
                                'title'       => 'Dropdown Name',
                                'placeholder' => 'Enter dropdown name',
                            ),
                            array(
                                'id'     => 'dropdown_items',
                                'type'   => 'repeater',
                                'title'  => 'Dropdown Items',
                                'fields' => array(
                                    array(
                                        'id'          => 'dropdown_item_name',
                                        'type'        => 'text',
                                        'title'       => 'Item Name',
                                        'placeholder' => 'Enter item name',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ) );

    // Step 2: Assign Modules to Products
    $prefix = '_assign_dropdowns';
    CSF::createMetabox( $prefix, array(
        'title'     => 'Assign Dropdown Modules',
        'post_type' => 'product',
    ) );

    CSF::createSection( $prefix, array(
        'title'  => 'Module Selection',
        'fields' => array(
            array(
                'id'      => 'assign_module',
                'type'    => 'select',
                'title'   => 'Select a Module',
                'options' => function () {
                    $global_settings = get_option( '_global_dropdowns' );
                    $modules         = !empty( $global_settings['dropdown_modules'] ) ? $global_settings['dropdown_modules'] : array();
                    $options         = array();
                    foreach ( $modules as $module ) {
                        $options[$module['module_name']] = $module['module_name'];
                    }
                    return $options;
                },
            ),
        ),
    ) );

    // Step 3: Allow Individual Products to Add Extra Dropdowns
    CSF::createSection( $prefix, array(
        'title'  => 'Custom Dropdowns',
        'fields' => array(
            array(
                'id'     => 'custom_dropdowns',
                'type'   => 'repeater',
                'title'  => 'Extra Dropdowns',
                'fields' => array(
                    array(
                        'id'          => 'extra_dropdown_name',
                        'type'        => 'text',
                        'title'       => 'Dropdown Name',
                        'placeholder' => 'Enter dropdown name',
                    ),
                    array(
                        'id'     => 'extra_dropdown_items',
                        'type'   => 'repeater',
                        'title'  => 'Dropdown Items',
                        'fields' => array(
                            array(
                                'id'          => 'extra_dropdown_item_name',
                                'type'        => 'text',
                                'title'       => 'Item Name',
                                'placeholder' => 'Enter item name',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ) );
}
