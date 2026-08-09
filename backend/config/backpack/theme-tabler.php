<?php

return [
    /*
    |--------------------------------------------------------------------------
    | BloodCare Backpack layout
    |--------------------------------------------------------------------------
    |
    | Keep the staff workspace in a fixed vertical layout. This file only
    | overrides the values BloodCare needs; the package still supplies the
    | remaining Tabler defaults and assets.
    */
    'layout' => 'vertical',

    'auth_layout' => 'default',

    'project_logo' => '<span class="bloodcare-logo-mark">+</span><span><b>Blood</b>Care</span>',

    // BloodCare ships its own local Tabler/theme CSS from the published Blade
    // overrides. An empty theme skin list prevents Backpack 7 from requesting
    // optional vendor skin files that are not published in this project.
    'styles' => [],

    'options' => [
        'colorModes' => [
            'light' => 'la-sun',
            'dark' => 'la-moon',
        ],
        'defaultColorMode' => 'light',
        'showColorModeSwitcher' => false,
        'useStickyHeader' => true,
        'useFluidContainers' => true,
        'sidebarFixed' => true,
        'doubleTopBarInHorizontalLayouts' => false,
        'showPasswordVisibilityToggler' => true,
    ],
];
