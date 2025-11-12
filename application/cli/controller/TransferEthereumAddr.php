<?php

namespace app\cli\controller;

use app\cli\util\Ethereum;
use think\Controller;
use think\Db;

class TransferEthereumAddr extends Controller
{
    private $systemEthAddress;
    private $systemEthKey;
    private $collectionAddress; // 归集账户地址
    private $lockExpireTime = 15; // 锁过期时间(秒)
    private $withdrawSymbol = ['ETH', 'BNB', 'POL', 'BEP20-USDT', 'BEP20-USDC', 'ERC20-USDT', 'ERC20-USDC', 'POL-USDT', 'POL-USDC']; //提现币种
    private $config = [
        'ETH' => [
            'amount_min' => '0.000001', //最小归集金额
            'decimals' => 18, //小数位数
            'symbol' => 'ETH',
            'tokenType' => '',
        ],
        'BNB' => [
            'amount_min' => '0.000001',
            'decimals' => 18,
            'symbol' => 'BNB',
            'tokenType' => '',
        ],
        'POL' => [
            'amount_min' => '0.000001',
            'decimals' => 18,
            'symbol' => 'POL',
            'tokenType' => '',
        ],
        'BEP20-USDT' => [
            'amount_min' => '0.001',
            'decimals' => 18,
            'tokenType' => 'usdt',
            'symbol' => 'BNB',
            'fee' => '0.000003',
        ],
        'BEP20-USDC' => [
            'amount_min' => '0.001',
            'decimals' => 18,
            'tokenType' => 'usdc',
            'symbol' => 'BNB',
            'fee' => '0.000003',
        ],
        'ERC20-USDT' => [
            'amount_min' => '0.001',
            'decimals' => 6,
            'tokenType' => 'usdt',
            'symbol' => 'ETH',
            'fee' => '0.00003',
        ],
        'ERC20-USDC' => [
            'amount_min' => '0.001',
            'decimals' => 6,
            'tokenType' => 'usdc',
            'symbol' => 'ETH',
            'fee' => '0.00003',
        ],
        'POL-USDT' => [
            'amount_min' => '0.001',
            'decimals' => 6,
            'tokenType' => 'usdt',
            'symbol' => 'POL',
            'fee' => '0.002',
        ],
        'POL-USDC' => [
            'amount_min' => '0.001',
            'decimals' => 6,
            'tokenType' => 'usdc',
            'symbol' => 'POL',
            'fee' => '0.002',
        ],
    ]; //配置信息

    public function __construct()
    {
        parent::__construct();
        // $this->collectionAddress = getConfig('collect_addr', 0)??';
        $this->collectionAddress = '0xd4b6f4c9af70c3287979228c34ec9c880847f608';
        // 获取系统以太坊账户信息
        $this->systemEthAddress = getConfig('tx_bnb_address', '');
        $this->systemEthKey     = getConfig('tx_bnb_key', '');
    }

    public function index()
    {
        echo "开始以太坊地址归集任务<br>\n";
        $now   = date('Y-m-d H:i:s');
        $list = Db::name('caozuo')->where([
            'type' => 'clollectEthereum',
            'op_time' => ['elt', $now]
        ])->limit(10)->select();
        if (empty($list)) {
            exit("没有需要处理的归集任务<br>\n");
        }
        foreach ($list as $item) {
            $lock_key = getRedisXM('clollectEthereum' . $item['id']);
            $is_lock = redisCache()->setnx($lock_key, 1);
            if (!$is_lock) {
                echo '当前任务操作中:' . $item['id'] . "<br>\n";
                redisCache()->expire($lock_key, $this->lockExpireTime);
                continue;
            }
            redisCache()->expire($lock_key, $this->lockExpireTime);
            try {
                $this->processCollectionTask($item);
            } catch (\Exception $e) {
                echo "归集出错: " . $e->getMessage() . "<br>\n";
            } finally {
                redisCache()->del($lock_key);
            }
        }
        echo "以太坊地址归集任务结束<br>\n";
    }

