<?php

namespace app\cli\util;
use think\Db;
class Util{
    public static function mainengliang($adddress){
        
        $API_KEY = "0DD624E0A82F4167BC077820A032464B";
        $API_SECRET = "A8F8585B7C591C9047F2C263F70D22A4099AEA1E43F1236A502BBC25DCF207AB";
        
        $timestamp = time();
        
        $data = [
            'energy_amount' => 66000,
            'period' => '1H',
            'receive_address' => $adddress,
            'callback_url' => 'https://www.baidu.com/',
            'out_trade_no' => time().mt_rand(10000,99999),
        ];
        
        ksort($data);
        
        $json_data = json_encode($data, JSON_UNESCAPED_SLASHES);
        
        $message = $timestamp . '&' . $json_data;
        
        $signature = hash_hmac('sha256', $message, $API_SECRET);
        
        $ch = curl_init("https://trxx.io/api/v1/frontend/order");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "API-KEY: $API_KEY",
            "TIMESTAMP: $timestamp",
            "SIGNATURE: $signature"
        ]);
        
        $result = curl_exec($ch);
        curl_close($ch);
        echo ($result);
    }
}
