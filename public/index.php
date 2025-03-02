<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
define('Lang_PATH', __DIR__ . '/../thinkphp/lang/');

define('XM', 'mall');
define('TRONKEY', '45c7f9ff-845e-45ee-9fda-e090d03254aa');
define('ZHB', 'USDT');
define('MOSHI', 'zhengshi');//主网
// define('MOSHI', 'ceshi');// 测试网
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
