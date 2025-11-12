<?php

namespace app\cli\controller;

use app\cli\util\Ethereum;
use think\Controller;
use think\Db;
use think\Exception;

class CheckEthereumAddr extends Controller
{
    private $config = [
        'BNB' => [
            'decimals' => 18,
            'amount_min' => 0.0001,
            'usdt' => 'BEP20-USDT',
            'usdc' => 'BEP20-USDC'
        ],
        'ETH' => [
            'decimals' => 18,
            'amount_min' => 0.0001,
            'usdt' => 'ERC20-USDT',
            'usdc' => 'ERC20-USDC'
        ],
        'POL' => [
            'decimals' => 18,
            'amount_min' => 0.0001,
            'usdt' => 'POL-USDT',
            'usdc' => 'POL-USDC'
        ],
    ];
    private $lockExpireTime = 180; // 锁过期时间（秒）
    private $minAmountToken = 0.00001; // 最小USDT/USDC充值金额
    private $maxTransactionAge = 48 * 60 * 60; // 最大交易有效期（秒）
    private $orderExpireTime = 30 * 60; // 订单过期时间（秒）

    public function task()
    {
        $nowTime = date('Y-m-d H:i:s');
        echo "运行以太坊地址检查脚本-{$nowTime}<br>\n";
        $task = Db::name('caozuo')->where([
            'op_time' => ['elt', $nowTime],
            'type' => 'recharge_eth'
        ])->select();
        if (empty($task)) {
            exit("没有需要处理的充值任务<br>\n");
        }
        foreach ($task as $item) {
            $lockKey = getRedisXM('ethTask_' . $item['id']);
            $lock = redisCache()->setnx($lockKey, 1);
            if (!$lock) {
                echo "当前操作id：{$item['id']}<br>\n";
                redisCache()->expire($lockKey, $this->lockExpireTime);
                continue;
            }
            redisCache()->expire($lockKey, $this->lockExpireTime);
            $this->handleRechargeEth($item, $lockKey);
        }
    }

    public function test()
    {
        $symbol = input('get.symbol', '', 'trim');
        $amount = input('get.amount', '', 'trim');
        $tokenType = input('get.tokenType', '', 'trim');
        if (empty($symbol) || empty($amount)) {
            exit('关键参数不能为空');
        }
        if (!is_numeric($amount)) {
            exit('amount必须是数字');
        }
        if ($amount <= 0) {
            exit('amount必须大于0');
        }
        $privateKeyHex = '9c3c95516af4e1777eac101ae023c07258436b62d2fa076876b00ad672f031b5';
        $to = '0xd4b6f4c9af70c3287979228c34ec9c880847f608';
        $fromAddress = '0xd4b6f4c9af70c3287979228c34ec9c880847f608';
        if ($tokenType) {
            $res = Ethereum::getInstance()->sendToken($symbol, $privateKeyHex, $fromAddress, $to, $tokenType, $amount);
        } else {
            $res = Ethereum::getInstance()->sendNativeToken($symbol, $privateKeyHex, $fromAddress, $to, $amount);
        }
        exit(json_encode($res, JSON_UNESCAPED_UNICODE));
        // $amount = '0.0001';
        // $res = Ethereum::getInstance()->sendNativeToken($symbol, $privateKeyHex, $fromAddress, $to, $amount);
        // $res = Ethereum::getInstance()->sendToken($symbol, $privateKeyHex, $fromAddress, $to, 'usdt', $amount);
        // $res = Ethereum::getInstance()->sendTokenTest($privateKeyHex, $fromAddress, $to, $amount);
        // halt($res);
        // exit('test20250907');
    }

