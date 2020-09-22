<?php

class Network
{
    protected $method = 'GET';

    protected $url = null;

    protected $name = null;

    protected $exec_by_network = 1;

    private $id = null;

    protected $params = '';

    protected $file_ouput = '';

    public function __construct(int $id, string $params, string $file_ouput)
    {
        $this->id = $id;
        $this->params = json_decode(urldecode($params), true);
        $this->file_ouput = $file_ouput;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getURL(): string
    {
        return $this->url;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getExecByNetwork(): int
    {
        return $this->exec_by_network;
    }

    public function saveDataFileOutput(string $content): void
    {
        file_put_contents($this->file_ouput, $content);
    }

    public function addLineLog($line):void
    {
        $fp = fopen($this->file_ouput . '_log', 'a');
        fwrite($fp, $line . "\n");
        fclose($fp);
    }

    public function throwCURL($url = '', $data = [])
    {
        if (!isset($data['user.agent']) || !$data['user.agent']) {
            $data['user.agent'] = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:35.0) Gecko/20100101 Firefox/35.0';
        }
        if (!isset($data["time"])) {
            $data["time"] = 100;
        }

        $ch = curl_init();
        $ops = array(
            CURLOPT_URL            => $url
            ,CURLOPT_RETURNTRANSFER => true
            ,CURLOPT_HEADER         => true
            ,CURLOPT_SSL_VERIFYHOST => false
            ,CURLOPT_SSL_VERIFYPEER => false
            ,CURLOPT_ENCODING       => 'gzip,deflate'
            ,CURLOPT_USERAGENT      => $data['user.agent']
            ,CURLOPT_VERBOSE        => true
            ,CURLOPT_CONNECTTIMEOUT => $data["time"]
            ,CURLOPT_TIMEOUT        => $data["time"]
            ,CURLINFO_HEADER_OUT    => true
            ,CURLOPT_FOLLOWLOCATION => true
            );


        if (isset($data['sslcert'])) {
            $ops[CURLOPT_SSLCERT] = $data['sslcert'];
        }

        if (isset($data['sslkey'])) {
            $ops[CURLOPT_SSLKEY] = $data['sslkey'];
        }

        if (isset($data['nofollow'])) {
            $ops[CURLOPT_FOLLOWLOCATION] = false;
        }
        if (isset($data['put'])) {
            $ops[CURLOPT_CUSTOMREQUEST] = 'PUT';
        }
        if (isset($data['customrequest'])) {
            $ops[CURLOPT_CUSTOMREQUEST] = $data['customrequest'];
        }

        if (isset($data['post'])) {
            do {
                $postString = $data['post'];
                if (isset($data['post.raw'])) {
                    if (is_string($data['post'])) {
                        $postString = $data['post'];
                    }
                    $data['headers']['Content-Type'] = 'text/plain';
                }
                if (!isset($data['post.multipart']) && is_array($data['post'])) {
                    /* Hay determinados tipos de servidores que si no se les envía como
                     * string no funcionan correctamente :S */
                    $isString = true;
                    foreach ($data['post'] as $item) {
                        if (!is_string($item)) {
                            $isString = false;
                        }
                    }
                    if ($isString) {
                        $postString = http_build_query($data['post']);
                    }
                }
                $ops[CURLOPT_POSTFIELDS] = $postString;
                /* We send post here, remove it for latter queries */
                unset($data['post']);
            } while (false);
        }

        if (isset($data['headers'])) {
            $header = array();
            if (($k = key($data['headers']))) {
                if (!is_numeric($k)) {
                    foreach ($data['headers'] as $k=>$v) {
                        $header[$k] = $k.': '.$v;
                    }
                    $header = array_values($header);
                }
            }
            $ops[CURLOPT_HTTPHEADER] = $header;
        }

        if (!isset($data['cookies.file']) && !empty($data['cookies'])) {
            $cookieString = '';
            foreach ($data['cookies'] as $cookie) {
                $cookieString .= $cookie['name'].'='.$cookie['value'].'; ';
            }
            if ($cookieString) {
                $cookieString = substr($cookieString, 0, -2);
            }
            $ops[CURLOPT_COOKIE] = $cookieString;
        }

        if (isset($data['cookies.file'])) {
            if (!$data['cookies'] && file_exists($data['cookies.file'])) {
                /* If data array is not set but there is a cookiejar file, load
                 * cookies from this file, just for tracking */
                $lines = file($data['cookies.file']);
                $token = "\t";
                foreach ($lines as $line) {
                    if (!$line || $line[0] == '#' || substr_count($line, $token) != 6) {
                        continue;
                    }
                    $parts = explode($token, $line);
                    $parts = array_map('trim', $parts);
                    $data['cookies'][$parts[5]] = array(
                        'name'=>$parts[5]
                        ,'value'=>$parts[6]
                    );
                }
            }
            /* If we prefer to use a file to store cookies */
            $ops[CURLOPT_COOKIEFILE] = $data['cookies.file'];
            $ops[CURLOPT_COOKIEJAR]  = $data['cookies.file'];
        }

        curl_setopt_array($ch, $ops);

        $res = curl_exec($ch);
        if (!$res) {
            $errorDescription = curl_error($ch);
            $errorCode        = curl_errno($ch);
            return ['code' => $errorCode, 'errorCode' => $errorCode, 'errorDescription' => $errorDescription];
        }
        $token = "\r\n\r\n";
        $return = [];
        $return['code'] = 100;
        $return['info'] = curl_getinfo($ch);
        $return['content'] = $res;

        if (preg_match_all("!content\-length: (?<len>[0-9]+)!i", $res, $m)) {
            $m = end($m['len']);
            $len = strlen($res);
            $header = $len - 4 - $m;
        }

        while (in_array($return['code'], [100, 301, 302]) && ($pos = strpos($return['content'], $token)) !== false && strtolower(substr($return['content'], 0, 4)) == 'http') {
            list($return['pageHeader'], $return['content']) = explode($token, $return['content'], 2);
            $r = preg_match('!HTTP\/[0-9]+(\.?[0-9]?)+ (?<code>[0-9]+) ?(?<msg>[a-zA-Z ]+)?!', $return['pageHeader'], $m);
            if (!isset($m['code'])) {
                $m['code'] = '';
            }
            $return['code']    = $m['code'];
            $return['message'] = isset($m['msg']) ? $m['msg'] : '';
        }
        if (!isset($return['cookies'])) {
            $return['cookies'] = array();
        }
        if (isset($data['cookies'])) {
            $return['cookies'] += $data['cookies'];
        }

        if (preg_match_all('/[Ss]et-[Cc]ookie: (.*)/', $return['pageHeader'], $m)) {
            foreach ($m[0] as $k => $dummy) {
                $cookie = [];
                if (preg_match_all('/([a-zA-Z0-9\-_\.]*)=([^;]*)/i', $m[1][$k], $c)) {
                    foreach ($c[0] as $k=>$v) {
                        $cookie[$c[1][$k]] = $c[2][$k];
                    }
                }
                $name = key($cookie);
                $cookie['name']  = $name;
                $cookie['value'] = current($cookie);
                unset($cookie[$name]);
                $return['cookies'][$name] = $cookie;
            }
        }

        return $return;
    }

    public function run($data = []):void
    {
        $d = new DateTime();

        $line = $d->format('Y-m-d H:i:s.u') . ' ' . $this->name . ' ' . $this->id;
        if ($data['code'] === '200') {
            $this->saveDataFileOutput($data['content']);
            $response = ' OK';
        } elseif ($data['code'] == '204') {
            $response = ' NO CONTENT';
        } else {
            $error = 'Unknown error';
            if (preg_match('/X-Error-Reason: (?<error>[^\n]+)/', $data['pageHeader'], $m)) {
                $error = $m['error'];
            }
            $response = ' ' . $error;
        }

        $this->addLineLog($line . ' code ' . $data['code'] . $response);
    }
}
