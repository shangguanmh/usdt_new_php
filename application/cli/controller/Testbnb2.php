<?php

namespace app\cli\controller;

use think\Controller;
use think\Request;
use GuzzleHttp\Client;
use think\Db;
use Binance\NodeApi;
use Binance\Bnb;
use Binance\BEP20;
use Binance\Utils;
use Binance\Wallet;

class Testbnb extends Controller 
{
    public function index()
    {
        $uri = 'https://bsc-dataseed1.defibit.io/';// Mainnet
        // 尝试其他节点如果当前节点不稳定
        // $uri = 'https://bsc-dataseed.binance.org/';
        // $uri = 'https://bsc-dataseed2.defibit.io/';

        $api = new NodeApi($uri);
        $bnb = new Bnb($api);
        $wallet = new Wallet(); // 创建钱包实例

        $config = [
            'contract_address' => '0xd4b6f4c9af70c3287979228c34ec9c880847f608',// USDT BEP20
            'decimals' => 18,
        ];

        $bep20 = new BEP20($api, $config);

        // 私钥（不带0x前缀）
        $privateKey = 'e9c275426f870d3ebf0753192311a80d312cff0083642261180572f83d937771';
        $toAddress = '0xd4b6f4c9af70c3287979228c34ec9c880847f608';

        // 使用钱包类获取地址
        $accountInfo = $wallet->revertAccountByPrivateKey($privateKey);
        $fromAddress = $accountInfo['address'];
        
        echo "发送方地址: " . $fromAddress . "\n";

        // 检查BNB余额
        $bnbBalance = $bnb->bnbBalance($fromAddress); // 注意这里使用bnbBalance方法
        echo "BNB余额: " . $bnbBalance . "\n";

        // 检查代币余额
        $tokenBalance = $bep20->balance($fromAddress);
        echo "代币余额: " . $tokenBalance . "\n";

        // 转账金额
        $amount = 0.1;

        // 如果余额足够，再进行转账
        if ($tokenBalance >= $amount) {
            // 使用正确的参数格式进行转账
            //$api = new \Binance\BscscanApi($api);
            //$fee = $api->gasPrice();
            //$fee = $api->gasPrice();
            //var_dump($fee);exit;
            $res2 = $bep20->transfer($privateKey, $toAddress, $amount);
            var_dump($res2);
            
            if ($res2) {
                echo "交易哈希: " . $res2 . "\n"; // 可能直接返回交易哈希
            } else {
                echo "转账失败，请检查私钥格式、余额和网络连接\n";
            }
        } else {
            echo "余额不足，无法转账\n";
        }

        // 交易转账(离线签名)
        // $from = '0x1667ca2c7****021be3a';
        // $to = '0xd8699f0****b60eef021';
        // $amount = 0.1;
        // $bnb->transfer($from, $to, $amount);
        // $bep20->transfer($from, $to, $amount);

        // // 查询最新区块
        // $bnb->blockNumber();
        // $bep20->blockNumber();

        // // 根据区块链查询信息
        // $blockID = 24631027;
        // $bnb->getBlockByNumber($blockID);
        // $bep20->getBlockByNumber($blockID);

        // // 根据交易哈希返回交易的收据
        // $txHash = '0x4dd20d01af4c621d2f****77988bfb245a18bfb6f50604b';
        // $bnb->getTransactionReceipt($txHash);
        // $bep20->getTransactionReceipt($txHash);

        // // 根据交易哈希返回关于所请求交易的信息
        // $txHash = '0x4dd20d01af4c621d2f****77988bfb245a18bfb6f50604b';
        // $bnb->getTransactionByHash($txHash);
        // $bep20->getTransactionByHash($txHash);

        // // 根据交易哈希查询交易状态
        // $txHash = '0x4dd20d01af4c621d2f****77988bfb245a18bfb6f50604b';
        // $bnb->receiptStatus($txHash);
        // $bep20->receiptStatus($txHash);
    }
} 