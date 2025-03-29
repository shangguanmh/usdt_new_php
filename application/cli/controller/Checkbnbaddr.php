<?php
namespace app\cli\controller;

use think\Controller;
use think\Db;
use GuzzleHttp\Client;
use Exception;

class Checkbnbaddr extends Controller
{
    private $apiKey                 = 'ZAD7KIVUQUCBKCN9RFW4DBHGBCU6SUAM3Z'; // BscScan API 密钥
    private $usdtContractAddress    = '0x55d398326f99059ff775485246999027b3197955'; // USDT BEP20 合约地址
    private $lockExpireTime         = 180; // 锁过期时间（秒）
    private $minBnbAmount           = 0.001; // 最小BNB充值金额
    private $minUsdtAmount          = 0.1; // 最小USDT充值金额
    private $maxTransactionAge      = 48 * 60 * 60; // 最大交易有效期（秒）
    private $orderExpireTime        = 30 * 60; // 订单过期时间（秒）
    
    public function task()
    {
        echo "运行BNB地址检查脚本\n";

        $now = date('Y-m-d H:i:s');

        $caozuo = Db::name('caozuo')->where([
            'op_time' => ['elt', $now], 
            'type' => 'chongzhi_bnb'
        ])->select();
        
        if (empty($caozuo)) {
            echo "没有需要处理的充值任务\n";
            exit;
        }
        
        foreach ($caozuo as $val) {
            $lock_key = getRedisXM('bnbtask' . $val['id']);
            $is_lock  = redisCache()->setnx($lock_key, 1);
            
            if (!$is_lock) {
                echo '当前id操作中:' . $val['id'];
                redisCache()->expire($lock_key, $this->lockExpireTime);
                continue;
            }
            
            redisCache()->expire($lock_key, $this->lockExpireTime);
            $this->processChongzhiBnb($val, $lock_key);
        }
    }
    
    private function processChongzhiBnb($val, $lock_key)
    {
        try {

            $success  = false;

            $distance_time = round((time() - strtotime($val['op_time'])) / 60, 1);

            $fen = date('i');
            
            // 根据时间间隔控制执行频率
            if ($distance_time >= 10 && $distance_time < 20) {
                // 10-20分钟每2分钟执行一次
                if ($fen % 2 != 0) {
                    echo "10-20分钟每2分钟执行一次\n";
                    redisCache()->del($lock_key);
                    exit;
                }
            } else if ($distance_time >= 20) {
                // 20分钟以后每3分钟执行一次
                if ($fen % 3 != 0) {
                    echo "20分钟以后3分钟执行一次\n";
                    redisCache()->del($lock_key);
                    exit;
                }
            }
            
            $invest_order = Db::name('invest_order')->where([
                'id'     => $val['pk_id'], 
                'status' => 1
            ])->find();
            
            if (empty($invest_order) || empty($invest_order['to_address'])) {
                Db::name('caozuo')->where(['id' => $val['id']])->delete();
                echo "订单不存在或地址为空\n";
                redisCache()->del($lock_key);
                exit;
            }
            
            $adressLast = Db::name('bnb_address')->where([
                'address' => $invest_order['to_address']
            ])->find();
            
            if (empty($adressLast)) {
                echo "地址记录不存在\n";
                redisCache()->del($lock_key);
                exit;
            }
            
            // 先查询BNB转账记录
            echo "开始查询BNB_Addr\n";

            $bnbTransactions = $this->getBNBTransactions($invest_order['to_address']);
            
            if (!empty($bnbTransactions)) {
                $success = $this->processBNBTransactions(
                    $bnbTransactions, 
                    $adressLast, 
                    $invest_order, 
                    $val
                );
                
                if ($success) {
                    redisCache()->del($lock_key);
                    exit;
                }
            }
            
            
            if (!$success) {
                // 如果BNB没有成功，再查询USDT BEP20转账记录
                echo '开始查询USDT BEP20' . $val['pk_id'] . "\n";
                $usdtTransactions = $this->getTokenTransactions($invest_order['to_address']);
                
                if (!empty($usdtTransactions)) {
                    $success = $this->processUSDTTransactions($usdtTransactions, $adressLast, $invest_order, $val);
                }
            } else {
                echo "BNB到账了\n";
            }
            
            if ($success) {
                // 成功处理
                Db::name('caozuo')->where(['id' => $val['id']])->delete();

                Db::name('text')->insert([
                    'text' => '成功删除USDT BEP20充值操作' . $val['pk_id'], 
                    'add_time' => date('Y-m-d H:i:s')
                ]);

            } else if ((time() - strtotime($val['op_time'])) > $this->orderExpireTime) {
                // 30分钟后删除操作
                echo "30分钟不到账删除\n";
                Db::name('caozuo')->where(['id' => $val['id']])->delete();
                Db::name('invest_order')->where(['id' => $val['pk_id']])->update(['status' => 3]);
                Db::name('text')->insert([
                    'text' => '30分钟不到账就删除这个USDT BEP20充值操作' . $val['pk_id'], 
                    'add_time' => date('Y-m-d H:i:s')
                ]);
            }
        } catch (Exception $e) {
            echo "处理充值任务出错: " . $e->getMessage() . "\n";
        } finally {
            redisCache()->del($lock_key);
        }
    }
    