    /**
     * 处理单个归集任务
     * @param array $task 任务数据
     */
    private function processCollectionTask($task)
    {
        echo "开始归集地址:" . $task['qianbao'] . "\n";
        $arr = explode('-', $task['huobi']);
        $config = $this->config[$task['huobi']];

        $res = Ethereum::getInstance()->getBalance($config['symbol'], $task['qianbao']);
        if ($res['status'] == '0') {
            echo "{$task['huobi']}余额获取失败-{$res['message']}<br>\n";
            Db::name('caozuo')->where(['id' => $task['id']])->delete();
            return;
        } else {
            $mainAmount = bcdiv($res['result'], 10 ** 18, 18);
            echo "{$config['symbol']}余额-{$mainAmount}<br>\n";
        }

        $isDel = false;
        if (count($arr) == 1) {
            echo "归集主币-{$task['huobi']}<br>\n";
            if ($mainAmount > $config['amount_min']) {
                $transferAmount = bcsub($mainAmount, $config['amount_min'], $config['decimals']);
                $isDel = $this->transferForCollection($task, $task['huobi'], $transferAmount);
            } else {
                echo "{$task['huobi']}余额小于最小归集金额-{$mainAmount}<br>\n";
                $isDel = true;
            }
        } else {
            echo "归集代币-{$task['huobi']}<br>\n";
            $res = Ethereum::getInstance()->getTokenBalance($config['symbol'], $task['qianbao'], $config['tokenType']);
            if ($res['status'] == '0') {
                echo "{$task['huobi']}余额获取失败-{$res['message']}<br>\n";
                $isDel = true;
            } else {
                $balance = $res['result'];
                echo "{$task['huobi']}余额-{$balance}<br>\n";
                $amount = bcdiv($balance, 10 ** $config['decimals'], $config['decimals']);
                if ($mainAmount > $config['fee'] && $amount > $config['amount_min']) {
                    $isDel = $this->transferForCollection($task, $config['symbol'], $amount, $config['tokenType']);
                } else {
                    echo "{$task['huobi']}余额小于最小归集金额-{$amount}，主币余额：{$mainAmount}<br>\n";
                    $isDel = true;
                }
            }
        }
        if ($isDel) {
            Db::name('caozuo')->where(['id' => $task['id']])->delete();
        }
    }

    /**
     * 归集转账
     * @param array $task 任务数据
     * @param string $symbol 主币
     * @param float $amount 金额
     * @param string $tokenType 代币符号
     */
    private function transferForCollection($task, $symbol, $amount, $tokenType = '')
    {
        $toAddress = $this->collectionAddress;
        $addressInfo = Db::name('bnb_address')->where(['address' => $task['qianbao']])->find();
        // 查找地址信息
        if (empty($addressInfo)) {
            echo "找不到地址信息: " . $task['qianbao'] . "<br>\n";
            return false;
        }
        // 检查是否有私钥
        if (empty($addressInfo['privateKey'])) {
            echo "地址没有私钥信息: " . $task['qianbao'] . "<br>\n";
            return false;
        }

        // 记录归集记录
        $collectionRecord = Db::name('bnb_guiji_record')->where([
            'from_address' => $task['qianbao'],
            'to_address'   => $toAddress,
            'huobi'        => $task['huobi'],
            'status'       => 0,
            'invest_id'    => $task['pk_id']
        ])->find();

        if (empty($collectionRecord)) {
            $insertData = [
                'from_address' => $task['qianbao'],
                'to_address'   => $toAddress,
                'add_time'     => date('Y-m-d H:i:s'),
                'huobi'        => $task['huobi'],
                'money'        => $amount,
                'status'       => 0,
                'invest_id'    => $task['pk_id']
            ];
            Db::name('bnb_guiji_record')->insert($insertData);
            $recordId = Db::name('bnb_guiji_record')->getLastInsID();
            echo "创建归集记录ID: " . $recordId . "<br>\n";
        } else {
            $recordId = $collectionRecord['id'];
            echo "使用已有归集记录ID: " . $recordId . "<br>\n";
        }
        //开始转账处理
        // 获取私钥（去除可能的0x前缀）
        $privateKey = $addressInfo['privateKey'];

        if (substr($privateKey, 0, 2) === '0x') {
            $privateKey = substr($privateKey, 2);
        }
        if (empty($tokenType)) {
            $res = Ethereum::getInstance()->sendNativeToken($symbol, $privateKey, $task['qianbao'], $toAddress, $amount, 1);
        } else {
            $res = Ethereum::getInstance()->sendToken($symbol, $privateKey, $task['qianbao'], $toAddress, $tokenType, $amount);
        }
        $updateData = [
            'money' => $amount,
            'detail' => json_encode($res, JSON_UNESCAPED_UNICODE),
        ];
        $result = false;
        if ($res['status'] == '1') {
            echo "归集交易成功: " . $task['qianbao'] . " -> " . $toAddress . ",数量：" . $amount . ",币种：" . $task['huobi'] . ",交易哈希：" . $res['result'] . "<br>\n";
            $updateData['status'] = 1;
            $result = true;
        } else {
            echo "归集交易失败: " . ($res['message'] ?? '') . "<br>\n";
        }
        Db::name('bnb_guiji_record')->where(['id' => $recordId])->update($updateData);
        return $result;
    }

    /**
     * 提现任务
     */
    public function withdraw()
    {
        echo "开始以太坊地址提现任务<br>\n";
        $now = date('Y-m-d H:i:s');
        $taskList = Db::name('caozuo')->where([
            'op_time' => ['elt', $now],
            'type' => 'autoWithdrawEthereum'
        ])->limit(10)->select();
        if (empty($taskList)) {
            echo "没有需要处理的以太坊币提现任务<br>\n";
            exit;
        }
        foreach ($taskList as $task) {
            $lock_key = getRedisXM('withdrawEthereum' . $task['id']);
            $is_lock = redisCache()->setnx($lock_key, 1);
            if (!$is_lock) {
                echo '当前任务操作中:' . $task['id'] . "<br>\n";
                redisCache()->expire($lock_key, $this->lockExpireTime);
                continue;
            }
            redisCache()->expire($lock_key, $this->lockExpireTime);
            $this->processWithdrawTask($task, $lock_key);
        }
    }

