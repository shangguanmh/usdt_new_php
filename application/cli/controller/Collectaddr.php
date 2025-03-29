<?php
namespace app\cli\controller;

use think\Controller;
use think\Db;
use GuzzleHttp\Client;
use Binance\NodeApi;
use Binance\Bnb;
use Binance\BEP20;
use Binance\Utils;
use Binance\Wallet;
use Exception;

class Collectaddr extends Controller
{
    private $apiKey;
    private $collectionAddress; // 归集账户地址
    private $minBnbAmount           = 0.0001; // 最小归集BNB数量
    private $minUsdtAmount          = 0.001; // 最小归集USDT数量
    private $lockExpireTime         = 15; // 锁过期时间(秒)
    private $usdtContractAddress    = '0x55d398326f99059ff775485246999027b3197955'; // USDT BEP20合约地址
    private $bnbReserveAmount       = 0.0001; // BNB保留数量，用于支付手续费
    private $bscNodes               = [
        'https://bsc-dataseed1.defibit.io/',
        'https://bsc-dataseed.binance.org/',
        'https://bsc-dataseed2.defibit.io/',
        'https://bsc-dataseed3.binance.org/',
        'https://bsc-dataseed4.binance.org/',
        'https://bsc-dataseed1.ninicoin.io/',
        'https://bsc-dataseed2.ninicoin.io/'
    ];

    public function __construct()
    {
        parent::__construct();
        //$this->apiKey = getConfig('bscscan_apikey', '');
        //$this->collectionAddress = getConfig('bnb_guijizhanghu', '');

        
        $this->apiKey = 'ZAD7KIVUQUCBKCN9RFW4DBHGBCU6SUAM3Z';
        $this->collectionAddress = '0x30945BF474333223E36364aa5361A4aa6212E8f7';
    }

    public function index()
    {
        echo "开始BNB归集任务\n";
        
        $now   = date('Y-m-d H:i:s');
        $tasks = Db::name('caozuo')->where([
            'type'    => 'guijibnb',
            'op_time' => ['elt', $now]
        ])->select();
        
        if (empty($tasks)) {
            echo "没有需要处理的归集任务\n";
            exit;
        }
        
        foreach ($tasks as $task) {
            $lock_key = getRedisXM('bnbguiji' . $task['id']);
            $is_lock  = redisCache()->setnx($lock_key, 1);
            
            if (!$is_lock) {
                echo '当前任务操作中:' . $task['id'] . "\n";
                redisCache()->expire($lock_key, $this->lockExpireTime);
                continue;
            }
            
            redisCache()->expire($lock_key, $this->lockExpireTime);
            
            try {
                $this->processCollectionTask($task);
            } catch (\Exception $e) {
                echo "归集出错: " . $e->getMessage() . "\n";
            } finally {
                redisCache()->del($lock_key);
            }
        }
        
        echo "归集任务结束\n";
    }
    
    /**
     * 获取BNB余额
     * @param string $address 钱包地址
     * @param string $coin 币种
     * @return float 余额
     */
    private function getBalance($address, $coin)
    {
        try {
            $url = "https://api.bscscan.com/api?module=account&action=balance&address={$address}&tag=latest&apikey={$this->apiKey}";
            $response = $this->httpRequest($url);
            $result = json_decode($response, true);
            
            if (isset($result['status']) && $result['status'] == '1' && isset($result['result'])) {
                // 将wei转换为BNB (1 BNB = 10^18 wei)
                $balance = bcdiv($result['result'], 1000000000000000000, 18);
                return $balance;
            }
            
            return 0;
        } catch (\Exception $e) {
            echo "获取BNB余额失败: " . $e->getMessage() . "\n";
            return 0;
        }
    }
    
    /**
     * 获取代币余额
     * @param string $address 钱包地址
     * @param string $contractAddress 代币合约地址
     * @return float 余额
     */
    private function getTokenBalance($address, $contractAddress)
    {
        try {
            $url = "https://api.bscscan.com/api?module=account&action=tokenbalance&contractaddress={$contractAddress}&address={$address}&tag=latest&apikey={$this->apiKey}";
            $response = $this->httpRequest($url);
            $result = json_decode($response, true);
            
            if (isset($result['status']) && $result['status'] == '1' && isset($result['result'])) {
                // 将wei转换为代币单位 (USDT BEP20通常是18位小数)
                $balance = bcdiv($result['result'], 1000000000000000000, 18);
                return $balance;
            }
            
            return 0;
        } catch (\Exception $e) {
            echo "获取代币余额失败: " . $e->getMessage() . "\n";
            return 0;
        }
    }
    
