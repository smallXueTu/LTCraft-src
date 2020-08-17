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

namespace pocketmine\network;

use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\BatchPacket;
use pocketmine\Player;
use pocketmine\Server;
use raklib\protocol\EncapsulatedPacket;
use raklib\protocol\PacketReliability;
use raklib\RakLib;
use raklib\server\RakLibServer;
use raklib\server\ServerHandler;
use raklib\server\ServerInstance;
use onebone\economyapi\EconomyAPI;
use MultiSign\Main as MultiSign;
use LTGrade\Main as LTGrade;
use LTLove\Main as LTLove;
use LTSociety\Main as LTSociety;
use LTVIP\Main as LTVIP;
use pocketmine\command\QQSender;

class RakLibInterface implements ServerInstance, AdvancedSourceInterface {

	/** @var Server */
	private $server;

	/** @var Network */
	private $network;

	/** @var RakLibServer */
	private $rakLib;

	/** @var Player[] */
	private $players = [];

	/** @var string[] */
	private $identifiers;

	/** @var int[] */
	private $identifiersACK = [];

	/** @var ServerHandler */
	private $interface;

	/**
	 * RakLibInterface constructor.
	 *
	 * @param Server $server
	 */
	public function __construct(Server $server){

		$this->server = $server;
		$this->identifiers = [];

		$this->rakLib = new RakLibServer($this->server->getLogger(), $this->server->getLoader(), $this->server->getPort(), $this->server->getIp() === "" ? "0.0.0.0" : $this->server->getIp());
		$this->interface = new ServerHandler($this->rakLib, $this);
        $this->server->interface = $this->interface;
	}

