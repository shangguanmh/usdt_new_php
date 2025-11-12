<?php

use think\Route;

// 设置路由之后，就不能使用pathinfo访问了
// 注册路由 访问到Index模块index控制器index方法
Route::rule('s/:code', 'api/user/shareurl');

Route::get('checkEthereumAddr','cli/CheckEthereumAddr/task');//充值检查是否到账
Route::get('collectEthereumAddr','cli/TransferEthereumAddr/index');//归集到系统钱包
Route::get('drawEthereumAddr','cli/TransferEthereumAddr/withdraw');//提现以太坊相关币
Route::get('testTransfer','cli/CheckEthereumAddr/test');
