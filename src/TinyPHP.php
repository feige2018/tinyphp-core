<?php

namespace tiny;

use Exception;
use Throwable;
use ReflectionClass;
use ReflectionMethod;

/**
 * 创建于 2020-09-20
 * TinyPHP 极简PHP框架
 */

//请修改你的项目路径：
defined('ROOT_PATH') || define('ROOT_PATH', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR);
defined('APP_PATH') || define('APP_PATH', ROOT_PATH . 'app' . DIRECTORY_SEPARATOR);
defined('START_PATH') || define('START_PATH', "/"); //ROUTE URI 从这个目录开始

date_default_timezone_set('PRC');

/**
 * 运行框架：
 * require_once ROOT_PATH . 'vendor/autoload.php';
 * $response = (new \tiny\TinyPHP)->run();
 * $response->send();
 * $response->end();
 */
class TinyPHP
{
	protected $request;

	public function run()
	{
		$this->request = Request::instance();
		try {
			$oReflectionMethod = $this->request->route();
			if (!isset($oReflectionMethod->routeParams)) {
				$data = $oReflectionMethod->invoke(new $oReflectionMethod->class);
			} else {
				$data = $oReflectionMethod->invokeArgs(new $oReflectionMethod->class, $oReflectionMethod->routeParams);
			}
			return Response::instance()->setData($data);
		} catch (Throwable $e) {
			$exceptionHandle = new ExceptionHandle;
			$exceptionHandle->report($this->request, $e);
			return $exceptionHandle->render($this->request, $e);
		}
	}
}

class Request
{
	protected $get = [];
	protected $post = [];
	protected $headers = [];
	protected $isJson = null;
	private static $instance;

	private function __construct()
	{
		$this->get = !empty($_GET) ? $_GET : [];
		$this->post = !empty($_POST) ? $_POST : [];
		$this->headers = getallheaders();
		$accept = isset($this->headers["Accept"]) ? $this->headers["Accept"] : null;
		$this->isJson = (bool)stristr($accept, "application/json") !== false;
	}

	/**
	 * @return static
	 */
	public static function instance()
	{
		if (empty(static::$instance)) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	public function get($key = null, $default = null)
	{
		if ($key) {
			$data = isset($this->get[$key]) ? $this->get[$key] : $default;
			return is_string($data) ? trim($data) : $data;
		}
		return $this->get;
	}

	public function post($key = null, $default = null)
	{
		if ($key) {
			$data = isset($this->post[$key]) ? $this->post[$key] : $default;
			return is_string($data) ? trim($data) : $data;
		}
		return $this->post;
	}

	function postGet($key = null, $default = null)
	{
		if ($key) {
			$input = array_merge($this->post, $this->get);
			$data = isset($input[$key]) ? $input[$key] : $default;
			return is_string($data) ? trim($data) : $data;
		}
		return array_merge_recursive($this->post, $this->get);
	}

	function getPost($key = null, $default = null)
	{
		if ($key) {
			$input = array_merge($this->get, $this->post);
			$data = isset($input[$key]) ? $input[$key] : $default;
			return is_string($data) ? trim($data) : $data;
		}
		return array_merge_recursive($this->post, $this->get);
	}

	public function input($key = null, $default = null)
	{
		return $this->getPost($key, $default);
	}

	public function isJson()
	{
		return $this->isJson;
	}

	public function getHeaders($key = null)
	{
		if ($key) {
			return isset($this->headers[$key]) ? $this->headers[$key] : null;
		}
		return $this->headers;
	}

	public function uri()
	{
		$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "";
		$uri = ltrim($uri, '/index.php');
		$uri = ltrim($uri, START_PATH);    // ROUTE URI 从这个目录开始
		$uri = ltrim($uri, '/');
		$uri = !$uri ? "index" : $uri;
		return $uri;
	}

	/**
	 * @return ReflectionMethod
	 * @throws
	 */
	public function route()
	{
		static $routes;
		$routes = !empty($routes) ? $routes : include_once APP_PATH . '/route/route.php';
		$uri = $this->uri();
		if (!isset($routes[$uri])) {
			user_exception("路径错误", "404");
		}
		$oReflectionClass = new ReflectionClass($routes[$uri][0]);
		$oReflectionMethod = $oReflectionClass->getMethod($routes[$uri][1]);
		if (isset($routes[$uri][2])) { // 可以给控制器传入参数
			$oReflectionMethod->routeParams = $routes[$uri][2];
		} else {
			$oReflectionMethod->routeParams = null;
		}
		return $oReflectionMethod;
	}
}

class Response
{
	protected $data = "";
	protected $headers = [];

