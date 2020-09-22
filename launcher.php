<?php

class Launcher
{
    /**
     * Archivo de donde leeremos los parámetros a enviar a la red
     * @var string
     */
    private $file = '';

    /**
     * Listado de redes a revisar
     * @var array
     */
    private $networks = [];

    public function __construct($file, $networks)
    {
        $this->file = $file;
        $this->networks = $networks;
    }

    /**
     * Método donde se ejecuta todo el proceso de lanzamiento de llamadas a las redes y posterior muestra de logs
     * @return void
     */
    public function run():void
    {
        $time_start = microtime(true);
        $params = json_decode(file_get_contents($this->file), true);
        $file_output = sys_get_temp_dir() . '/' . uniqid();
        file_put_contents($file_output, '');
        file_put_contents($file_output . '_log', '');

        foreach ($this->networks as $network_class) {
            // Para este ejemplo he añadido una funcionalidad para lanzar varias veces la misma red como solo tenemos una
            $commands = [];
            foreach (range(1, 50) as $id) {
                $exec = '(php classes/networks/' . $network_class . '.php ' . $id . ' "' . urlencode(json_encode($params)) . '" "' . $file_output . '" > /dev/null 2>/dev/null &)';
                $commands[] = $exec;
                // exec($exec);
            }
            exec(implode(' ; ', $commands));
        }

        do {

            // Si ya tenemos el ok paramos el proceso
            if (file_get_contents($file_output)) {
                echo 'OK CONTENT' . PHP_EOL;
                break;
            }

            // Voy a desactivar este control ya que no he sido capaz de conseguir bajar de 15ms
            // $time_end = microtime(true);
            // $time = round(($time_end - $time_start) * 1000, 2);

            // Si el proceso tarda más de 15ms paramos
            // if ($time > 15) {
            //     echo 'TIMEOUT' . PHP_EOL;
            //     break;
            // }
        } while (true);
        $time_end = microtime(true);
        $time = round(($time_end - $time_start) * 1000, 2);

        echo file_get_contents($file_output . '_log');
        echo 'PROCESS TIME ' . $time . 'ms' . PHP_EOL;

        echo file_get_contents($file_output);
    }
}
