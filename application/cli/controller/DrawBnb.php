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

class DrawBnb extends Controller
{
    private $apiKey;
    private $bscNodes = [
        'https://bsc-dataseed1.defibit.io/',
        'https://bsc-dataseed.binance.org/',
        'https://bsc-dataseed2.defibit.io/',
        'https://bsc-dataseed3.binance.org/',
        'https://bsc-dataseed4.binance.org/',
        'https://bsc-dataseed1.ninicoin.io/',
        'https://bsc-dataseed2.ninicoin.io/'
    ];
    private $usdtContractAddress = '0xd4b6f4c9af70c3287979228c34ec9c880847f608'; // USDT BEP20合约地址
    private $lockExpireTime = 15; // 锁过期时间(秒)
    private $gasLimit = 65000; // USDT转账的Gas限制
    private $gasLimitBnb = 21000; // BNB转账的Gas限制
    private $gasMultiplier = 1.2; // Gas价格安全系数

    public function __construct()
    {
        parent::__construct();
        $this->apiKey = 'ZAD7KIVUQUCBKCN9RFW4DBHGBCU6SUAM3Z'; // BscScan API 密钥
    }

    /**
     * 处理BEP20-USDT提现任务
     */
    public function task()
    {
        echo "运行BNB提现脚本 - " . date('Y-m-d H:i:s') . "\n";

        $now = date('Y-m-d H:i:s');
        $caozuo = Db::name('caozuo')->where([
            'op_time' => ['elt', $now], 
            'type' => 'autotixianbnb'
        ])->select();
        
        if (empty($caozuo)) {
            echo "没有需要处理的BNB提现任务\n";
            exit;
        }
        
        foreach ($caozuo as $val) {
            $lock_key = getRedisXM('bnbtixian' . $val['id']);
            $is_lock = redisCache()->setnx($lock_key, 1);
            
            if (!$is_lock) {
                echo '当前id操作中:' . $val['id'] . "\n";
                redisCache()->expire($lock_key, $this->lockExpireTime);
                continue;
            }
            
            redisCache()->expire($lock_key, $this->lockExpireTime);
            $this->processBnbWithdraw($val, $lock_key);
        }
    }

    /**
     * 处理BNB提现
     * @param array $task 任务数据
     * @param string $lock_key 锁键名
     */
    private function processBnbWithdraw($task, $lock_key)
    {
        try {
            echo "处理提现任务ID: " . $task['id'] . "\n";
            
            // 获取提现订单信息
            $withdraw = Db::name('draw_order')->where(['id' => $task['pk_id'], 'status' => 1])->find();
            
            if (empty($withdraw)) {
                echo "找不到提现订单或状态不正确: " . $task['pk_id'] . "\n";
                Db::name('caozuo')->where(['id' => $task['id']])->delete();
                redisCache()->del($lock_key);
                return;
            }
            
            // 检查提现类型
            if (!in_array($withdraw['huobi'], ['BEP20-USDT', 'BNB'])) {
                echo "不支持的提现类型: " . $withdraw['huobi'] . "\n";
                Db::name('caozuo')->where(['id' => $task['id']])->delete();
                redisCache()->del($lock_key);
                return;
            }
            
            // 获取提现账户信息
            $withdrawAddress = '';
            $withdrawKey = '';
            
            if ($withdraw['huobi'] == 'BEP20-USDT') {
                $withdrawAddress = getConfig('tx_bep_address', '');
                $withdrawKey = getConfig('tx_bep_key', '');
            } else if ($withdraw['huobi'] == 'BNB') {
                $withdrawAddress = getConfig('tx_bnb_address', '');
                $withdrawKey = getConfig('tx_bnb_key', '');
            }
            
            if (empty($withdrawAddress) || empty($withdrawKey)) {
                echo "提现账户信息未配置\n";
                redisCache()->del($lock_key);
                return;
            }
            
            // 检查提现地址
            if (empty($withdraw['to_address'])) {
                echo "提现地址为空\n";
                redisCache()->del($lock_key);
                return;
            }
            
            // 执行转账
            $result = $this->executeTransfer($withdrawKey, $withdraw['to_address'], $withdraw['daozhang'], $withdraw['huobi']);
            
            if ($result['status']) {
                // 更新提现订单状态
                $updateData = [
                    'tranfer_detail' => json_encode($result),
                    'status' => 2,
                    'shenhe_user_id' => 0,
                    'shenhe_time' => date('Y-m-d H:i:s'),
                    'hash' => $result['txHash']
                ];
                
                Db::name('draw_order')->where(['id' => $withdraw['id']])->update($updateData);
                
                // 处理积分变更
                $this->tixianjiajifen($withdraw['user_id'], $withdraw['zuihou_value']);
                
                echo "提现成功，交易哈希: " . $result['txHash'] . "\n";
            } else {
                echo "提现失败: " . $result['message'] . "\n";
            }
            
            // 删除任务
            Db::name('caozuo')->where(['id' => $task['id']])->delete();
            
        } catch (Exception $e) {
            echo "处理提现任务异常: " . $e->getMessage() . "\n";
        }
        
        redisCache()->del($lock_key);
    }

