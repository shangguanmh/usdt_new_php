<?php

namespace app\cli\util;

use Web3\Web3;
use Web3\Contract;
use Blockchainethdev\EthereumTx\Transaction;

class Ethereum
{
    //网络
    private $network = [
        "ETH" => [
            'chain_id' => 1,
            'rpc' => 'https://go.getblock.us/0d77570fda7d4f86a50d3a3faa6ed01a',
            'chain_name' => 'Ethereum Mainnet',
            'decimals' => 18,
        ],
        "BNB" => [
            'chain_id' => 56,
            'rpc' => 'https://go.getblock.us/d9b2ebbb5dff48df91aa143bb4388307',
            'chain_name' => 'BNB Smart Chain Mainnet',
            'decimals' => 18,
        ],
        "POL" => [
            'chain_id' => 137,
            'rpc' => 'https://polygon-rpc.com',
            'chain_name' => 'Polygon Mainnet',
            'decimals' => 18,
        ],
    ];
    //代币合约配置
    private $contracts = [
        'ETH' => [
            'usdt' => '0xd4b6f4c9af70c3287979228c34ec9c880847f608',
            'usdc' => '0xd4b6f4c9af70c3287979228c34ec9c880847f608',
        ], // Ethereum
        'BNB' => [
            'usdt' => '0xd4b6f4c9af70c3287979228c34ec9c880847f608',
            'usdc' => '0xd4b6f4c9af70c3287979228c34ec9c880847f608',
        ], // BSC
        'POL' => [
            'usdt' => '0xd4b6f4c9af70c3287979228c34ec9c880847f608',
            'usdc' => '0xd4b6f4c9af70c3287979228c34ec9c880847f608',
        ], // Polygon
    ];
    //代币ABI
    private $tokenABI = '[
        {
            "constant": true,
            "inputs": [],
            "name": "name",
            "outputs": [{"name": "", "type": "string"}],
            "type": "function"
        },
        {
            "constant": true,
            "inputs": [],
            "name": "symbol",
            "outputs": [{"name": "", "type": "string"}],
            "type": "function"
        },
        {
            "constant": true,
            "inputs": [],
            "name": "decimals",
            "outputs": [{"name": "", "type": "uint8"}],
            "type": "function"
        },
        {
            "constant": true,
            "inputs": [{"name": "_owner", "type": "address"}],
            "name": "balanceOf",
            "outputs": [{"name": "balance", "type": "uint256"}],
            "type": "function"
        },
        {
            "constant": false,
            "inputs": [{"name": "_to", "type": "address"}, {"name": "_value", "type": "uint256"}],
            "name": "transfer",
            "outputs": [{"name": "", "type": "bool"}],
            "type": "function"
        },
        {
            "constant": false,
            "inputs": [{"name": "_spender", "type": "address"}, {"name": "_value", "type": "uint256"}],
            "name": "approve",
            "outputs": [{"name": "", "type": "bool"}],
            "type": "function"
        },
        {
            "constant": true,
            "inputs": [{"name": "_owner", "type": "address"}, {"name": "_spender", "type": "address"}],
            "name": "allowance",
            "outputs": [{"name": "", "type": "uint256"}],
            "type": "function"
        }
    ]';    //api接口地址
    private $baseUrl = 'https://api.etherscan.io/v2/api';
    private $apiKey = '2JMRVVG12Z9PM7ARYPCAEB9GGAJUM5HUV2'; //api密钥

    private static $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getContractInfo($symbol): array
    {
        return $this->contracts[$symbol] ?? [];
    }

    //统一接口请求
    private function httpRequest(array $params)
    {
        $params['apikey'] = $this->apiKey;
        try {
            $url = $this->baseUrl . '?' . http_build_query($params);
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Blockchain Explorer)',
                CURLOPT_HTTPHEADER => ['Content-Type: application/json']
            ]);
            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);
            if ($error) {
                return ['status' => '0', 'message' => '接口出错-' . $error];
            }
            return json_decode($response, true);
        } catch (\Exception $e) {
            return ['status' => '0', 'message' => '接口异常-' . $e->getMessage()];
        }
    }

    /**
     * 查询账户余额
     * @param string $symbol
     * @param string $address 查询地址
     * @return array
     */
    public function getBalance(string $symbol, string $address): array
    {
        $network = $this->network[$symbol] ?? [];
        if (empty($network)) {
            return ['status' => '0', 'message' => '未找到对应网络数据'];
        }
        $params = [
            'chainid' => $network['chain_id'],
            'module' => 'account',
            'action' => 'balance',
            'address' => $address,
            'tag' => 'latest',
        ];
        return $this->httpRequest($params);
    }

    /**
     * 获取交易列表（支持所有链）
     * @param string $symbol 币种代码
     * @param string $address 地址
     * @param int $page 页码
     * @param int $offset 每页显示数量
     */
    public function getTransactions(string $symbol, string $address, int $page = 1, int $offset = 20): array
    {
        $network = $this->network[$symbol] ?? [];
        if (empty($network)) {
            return ['status' => '0', 'message' => '未找到对应网络数据'];
        }
        $params = [
            'chainid' => $network['chain_id'],
            'module' => 'account',
            'action' => 'txlist',
            'address' => $address,
            'startblock' => 0,
            'endblock' => 99999999,
            'page' => $page,
            'offset' => $offset,
            'sort' => 'desc',
        ];
        return $this->httpRequest($params);
    }

    /**
     * 获取代币余额
     */
    public function getTokenBalance(string $symbol, string $address, string $tokenSymbol): array
    {
        $network = $this->network[$symbol] ?? [];
        if (empty($network)) {
            return ['status' => '0', 'message' => '未找到对应网络数据'];
        }
        // $tokenSymbol = strtolower($tokenSymbol);
        $contractAddress = $this->contracts[$symbol][$tokenSymbol] ?? '';
        if (empty($contractAddress)) {
            return ['status' => '0', 'message' => '未找到对应合约数据'];
        }
        $params = [
            'chainid' => $network['chain_id'],
            'module' => 'account',
            'action' => 'tokenbalance',
            'contractaddress' => $contractAddress,
            'address' => $address,
            'tag' => 'latest',
        ];
        return $this->httpRequest($params);
    }

    /**
     * 获取代币交易记录
     */
    public function getTokenTransactions(string $symbol, string $address, string $tokenSymbol, int $page = 1, int $offset = 20): array
    {
        $network = $this->network[$symbol] ?? [];
        if (empty($network)) {
            return ['status' => '0', 'message' => '未找到对应网络数据'];
        }
        // $tokenSymbol = strtolower($tokenSymbol);
        $contractAddress = $this->contracts[$symbol][$tokenSymbol] ?? '';
        if (empty($contractAddress)) {
            return ['status' => '0', 'message' => '未找到对应合约数据'];
        }
        $params = [
            'chainid' => $network['chain_id'],
            'module' => 'account',
            'action' => 'tokentx',
            'contractaddress' => $contractAddress,
            'address' => $address,
            'page' => $page,
            'offset' => $offset,
            'sort' => 'desc',
            'startblock' => 0,
            'endblock' => 99999999,
        ];
        return $this->httpRequest($params);
    }

    /**
     * 获取Gas价格（支持所有链）
     */
    public function getGasPrice(string $symbol): array
    {
        $network = $this->network[$symbol] ?? [];
        if (empty($network)) {
            return ['status' => '0', 'message' => '未找到对应网络数据'];
        }
        $params = [
            'chainid' => $network['chain_id'],
            'module' => 'gastracker',
            'action' => 'gasoracle',
        ];
        return $this->httpRequest($params);
    }

    /**
     * 发送原生币转账交易
     * @param string $symbol 币代码
     * @param string $privateKeyHex 发送方私钥（十六进制字符串，不带0x前缀）
     * @param string $fromAddress 发送方地址
     * @param string $toAddress 接收方地址
     * @param string $amount 要转账的金额
     * @param int $type 1:转账主币，2:提现
     * @return array
     */
    public function sendNativeToken(string $symbol, string $privateKeyHex, string $fromAddress, string $toAddress, $amount, $type = 1): array
    {
        if (empty($symbol) || empty($privateKeyHex) || empty($fromAddress) || empty($toAddress) || $amount < 0) {
            return ['status' => '0', 'message' => '参数错误'];
        }
        $network = $this->network[$symbol] ?? [];
        if (empty($network)) {
            return ['status' => '0', 'message' => '未找到对应网络数据'];
        }
        $amountWei = bcmul($amount, 10 ** $network['decimals'], 0);
        try {
            $client = new Web3($network['rpc']);
            $eth = $client->eth;
            $nonce = '';
            $eth->getTransactionCount($fromAddress, 'latest', function ($err, $result) use (&$nonce) {
                if ($err !== null) {
                    throw new \Exception("获取 nonce 失败: " . $err->getMessage());
                }
                $nonce = $result->toString();
            });
            // 估算Gas价格
            $gasPrice = '';
            $eth->gasPrice(function ($err, $result) use (&$gasPrice) {
                if ($err !== null) {
                    throw new \Exception("获取gasPrice失败: " . $err->getMessage());
                }
                $gasPrice = $result->toString();
            });
            // 估算Gas Limit，对于简单转账，可以设置为21000
            $gasLimit = '21000';
            //计算手续费
            $feeInWei = bcmul($gasPrice, $gasLimit, 0);
            if ($type == 1) {
                if ($amountWei < $feeInWei) {
                    return ['status' => '0', 'message' => '转账主币手续费不足'];
                }
                $amountWei = bcsub($amountWei, $feeInWei, 0);
            }
            // 构建交易参数
            $transactionParams = [
                'nonce' => '0x' . dechex($nonce),
                'from' => $fromAddress,
                'to' => $toAddress,
                'value' => '0x' . dechex($amountWei), // 确保金额是十六进制格式
                'gasPrice' => '0x' . dechex($gasPrice),
                'gasLimit' => '0x' . dechex($gasLimit),
                'chainId' => $network['chain_id'] // 根据目标链设置Chain ID：ETH主网=1, BSC主网=56, Polygon主网=137
            ];
            // 5. 使用 blockchainethdev/ethereum-tx 或其他库签名交易
            $transaction = new Transaction($transactionParams);
            $signedTx = '0x' . $transaction->sign($privateKeyHex);
            // 6. 发送已签名的原始交易
            $txHash = '';
            $eth->sendRawTransaction($signedTx, function ($err, $result) use (&$txHash) {
                if ($err !== null) {
                    throw new \Exception("发送交易失败: " . $err->getMessage());
                }
                $txHash = $result;
            });
            return ['status' => 1, 'message' => '', 'result' => $txHash, 'fee' => $feeInWei];
        } catch (\Exception $e) {
            return ['status' => '0', 'message' => $e->getMessage() . ',行：' . $e->getLine() . ',转账金额:' . $amount];
        }
    }

    /**
     * 发送代币转账交易
     * @param string $symbol 币代码
     * @param string $privateKeyHex 发送方私钥（十六进制字符串，不带0x前缀）
     * @param string $fromAddress 发送方地址
     * @param string $toAddress 接收方地址
     * @param string $tokenSymbol 代币符号
     * @param string $amount 要转账的金额
     * @return array
     */
    public function sendToken(string $symbol, string $privateKeyHex, string $fromAddress, string $toAddress, string $tokenSymbol, $amount): array
    {
        if (empty($symbol) || empty($privateKeyHex) || empty($fromAddress) || empty($toAddress) || $amount < 0 || empty($tokenSymbol)) {
            return ['status' => '0', 'message' => '参数错误'];
        }
        $network = $this->network[$symbol] ?? [];
        if (empty($network)) {
            return ['status' => '0', 'message' => '未找到对应网络数据'];
        }
        $amountWei = bcmul($amount, 10 ** $network['decimals'], 0);
        try {
            $client = new Web3($network['rpc']);
            $eth = $client->eth;
            $tokenAddr = $this->contracts[$symbol][$tokenSymbol];
            if (empty($tokenAddr)) {
                return ['status' => '0', 'message' => '未找到代币合约地址'];
            }
            $contract = new Contract($client->provider, $this->tokenABI);
            $contract->at($tokenAddr);
            //检查余额
            $balance = '';
            $contract->call('balanceOf', $fromAddress, function ($err, $result) use (&$balance) {
                if ($err !== null) {
                    throw new \Exception("获取余额失败: " . $err->getMessage());
                }
                $balance = $result['balance']->toString();
            });
            // 2. 检查授权（如果需要）
            // 注意：只有当发送者不是代币所有者时才需要检查授权
            // $allowance = '';
            // $contract->call('allowance', $fromAddress, $fromAddress, function ($err, $result) use (&$allowance) {
            //     if ($err !== null) {
            //         // 有些代币可能没有allowance方法，忽略错误
            //         $allowance = PHP_INT_MAX;
            //     } else {
            //         if (is_array($result)) {
            //             $allowance = $result[0]->toString();
            //         } else {
            //             $allowance = $result->toString();
            //         }
            //     }
            // });

            // if (bccomp($allowance, $amountWei) < 0) {
            //     // 构建approve函数调用数据
            //     $data = $contract->getData('approve', $toAddress, $amountWei);
            //     // return ['status' => '0', 'message' => '授权额度不足，请先授权合约'];
            // } else {
            //     // 构建 transfer 函数调用数据
            //     $data = $contract->getData('transfer', $toAddress, $amountWei);
            // }
            $data = $contract->getData('transfer', $toAddress, $amountWei);
            // exit($allowance);
            $nonce = '';
            $eth->getTransactionCount($fromAddress, 'pending', function ($err, $result) use (&$nonce) {
                if ($err !== null) {
                    throw new \Exception("获取 nonce 失败: " . $err->getMessage());
                }
                $nonce = $result->toString();
            });

            // 估算Gas价格
            $gasPrice = '';
            $eth->gasPrice(function ($err, $result) use (&$gasPrice) {
                if ($err !== null) {
                    throw new \Exception("获取gasPrice失败: " . $err->getMessage());
                }
                $gasPrice = $result->toString();
            });
            // 估算Gas Limit，对于简单转账，可以设置为21000
            $gasLimit = '60000';
            $estimateGas = [
                'from' =>  $fromAddress,
                'to' =>  $tokenAddr, // 注意这里是代币合约地址
                'data' => '0x' . $data
            ];
            // 尝试估算 Gas（可选，取决于你的节点是否支持）
            $eth->estimateGas($estimateGas, function ($err, $result) use (&$gasLimit) {
                if ($err !== null) {
                    throw new \Exception("获取gasLimit失败: " . $err->getMessage());
                } else {
                    $gasLimit = $result->toString();
                }
            });
            // 增加20%的缓冲
            $gasLimit = bcmul($gasLimit, '1.2', 0);
            $feeInWei = bcmul($gasPrice, $gasLimit, 0);
            // 构建交易参数
            $transactionParams = [
                'nonce' => '0x' . dechex($nonce),
                'to' => $tokenAddr, // 代币合约地址
                'value' => '0x0', // 关键：目标是代币合约地址，不是接收者钱包地址
                'gasPrice' => '0x' . dechex($gasPrice),
                'gasLimit' => '0x' . dechex($gasLimit),
                'data' => '0x' . $data,
                'chainId' => $network['chain_id'], // 根据目标链设置Chain ID：ETH主网=1, BSC主网=56, Polygon主网=137
            ];
            // 5. 使用 blockchainethdev/ethereum-tx 或其他库签名交易
            $transaction = new Transaction($transactionParams);
            $signedTx = '0x' . $transaction->sign($privateKeyHex);
            // 6. 发送已签名的原始交易
            $txHash = '';
            $eth->sendRawTransaction($signedTx, function ($err, $result) use (&$txHash) {
                if ($err !== null) {
                    throw new \Exception("发送交易失败: " . $err->getMessage());
                }
                $txHash = $result;
            });
            return ['status' => 1, 'message' => '', 'result' => $txHash, 'fee' => $feeInWei];
        } catch (\Exception $e) {
            return ['status' => '0', 'message' => $e->getMessage() . ',行：' . $e->getLine()];
        }
    }
}
