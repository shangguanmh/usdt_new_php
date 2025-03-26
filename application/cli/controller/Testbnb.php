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
use Exception;

class Testbnb extends Controller 
{
    public function index()
    {
        try {
            // 尝试多个节点以提高成功率
            $nodes = [
                'https://bsc-dataseed1.defibit.io/',
                'https://bsc-dataseed.binance.org/',
                'https://bsc-dataseed2.defibit.io/',
                'https://bsc-dataseed3.binance.org/',
                'https://bsc-dataseed4.binance.org/',
                'https://bsc-dataseed1.ninicoin.io/',
                'https://bsc-dataseed2.ninicoin.io/'
            ];
            
            // 选择一个节点
            $uri = $nodes[0]; // 默认使用第一个节点
            
            echo "使用节点: " . $uri . "\n";
            
            $api = new NodeApi($uri);
            $bnb = new Bnb($api);
            $wallet = new Wallet(); // 创建钱包实例

            $config = [
                'contract_address' => '0x55d398326f99059fF775485246999027B3197955',// USDT BEP20
                'decimals' => 18,
            ];

            $bep20 = new BEP20($api, $config);

            // 私钥（不带0x前缀）
            $privateKey = 'e9c275426f870d3ebf0753192311a80d312cff0083642261180572f83d937771';
            $toAddress = '0x30945BF474333223E36364aa5361A4aa6212E8f7';

            // 使用钱包类获取地址
            $accountInfo = $wallet->revertAccountByPrivateKey($privateKey);
            $fromAddress = $accountInfo['address'];
            
            echo "发送方地址: " . $fromAddress . "\n";

            // 检查BNB余额
            $bnbBalance = $bnb->bnbBalance($fromAddress); // 注意这里使用bnbBalance方法
            echo "BNB余额: " . $bnbBalance . "\n";
            
            if ($bnbBalance < 0.001) {
                echo "警告: BNB余额可能不足以支付Gas费用\n";
            }

            // 检查代币余额
            $tokenBalance = $bep20->balance($fromAddress);
            echo "代币余额: " . $tokenBalance . "\n";

            // 转账金额
            $amount = 0.1;

            // 如果余额足够，再进行转账
            if ($tokenBalance >= $amount) {
                // 尝试设置自定义gas价格 (可选)
                // 默认是standard, 也可以设置为：fast 或 low
                $gasPrice = 'fast'; 
                
                echo "开始转账...\n";
                
                // 添加调试信息
                echo "转账参数: 从地址 {$fromAddress} 发送 {$amount} 到地址 {$toAddress}\n";
                
                $res2 = $bep20->transfer($privateKey, $toAddress, $amount, $gasPrice);
                var_dump($res2);
                
                if ($res2) {
                    echo "交易哈希: " . $res2 . "\n"; // 可能直接返回交易哈希
                    
                    // 等待几秒并尝试查询交易状态
                    echo "等待交易确认...\n";
                    sleep(5);
                    try {
                        $status = $bep20->receiptStatus($res2);
                        echo "交易状态: " . ($status ? "成功" : "失败") . "\n";
                    } catch (Exception $e) {
                        echo "查询交易状态失败: " . $e->getMessage() . "\n";
                    }
                } else {
                    echo "转账失败!\n";
                    
                    // 尝试切换节点重试
                    echo "尝试使用其他节点...\n";
                    
                    foreach (array_slice($nodes, 1) as $fallbackNode) {
                        echo "切换到节点: " . $fallbackNode . "\n";
                        $fallbackApi = new NodeApi($fallbackNode);
                        $fallbackBep20 = new BEP20($fallbackApi, $config);
                        
                        $res2 = $fallbackBep20->transfer($privateKey, $toAddress, $amount, $gasPrice);
                        if ($res2) {
                            echo "交易哈希: " . $res2 . "\n";
                            break;
                        } else {
                            echo "使用节点 {$fallbackNode} 转账仍然失败\n";
                        }
                    }
                    
                    if (!$res2) {
                        echo "所有节点均尝试失败，请检查以下问题:\n";
                        echo "1. 私钥格式是否正确\n";
                        echo "2. BNB余额是否足够支付Gas费用\n";
                        echo "3. 网络连接是否稳定\n";
                        echo "4. 合约地址是否正确\n";
                    }
                }
            } else {
                echo "余额不足，无法转账\n";
            }
        } catch (Exception $e) {
            echo "发生错误: " . $e->getMessage() . "\n";
            echo "错误行号: " . $e->getLine() . "\n";
            echo "堆栈跟踪: " . $e->getTraceAsString() . "\n";
        }
    }

    public function getBSCTransactionHistory($address) 
    {
        $apiKey = 'ZAD7KIVUQUCBKCN9RFW4DBHGBCU6SUAM3Z'; // 需要替换为您的 BscScan API 密钥
        // 1. 获取普通BNB转账交易
        $normalTxUrl = "https://api.bscscan.com/api?module=account&action=txlist&address={$address}&startblock=0&endblock=99999999&sort=desc&apikey={$apiKey}";
        
        // 2. 获取内部交易
        $internalTxUrl = "https://api.bscscan.com/api?module=account&action=txlistinternal&address={$address}&startblock=0&endblock=99999999&sort=desc&apikey={$apiKey}";
        
        // 3. 获取BEP20代币转账
        $tokenTxUrl = "https://api.bscscan.com/api?module=account&action=tokentx&address={$address}&startblock=0&endblock=99999999&sort=desc&apikey={$apiKey}";
        
        // 4. 获取BEP721(NFT)转账
        $nftTxUrl = "https://api.bscscan.com/api?module=account&action=tokennfttx&address={$address}&startblock=0&endblock=99999999&sort=desc&apikey={$apiKey}";
        
        // 执行请求并返回结果
        $normalTx = json_decode(file_get_contents($normalTxUrl), true);
        $internalTx = json_decode(file_get_contents($internalTxUrl), true);
        $tokenTx = json_decode(file_get_contents($tokenTxUrl), true);
        $nftTx = json_decode(file_get_contents($nftTxUrl), true);
        
        return [
            'normal' => $normalTx['result'] ?? [],
            'internal' => $internalTx['result'] ?? [],
            'token' => $tokenTx['result'] ?? [],
            'nft' => $nftTx['result'] ?? []
        ];
    }
} 