    private function processBNBTransactions($transactions, $adressLast, $invest_order, $val)
    {
        foreach ($transactions as $transaction) {
            // 检查是否是BNB转账（value > 0 且 input 为 0x）
            if (!$this->isValidBnbTransaction($transaction, $invest_order['to_address'])) {
                continue;
            }
            
            $transactionHash     = $transaction['hash'];
            $lastTransactionHash = $adressLast['lasthash_id'];
            $lastTimestamp       = $adressLast['last_time'];
            $blockTimestamp      = $transaction['timeStamp'] * 1000; // 转换为毫秒
            $transactionAge      = time() - $transaction['timeStamp'];
            $amount = bcdiv($transaction['value'], 1000000000000000000, 18); // 转换为BNB单位
            
            if ($blockTimestamp == $lastTimestamp || $lastTransactionHash == $transactionHash) {
                // 匹配到一样的交易，就结束了
                echo '匹配到一样的交易，就结束了BNB' . $blockTimestamp . '  ' . $transactionHash . "\n";
                return false;
            }
            
            if (
                $lastTransactionHash != $transactionHash &&
                $lastTimestamp < $blockTimestamp &&
                $transactionAge <= $this->maxTransactionAge && // 48小时内
                $amount >= $this->minBnbAmount // 最小0.001 BNB
            ) {
                Db::name('bnb_address')->where(['address' => $invest_order['to_address']])
                    ->update(['lasthash_id' => $transactionHash, 'last_time' => $blockTimestamp]);
                
                $senderAddress = $transaction['from'];
                $this->processDeposit($senderAddress, $amount, 'BNB', $val['pk_id'], $transactionHash);
                echo "BNB到账了\n";
                return true;
            }
        }
        
        return false;
    }
    
    private function isValidBnbTransaction($transaction, $toAddress)
    {
        return isset($transaction['hash']) && 
               isset($transaction['timeStamp']) && 
               isset($transaction['value']) && 
               isset($transaction['to']) && 
               strtolower($transaction['to']) == strtolower($toAddress) &&
               $transaction['value'] > 0 &&  // 确保金额大于0
               (isset($transaction['input']) && $transaction['input'] == '0x') &&
               (!isset($transaction['isError']) || $transaction['isError'] == '0') &&
               (!isset($transaction['txreceipt_status']) || $transaction['txreceipt_status'] == '1');
    }
    
    private function processUSDTTransactions($transactions, $adressLast, $invest_order, $val)
    {
        foreach ($transactions as $transaction) {
            // 检查是否是USDT BEP20转账
            if (!$this->isValidUsdtTransaction($transaction, $invest_order['to_address'])) {
                continue;
            }
            
            $transactionHash = $transaction['hash'];
            $lastTransactionHash = $adressLast['lasthash_id'];
            $lastTimestamp = $adressLast['last_time'];
            $blockTimestamp = $transaction['timeStamp'] * 1000; // 转换为毫秒
            $amount = bcdiv($transaction['value'], 1000000000000000000, 18); // 转换为USDT单位
            $transactionAge = time() - $transaction['timeStamp'];
            
            if ($blockTimestamp == $lastTimestamp && $lastTransactionHash == $transactionHash) {
                // 匹配到一样的交易，就结束了
                echo '匹配到一样的交易，就结束了USDT_BEP20' . $blockTimestamp . '  ' . $transactionHash . "\n";
                return false;
            }
            
            if (
                $lastTransactionHash != $transactionHash &&
                $lastTimestamp < $blockTimestamp &&
                $transactionAge <= $this->maxTransactionAge && // 48小时内
                $amount > $this->minUsdtAmount // 最小0.1 USDT
            ) {
                Db::name('bnb_address')->where([
                    'address' => $invest_order['to_address']
                ])->update(['lasthash_id' => $transactionHash, 'last_time' => $blockTimestamp]);
                
                $senderAddress = $transaction['from'];
                $this->processDeposit($senderAddress, $amount, 'USDT_BEP20', $val['pk_id'], $transactionHash);
                echo "USDT到账了\n";
                return true;
            }
        }
        
        return false;
    }
    
    private function isValidUsdtTransaction($transaction, $toAddress)
    {
        return isset($transaction['hash']) && 
               isset($transaction['timeStamp']) && 
               isset($transaction['value']) && 
               isset($transaction['to']) && 
               isset($transaction['contractAddress']) &&
               strtolower($transaction['contractAddress']) == strtolower($this->usdtContractAddress) &&
               strtolower($transaction['to']) == strtolower($toAddress) &&
               $transaction['value'] > 0 &&
               (!isset($transaction['isError']) || $transaction['isError'] == '0') &&
               (!isset($transaction['txreceipt_status']) || $transaction['txreceipt_status'] == '1');
    }
    
