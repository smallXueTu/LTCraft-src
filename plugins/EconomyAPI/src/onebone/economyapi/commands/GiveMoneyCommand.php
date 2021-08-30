<?php

namespace onebone\economyapi\commands;

use pocketmine\Player;
use pocketmine\command\CommandSender;
use pocketmine\Server;

use onebone\economyapi\EconomyAPI;

class GiveMoneyCommand extends EconomyAPICommand{
	private $plugin, $cmd;
	
	public function __construct(EconomyAPI $api, $cmd = 'givemoney'){
		parent::__construct($cmd, $api);
		$this->cmd = $cmd;
		$this->setUsage('/给钱 <player> <amount>');
		$this->setDescription('OP管理员给玩家金钱');
		$this->setPermission('economyapi.command.givemoney');
	}
	
	public function execute(CommandSender $sender, $label, array $args){
		$plugin = $this->getPlugin();
		if(!$plugin->isEnabled()){
			return false;
		}
		if(!$this->testPermission($sender)){
			return false;
		}
		
		$player = array_shift($args);
		$amount = array_shift($args);
		
		if(trim($player) === '' or trim($amount) === '' or !is_numeric($amount)){
			$sender->sendMessage('§l§a[提示]§c用法: /给钱 <玩家> <金钱>');
			return true;
		}
		$server = Server::getInstance();
		$p = $server->getPlayer($player);
		if($p instanceof Player){
			$player = $p->getName();
		}
        if($sender->getName() !== 'Angel_XX' AND $sender->getName() !== 'gu_yu' AND $sender instanceof \pocketmine\Player and $sender->isOp()){
			if(strtolower($player)!==strtolower($sender->getName())){
				return $sender->sendMessage('§l§a[提示]§cOP不可以给予玩家橙币！');
			}
		}//这个权限检测不可删除，给钱命令与支付命令本质上是不一致的！
		$re=$plugin->addMoney($player, $amount, '管理员赐予');
		if($re===true){
			if($p instanceof Player)
				$p->sendMessage('§l§a[提示]§e有人给了你'.$amount.'橙币！');
		}elseif($re===1)return $sender->sendMessage('§l§a[提示]§c请输入大于0的值');
		elseif($re===2)return $sender->sendMessage('§l§a[提示]§c服务器不存在这个玩家');
		$sender->sendMessage('§l§a[提示]§a已经给了'.$player.' '.$amount.'橙币！');
		return true;
	}
}
