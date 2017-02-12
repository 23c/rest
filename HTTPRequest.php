<?php
/**
 * Created by PhpStorm.
 * User: 23c
 * Date: 16/11/8
 * Time: 下午1:38
 */
class HTTPRequest
{
    public static function __callStatic($name, $arguments)
    {
        $name = strtoupper($name);
        $methods = array('GET', 'PUT', 'DELETE','POST');

        if (empty($arguments)) {
            return json_decode(json_encode(['error_code' => '-10000', 'msg' => '请求的参数错误' ], JSON_UNESCAPED_UNICODE));
        }

        if (!in_array($name, $methods)) {
            return json_decode(json_encode(['error_code' => '-10000', 'msg' => '请求的方法错误'], JSON_UNESCAPED_UNICODE));
        }

        $url = $arguments[0];
        $data = empty($arguments[1]) ? array() : $arguments[1];

        try {

            $config = \Phalcon\DI::getDefault()->get("config");
            $webApi = $config->webApi;
            $scheme = 'http';
            $parseURI = parse_url($webApi->domain);
            $version = empty($arguments[2]) ? $webApi->version : $arguments[2];

            if (isset($parseURI['scheme'])) {
                $scheme = $parseURI['scheme'];
            }

            $url = $webApi->domain . $url;
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt ( $curl, CURLOPT_CUSTOMREQUEST, $name );
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

            $aHeader[] = "Content-Type: application/json";
            $aHeader[] = "DCAPPID: {$webApi->appId}";
            $aHeader[] = "{$webApi->versionKey}: {$version}";
            $aHeader[] = "DCTOKEN: " . md5($webApi->apiKeys . json_encode($data));

            curl_setopt($curl, CURLOPT_HTTPHEADER, $aHeader);
            $data = curl_exec($curl);
            curl_close($curl);

            return json_decode($data, true);

        } catch (\Exception $exception) {
            return json_decode(json_encode(['error_code' => '10001', 'msg' => $exception->getMessage(), 'data' => []]));
        }

    }
}