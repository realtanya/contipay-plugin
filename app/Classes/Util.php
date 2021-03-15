<?php

namespace App\Classes;

use GuzzleHttp\Client;

class Util
{
    const URL = 'https://api2-test.contipay.co.zw';

    public static function dump($data)
    {
        echo "<code>";
        echo "<pre>";
        print_r($data);
        echo "</pre>";
        echo "</code>";
    }

    public static function dump_die($data)
    {
        echo "<code>";
        echo "<pre>";
        die(print_r($data));
        echo "</pre>";
        echo "</code>";
    }

    public static function generate(int $val)
    {
        $chars = '0123456789abcdefghijklmnopqrstvwZABCDEFGHIJKLMNOPQRSTVUXWz';
        $str = '';

        for ($i = 0; $i < $val; $i++) {
            $index = rand(0, strlen($chars) - 1);
            $str .= $chars[$index];
        }

        return $str . date('mdyHm');
    }

    public static function encode($val)
    {
        return base64_encode($val);
    }

    public static function decode($val)
    {
        return base64_decode($val);
    }

    public static function sanitizeAuth($val, $index = 0)
    {
        $sanitized_auth = explode(":", base64_encode($val[$index]));

        return $sanitized_auth;
    }

    public static function basicAuth($arr)
    {
        $username = Util::sanitizeAuth($arr, 0);
        $password = Util::sanitizeAuth($arr, 1);

        $colon = Util::encode(':');

        $num1 = Util::decode($username[0]);
        $num2 = Util::decode($password[0]);
        $num3 = Util::decode($colon);

        $auth = $num1 . $num3 . $num2;

        $sanitized_auth = Util::encode($auth);

        return $sanitized_auth;
    }

    public static function http($auth = null)
    {
        $client = new Client([
            'base_uri' => self::URL,
            'headers' => [
                'Content-Type' => ' application/json',
                'Accept' => 'application/json',
                'Authorization' => $auth
            ]
        ]);
        return $client;
    }

    public static function redirect(string $url)
    {
        header("Location: $url");
        exit();
    }

    public static function logger($text, $location = 'logs')
    {
        $date = date("Y-m-d H:i:s");
        $filename = date('Y_m_d') . '_logs';

        $logging_text = "$date Log: $text";

        $log = file_put_contents("./$location/$filename.txt", $logging_text . PHP_EOL, FILE_APPEND | LOCK_EX);

        return true;
    }

    public static function checkError(array $data)
    {
        if ($data['status'] == 'Error') {
            // log data
            Util::logger($data);
        } else {
            //log data 
            Util::logger($data);

            // redirect to contipay
            $redirectUrl = $data['redirectUrl'];
            Util::redirect($redirectUrl);
        }
    }
}
