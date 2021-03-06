<?php

require_once('init.php');

const FILE_REQUEST = 'ExampleRequestPost.json';

$route = getcwd() . '/' . FILE_REQUEST;

$networks = getNetworks();

$launcher = new Launcher($route, $networks);
$launcher->run();

function getNetworks(): array
{
    $list = [];

    if ($networks = opendir('./classes/networks')) {
        while (false !== ($network = readdir($networks))) {
            // Ignoramos el archivo base Network.php ya que es de donde heredan las demás redes
            if (in_array($network, ['.', '..', 'Network.php'])) {
                continue;
            }

            $network_class = str_replace('.php', '', $network);
            $list[] = $network_class;
        }
    }

    return $list;
}
