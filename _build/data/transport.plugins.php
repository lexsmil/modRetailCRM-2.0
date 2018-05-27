<?php

$plugins = array();

$tmp = array(    
    'modRetailCRM'       => array(
        'file'        => 'plugin',
        'description' => 'Плагин для создания нового пользователя и нового заказа в RetailCRM',
        'events'      => array(
            'OnUserSave' => array(
                'priority' => 100
            ),
			'msOnCreateOrder' => array(
                'priority' => 150
            ),
        ),
        'disabled'    => 0
    )

);

foreach ($tmp as $k => $v) {
    /* @avr modplugin $plugin */
    $plugin = $modx->newObject('modPlugin');
    $plugin->fromArray(array(
        'name'        => $k,
        'category'    => 0,
        'description' => @$v['description'],
        'plugincode'  => getSnippetContent($sources['source_core'] . '/elements/plugins/plugin.' . $v['file'] . '.php'),
        'static'      => BUILD_PLUGIN_STATIC,
        'source'      => 1,
        'static_file' => 'core/components/' . PKG_NAME_LOWER . '/elements/plugins/plugin.' . $v['file'] . '.php',
        'disabled'    => isset($v['disabled']) ? $v['disabled'] : 0
    ), '', true, true);

    $events = array();
    if (!empty($v['events'])) {
        foreach ($v['events'] as $k2 => $v2) {
            /* @var modPluginEvent $event */
            $event = $modx->newObject('modPluginEvent');
            $event->fromArray(array_merge(
                array(
                    'event'       => $k2,
                    'priority'    => 0,
                    'propertyset' => 0,
                ), $v2
            ), '', true, true);
            $events[] = $event;
        }
        unset($v['events']);
    }

    if (!empty($events)) {
        $plugin->addMany($events);
    }

    $plugins[] = $plugin;
}

unset($tmp, $properties);
return $plugins;