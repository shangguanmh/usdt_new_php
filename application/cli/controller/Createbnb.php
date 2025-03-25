<?php

namespace app\cli\controller;

use think\Controller;
use think\Request;
use GuzzleHttp\Client;
use think\Db;
use Binance\Wallet;

class Createbnb extends Controller 
{
    public function index()
    {
        // 检查空闲钱包数量
        $add_count = Db::name('bnb_address')->where(['user_id'=>'0'])->count();

        if ($add_count > 100) {
            echo("目前空闲的BNB钱包地址很多，无需新增\n");
            exit();
        }
        
        // 初始化 BSC Wallet
        $wallet = new Wallet();
        
        // 生成新钱包
        for ($i = 0; $i <= 20; $i++){
            try {
                // 使用 fenguoz/bsc-php 库生成新的 BNB 地址
                $newAccount = $wallet->newAccountByPrivateKey();
                //var_dump($newAccount);exit;
                // 生成随机盐值
                $pool        = 'qwertyuioplkjhgfdsazxcvbnmn'; // 定义一个验证码池
                $word_length = 6; // 验证码长度
                $salt        = ''; // 盐值
                for ($j = 0, $mt_rand_max = strlen($pool) - 1; $j < $word_length; $j++)
                {
                    $salt .= $pool[mt_rand(0, $mt_rand_max)];
                }
                
                // 保存到数据库
                Db::name('bnb_address')->insert([
                    'privateKey' => $newAccount['key'],
                    'address'    => $newAccount['address'],
                    'add_time'   => date('Y-m-d H:i:s'),
                    'salt'       => $salt
                ]);
                
                echo "已生成BNB钱包地址: " . $newAccount['address'] . "\n";
                
            } catch(\Exception $e) {
                echo "生成钱包失败: " . $e->getMessage() . "\n";
                continue;
            }
        }
        
        echo "BNB钱包生成完成\n";
    }
} 