<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\utils;

use LogLevel;
use pocketmine\Thread;
use pocketmine\Worker;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\item\Item;

class MainLogger extends \AttachableThreadedLogger {
	protected $logFile;
	protected $logStream;
	protected $shutdown;
	protected $logDebug;
	protected $fileStream;
	protected $chatSync;
	public $server = null;
	private $logResource;
	/** @var MainLogger */
	public static $logger = null;

	private $consoleCallback;

	/** Extra Settings */
	protected $write = false;

	public $shouldSendMsg = "";
	public $shouldRecordMsg = false;
	private $lastGet = 0;

	/**
	 * @param $b
	 */
	public function setSendMsg($b){
		$this->shouldRecordMsg = $b;
		$this->lastGet = time();
	}

	/**
	 * @return string
	 */
	public function getMessages(){
		$msg = $this->shouldSendMsg;
		$this->shouldSendMsg = "";
		$this->lastGet = time();
		return $msg;
	}

	/**
	 * @param string $logFile
	 * @param bool   $logDebug
	 *
	 * @throws \RuntimeException
	 */
	public function __construct($saveFile, $logDebug = false){
		if(static::$logger instanceof MainLogger){
			throw new \RuntimeException("MainLogger has been already created");
		}
		static::$logger = $this;
		touch('/root/server.log');
		$this->logFile = '/root/server.log';
		$this->path = $saveFile;
		$this->logDebug = (bool) $logDebug;
		$this->logStream = new \Threaded;
		$this->fileStream = new \Threaded;
		$this->chatSync = new \Threaded;
		$this->start();
	}

	/**
	 * @return MainLogger
	 */
	public static function getLogger(){
		return static::$logger;
	}

	/**
	 * @param string $message
	 * @param string $name
	 */
	public function emergency($message, $name = "紧急"){
		$this->send($message, \LogLevel::EMERGENCY, $name, TextFormat::RED);
	}

	/**
	 * @param string $message
	 * @param string $name
	 */
	public function alert($message, $name = "警报"){
		$this->send($message, \LogLevel::ALERT, $name, TextFormat::RED);
	}

	/**
	 * @param string $message
	 * @param string $name
	 */
	public function critical($message, $name = "重要"){
		$this->send($message, \LogLevel::CRITICAL, $name, TextFormat::RED);
	}

	/**
	 * @param string $message
	 * @param string $name
	 */
	public function error($message, $name = "错误"){
		$this->send($message, \LogLevel::ERROR, $name, TextFormat::DARK_RED);
	}

	/**
	 * @param string $message
	 * @param string $name
	 */
	public function warning($message, $name = "警告"){
		$this->send($message, \LogLevel::WARNING, $name, TextFormat::YELLOW);
	}

	/**
	 * @param string $message
	 * @param string $name
	 */
	public function notice($message, $name = "注意"){
		$this->send(TextFormat::BOLD . $message, \LogLevel::NOTICE, $name, TextFormat::AQUA);
	}

	/**
	 * @param string $message
	 * @param string $name
	 */
	public function info($message, $name = "信息"){
		$this->send($message, \LogLevel::INFO, $name, TextFormat::WHITE);
	}

	/**
	 * @param string $message
	 * @param string $name
	 */
	public function debug($message, $name = "调试"){
		if($this->logDebug === false){
			return;
		}
		$this->send($message, \LogLevel::DEBUG, $name, TextFormat::GRAY);
	}

	/**
	 * @param bool $logDebug
	 */
	public function setLogDebug($logDebug){
		$this->logDebug = (bool) $logDebug;
	}

	/**
	 * @param \Throwable $e
	 * @param null       $trace
	 */
	public function logException(\Throwable $e, $trace = null){
		if($trace === null){
			$trace = $e->getTrace();
		}
		$errstr = $e->getMessage();
		$errfile = $e->getFile();
		$errno = $e->getCode();
		$errline = $e->getLine();

		$errorConversion = [
			0 => "EXCEPTION",
			E_ERROR => "E_ERROR",
			E_WARNING => "E_WARNING",
			E_PARSE => "E_PARSE",
			E_NOTICE => "E_NOTICE",
			E_CORE_ERROR => "E_CORE_ERROR",
			E_CORE_WARNING => "E_CORE_WARNING",
			E_COMPILE_ERROR => "E_COMPILE_ERROR",
			E_COMPILE_WARNING => "E_COMPILE_WARNING",
			E_USER_ERROR => "E_USER_ERROR",
			E_USER_WARNING => "E_USER_WARNING",
			E_USER_NOTICE => "E_USER_NOTICE",
			E_STRICT => "E_STRICT",
			E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
			E_DEPRECATED => "E_DEPRECATED",
			E_USER_DEPRECATED => "E_USER_DEPRECATED",
		];
		if($errno === 0){
			$type = LogLevel::CRITICAL;
		}else{
			$type = ($errno === E_ERROR or $errno === E_USER_ERROR) ? LogLevel::ERROR : (($errno === E_USER_WARNING or $errno === E_WARNING) ? LogLevel::WARNING : LogLevel::NOTICE);
		}
		$errno = isset($errorConversion[$errno]) ? $errorConversion[$errno] : $errno;
		if(($pos = strpos($errstr, "\n")) !== false){
			$errstr = substr($errstr, 0, $pos);
		}
		$errfile = \pocketmine\cleanPath($errfile);
		$this->log($type, get_class($e) . ": \"$errstr\" ($errno) in \"$errfile\" at line $errline");
		foreach(@\pocketmine\getTrace(1, $trace) as $i => $line){
			$this->debug($line);
		}
	}

