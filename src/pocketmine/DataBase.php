<?php
namespace pocketmine;

class DataBase extends \Thread{
	const BLOCK_QUERY = 1;
	const ORDINARY_SQL = 2;
	const EXP_BLOCK_QUERT = 3;
	const MOVE_CHECK = 4;
	const GET_PLAYER_DATA = 5;
	const CHECH_MORE_IP = 6;
	const GET_WEAPON = 7;
	const CHECK_R = 8;
	const SET_VIP = 9;
	public static $connection;
	private $autoloader;
	private $serviceQueue;
	private $doneQueue;
	public $shutdown;
	public $server;
	public $logger;
	public $loadPaths;
	/** @var string $username */
    public string $username;
	/** @var string $password */
	public string $password;
	/** @var string $database */
	public string $database = 'server';
	/** @var string $database */
	public string $localhost = '127.0.0.1';
	public function __construct(\pocketmine\utils\MainLogger $log, Server $server, \ClassLoader $autoloader, string $username, string $password, string $database = 'server', string $localhost = '127.0.0.1'){
		$this->logger = $log;
		$this->server = $server;
		$this->autoloader = $autoloader;
		$this->server = $server;
		$this->localhost = $localhost;
		$this->username = $username;
		$this->password = $password;
		$this->database = $database;
		$this->serviceQueue = new \Threaded;
		$this->doneQueue = new \Threaded;
		$loadPaths = [];
		$this->addDependency($loadPaths, new \ReflectionClass($server));
		$this->addDependency($loadPaths, new \ReflectionClass($log));
		$this->addDependency($loadPaths, new \ReflectionClass($autoloader));
		$this->loadPaths = array_reverse($loadPaths);
		$this->start();
	}
	protected function addDependency(array &$loadPaths, \ReflectionClass $dep){
		if($dep->getFileName() !== false){
			$loadPaths[$dep->getName()] = $dep->getFileName();
		}

		if($dep->getParentClass() instanceof \ReflectionClass){
			$this->addDependency($loadPaths, $dep->getParentClass());
		}

		foreach($dep->getInterfaces() as $interface){
			$this->addDependency($loadPaths, $interface);
		}
	}
	public function shutdown(){
		$this->shutdown = true;
	}
	public function pushService($str, $notify=true){
		$this->serviceQueue[] = $str;
		if($notify)
			$this->synchronized(function(){
				$this->notify();
			});
	}
	public function readService(){
		return $this->serviceQueue->shift();
	}
	public function pushDone($str){
		return $this->doneQueue[] = $str;
	}
	public function readDone(){
		return $this->doneQueue->shift();
	}
	public function Connection(){
		$this->getLogger()->info('§e正在连接至数据库');
		if(self::$connection=new \PDO("mysql:host=".$this->localhost.";dbname=".$this->database,$this->username,$this->password,array(\PDO::ATTR_PERSISTENT => true))){
			$this->getLogger()->info('§a数据库连接成功！'); 
			self::$connection->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_WARNING);
			self::$connection->exec('SET NAMES UTF8');
		}else{
			$this->getLogger()->warning('§a数据库连接失败！');
			$this->server->shutdown();
		}
	}
	public function getConnection(){
		return self::$connection;
	}
	public function run(){
		$this->shutdown = false;
		foreach($this->loadPaths as $name => $path){
			if(!class_exists($name, false) and !interface_exists($name, false)){
				require($path);
			}
		}
		$this->autoloader->register(true);
		$this->Connection();
		while(!$this->shutdown or $this->serviceQueue->count()>1){
			$this->synchronized(function(){
				while(strlen($str = $this->readService()) > 0){
					$offset=0;
					$SQLType=$str[$offset++];
					$type=ord($str[$offset++]);
					switch($SQLType){
						case 0://query 带事件
							$len=ord($str[$offset++]);
							$eventid=substr($str, $offset, $len);
							$sql=substr($str, $offset+$len);
							$cx=self::$connection->query($sql);
							if (!$cx){
							    $this->fixConn();
                                $cx=self::$connection->query($sql);
                            }
							switch($type){
								case self::BLOCK_QUERY:
									$count=count($cx->fetchAll());
									$this->pushDone(chr(self::BLOCK_QUERY) .chr(strlen($eventid)).$eventid.$count);
								break;
								case self::EXP_BLOCK_QUERT:
									$count=count($cx->fetchAll());
									$this->pushDone(chr(self::EXP_BLOCK_QUERT) .chr(strlen($eventid)).$eventid.$count);
								break;
								case self::GET_PLAYER_DATA:
									$row=$cx->fetch();
									if($row!==false)						
										$this->pushDone(chr(self::GET_PLAYER_DATA) .chr(strlen($eventid)).$eventid. serialize($row));
									else
										$this->pushDone(chr(self::GET_PLAYER_DATA). chr(strlen($eventid)).$eventid);
								break;
								case self::CHECH_MORE_IP:
									$row=$cx->fetchAll();
									if(count($row)>1)
										$this->pushDone(chr(self::CHECH_MORE_IP) .chr(strlen($eventid)).$eventid. serialize($row));
								break;
								case self::GET_WEAPON:
									$row=$cx->fetchAll();
									$datas=[];
									foreach($row as $r){
										if($r['status']!=0)continue;
										$datas[]=[$r['type'], $r['name'], $r['count'], $r['id']];
									}
									$this->pushDone(chr(self::GET_WEAPON) .chr(strlen($eventid)).$eventid. serialize($datas));
								break;
							}
						break;
						case 1://exec
							$sql=substr($str, $offset++);
							$exec=self::$connection->exec($sql);
						break;
						case 2://query 不带事件
							$sql=substr($str, $offset);
							$cx=self::$connection->query($sql);
                            if (!$cx){
                                $this->fixConn();
                                $cx=self::$connection->query($sql);
                            }
							switch($type){
								case self::MOVE_CHECK:
									$row=$cx->fetch();
									if($row!==false and $row['moveCheck']==1)
										$this->pushDone(chr(self::MOVE_CHECK) .$row['name']);
								break;
								case self::CHECK_R:
									$row=$cx->fetchAll();
									foreach($row as $r){
										if($r['status']!=0)continue;
										$this->pushDone(chr(self::CHECK_R) . serialize($r));
										break;
									}
								break;
							}
						break;
						case 3://设置VIP
							$info=explode(' ', substr($str, $offset));
							$this->setVIP($info[0], $info[1], $info[2]);
						break;
					}
				}
				if(!$this->shutdown)$this->wait(200000);
			});
		}
	}
	public function setVIPInfo($name, $level, $time){//设置VIP
		$name=strtolower($name);
		if($level==null)
			$c=self::$connection->exec("update server.user set VIP=NULL where name='{$name}'");
		else
			$c=self::$connection->exec("update server.user set VIP='{$level}:{$time}' where name='{$name}'");
		return $c;
	}
	public function getUser($username){//获取用户信息
		$username=strtolower($username);
		$cx=self::$connection->query("SELECT * FROM server.user WHERE name='{$username}'");
		$arr=$cx->fetch();
		if($arr){
			return $arr;
		}else{
			return false;
		}
	}
	public function setVIP($name, $level = 0, $time = 0){
		$UserData=$this->getUser($name);
		if($UserData!==false){
			if($level==0 and $time==0){
				$this->setVIPInfo($name, null, 0);
				return;
			}
			if($UserData['VIP']!==null){
				$vipd=explode(':', $UserData['VIP']);
				if($vipd[0]===$level){
					$this->setVIPInfo($name, $level, $vipd[1]+86400*$time);
				}else{
					if($time==0){
						$this->setVIPInfo($name, $level, $vipd[1]);
					}else{
						$this->setVIPInfo($name, $level, time()+86400*$time);
					}
				}
			}else{
				$this->setVIPInfo($name, $level, time()+86400*$time);
			}
		}
	}
	public function getLogger(){
		return $this->logger;
	}
	public function fixConn(){
		$this->getLogger()->info('§e重新正在连接至数据库');
		// unset(self::$connection);
		if(self::$connection=new \PDO("mysql:host=.$this->localhost.;dbname=".$this->database ,$this->username,$this->password,array(\PDO::ATTR_PERSISTENT => true))){
			self::$connection->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_WARNING);
			$this->getLogger()->info('§a数据库连接成功！');
			self::$connection->exec('SET NAMES UTF8');
			return true;
		}else{
			$this->BroadCastMessage('§a数据库连接失败！关闭服务器！');
			$this->server->shutdown();
			return false;
		}
	}

    public function setGarbage()
    {
        // TODO: Implement setGarbage() method.
    }
}