    /**
     * 归集转账
     * @param string $fromAddress 来源地址
     * @param string $tokenType 代币类型
     * @param string $toAddress 目标地址
     * @param float $amount 金额
     * @param int $investId 投资ID
     * @return array|bool 转账结果
     */
    private function transferForCollection($fromAddress, $tokenType, $toAddress, $amount, $investId)
    {
        // 查找地址信息
        $addressInfo = Db::name('bnb_address')->where(['address' => $fromAddress])->find();

        if (empty($addressInfo)) {
            echo "找不到地址信息: " . $fromAddress . "\n";
            return false;
        }
        
        // 检查是否有私钥
        if (empty($addressInfo['privateKey'])) {
            echo "地址没有私钥信息: " . $fromAddress . "\n";
            return false;
        }
        
        // 记录归集记录
        $collectionRecord = Db::name('bnb_guiji_record')->where([
            'from_address' => $fromAddress,
            'to_address'   => $toAddress,
            'huobi'        => $tokenType,
            'money'        => (string)$amount,
            'status'       => 0,
            'invest_id'    => (string)$investId
        ])->find();
        
        $recordId = 0;
        
        if (empty($collectionRecord)) {
            // 插入新记录
            $insertData = [
                'from_address' => $fromAddress,
                'to_address'   => $toAddress,
                'add_time'     => date('Y-m-d H:i:s'),
                'huobi'        => $tokenType,
                'money'        => (string)$amount,
                'status'       => 0,
                'invest_id'    => (string)$investId
            ];
            
            Db::name('bnb_guiji_record')->insert($insertData);
            $recordId = Db::name('bnb_guiji_record')->getLastInsID();
            echo "创建归集记录ID: " . $recordId . "\n";
        } else {
            $recordId = $collectionRecord['id'];
            echo "使用已有归集记录ID: " . $recordId . "\n";
        }
        
        // 实际转账逻辑
        $transactionResult = [];
        try {
            // 获取私钥（去除可能的0x前缀）
            $privateKey = $addressInfo['privateKey'];

            if (substr($privateKey, 0, 2) === '0x') {
                $privateKey = substr($privateKey, 2);
            }
            
            // 选择一个BSC节点
            $nodeUrl = $this->bscNodes[0]; // 默认使用第一个节点
            echo "使用节点: " . $nodeUrl . "\n";
            
            // 初始化API和钱包
            $api    = new NodeApi($nodeUrl);
            $wallet = new Wallet();
            
            // 验证发送方地址
            $accountInfo = $wallet->revertAccountByPrivateKey($privateKey);
            if ($accountInfo['address'] !== $fromAddress) {
                throw new Exception("私钥与地址不匹配");
            }
            
            if ($tokenType === 'BNB') {
                // BNB转账
                $bnb    = new Bnb($api);
                $txHash = $bnb->transfer($privateKey, $toAddress, $amount);
                
                $transactionResult = [
                    'status'    => 1,
                    'message'   => 'BNB转账成功',
                    'txHash'    => $txHash
                ];
            } else if ($tokenType === 'USDT_BEP20') {
                // USDT BEP20转账
                $config = [
                    'contract_address' => $this->usdtContractAddress,
                    'decimals' => 18,
                ];
                
                $bep20  = new BEP20($api, $config);
                $txHash = $bep20->transfer($privateKey, $toAddress, $amount);
                
                $transactionResult = [
                    'status'    => 1,
                    'message'   => 'USDT转账成功',
                    'txHash'    => $txHash
                ];
            } else {
                throw new Exception("不支持的代币类型: " . $tokenType);
            }
            
            // 交易成功，更新状态为1
            Db::name('bnb_guiji_record')->where(['id' => $recordId])->update($transactionResult);
            
            echo "归集交易成功: " . $fromAddress . " -> " . $toAddress . " " . $amount . " " . $tokenType . "\n";
            
        } catch (Exception $e) {
            echo "转账失败: " . $e->getMessage() . "\n";
            
            $transactionResult = [
                'message'   => '归集交易失败: ' . $e->getMessage()
            ];
            
            // 交易失败，保持状态为0，更新详情
            Db::name('bnb_guiji_record')->where(['id' => $recordId])->update($transactionResult);
        }
        
        return $transactionResult;
    }
    