	/**
	 * @param Network $network
	 */
	public function setNetwork(Network $network){
		$this->network = $network;
	}
	public function getInterface(){
		return $this->interface;
	}
	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function process(){
		$work = false;
		$c=$this->interface->server->getEventQueue();
		if($c!==null and $c!==''){
			switch(substr($c,0,3)){
                case 'HEA':
                    $this->interface->server->pushMainToThreadPacket('HEA');
                break;
				case 'GET':
					$cmd=substr($c,3,strlen($c));
					if(($cmd[0] ==='A')){
						$cmd=explode('>',substr($cmd,1,strlen($cmd)));
						
						$server = $this->server;
						$banList = $server->getNameBans();
						if($banList->isBanned($cmd[0])){
							$money=0;
						}else{
							$money=EconomyAPI::getInstance()->myMoney($cmd[0]);
						}
						$all=[
							'橙币'=>$money,
						];
						$data=serialize($all);
						$this->interface->server->pushMainToThreadPacket('WED'.$data.'€'.$cmd[1]);
					}elseif($cmd[0] ==='M'){
						$cmd=explode('>',substr($cmd,1,strlen($cmd)));
						$money=EconomyAPI::getInstance()->myMoney($cmd[0]);
						$this->interface->server->pushMainToThreadPacket('WED'.$money.'€'.$cmd[1]);
					}elseif($cmd[0] ==='G'){
						$cmd=explode('>',substr($cmd,1,strlen($cmd)));
						$grade=LTGrade::getInstance()->getGender($cmd[0]);
						$this->interface->server->pushMainToThreadPacket('WED'.$grade.'€'.$cmd[1]);
					}elseif($cmd[0] ==='S'){
						$cmd=explode('>',substr($cmd,1,strlen($cmd)));
						$banStatus=(String)$this->server->getNameBans()->isBanned($cmd[0]);
						$this->interface->server->pushMainToThreadPacket('WED'.$banStatus.'€'.$cmd[1]);
					}
				break;
				case 'SAY':
					$cmd=substr($c,3,strlen($c));
					$len=strpos($cmd,'-')+1;
					$name=substr($cmd,0,$len-1);
					$say=substr($cmd,$len,strlen($cmd));
					if(substr($say, 0, 1)==='/' and strtolower($name)==='angel_xx'){
						$this->server->dispatchCommand($this->server->consoleSender, substr($say, 1));
					}else
					$this->server->broadcast('§l§a来至网页§e'.$name.'§a的消息:§d'.$say, Server::BROADCAST_CHANNEL_USERS, true);
				break;
				case 'ASY':
					$cmd=substr($c,3,strlen($c));
					$len=strpos($cmd,'-')+1;
					$name=substr($cmd,0,$len-1);
					$say=substr($cmd,$len,strlen($cmd));
					if(substr($say, 0, 1)==='/' and strtolower($name)==='angel_xx'){
						$this->server->dispatchCommand($this->server->consoleSender, substr($say, 1));
					}else
					$this->server->broadcast('§l§a来至APP§e'.$name.'§a的消息:§d'.$say, Server::BROADCAST_CHANNEL_USERS, true);
				break;
				case 'USY':
					$cmd=substr($c,3,strlen($c));
					$len=strpos($cmd,'-')+1;
					$name=substr($cmd,0,$len-1);
					$say=substr($cmd,$len,strlen($cmd));
					if(substr($say, 0, 1)==='/' and strtolower($name)==='angel_xx'){
						$this->server->dispatchCommand($this->server->consoleSender, substr($say, 1));
					}else
					$this->server->broadcast('§l§a来至UDPCHAT§e'.$name.'§a的消息:§d'.$say, Server::BROADCAST_CHANNEL_USERS, true);
				break;
				case 'QQS':
					$cmd=substr($c,3,strlen($c));
					$cmd=explode('>',substr($cmd,0,strlen($cmd)));
					$info=explode(' ', $cmd[0]);
					$sender=new QQSender();
					switch($info[0]){
						case '!执行命令':
							unset($info[0]);
							$this->server->dispatchCommand($sender, implode(' ',$info));
						break;
						case '!重启服务器':
							$this->server->dispatchCommand($sender, '重启');
						break;
						case '!踢':
							$this->server->dispatchCommand($sender, 'kick '.$info[1]);
						break;
						case '!偷钱':
							if(EconomyAPI::getInstance()->myMoney($info[1])>=$info[2]){
								EconomyAPI::getInstance()->reduceMoney($info[1], $info[2]);
							}else{
								$sender->sendMessage('目标橙币不足！', false);
							}
						break;
						case '!给钱':
							if(EconomyAPI::getInstance()->addMoney($info[1], $info[2], '管理员赐予')){
								$sender->sendMessage('成功给了'.$info[1].' '.$info[2].'橙币！', false);
							}else{
								$sender->sendMessage('给钱失败！', false);
							}
						break;
						case '!富豪榜':
							$page = $info[1]??1;
							$moneyData = EconomyAPI::getInstance()->getAllMoney();
							$server = $this->server;
							$banList = $server->getNameBans();
							arsort($moneyData);
							$n = 1;
							$max = ceil((count($moneyData) - count($banList->getEntries())) / 5);
							$page = max(1, $page);
							$page = min($max, $page);
							$page = (int)$page;
							
							$output = "- 富豪榜 ($page of $max) -\n";
							
							foreach($moneyData as $player => $money){
								if($banList->isBanned($player)) continue;
								if($server->isOp(strtolower($player))) continue;
								$current = (int)ceil($n / 5);
								if($current === $page){
									$output .= '['.$n.'] '.$player .': '.$money.PHP_EOL;
								}elseif($current > $page){
									break;
								}
								++$n;
							}
							$sender->sendMessage($output, false);
						break;
						case '!转账':
							$server = $this->server;
							$banList = $server->getNameBans();
							if($banList->isBanned($info[1])){
								$sender->sendMessage('被封锁玩家不能转账！', false);
							}else{
								if(EconomyAPI::getInstance()->myMoney($info[1])>=$info[3]){
									EconomyAPI::getInstance()->reduceMoney($info[1], $info[3], 'QQ转账给'.$info[2]);
									if(($r=EconomyAPI::getInstance()->addMoney($info[2], $info[3], 'QQ来自'.$info[1].'的转账'))===true){
										$sender->sendMessage('转账成功！', false);
									}elseif($r==1){
										
											$sender->sendMessage('请输入整数!', false);
											EconomyAPI::getInstance()->addMoney($info[1], $info[3], 'QQ转账失败回滚');
										}elseif($r==2){
											$sender->sendMessage('服务器不存在这个玩家！', false);
											EconomyAPI::getInstance()->addMoney($info[1], $info[3], 'QQ转账失败回滚');
										}else{
											$sender->sendMessage('转账失败！', false);
											EconomyAPI::getInstance()->addMoney($info[1], $info[3], 'QQ转账失败回滚');
									}
								}else{
									$sender->sendMessage('橙币不足！', false);
								}
							}
						break;
						case '!命令':
							$name = array_shift($info);
							$name = array_shift($info);
							if(($player = $this->server->getPlayerExact($name)) instanceof Player){
								$player->getServer()->dispatchCommand($player->getServer()->consoleSender, implode(' ',$info));
								$sender->sendMessage('玩家在线,执行成功！', false);
							}else{
								\LTCraft\Main::offlineCommand($name, implode(' ',$info));
								$sender->sendMessage('玩家不在线,已加入离线命令!', false);
							}
						break;
						case '!说话':
							unset($info[0]);
							$this->server->BroadCastMessage('§l§a来至QQ消息:§d'.implode(' ',$info));
						break;
					}
					if(count($sender->getMessages())>0){
						$this->interface->server->pushMainToThreadPacket('WED'.serialize($sender->getMessages()).'€'.$cmd[1]);
					}
				break;
			}
		}
		if($this->interface->handlePacket()){
			$work = true;
			$lasttime = time();
			while($this->interface->handlePacket()){
				$diff = time() - $lasttime;
				if($diff >= 1) break;
			}
		}
		
		if(!$this->rakLib->isRunning() and !$this->rakLib->isShutdown()){
			$this->network->unregisterInterface($this);

			throw new \Exception("RakLib Thread crashed");
		}

		return $work;
	}

