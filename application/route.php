<?php
use think\Route;

	// 设置路由之后，就不能使用pathinfo访问了
	// 注册路由 访问到Index模块index控制器index方法
	Route::rule('s/:code','api/user/shareurl');
