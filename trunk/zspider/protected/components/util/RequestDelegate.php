<?php
class RequestDelegate
{/*{{{*/
    const TIME_OUT = 1;
    const MAX_RETRY_TIME = 10;

    private $errlogPath;
    private $isHttps;

    public function __construct($errlogPath=null)
    {/*{{{*/
        $this->errlogPath = $errlogPath;
        $this->isHttps = false;
    }/*}}}*/

    /*
     * $ips = array();
     * $ips['119.147.113.116'] = '80';
     * $ips['211.138.124.199'] = '80';
     * $ips['60.217.232.51'] = '80';
     * $ips['58.240.237.32'] = '80';
     * $ips['221.130.7.82'] = '80';
     * $ips['221.130.17.69'] = '80';
     * $ips['119.167.219.78'] = '80';
     * $ips['221.130.17.98'] = '80';
     * $ips['72.254.128.202'] = '80';
     * $ips['58.150.182.76'] = '8080';
     *
     * $url = array('scheme' => 'http', 'port' => '80', 'host' => 'www.baidu.com', 'path' => '/index.php');
     * foreach ($ips as $host => $port)
     * {
     * sleep(rand(1, 5));
     * $proxy = array('scheme' => 'http', 'port' => $port, 'host' => $host);
     * echo $host.' '.$port."\n";
     * echo $remoter->requestByProxy($proxy, $url, array('id'=>123, ))."\n";
     * }
     * */
    public function requestByProxy($proxy, $urlInfo, $params=array(), $timeout=3)
    {/*{{{*/
        $request = $this->parseUrl($urlInfo) . $this->parseParams($params);

        $ch = curl_init();

        $proxyUrl = $this->parseUrl($proxy);
        $header = array('Cache-Control: no-cache');
        curl_setopt($ch, CURLOPT_PROXY, $proxyUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        curl_setopt($ch, CURLOPT_URL, $request);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }/*}}}*/

    public function setHttps()
    {/*{{{*/
        $this->isHttps = true;
    }/*}}}*/

    public function request($hosts, $method='get', $args=array(), $cookie='', $timeout=self::TIME_OUT, $noRetry=false, $host='', $key='')
    {/*{{{*/
        assert(false==empty($hosts));

        $url = $this->pickupHost($hosts);

        if (empty($args))
            $args = array();

        $ch = curl_init();

        if ($this->isHttps)
        {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); 
            curl_setopt($ch, CURLOPT_SSLVERSION, 3); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); 
        }

        if($host)
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('host:'.$host)); 
        }

        if ('get' == strtolower($method))
        {
            $url = $this->preGetData($url, $args);
        }
        else
        {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args));
        }
        curl_setopt($ch, CURLOPT_URL, $url);

        //proxy
        /*
        $header = array('Cache-Control: no-cache');
        curl_setopt($ch, CURLOPT_PROXY, 'http://cache1.web.bjt.qihoo.net:80');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        */
        if($key)
        {
            curl_setopt($ch, CURLOPT_USERPWD, $key);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, 'User-AgentMozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3');
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');

        $result = curl_exec($ch);
        $this->processErr($ch, $url, $args);

        //retry
        $retries = 0;
        while ( (false == $noRetry) && (false === $result) && (false == empty($hosts)) && ($retries < self::MAX_RETRY_TIME) )
        {
            ++$retries;
            $result = $this->request($hosts, $method, $args, $cookie, $timeout, true);
        }
        curl_close($ch);
        return $result;
    }/*}}}*/

    private function processErr($ch, $url, $args)
    {/*{{{*/
        $msg = curl_error($ch);
        if ('' != $msg && $this->errlogPath)
        {
            $time = date('Y-m-d H:i:s');
            $msg = '['.$time.'] '.$msg." | ".$url.' | '.implode(',', $args)."\n";
            
            error_log($msg, 3, $this->errlogPath);
        }
    }/*}}}*/

    private function preGetData($url, $args)
    {/*{{{*/
        $data = http_build_query($args);
        if (false === strstr($url, '?'))
        {
            $url = $url.'?'.$data;
        }
        else
        {
            $url = $url.'&'.$data;
        }
        return $url;
    }/*}}}*/

    private function microtimeFloat()
    {/*{{{*/
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }/*}}}*/

    private function pickupHost(&$hosts)
    {/*{{{*/
        if (false == is_array($hosts))
        {
            $tries = array();
            for ($i=1;$i<self::MAX_RETRY_TIME;$i++)
                $tries[] = $hosts;
            $hosts = $tries;
        }
        $key = array_rand($hosts);
        if (null !== $key)
        {
            $url = $hosts[$key];
            unset($hosts[$key]);
            return $url;
        }
        //url is none!;
        assert(false);
    }/*}}}*/

    public function multiRequest($requests, $timeout=self::TIME_OUT)
    {/*{{{*/
        $mh = curl_multi_init();

        foreach($requests as $request)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $request);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_multi_add_handle($mh, $ch);
            $conn[] = $ch;
        }
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active and $mrc == CURLM_OK)
        {
            if (curl_multi_select($mh) != -1)
            {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        $cnt = count($requests);
        $res = array();
        for($i =0; $i < $cnt; $i++)
        {
            if(!curl_errno($conn[$i]))
            {
                $res[$i] = curl_multi_getcontent($conn[$i]);
            }
            curl_multi_remove_handle($mh, $conn[$i]);
            curl_close($conn[$i]);
        }
        curl_multi_close($mh);

        return $res;
    }/*}}}*/

    public function multiRequestByProxy($proxy, $requests, $timeout=self::TIME_OUT)
    {/*{{{*/
        $proxyUrl = $this->parseUrl($proxy);
        $header = array('Cache-Control: no-cache');
        $mh = curl_multi_init();

        foreach($requests as $request)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_PROXY, $proxyUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_URL, $request);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_multi_add_handle($mh, $ch);
            $conn[] = $ch;
        }

        do {
            $mrc = curl_multi_exec($mh, $active);
        } while($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active and $mrc == CURLM_OK)
        {
            if (curl_multi_select($mh) != -1)
            {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        $cnt = count($requests);
        for($i =0; $i < $cnt; $i++)
        {
            if(!curl_errno($conn[$i]))
            {
                $res[$i] = curl_multi_getcontent($conn[$i]);
            }
            curl_multi_remove_handle($mh, $conn[$i]);
            curl_close($conn[$i]);
        }
        curl_multi_close($mh);

        return $res;
    }/*}}}*/

    public function parseUrl($urlInfo)
    {/*{{{*/
        $scheme = isset($urlInfo['scheme']) ? $urlInfo['scheme'] : 'http';
        $port = isset($urlInfo['port']) ? $urlInfo['port'] : 80;
        $path = isset($urlInfo['path']) ? $urlInfo['path'] : '';

        $request = $scheme . '://'. $urlInfo['host'] .':'. $port . $path;
        return $request;
    }/*}}}*/

    public function parseParams($params)
    {/*{{{*/
        $paramString = '';
        $pairs = array();
        foreach($params as $key => $value)
        {
            $pair = $key .'='. $value;
            array_push($pairs, $pair);
        }
        if($query = implode('&', $pairs))
        {
            $paramString .= '?' . $query;
        }

        return $paramString;
    }/*}}}*/

    public static function getIp()
    {/*{{{*/
        if ($ip = getenv('HTTP_X_FORWARDED_FOR')){}
        else if ($ip = getenv('HTTP_VIA')){}
        else if ($ip = getenv('REMOTE_ADDR')){}
        else 
            $ip = '0.0.0.0';
        return $ip;
    }/*}}}*/
}/*}}}*/