	/**
	 * @param string $identifier
	 * @param string $reason
	 */
	public function closeSession($identifier, $reason){
		if(isset($this->players[$identifier])){
			$player = $this->players[$identifier];
			unset($this->identifiers[spl_object_hash($player)]);
			unset($this->players[$identifier]);
			unset($this->identifiersACK[$identifier]);
			$player->close($player->getLeaveMessage(), $reason);
		}
	}

	/**
	 * @param Player $player
	 * @param string $reason
	 */
	public function close(Player $player, $reason = "理由未知"){
		if(isset($this->identifiers[$h = spl_object_hash($player)])){
			unset($this->players[$this->identifiers[$h]]);
			unset($this->identifiersACK[$this->identifiers[$h]]);
			$this->interface->closeSession($this->identifiers[$h], $reason);
			unset($this->identifiers[$h]);
		}
	}

	public function shutdown(){
		$this->interface->shutdown();
	}

	public function emergencyShutdown(){
		$this->interface->emergencyShutdown();
	}

	/**
	 * @param string     $identifier
	 * @param string     $address
	 * @param int        $port
	 * @param int|string $clientID
	 */
	public function openSession($identifier, $address, $port, $clientID){
		$ev = new PlayerCreationEvent($this, Player::class, Player::class, null, $address, $port);
		$this->server->getPluginManager()->callEvent($ev);
		$class = $ev->getPlayerClass();

		$player = new $class($this, $ev->getClientId(), $ev->getAddress(), $ev->getPort());
		$this->players[$identifier] = $player;
		$this->identifiersACK[$identifier] = 0;
		$this->identifiers[spl_object_hash($player)] = $identifier;
		$this->server->addPlayer($identifier, $player);
	}

	/**
	 * @param string             $identifier
	 * @param EncapsulatedPacket $packet
	 * @param int                $flags
	 */
	public function handleEncapsulated($identifier, EncapsulatedPacket $packet, $flags){
		if(isset($this->players[$identifier])){
			try{
				if($packet->buffer !== ""){
					$pk = $this->getPacket($packet->buffer);
					if($pk !== null){
						$pk->decode();
						assert($pk->feof(), "Still " . strlen(substr($pk->buffer, $pk->offset)) . " bytes unread!");
						$this->players[$identifier]->handleDataPacket($pk);
					}
				}
			}catch(\Throwable $e){
				$logger = $this->server->getLogger();
				if(\pocketmine\DEBUG > 1 and isset($pk)){
					$logger->debug("Exception in packet " . get_class($pk) . " 0x" . bin2hex($packet->buffer));
				}
				$logger->logException($e);
			}
		}
	}

	/**
	 * @param string $address
	 * @param int    $timeout
	 */
	public function blockAddress($address, $timeout = 300){
		$this->interface->blockAddress($address, $timeout);
	}