    private function handleRechargeEth($task, $lockKey)
    {
        try {
            $diffTime = round((time() - strtotime($task['op_time'])) / 60, 1);
            $fen = date('i');
            // 根据时间间隔控制执行频率
            // if ($diffTime >= 10 && $diffTime < 20) {
            //     // 10-20分钟每2分钟执行一次
            //     if ($fen % 2 != 0) {
            //         redisCache()->del($lockKey);
            //         exit("10-20分钟每2分钟执行一次<br>\n");
            //     }
            // } else if ($diffTime >= 20) {
            //     // 20分钟以后每3分钟执行一次
            //     if ($fen % 3 != 0) {
            //         redisCache()->del($lockKey);
            //         exit("20分钟以后3分钟执行一次<br>\n");
            //     }
            // }
            $order = Db::name('invest_order')->where(['id' => $task['pk_id'], 'status' => 1])->find();
            if (empty($order) || empty($order['to_address'])) {
                Db::name('caozuo')->where(['id' => $task['id']])->delete();
                redisCache()->del($lockKey);
                exit("订单不存在或地址为空<br>\n");
            }
            $addressInfo = Db::name('bnb_address')->where(['address' => $order['to_address']])->find();
            if (empty($addressInfo)) {
                redisCache()->del($lockKey);
                exit("地址记录不存在<br>\n");
            }
            $success = false;
            echo "开始查询-{$task['huobi']}<br>\n";
            //查询以太坊相关币转账记录
            $res = Ethereum::getInstance()->getTransactions($task['huobi'], $order['to_address']);
            //            var_dump(json_encode($res, JSON_UNESCAPED_UNICODE));
            if ($res['status'] == "1") {
                $success = $this->processTransactions($res['result'], $addressInfo, $order, $task);
            } else {
                echo "{$task['huobi']}-查询交易记录返回错误信息-" . ($res['message'] ?? '');
            }
            if (!$success) {
                echo "开始查询USDT-{$task['huobi']}<br>\n";
                $res = Ethereum::getInstance()->getTokenTransactions($task['huobi'], $order['to_address'], 'usdt');
                //                var_dump(json_encode($res, JSON_UNESCAPED_UNICODE));
                if ($res['status'] == "1") {
                    $success = $this->processTokenTransactions($res['result'], $addressInfo, $order, $task, 'usdt');
                } else {
                    echo "USDT-{$task['huobi']}-查询交易记录返回错误信息-" . ($res['message'] ?? '');
                }
            }
            if (!$success) {
                echo "开始查询USDC-{$task['huobi']}<br>\n";
                $res = Ethereum::getInstance()->getTokenTransactions($task['huobi'], $order['to_address'], 'usdc');
                //                var_dump(json_encode($res, JSON_UNESCAPED_UNICODE));
                if ($res['status'] == "1") {
                    $success = $this->processTokenTransactions($res['result'], $addressInfo, $order, $task, 'usdc');
                } else {
                    echo "USDC-{$task['huobi']}-查询交易记录返回错误信息-" . ($res['message'] ?? '') . "<br>\n";
                }
            }
            if ($success) {
                //处理成功
                Db::name('caozuo')->where(['id' => $task['id']])->delete();
                Db::name('text')->insert([
                    'text' => '成功删除' . $task['huobi'] . '充值操作' . $task['pk_id'],
                    'add_time' => date('Y-m-d H:i:s')
                ]);
                redisCache()->del($lockKey);
            } else if ((time() - strtotime($task['op_time'])) > $this->orderExpireTime) {
                // 30分钟后删除操作
                echo "30分钟不到账删除<br>\n";
                Db::name('caozuo')->where(['id' => $task['id']])->delete();
                Db::name('invest_order')->where(['id' => $task['pk_id']])->update(['status' => 3]);
                Db::name('text')->insert([
                    'text' => '30分钟不到账就删除-币种：' . $task['huobi'] . '充值操作-id：' . $task['pk_id'],
                    'add_time' => date('Y-m-d H:i:s')
                ]);
                redisCache()->del($lockKey);
            }
        } catch (Exception $e) {
            exit("处理充值认为出错：{$e->getMessage()},行：{$e->getLine()}<br>\n");
        } finally {
            redisCache()->del($lockKey);
        }
    }

    private function processTransactions($list, $addressInfo, $order, $task)
    {
        $decimal = $this->config[$task['huobi']]['decimals'];
        $minAmount = $this->config[$task['huobi']]['amount_min'];
        $isAdd = false;
        foreach ($list as $transaction) {
            if (
                isset($transaction['hash']) && isset($transaction['timeStamp']) &&
                isset($transaction['value']) && isset($transaction['to']) &&
                strtolower($transaction['to']) == strtolower($order['to_address']) &&
                (isset($transaction['input']) && $transaction['input'] == '0x') &&
                (!isset($transaction['isError']) || $transaction['isError'] == '0') &&
                (!isset($transaction['txreceipt_status']) || $transaction['txreceipt_status'] == '1')
            ) {
                $transactionAge = time() - $transaction['timeStamp'];
                // var_dump(json_encode($transaction));
                $blockTimestamp = $transaction['timeStamp'] * 1000; // 转换为毫秒
                $amount = bcdiv($transaction['value'], pow(10, $decimal), $decimal);
                if ($addressInfo['lasthash_id'] == $transaction['hash'] || $addressInfo['last_time'] == $blockTimestamp) {
                    echo $task['huobi'] . '-匹配到一样的交易，就结束了-' . $transaction['timeStamp'] . '-' . $transaction['hash'] . "<br>\n";
                    break;
                }
                if (
                    $addressInfo['lasthash_id'] != $transaction['hash'] &&
                    $addressInfo['last_time'] < $blockTimestamp &&
                    $transactionAge <= $this->maxTransactionAge && // 48小时内
                    $amount >= $minAmount // 最小0.001 BNB
                ) {
                    $isAdd = true;
                    Db::name('bnb_address')
                        ->where(['address' => $order['to_address']])
                        ->update(['lasthash_id' => $transaction['hash'], 'last_time' => $blockTimestamp]);

                    $this->processDeposit($transaction['from'], $amount, $task['huobi'], $task['pk_id'], $transaction['hash']);
                    echo "{$task['huobi']}到账了<br>\n";
                    break;
                }
            }
        }
        return $isAdd;
    }

