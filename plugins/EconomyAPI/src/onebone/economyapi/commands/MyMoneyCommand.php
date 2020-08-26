<?php

namespace onebone\economyapi\commands;

use pocketmine\command\CommandSender;
use pocketmine\Player;

use onebone\economyapi\EconomyAPI;

class MyMoneyCommand extends EconomyAPICommand{
	private $plugin;
	
	public function __construct(EconomyAPI $api, $cmd = "mymoney"){
		parent::__construct($cmd, $api);
		$this->setUsage("/钱包");
		$this->setDescription("显示自己的橙币");
		$this->setPermission("economyapi.command.mymoney");
	}
	
	public function execute(CommandSender $sender, $label, array $args){
		if(!$this->getPlugin()->isEnabled()){
			return false;
		}
		if(!$this->testPermission($sender)){
			return false;
		}
		
		if(!$sender instanceof Player){
			$sender->sendMessage('§l§a[提示]§c请在游戏里运行该命令');
			return true;
		}
		$username = $sender->getName();
		$result = $this->getPlugin()->myMoney($username);
		$sender->sendMessage('§l§a[提示]§a你有'.$result.'橙币');
	}
}