	/**
	 * @param $address
	 */
	public function unblockAddress($address){
		$this->interface->unblockAddress($address);
	}

	/**
	 * @param string $address
	 * @param int    $port
	 * @param string $payload
	 */
	public function handleRaw($address, $port, $payload){
		$this->server->handlePacket($address, $port, $payload);
	}

	/**
	 * @param string $address
	 * @param int    $port
	 * @param string $payload
	 */
	public function sendRawPacket($address, $port, $payload){
		$this->interface->sendRaw($address, $port, $payload);
	}

	/**
	 * @param string $identifier
	 * @param int    $identifierACK
	 */
	public function notifyACK($identifier, $identifierACK){

	}

	/**
	 * @param string $name
	 */
	public function setName($name){

		if($this->server->isDServerEnabled()){
			if($this->server->dserverConfig["motdMaxPlayers"] > 0) $pc = $this->server->dserverConfig["motdMaxPlayers"];
			elseif($this->server->dserverConfig["motdAllPlayers"]) $pc = $this->server->getDServerMaxPlayers();
			else $pc = $this->server->getMaxPlayers();

			if($this->server->dserverConfig["motdPlayers"]) $poc = $this->server->getDServerOnlinePlayers();
			else $poc = count($this->server->getOnlinePlayers());
		}else{
			$info = $this->server->getQueryInformation();
			$pc = $info->getMaxPlayerCount();
			$poc = $info->getPlayerCount();
		}

		$this->interface->sendOption("name",
			"MCPE;" . rtrim(addcslashes($name, ";"), '\\') . ";" .
			Info::CURRENT_PROTOCOL . ";" .
			Info::MINECRAFT_VERSION_NETWORK . ";" .
			$poc . ";" .
			$pc
		);
	}

	/**
	 * @param $name
	 */
	public function setPortCheck($name){
		$this->interface->sendOption("portChecking", (bool) $name);
	}

	/**
	 * @param string $name
	 * @param string $value
	 */
	public function handleOption($name, $value){
		if($name === "bandwidth"){
			$v = unserialize($value);
			$this->network->addStatistics($v["up"], $v["down"]);
		}
	}

	/**
	 * @param Player     $player
	 * @param DataPacket $packet
	 * @param bool       $needACK
	 * @param bool       $immediate
	 *
	 * @return int|null
	 */
	public function putPacket(Player $player, DataPacket $packet, $needACK = false, $immediate = false){
		if(isset($this->identifiers[$h = spl_object_hash($player)])){
			$identifier = $this->identifiers[$h];
			if(!$packet->isEncoded){
				$packet->encode();
				$packet->isEncoded = true;
			}

			if($packet instanceof BatchPacket){
				if($needACK){
					$pk = new EncapsulatedPacket();
					$pk->buffer = $packet->buffer;
					$pk->reliability = PacketReliability::RELIABLE_ORDERED;
					$pk->orderChannel = 0;

					if($needACK === true){
						$pk->identifierACK = $this->identifiersACK[$identifier]++;
					}
				}else{
					if(!isset($packet->__encapsulatedPacket)){
						$packet->__encapsulatedPacket = new CachedEncapsulatedPacket;
						$packet->__encapsulatedPacket->identifierACK = null;
						$packet->__encapsulatedPacket->buffer = $packet->buffer; // #blameshoghi
						$packet->__encapsulatedPacket->reliability = PacketReliability::RELIABLE_ORDERED;
						$packet->__encapsulatedPacket->orderChannel = 0;
					}
					$pk = $packet->__encapsulatedPacket;
				}

				$this->interface->sendEncapsulated($identifier, $pk, ($needACK === true ? RakLib::FLAG_NEED_ACK : 0) | ($immediate === true ? RakLib::PRIORITY_IMMEDIATE : RakLib::PRIORITY_NORMAL));
				return $pk->identifierACK;
			}else{
				$this->server->batchPackets([$player], [$packet], true);
				return null;
			}
		}

		return null;
	}

	/**
	 * @param $buffer
	 *
	 * @return null|DataPacket
	 */
	private function getPacket($buffer){
		$pid = ord($buffer[0]);
		if(($data = $this->network->getPacket($pid)) === null){
			return null;
		}
		$data->setBuffer($buffer, 1);

		return $data;
	}
}