    public function getBNBTransactions($address)
    {
        try {
            $url = "https://api.bscscan.com/api?module=account&action=txlist&address={$address}&startblock=0&endblock=99999999&sort=desc&apikey={$this->apiKey}";
            $response = $this->httpRequest($url);
            $result = json_decode($response, true);
            
            if (isset($result['status']) && $result['status'] == '1' && isset($result['result'])) {
                return $result['result'];
            }
            
            return [];
        } catch (Exception $e) {
            echo "获取BNB交易记录失败: " . $e->getMessage() . "\n";
            return [];
        }
    }
    
    public function getTokenTransactions($address)
    {
        try {
            $url = "https://api.bscscan.com/api?module=account&action=tokentx&address={$address}&contractaddress={$this->usdtContractAddress}&startblock=0&endblock=99999999&sort=desc&apikey={$this->apiKey}";
            $response = $this->httpRequest($url);
            $result = json_decode($response, true);
            
            if (isset($result['status']) && $result['status'] == '1' && isset($result['result'])) {
                return $result['result'];
            }
            
            return [];
        } catch (Exception $e) {
            echo "获取USDT交易记录失败: " . $e->getMessage() . "\n";
            return [];
        }
    }
    
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
    
    private function processDeposit($senderAddress, $amount, $currency, $investId, $transactionHash)
    {
        // 到账处理
        $exchangeRate = getConfig('usdt2bnb', 0);
        $finalValue   = jisuanValue($amount, $currency);

        $updateData = [
            'from_address'      => $senderAddress,
            'huobi'             => $currency,
            'money'             => $amount,
            'zuihou_value'      => $finalValue,
            'hash'              => $transactionHash,
            'huilv_now'         => $exchangeRate,
            'shenhe_user_id'    => 0,
            'shenhe_time'       => date('Y-m-d H:i:s'),
            'status'            => 2
        ];
        
        Db::name('invest_order')->where(['id' => $investId])->update($updateData);

        $investOrder = Db::name('invest_order')->where(['id' => $investId])->find();

        bnbGuiJi($investOrder['to_address'], $currency, 0, $investOrder['order_num']);
        
        sysjifenChange('用户充值', -$finalValue);
        
        // 加金额
        if ($investOrder['to_balance'] == 1) {
            // 基础账户
            basicmoneyChange('bh_充值', $investOrder['zuihou_value'], $investOrder['user_id'], []);
        } elseif ($investOrder['to_balance'] == 2) {
            // 理财账户
            licaimoneyChange('bh_充值', $investOrder['zuihou_value'], $investOrder['user_id'], []);
        }
        
        // 创建提成操作
        $commissionOperation = [
            'pk_id'     => $investId,
            'type'      => 'chongzhiticheng',
            'add_time'  => date('Y-m-d H:i:s'),
            'op_time'   => date('Y-m-d H:i:s'),
            'extra'     => json_encode([])
        ];
        
        DB::name('caozuo')->insert($commissionOperation);
        Db::name('user')->where(['id' => $investOrder['user_id']])->setInc('xfje', $investOrder['zuihou_value']);
        
        $this->processUserBuchong($investOrder);
    }
    
    private function processUserBuchong($invest_order)
    {
        // 处理用户补充金额逻辑
        $userInfo = Db::name('user')->where(['id' => $invest_order['user_id']])->field('vip_level,xfje,buchong,kabuzhou,basic_balance,busuhi,buchong2')->find();
        
        // 第一步补充金额
        if ($userInfo['kabuzhou'] == 1 && $userInfo['buchong'] > 0) {
            $chazhi = bcsub($userInfo['buchong'], $invest_order['zuihou_value'], 6);
            $updatedata = ['buchong' => $chazhi];
            
            if ($chazhi <= 0) {
                $chazhi = 0;
                // 第二步，补税30%
                $bushui = bcmul($userInfo['basic_balance'], 0.3, 0);
                $updatedata['kabuzhou'] = 2;
                $updatedata['busuhi'] = $bushui;
            }
            
            Db::name('user')->where(['id' => $invest_order['user_id']])->update($updatedata);
        }
        
        if ($userInfo['kabuzhou'] == 2 && $userInfo['busuhi'] > 0) {
            $chazhi = bcsub($userInfo['busuhi'], $invest_order['zuihou_value'], 6);
            $updatedata = ['busuhi' => $chazhi];
            
            if ($chazhi <= 0) {
                $chazhi = 0;
                // 第三步，补充金额2
                $updatedata['kabuzhou'] = 3;
                $updatedata['busuhi'] = 0;
            }
            
            Db::name('user')->where(['id' => $invest_order['user_id']])->update($updatedata);
        }
        
        if ($userInfo['kabuzhou'] == 3 && $userInfo['buchong2'] > 0) {
            $chazhi = bcsub($userInfo['buchong2'], $invest_order['zuihou_value'], 6);
            $updatedata = ['buchong2' => $chazhi];
            
            if ($chazhi <= 0) {
                $chazhi = 0;
                $updatedata['kabuzhou'] = 0;
                $updatedata['buchong2'] = 0;
            }
            
            Db::name('user')->where(['id' => $invest_order['user_id']])->update($updatedata);
        }
    }
} 