    private function processWithdrawTask($task, $lock_key)
    {
        try {
            echo "处理以太坊提现任务ID: " . $task['id'] . "<br>\n";

            // 获取提现订单信息
            $withdraw = Db::name('draw_order')->where(['id' => $task['pk_id'], 'status' => 1])->find();
            if (empty($withdraw)) {
                echo "找不到提现订单或状态不正确: " . $task['pk_id'] . "<br>\n";
                Db::name('caozuo')->where(['id' => $task['id']])->delete();
                redisCache()->del($lock_key);
                return;
            }
            if (!in_array($withdraw['huobi'], $this->withdrawSymbol)) {
                echo "不支持的提现类型: " . $withdraw['huobi'] . "<br>\n";
                Db::name('caozuo')->where(['id' => $task['id']])->delete();
                redisCache()->del($lock_key);
                return;
            }
            if (empty($withdraw['to_address'])) {
                echo "提现地址为空: " . json_encode($withdraw, JSON_UNESCAPED_UNICODE) . "<br>\n";
                Db::name('caozuo')->where(['id' => $task['id']])->delete();
                redisCache()->del($lock_key);
                return;
            }
            // 获取配置信息
            $config = $this->config[$withdraw['huobi']];
            // 获取提现账户信息
            $withdrawAddress = $this->systemEthAddress;
            $withdrawKey = $this->systemEthKey;
            if (empty($withdrawAddress) || empty($withdrawKey)) {
                echo $withdraw['huobi'] . "提现账户信息未配置: " . $withdrawAddress . "===" . $withdrawKey . "<br>\n";
                redisCache()->del($lock_key);
                return;
            }
            $symbol = $config['symbol'];
            //获取系统钱包余额
            $res = Ethereum::getInstance()->getBalance($symbol, $withdrawAddress);
            if ($res['status'] == '0') {
                echo "获取系统钱包-{$symbol}-余额失败: " . $res['message'] . "<br>\n";
                redisCache()->del($lock_key);
                return;
            } else {
                $systemBalance = bcdiv($res['result'], 10 ** 18, 18);
                echo "系统钱包-{$symbol}-余额: " . $systemBalance . "<br>\n";
            }

            // 执行转账
            $tokenType = $config['tokenType'];
            if (empty($tokenType)) {
                if ($systemBalance < $withdraw['daozhang']) {
                    $res = ['status' => '0', 'message' => '系统钱包余额不足:' . $systemBalance . '，提现金额:' . $withdraw['daozhang']];
                } else {
                    $res = Ethereum::getInstance()->sendNativeToken($symbol, $withdrawKey, $withdrawAddress, $withdraw['to_address'], $withdraw['daozhang'], 2);
                }
            } else {
                $res = Ethereum::getInstance()->getTokenBalance($symbol, $withdrawAddress, $tokenType);
                if ($res['status'] == '0') {
                    echo "获取系统钱包-{$symbol}-代币余额失败: " . $res['message'] . "<br>\n";
                    redisCache()->del($lock_key);
                    return;
                } else {
                    $tokenBalance = bcdiv($res['result'], 10 ** $config['decimals'], $config['decimals']);
                    echo "系统钱包-{$symbol}-代币余额: " . $tokenBalance . "<br>\n";
                }
                if ($tokenBalance < $withdraw['daozhang']) {
                    $res = ['status' => '0', 'message' => '系统钱包代币余额不足:' . $tokenBalance . '，提现金额:' . $withdraw['daozhang']];
                } else {
                    if ($systemBalance < $config['fee']) {
                        $res = ['status' => '0', 'message' => '系统钱包余额不足:' . $systemBalance . '，提现所需预估手续费:' . $config['fee']];
                    } else {
                        $res = Ethereum::getInstance()->sendToken($symbol, $withdrawKey, $withdrawAddress, $withdraw['to_address'], $tokenType, $withdraw['daozhang']);
                    }
                }
            }
            $updateData = ['tranfer_detail' => json_encode($res, JSON_UNESCAPED_UNICODE)];
            if ($res['status'] == '1') {
                // 更新提现订单状态
                $updateData = [
                    'status' => 2,
                    'shenhe_user_id' => 0,
                    'shenhe_time' => date('Y-m-d H:i:s'),
                    'hash' => $res['result']
                ];
                Db::name('caozuo')->where(['id' => $task['id']])->delete();
                // 处理积分变更
                $this->tixianjiajifen($withdraw['user_id'], $withdraw['zuihou_value']);
                echo $withdraw['huobi'] . "提现成功,交易哈希: " . $res['result'] . "<br>\n";
            } else {
                echo $withdraw['huobi'] . "提现失败: " . $res['message'] . "<br>\n";
                Db::name('caozuo')->where(['id' => $task['id']])->update(['op_time' => date('Y-m-d H:i:s', time() + 300)]);
                redisCache()->del($lock_key);
            }
            Db::name('draw_order')->where(['id' => $withdraw['id']])->update($updateData);
        } catch (\Exception $e) {
            echo "提现任务出错: " . $e->getMessage() . "<br>\n";
        }
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
}
