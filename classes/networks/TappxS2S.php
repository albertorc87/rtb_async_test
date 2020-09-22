<?php

require_once('Network.php');

class TappxS2S extends Network
{
    public const KEY = 'pub-1234-android-1234';
    protected $method = 'GET';
    protected $url = 'http://test-ssp.tappx.net/ssp/req.php';
    protected $timeout = 15;
    protected $name = 'Tappx S2S';
    protected $exec_by_network = 50;

    public function run($data = []):void
    {

        // Aquí habría que poner un control para validar si el formato del banner es válido
        $sz = $this->params['imp'][0]['banner']['w'] . 'x' . $this->params['imp'][0]['banner']['h'];

        $params = [
            'key' => self::KEY,
            'sz' => $sz,
            'os' => $this->params['device']['os'],
            'ip' => $this->params['device']['ip'],
            'source' => 'app',
            'ab' => $this->params['app']['bundle'],
            'aid' => $this->params['device']['ifa'],
            'mraid' => 2,// Esto lo he puesto así por defecto porque no tengo ni idea, en un trabajo lo preguntaría
            'ua' => $this->params['device']['ua'],
            'cb' => time(),
            'timeout' => $this->timeout,
        ];
        $url = $this->url . '?' . http_build_query($params);

        $data = $this->throwCURL($url, $data);

        parent::run($data);
    }
}

$tappx = new TappxS2S((int)$argv[1], $argv[2], $argv[3]);
$tappx->run();
