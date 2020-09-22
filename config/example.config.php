<?php

return [

	'env' => "production",    // local、development、testing、production

	'debug' => true,

	'app.name' => "TinyPHP",

	'app.url' => "http://127.0.0.1",

	"database" => [
		'default' => 'mysql',
		'connections' => [
			'mysql' => [
				// 数据库类型
				'type' => 'mysql',
				// 服务器地址
				'hostname' => "localhost",
				// 数据库名
				'database' => "test",
				// 数据库用户名
				'username' => "root",
				// 数据库密码
				'password' => "123456",
				// 数据库连接端口
				'hostport' => '',
				// 数据库连接参数
				'params' => [],
				// 数据库编码默认采用utf8
				'charset' => 'utf8',
				// 数据库表前缀
				'prefix' => "x_",
			],
		],
	],

];