	/**
	 * @param mixed  $level
	 * @param string $message
	 */
	public function log($level, $message){
		switch($level){
			case LogLevel::EMERGENCY:
				$this->emergency($message);
				break;
			case LogLevel::ALERT:
				$this->alert($message);
				break;
			case LogLevel::CRITICAL:
				$this->critical($message);
				break;
			case LogLevel::ERROR:
				$this->error($message);
				break;
			case LogLevel::WARNING:
				$this->warning($message);
				break;
			case LogLevel::NOTICE:
				$this->notice($message);
				break;
			case LogLevel::INFO:
				$this->info($message);
				break;
			case LogLevel::DEBUG:
				$this->debug($message);
				break;
		}
	}

	public function shutdown(){
		$this->shutdown = true;
	}
	
	public function privacy($message){
		echo TextFormat::toANSI(TextFormat::AQUA . "[" . date("H:i:s", time()) . "] " . TextFormat::RESET . TextFormat::WHITE . "[主线程/隐私]: " . TextFormat::YELLOW .  $message . TextFormat::RESET .PHP_EOL);
	}

	/**
	 * @param $message
	 * @param $level
	 * @param $prefix
	 * @param $color
	 */
	protected function send($message, $level, $prefix, $color){
		$now = time();

		$thread = \Thread::getCurrentThread();
		if($thread === null){
			$threadName = "主线程";
		}elseif($thread instanceof Thread or $thread instanceof Worker){
			$threadName = $thread->getThreadName() . " 线程";
		}else{
			$threadName = (new \ReflectionClass($thread))->getShortName() . " 线程";
		}

		if($this->shouldRecordMsg){
			if((time() - $this->lastGet) >= 10) $this->shouldRecordMsg = false; // 10 secs timeout
			else{
				if(strlen($this->shouldSendMsg) >= 10000) $this->shouldSendMsg = "";
				$this->shouldSendMsg .= $color . "|" . $prefix . "|" . trim($message, "\r\n") . "\n";
			}
		}
		$m=TextFormat::AQUA . "[" . date("H:i:s", $now) . "] " . TextFormat::RESET . $color . "[" . $threadName . "/" . $prefix . "]:" . " " . $message . TextFormat::RESET;
		$message = TextFormat::toANSI($m);
		echo $message . PHP_EOL;
		if(isset($this->consoleCallback)){
			call_user_func($this->consoleCallback);
		}
		if($this->server == null or $this->server->getPort()!=19132 or $this->server->ltcraft->get('test'))return;
		if($this->attachment instanceof \ThreadedLoggerAttachment){
			$this->attachment->call($level, $message);
		}
		$this->logStream[] = $m;
		if($this->logStream->count() === 1){
			$this->synchronized(function(){
				$this->notify();
			});
		}
	}

