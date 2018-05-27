<?php

$settings = array();

$tmp = array(

    'apiKey'       => array(
        'value' => '',
        'xtype' => 'textfield',
        'area'  => 'modretailcrm_main',
    ),
    'siteCode'     => array(
        'value' => '',
        'xtype' => 'textfield',
        'area'  => 'modretailcrm_main',
    ),
    'url' => array(
        'value' => '',
        'xtype' => 'textfield',
        'area'  => 'modretailcrm_main',
    ),  

);

foreach ($tmp as $k => $v) {
    /* @var modSystemSetting $setting */
    $setting = $modx->newObject('modSystemSetting');
    $setting->fromArray(array_merge(
        array(
            'key'       => 'modretailcrm_' . $k,
            'namespace' => PKG_NAME_LOWER,
        ), $v
    ), '', true, true);

    $settings[] = $setting;
}

unset($tmp);
return $settings;
