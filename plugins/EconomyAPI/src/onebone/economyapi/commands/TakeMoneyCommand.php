<?php
namespace onebone\economyapi\commands;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;

use onebone\economyapi\EconomyAPI;

class TakeMoneyCommand extends EconomyAPICommand{
	public function __construct(EconomyAPI $plugin, $cmd = 'takemoney'){
		parent::__construct($cmd, $plugin);
		$this->setUsage('/偷钱 <玩家> <数量>');
		$this->setPermission('economyapi.command.takemoney');
		$this->setDescription('偷钱命令');
	}
	
	public function execute(CommandSender $sender, $label, array $params){
		if(!$this->testPermission($sender)){
			return false;
		}
		$player = array_shift($params);
		$amount = array_shift($params);
		if(trim($player) === '' or trim($amount) === '' or !is_numeric($amount)){
			$sender->sendMessage('§l§a[LTcraft温馨提示]§用法: /偷钱 <玩家> <数量>');
			return true;
		}
		
//        if($sender->getName() !== 'Angel_XX' AND $sender instanceof \pocketmine\Player and $sender->isOp()){
//			return $sender->sendMessage('§l§a[LTcraft温馨提示]§cOP不能用这个命令！');
//		}
		if($amount <= 0){
			$sender->sendMessage('§l§a[LTcraft温馨提示]§请输入大于0的数字');
			return true;
		}
		$server = Server::getInstance();
		$p = $server->getPlayer($player);
		if($p instanceof Player)
			$player = $p->getName();
		$result = $this->getPlugin()->reduceMoney($player, $amount, '管理员偷钱');
		if($result)
			$sender->sendMessage('§l§a[LTcraft温馨提示]§a成功偷走玩家'.$player.' '.$amount.'橙币');
		elseif($result==2)
			return $sender->sendMessage('§l§a[LTcraft温馨提示]§c服务器不存在这个玩家！');
		elseif($result==3)
			return $sender->sendMessage('§l§a[LTcraft温馨提示]§c玩家没怎么多钱！');
		return true;
	}
}