    /**
     * 执行转账
     * @param string $privateKey 私钥
     * @param string $toAddress 目标地址
     * @param float $amount 金额
     * @param string $tokenType 代币类型
     * @return array 转账结果
     */
    private function executeTransfer($privateKey, $toAddress, $amount, $tokenType)
    {
        try {
            // 去除可能的0x前缀
            if (substr($privateKey, 0, 2) === '0x') {
                $privateKey = substr($privateKey, 2);
            }
            
            // 选择一个BSC节点
            $nodeUrl = $this->bscNodes[0]; // 默认使用第一个节点
            echo "使用节点: " . $nodeUrl . "\n";
            
            // 初始化API和钱包
            $api = new NodeApi($nodeUrl);
            $wallet = new Wallet();
            
            // 验证发送方地址
            $accountInfo = $wallet->revertAccountByPrivateKey($privateKey);
            $fromAddress = $accountInfo['address'];
            
            echo "发送方地址: " . $fromAddress . "\n";
            echo "转账金额: " . $amount . " " . $tokenType . "\n";
            
            if ($tokenType === 'BNB') {
                // BNB转账
                echo "开始BNB转账\n";
                $bnb = new Bnb($api);
                
                // 检查BNB余额
                $bnbBalance = $bnb->bnbBalance($fromAddress);
                echo "BNB余额: " . $bnbBalance . " BNB\n";
                
                if ($bnbBalance < $amount) {
                    return [
                        'status' => false,
                        'message' => '余额不足，提现失败'
                    ];
                }
                
                $txHash = $bnb->transfer($privateKey, $toAddress, $amount);
                
                return [
                    'status' => true,
                    'message' => 'BNB转账成功',
                    'txHash' => $txHash
                ];
            } else if ($tokenType === 'BEP20-USDT') {
                // USDT BEP20转账
                echo "开始USDT BEP20转账\n";
                $config = [
                    'contract_address' => $this->usdtContractAddress,
                    'decimals' => 18,
                ];
                
                $bep20 = new BEP20($api, $config);
                
                // 检查USDT余额
                $usdtBalance = $bep20->balance($fromAddress);
                echo "USDT余额: " . $usdtBalance . " USDT\n";
                
                if ($usdtBalance < $amount) {
                    return [
                        'status' => false,
                        'message' => '余额不足，提现失败'
                    ];
                }
                
                // 检查BNB余额（用于支付手续费）
                $bnb = new Bnb($api);
                $bnbBalance = $bnb->bnbBalance($fromAddress);
                echo "BNB余额: " . $bnbBalance . " BNB\n";
                
                // 计算所需手续费
                $gasPrice = $this->getCurrentGasPrice();
                $estimatedFee = $this->calculateUsdtTransferFee($gasPrice);
                
                if ($bnbBalance < $estimatedFee) {
                    return [
                        'status' => false,
                        'message' => 'BNB手续费不足，提现失败'
                    ];
                }
                
                $txHash = $bep20->transfer($privateKey, $toAddress, $amount);
                
                return [
                    'status' => true,
                    'message' => 'USDT BEP20转账成功',
                    'txHash' => $txHash
                ];
            } else {
                return [
                    'status' => false,
                    'message' => '不支持的代币类型: ' . $tokenType
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => '转账失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取当前BSC网络的Gas价格
     * @return float Gas价格(Gwei)
     */
    private function getCurrentGasPrice()
    {
        try {
            // 选择一个BSC节点
            $nodeUrl = $this->bscNodes[0];
            
            // 初始化API
            $api = new NodeApi($nodeUrl);
            
            // 直接使用固定的Gas价格(5 Gwei)，这是BSC网络的一个合理值
            $gasPriceGwei = 5;
            
            echo "使用固定的Gas价格: " . $gasPriceGwei . " Gwei\n";
            
            return $gasPriceGwei;
        } catch (Exception $e) {
            echo "获取Gas价格失败: " . $e->getMessage() . "\n";
            // 返回默认Gas价格(5 Gwei)
            return 5;
        }
    }

    /**
     * 计算USDT转账所需的BNB手续费
     * @param float $gasPrice Gas价格(Gwei)
     * @return float 所需BNB手续费
     */
    private function calculateUsdtTransferFee($gasPrice)
    {
        // 确保Gas价格不为0
        if ($gasPrice <= 0) {
            $gasPrice = 5; // 使用默认值5 Gwei
        }
        
        // 计算手续费: (gasPrice * gasLimit) / 10^9 (转换为BNB)
        // 增加安全系数
        $gasPrice = bcmul($gasPrice, $this->gasMultiplier, 9);
        
        // 将Gwei转换为Wei (1 Gwei = 10^9 Wei)
        $gasPriceWei = bcmul($gasPrice, 1000000000, 0);
        
        // 计算总费用(Wei)
        $feeWei = bcmul($gasPriceWei, $this->gasLimitBnb, 0);
        
        // 将Wei转换为BNB (1 BNB = 10^18 Wei)
        $feeBnb = bcdiv($feeWei, 1000000000000000000, 8);
        
        echo "Gas价格: " . $gasPrice . " Gwei\n";
        echo "Gas限制: " . $this->gasLimitBnb . "\n";
        echo "计算的USDT转账手续费: " . $feeBnb . " BNB\n";
        
        return $feeBnb;
    }

    /**
     * 提现加积分
     * @param int $user_id 用户ID
     * @param float $jifen 积分
     */
    private function tixianjiajifen($user_id, $jifen)
    {
        $zengyue = Db::name('user')->where(['id' => $user_id])->value('zyue');
        if ($zengyue > 0) {
            if ($jifen > $zengyue) { // 如果手动余额不足以抵扣，就要加剩余积分
                $jiajifen = bcsub($jifen, $zengyue, 6);
                sysjifenChange('用户提现', $jiajifen);
                htzengyueChange('用户提现抵消', -$zengyue, $user_id, []);
            } else {
                htzengyueChange('用户提现抵消', -$jifen, $user_id, []);
            }
        } else {
            sysjifenChange('用户提现', $jifen);
        }
    }

    /**
     * HTTP请求
     * @param string $url URL
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
} 