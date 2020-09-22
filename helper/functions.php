<?php

use tiny\Request;
use tiny\UserException;

/**
 * 格式化输出数组并终止程序
 * @param array $data 输入的数据
 * @param int $exit 1:终止，0:继续
 */
function prin($data, $exit = 1)
{
    if (is_array($data) or is_object($data)) {
        echo "<pre>" . PHP_EOL;
        print_r($data);
        echo "</pre>" . PHP_EOL;
    } else {
        print_r($data);
    }
    if ($exit) exit;
}

function vdump($data, $exit = 1)
{
    if (is_array($data) or is_object($data)) {
        echo "<pre>" . PHP_EOL;
        var_dump($data);
        echo "</pre>" . PHP_EOL;
    } else {
        var_dump($data);
    }
    if ($exit) exit;
}

function dd($data)
{
    vdump($data);
}

function is_debug()
{
    return config("debug", false);
}

function json($data = [], $code = 1, $message = "", $debug = null)
{
    $api = ["code" => $code, "message" => $message, "data" => $data];
    if ($debug) {
        $api["debug"] = $debug;
    }
    return json_encode($api, JSON_UNESCAPED_UNICODE);
}

function user_exception($message, $code = "0", $data = [], $file = "")
{
    throw new UserException($message, $code, $data, $file);
}

/**
 * @param string $key
 * @param mixed $default
 * @return mixed|null
 * 如：config("database.connections.mysql.prefix", "")
 */
function config($key = null, $default = null)
{
    static $config;
    try {
        $config = !empty($config) ? $config : include_once APP_PATH . 'config/config.php';
    } catch (\Throwable $e) {
        // todo ...
    }
    if ($key) {
        $value = array_get($config, $key);
        return !is_null($value) ? $value : $default;
    }
    return $config;
}

/**
 * 递归获取key值
 * @param array $array N 维数组
 * @param string $key 以.分隔
 * @return mixed|null
 */
function array_get($array, $key)
{
    $list = explode('.', $key);
    $first = array_shift($list);
    $result = isset($array[$first]) ? $array[$first] : null;
    if (!empty($result) && is_array($result)) {
        return array_get($result, join('.', $list));
    }
    return $result;
}

/**
 * 动态设置数组值
 * @param array $array
 * @param string $key 以.分隔
 * @param mixed $val 值
 * @return array|mixed
 */
function array_set(&$array, $key, $val)
{
    $list = explode('.', $key);
    $first = array_shift($list);
    if (count($list) > 0) {
        return array_set($array[$first], join('.', $list), $val);
    }
    $array[$first] = $val;
    return $array;
}

/**
 * 过滤异常信息
 * @param Throwable $e
 * @param bool $isTrace
 * @return array
 */
function filter_err(\Throwable $e, $isTrace = false)
{
    $root = ROOT_PATH;
    $err['code'] = $e->getCode();
    $err['file'] = str_replace($root, "", $e->getFile());
    $err['line'] = $e->getLine();
    $err['message'] = $e->getMessage();
    $errInfo["uri"] = Request::instance()->uri();
    if ($isTrace) {
        $getTrace = $e->getTrace();
        $getTrace = array_splice($getTrace, 0, 5);
        foreach ($getTrace as $i => $trace) {
            if (isset($trace["file"])) {
                $trace["file"] = str_replace($root, "", $trace["file"]);
            }
            $getTrace[$i] = $trace;
        }
        $err['trace'] = $getTrace;
    }
    return $err;
}

/**
 * 把任意类型的数据转化为字符串，用于记录日志
 * @param mixed $obj 可以是任意数据类型
 * @return string
 */
function to_string($obj)
{
    if (is_string($obj)) {
        return $obj;
    } elseif (is_null($obj)) {
        return "NULL";
    } elseif (is_bool($obj)) {
        return $obj ? "TRUE" : "FALSE";
    } elseif (is_object($obj)) {
        if ($obj instanceof \Throwable) {
            $obj = filter_err($obj);
        }
        return json_encode($obj, JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES);
    } elseif (is_array($obj)) {
        return json_encode($obj, JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES);
    } else {
        return var_export($obj, true);
    }
}
