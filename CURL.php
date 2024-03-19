<?php

class CURL{

    private $CURL_URL;
    private $CURL_METHOD;
    private $CURL_PAYLOAD;
    private $CURL_HEADERS;
    private $CURL_DEFAULT_USER_AGENT = "Renyu-CURL/1.0";
    private $CURL_REDIRECT_ALLOW = false;
    private $OPTIMIZE_CLOUDFLARE = true;
    private $CLOUDFLARE_ENTERPRISE_DOMAIN = "arca.live";
    
    public function __construct($URL, $METHOD="GET"){
        $this->CURL_URL = $URL;
        $this->CURL_METHOD = $METHOD;
    }
    private function RETURN($STATUS, $MSG = null, $STATUS_CODE = null, $DATA = null){
        $RETURN = ["STATUS" => $STATUS === "OK" ? "OK" : "ERR"];
        if ($STATUS_CODE) $RETURN["CODE"] = $STATUS_CODE;
        if ($MSG) $RETURN["MSG"] = $MSG;
        if ($DATA) $RETURN["DATA"] = $DATA;
        return $RETURN;
    }

    public function PAYLOAD($DATA){
        $this->CURL_PAYLOAD = $DATA;
    }

    public function HEADER(array $HEDAERS) {
        $this->CURL_HEADERS = $HEDAERS;
    }

    private function CLOUDFLARE_IP_RANGE(){
        return self::RETURN("OK", "Cloudflare IP Ranges", 200, array(
            "173.245.48.0/20", "103.21.244.0/22", "103.22.200.0/22", "103.31.4.0/22", "141.101.64.0/18", "108.162.192.0/18",
            "190.93.240.0/20", "188.114.96.0/20", "197.234.240.0/22", "198.41.128.0/17", "162.158.0.0/15", "104.16.0.0/13", 
            "104.24.0.0/14", "172.64.0.0/13", "131.0.72.0/22"
        ));
    }

    private function IS_CLOUDFLARE($IP_ADDR){
        $CF_IP_RANGES = $this->CLOUDFLARE_IP_RANGE();
        $ORIGIN_IP_ADDR = gethostbyname(parse_url($this->CURL_URL, PHP_URL_HOST));
        $IS_CLOUDFLARE = array_reduce($CF_IP_RANGES['DATA'], function ($status, $range) use ($ORIGIN_IP_ADDR) {
            [$subnet, $mask] = explode('/', $range);
            $ip_long = ip2long($ORIGIN_IP_ADDR);
            $subnet_long = ip2long($subnet);
            $mask_long = ~((1 << (32 - $mask)) - 1);
            return $status || (($ip_long & $mask_long) == ($subnet_long & $mask_long));
        }, false);
        return $IS_CLOUDFLARE;
    }

    public function SEND(){
        $HOST = parse_url($this->CURL_URL, PHP_URL_HOST);

        if($this->OPTIMIZE_CLOUDFLARE){
            $ORIGIN_IP_ADDR = gethostbyname(parse_url($this->CURL_URL, PHP_URL_HOST));
            $IS_CLOUDFLARE = $this->IS_CLOUDFLARE($ORIGIN_IP_ADDR);
            if($IS_CLOUDFLARE) $ENTERPRISE_REGION_ADDR = gethostbyname($this->CLOUDFLARE_ENTERPRISE_DOMAIN);
        }
        if(empty($this->CURL_HEADERS["User-Agent"])) $this->CURL_HEADERS["User-Agent"] = $this->CURL_DEFAULT_USER_AGENT;

        $CURL = curl_init();
        curl_setopt($CURL, CURLOPT_URL, $this->CURL_URL);
        curl_setopt($CURL, CURLOPT_CUSTOMREQUEST, $this->CURL_METHOD);
        curl_setopt($CURL, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($CURL, CURLOPT_HEADER, 1);
        curl_setopt($CURL, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($CURL, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($CURL, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($CURL, CURLOPT_HTTPHEADER, array_map(function($k, $v) { return "$k: $v"; }, array_keys($this->CURL_HEADERS), $this->CURL_HEADERS));
        if($this->CURL_REDIRECT_ALLOW) curl_setopt($CURL, CURLOPT_FOLLOWLOCATION, true);
        if(!is_null($this->CURL_PAYLOAD)) curl_setopt($CURL, CURLOPT_POSTFIELDS, $this->CURL_PAYLOAD);
        if($this->OPTIMIZE_CLOUDFLARE || !empty($ENTERPRISE_REGION_ADDR)) curl_setopt($CURL, CURLOPT_RESOLVE, [$HOST.":443:".$ENTERPRISE_REGION_ADDR, $HOST.":80:".$ENTERPRISE_REGION_ADDR]);

        try{
            $EXEC = curl_exec($CURL);
            $CURL_INFO = curl_getinfo($CURL);
            curl_close($CURL);
            if($CURL_INFO['http_code'] == 0) return self::RETURN("ERR", curl_error($CURL), curl_errno($CURL));
            $FORMATED_HEADERS = array_reduce(explode("\n", substr($EXEC, 0, $CURL_INFO['header_size'])), function($carry, $header) {
                list($key, $value) = array_pad(explode(': ', $header, 2), 2, null);
                if($key && $value) $carry[trim($key)] = trim($value);
                return $carry;
            }, []);

            return self::RETURN("OK", "FETCHED", $CURL_INFO['http_code'], array(
                "RESPONSE" => array("DATA" => substr($EXEC, $CURL_INFO['header_size']), "HEADER" => $FORMATED_HEADERS, "META" => array_change_key_case($CURL_INFO, CASE_UPPER)),
                "REQUEST" => array("DATA" => $this->CURL_PAYLOAD, "HEADER" => $this->CURL_HEADERS)
            ));
        } catch (\Exception $e) {
            return self::RETURN("ERR", $e->getMessage());
        }
    }

}
