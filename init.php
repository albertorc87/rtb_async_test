<?php

spl_autoload_register(function ($name) {
    $name = str_replace('\\', '/', $name);

    $CLASSPATHS = [
        'classes/networks/',
        ''
    ];

    foreach ($CLASSPATHS as $p) {
        $filename = __DIR__ . '/' . $p . $name . '.php';
        if (file_exists($filename)) {
            include_once($filename);
            return;
        }
    }
});