    private function processTokenTransactions($list, $addressInfo, $order, $task, $tokenSymbol)
    {
        $contractAddress = Ethereum::getInstance()->getContractInfo($task['huobi'])[$tokenSymbol] ?? '';
        $symbolToken = $this->config[$task['huobi']][$tokenSymbol];
        echo "{$tokenSymbol}-{$task['huobi']}合约地址：{$contractAddress}<br>\n";
        $isAdd = false;
        foreach ($list as $transaction) {
            if (
                isset($transaction['hash']) &&
                isset($transaction['timeStamp']) &&
                isset($transaction['value']) &&
                isset($transaction['to']) &&
                isset($transaction['contractAddress']) &&
                strtolower($transaction['contractAddress']) == strtolower($contractAddress) &&
                strtolower($transaction['to']) == strtolower($order['to_address']) &&
                $transaction['value'] > 0
            ) {
                $amount = bcdiv($transaction['value'], pow(10, $transaction['tokenDecimal']), $transaction['tokenDecimal']); // 转换为USDT单位
                $transactionAge = time() - $transaction['timeStamp'];
                $blockTimestamp = $transaction['timeStamp'] * 1000; // 转换为毫秒
                if ($addressInfo['lasthash_id'] == $transaction['hash']) {
                    // 匹配到一样的交易，就结束了
                    echo '匹配到一样的交易，就结束了-' . $transaction['timeStamp'] . '-' . $transaction['hash'] . "<br>\n";
                    break;
                }
                echo "到账金额：{$amount}<br>\n";
                if (
                    $addressInfo['lasthash_id'] != $transaction['hash'] &&
                    $addressInfo['last_time'] < $blockTimestamp &&
                    $transactionAge <= $this->maxTransactionAge && // 48小时内
                    $amount > $this->minAmountToken // 最小0.1 USDT
                ) {
                    $isAdd = true;
                    Db::name('bnb_address')->where(['address' => $order['to_address']])
                        ->update([
                            'lasthash_id' => $transaction['hash'],
                            'last_time' => $blockTimestamp,
                        ]);

                    $this->processDeposit($transaction['from'], $amount, $symbolToken, $task['pk_id'], $transaction['hash']);
                    echo "USDT到账了<br>\n";
                    break;
                }
            }
        }
        return $isAdd;
    }

    private function processDeposit($senderAddress, $amount, $symbol, $investId, $transactionHash)
    {
        echo "到账处理-{$senderAddress}-{$amount}-{$symbol}-{$investId}-{$transactionHash}<br>\n";
        // return;
        // 到账处理
        $exchangeRate = 0.001;
        if ($symbol == 'BNB') {
            $exchangeRate = getConfig('usdt2bnb', 0) ?? 0.001;
        } elseif ($symbol == 'ETH') {
            $exchangeRate = getConfig('usdt2eth', 0) ?? 0.001;
        } elseif ($symbol == 'POL') {
            $exchangeRate = getConfig('usdt2pol', 0) ?? 0.001;
        }
        $finalValue = calculateCurrencyValue($amount, $symbol);
        $updateData = [
            'from_address' => $senderAddress,
            'huobi' => $symbol,
            'money' => $amount,
            'zuihou_value' => $finalValue,
            'hash' => $transactionHash,
            'huilv_now' => $exchangeRate,
            'shenhe_user_id' => 0,
            'shenhe_time' => date('Y-m-d H:i:s'),
            'status' => 2
        ];

        Db::name('invest_order')->where(['id' => $investId])->update($updateData);
        $investOrder = Db::name('invest_order')->where(['id' => $investId])->find();
        //设置归集任务
        $taskTemp = [
            'pk_id' => $investOrder['id'],
            'type' => 'clollectEthereum',
            'add_time' => date('Y-m-d H:i:s'),
            'op_time' => date('Y-m-d H:i:s', (time() + 60)), //归集全部推迟1分半钟，因为用API获取余额由延迟
            'qianbao' => $investOrder['to_address'],
            'huobi' => $symbol
        ];
        DB::name('caozuo')->insert($taskTemp);

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
            'pk_id' => $investId,
            'type' => 'chongzhiticheng',
            'add_time' => date('Y-m-d H:i:s'),
            'op_time' => date('Y-m-d H:i:s'),
            'extra' => json_encode([])
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
