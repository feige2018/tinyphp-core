<?php

namespace tiny;

use think\DbManager;

/**
 * use tiny\Db;
 * 
 * $list = Db::instance("user")->field("uid,name")->page(1, 5)->select()->toArray();
 */

class Db
{
	protected static $db;

	/**
	 * 获取数据库实例
	 * @param string $table 不带前缀的表名
	 * @return DbManager
	 */
	public function instance($table = "")
	{
		if (!static::$db) {
			static::$db = new DbManager();
			static::$db->setConfig(config("database"));
		}
		if ($table) {
			return static::$db->name($table);
		}
		return static::$db;
	}
}