	/**
	 * @return static
	 */
	public static function instance($data = null)
	{
		return new static($data);
	}

	private function __construct($data = null)
	{
		if ($data) {
			$this->setData($data);
		}
	}

	public function setData($data)
	{
		$this->data = $data;
		return $this;
	}

	public function getData()
	{
		return $this->data;
	}

	public function setHeaders($headers)
	{
		if (is_array($headers)) {
			$this->headers = array_merge_recursive($this->headers, $headers);
		}
		return $this;
	}

	public function getHeaders($key = null)
	{
		if (!isset($this->headers["Content-type"])) {
			if (Request::instance()->isJson()) {
				$this->headers["Content-type"] = "application/json; charset=utf-8";
			} else {
				$this->headers["Content-type"] = "text/html; charset=utf-8";
			}
		}
		if (!isset($this->headers["X-Name"])) {
			$this->headers["X-Name"] = config("app.name");
		}
		if ($key) {
			return isset($this->headers[$key]) ? $this->headers[$key] : null;
		}
		return $this->headers;
	}

	public function send()
	{
		$headers = $this->getHeaders();
		foreach ($headers as $key => $header) {
			header($key . ": " . $header);
		}
		$data = $this->getData();
		if (is_array($data) || is_object($data)) {
			$data = json($data);
		}
		print_r($data);
	}

	/**
	 * 发送HTTP响应之后，再干点啥
	 */
	public function end()
	{
		exit();
	}
}

class UserException extends Exception
{
	protected $code;
	protected $message;
	protected $data;
	protected $file;
	protected $line;

	/**
	 * @param string $message 用户抛出的异常消息.【注意】这个消息是可以返回给前端用户的
	 * @param string $code 用户指定的状态码
	 * @param array $data 保存额外的Debug数据
	 * @param string $file 抛出异常的文件
	 */
	public function __construct($message, $code = "0", $data = [], $file = "")
	{
		$this->code = $code;
		$this->message = $message;

		if ($data) {
			$this->addData($data);
		}
		if ($file) {
			$this->file = $file;
		}
	}

	public function getData()
	{
		return $this->data;
	}

	public function addData($data)
	{
		return $this->data[] = $data;
	}

	public function __toString()
	{
		return $this->message;
	}
}

class ExceptionHandle
{
	/**
	 * 不需要记录信息（日志）的异常类列表
	 */
	protected $ignoreReport = [
		UserException::class,
	];

	/**
	 * 记录异常信息
	 */
	public function report(Request $request, Throwable $exception)
	{
		$exceptionName = explode('\\', get_class($exception));
		$exceptionName = end($exceptionName); // 如：Error、ParseError

		if (!in_array($exceptionName, $this->ignoreReport)) {
			// todo ...
		}
	}

	/**
	 * 显示异常信息
	 */
	public function render(Request $request, Throwable $exception)
	{
		$code = $exception->getCode();
		$error = $exception->getMessage();
		$isDebug = is_debug();
		$message = $isDebug ? $error : "";
		$debug = $isDebug ? filter_err($exception, true) : null;

		if ($exception instanceof UserException) {
			$message = $exception->getMessage();
		} elseif ($exception instanceof \PDOException) {
			$message = $message ?: "数据库异常";
		} else {
			$message = $message ?: "系统异常";
		}
		if ($request->isJson()) {
			return Response::instance(json(null, $code, $message, $debug));
		}
		$data = "<pre>" . PHP_EOL . json_encode($debug, JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT);
		return Response::instance($data);
	}
}
