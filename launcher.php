<?php

class Launcher
{
    private $file = '';
    private $networks = [];
    public function __construct($file, $networks)
    {
        $this->file = $file;
        $this->networks = $networks;
    }

    public function run()
    {
        $params = json_decode(file_get_contents($this->file), true);
        $file_output = getcwd() . '/logs/' . uniqid();
        file_put_contents($file_output, '');

        $time_start = microtime(true);
        foreach ($this->networks as $network_class) {
            // Para este ejemplo he añadido una funcionalidad para lanzar varias veces la misma red como solo tenemos una
            foreach (range(1, 50) as $id) {
                $exec = 'php classes/networks/' . $network_class . '.php ' . $id . ' "' . urlencode(json_encode($params)) . '" "' . $file_output . '" > /dev/null 2>/dev/null &';
                exec($exec);
                // Si ya tenemos el resultado que queremos paramos la ejecución
                if (file_get_contents($file_output)) {
                    break 2;
                }
                // echo 'For ' . $network->getName() . ' Num exec ' . $id . PHP_EOL;
            }
        }

        do {

            // Si ya tenemos el ok paramos el proceso
            if (file_get_contents($file_output)) {
                break;
            }

            $time_end = microtime(true);
            $time = round(($time_end - $time_start) * 1000, 2);

            // Si el proceso tarda más de 15ms paramos
            if ($time > 15) {
                break;
            }
        } while (true);
        $time_end = microtime(true);
        $time = round(($time_end - $time_start) * 1000, 2);

        echo file_get_contents($file_output . '_log');
        echo 'PROCESS TIME ' . $time . 'ms' . PHP_EOL;
    }
}
