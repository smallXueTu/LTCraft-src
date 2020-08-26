<?php

namespace onebone\economyapi\commands;

use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\Player;

use onebone\economyapi\EconomyAPI;

class SeeMoneyCommand extends EconomyAPICommand{
	private $plugin, $cmd;
	
	public function __construct(EconomyAPI $plugin, $cmd = 'seemoney'){
		parent::__construct($cmd, $plugin);
		$this->cmd = $cmd;
		$this->setUsage('/查询 <player>');
		$this->setDescription('查询某个玩家的橙币');
		$this->setPermission('economyapi.command.seemoney');
	}
	
	public function execute(CommandSender $sender, $label, array $args){
		if(!$this->getPlugin()->isEnabled()){
			return false;
		}
		if(!$this->testPermission($sender)){
			return false;
		}
		
		$player = array_shift($args);
		if(trim($player) === ''){
			$sender->sendMessage('§l§a[提示]§c用法: /查询 <玩家>');
			return true;
		}
		
		//  Player finder  //
		$server = Server::getInstance();
		$p = $server->getPlayer($player);
		if($p instanceof Player){
			$player = $p->getName();
		}
		// END //
		$result = $this->getPlugin()->myMoney($player);
		if($result === false){
			$sender->sendMessage('§l§a[提示]§c服务器不存在这个玩家！');
			return true;
		}else{
			$sender->sendMessage('§l§a[提示]§a'.$player.'一共有'.$result.'橙币');
			return true;
		}
	}
}