    /**
     * HTTP请求
     * @param string $url 请求URL
     * @return string 响应内容
     */
    private function httpRequest($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    
    /**
     * 一键归集所有地址
     */
    public function collectAll()
    {
        $list = Db::name('bnb_address')->where('user_id', '<>', 0)->select();
        
        foreach ($list as $val) {
            $this->createCollectionTask($val['address'], 'USDT_BEP20', 0);
        }
        
        echo '操作成功，重新发起归集' . count($list) . '条';
    }
    
    /**
     * 创建归集任务
     * @param string $wallet 钱包地址
     * @param string $tokenType 代币类型
     * @param int $extra 额外参数
     */
    private function createCollectionTask($wallet, $tokenType, $extra = 0)
    {
        $taskData = [
            'pk_id' => 0,
            'type' => 'guijibnb',
            'add_time' => date('Y-m-d H:i:s'),
            'op_time' => date('Y-m-d H:i:s'),
            'extra' => $extra,
            'qianbao' => $wallet,
            'huobi' => $tokenType
        ];
        
        Db::name('caozuo')->insert($taskData);
    }
    
    /**
     * 处理单个归集任务
     * @param array $task 任务数据
     */
    private function processCollectionTask($task)
    {
        echo "开始归集地址:" . $task['qianbao'] . "\n";
        $address   = $task['qianbao'];
        $tokenType = $task['huobi'];
        
        // 获取BNB余额
        $bnbBalance = $this->getBalance($address, 'BNB');
        echo "BNB余额:" . $bnbBalance . "\n";
        
        if ($tokenType == 'BNB') {
            echo "BNB不提取\n";
            $this->processBnbCollection($address, $bnbBalance, $task);
        } else if ($tokenType == 'USDT_BEP20') {
            $this->processUsdtCollection($address, $bnbBalance, $task);
        }
    }
    
    /**
     * 处理BNB归集
     * @param string $address 钱包地址
     * @param float $bnbBalance BNB余额
     * @param array $task 任务数据
     */
    private function processBnbCollection($address, $bnbBalance, $task)
    {
        // 归集BNB
        if ($bnbBalance > $this->bnbReserveAmount && $bnbBalance > 0.02) { // 留0.01 BNB作为手续费
            $collectionAddress = $this->collectionAddress;
            $transferAmount    = bcsub($bnbBalance, $this->bnbReserveAmount, 8);
            echo '转账' . $transferAmount . 'BNB' . "\n";
            $this->transferForCollection($address, 'BNB', $collectionAddress, $transferAmount, $task['pk_id']);
        } else {
            echo 'BNB不够' . $this->bnbReserveAmount . '，不转账' . "\n";
        }
        Db::name('caozuo')->where(['id' => $task['id']])->delete();
    }
    
    /**
     * 处理USDT归集
     * @param string $address 钱包地址
     * @param float $bnbBalance BNB余额
     * @param array $task 任务数据
     */
    private function processUsdtCollection($address, $bnbBalance, $task)
    {
        // 归集USDT BEP20
        echo "开始归集USDT BEP20\n";
        $usdtBalance = $this->getTokenBalance($address, $this->usdtContractAddress);
        echo "USDT余额:" . $usdtBalance . "\n";
        
        $minBnb = 0.003; // 最低需要的BNB手续费
        
        if ($bnbBalance >= $minBnb && $usdtBalance > $this->minUsdtAmount) {
            $collectionAddress = $this->collectionAddress;
            $this->transferForCollection($address, 'USDT_BEP20', $collectionAddress, $usdtBalance, $task['pk_id']);
            echo 'USDT BEP20转账了:' . $usdtBalance . "\n";
            
            // 如果BNB余额足够，也归集BNB
            if ($bnbBalance > 0.01) {
                $bnbToTransfer = bcsub($bnbBalance, 0.01, 8);
                echo '转账剩余BNB:' . $bnbToTransfer . "\n";
                $this->transferForCollection($address, 'BNB', $collectionAddress, $bnbToTransfer, $task['pk_id']);
            }
            
            Db::name('caozuo')->where(['id' => $task['id']])->delete();
        } else {
            echo 'BNB手续费不够或USDT余额太少，不转账' . "\n";
        }
    }
} 