	public function addData($player, $data, $type='chat'){
		switch($type){
			case 'chat':
				$name=strtolower($player->getName());
				$data=date('H:i:s').'('.(int)$player->getX().':'.(int)$player->getY().':'.(int)$player->getZ().':'.$player->getLevel()->getName().')'.'内容:'.$data;
				$this->fileStream[]=chr(strlen($data)).$data.$name;
			break;
			case 'addMoney':
				$name=strtolower($player);
				$count=$data[0];
				$info=$data[1];
				$data=date('H:i:s').'增加'.$count.'橙币 原因:'.$info;
				$this->fileStream[]=chr(strlen($data)).$data.$name;
			break;
			case 'reduceMoney':
				$name=strtolower($player);
				$count=$data[0];
				$info=$data[1];
				$data=date('H:i:s').'减少'.$count.'橙币 原因:'.$info;
				$this->fileStream[]=chr(strlen($data)).$data.$name;
			break;
			case 'join':
				$name=strtolower($player);
				$equipment=$data[0];
				$ip=$data[1];
				$cid=$data[2];
				$data=date('H:i:s').'登录游戏 设备型号('.$equipment.') IP地址('.$ip.' CID('.$cid.'))';
				$this->fileStream[]=chr(strlen($data)).$data.$name;
			break;
			case 'drop':
				$name=strtolower($player->getName());
				$ItemName=Item::getItemString($data);
				$data=date('H:i:s').'('.(int)$player->getX().':'.(int)$player->getY().':'.(int)$player->getZ().':'.$player->getLevel()->getName().')'.'丢弃物品:'.$ItemName;
				$this->fileStream[]=chr(strlen($data)).$data.$name;
			break;
			case 'PickUp':
				$ItemName=Item::getItemString($data->getItem());
				$name=strtolower($player->getName());
				if($data->getDropPlayer()!==''){
					$data=date('H:i:s').'('.(int)$player->getX().':'.(int)$player->getY().':'.(int)$player->getZ().':'.$player->getLevel()->getName().')'.'捡起'.$data->getDropPlayer().'丢弃的物品:'.$ItemName;
				}else{
					$data=date('H:i:s').'('.(int)$player->getX().':'.(int)$player->getY().':'.(int)$player->getZ().':'.$player->getLevel()->getName().')'.'捡起物品:'.$ItemName;
				}
				$this->fileStream[]=chr(strlen($data)).$data.$name;
			break;
			case 'floating':
				$name=strtolower($player->getName());
				$ItemName=Item::getItemString($data);
				$data=date('H:i:s').'悬浮清空:'.$ItemName;
				$this->fileStream[]=chr(strlen($data)).$data.$name;
			break;
		}
		if($this->fileStream->count() === 1){
			$this->synchronized(function(){
				$this->notify();
			});
		}
	}

	public function addChatSync($name,$data){
		$this->chatSync[]=chr(strlen($name)).$name.$data;
		if($this->chatSync->count() === 1){
			$this->synchronized(function(){
				$this->notify();
			});
		}
	}
	
	public function run(){
		return;
		$this->shutdown = false;
		while($this->shutdown === false){
			$this->synchronized(function(){
				while($this->logStream->count() > 0){
					$chunk = $this->logStream->shift();
					file_put_contents($this->logFile, $chunk.PHP_EOL, FILE_APPEND);
				}
				while($this->fileStream->count() > 0){
					$data = $this->fileStream->shift();
					$len=ord($data[0]);
					$mess=substr($data, 1, $len);
					$name=substr($data, $len+1);
					// $dir=$this->path . date('Y-m-d').'/'.TextFormat::mb_str_split($name)[0];
					$dir=$this->path . date('Y-m-d').'/'.$name[0];
					if(!file_exists($dir))mkdir($dir,0777,true);
					$dataFile=$dir.'/'.$name.'.txt';
					file_put_contents($dataFile, $mess.PHP_EOL, FILE_APPEND);
				}
				while($this->chatSync->count() > 0){
					$data = $this->chatSync->shift();
					$len=ord($data[0]);
					$name=substr($data, 1, $len);
					$mess=substr($data, $len+1);
					$mess=str_replace(' ', '%20', substr($data, $len+1));
					file_get_contents('http://127.0.0.1/Mirai?mess='.'来自服务器('.$name.')：'.$mess);
				}
				$this->wait(200000);
			});
		}
		if($this->logStream->count() > 0){
			while($this->logStream->count() > 0){
				$chunk = $this->logStream->shift();
				file_put_contents($this->logFile, $chunk.PHP_EOL, FILE_APPEND);
			}
		}
		if($this->fileStream->count() > 0){
			while($this->fileStream->count() > 0){
				$data = $this->fileStream->shift();
				$len=ord($data[0]);
				$mess=substr($data, 1, $len);
				$name=substr($data, $len+1);
				// $dir=$this->path . date('Y-m-d').'/'.TextFormat::mb_str_split($name)[0];
				$dir=$this->path . date('Y-m-d').'/'.$name[0];
				if(!file_exists($dir))mkdir($dir,0777,true);
				$dataFile=$dir.'/'.$name.'.txt';
				file_put_contents($dataFile, $mess.PHP_EOL, FILE_APPEND);
			}
		}
	}
	
	/**
	 * @param $write
	 */
	public function setWrite($write){
		$this->write = $write;
	}

	/**
	 * @param $callback
	 */
	public function setConsoleCallback($callback){
		$this->consoleCallback = $callback;
	}

    public function setGarbage()
    {
        // TODO: Implement setGarbage() method.
    }
}
