<?php

namespace onebone\economyapi\commands;

use pocketmine\command\CommandSender;
use pocketmine\Server;

use onebone\economyapi\EconomyAPI;

class TopMoneyCommand extends EconomyAPICommand{
	private $plugin, $cmd;
	
	public function __construct(EconomyAPI $plugin, $cmd = "topmoney"){
		parent::__construct($cmd, $plugin);
		$this->plugin = $plugin;
		$this->setUsage("/富豪榜 <page>");
		$this->setDescription("显示玩家的橙币排行榜");
		$this->setPermission("economyapi.command.topmoney");
	}
	
	public function execute(CommandSender $sender, $label, array $params){
		if(!$this->getPlugin()->isEnabled() or !$this->testPermission($sender)){
			return false;
		}
		
		$page = array_shift($params);
		
		$moneyData = $this->getPlugin()->getAllMoney();
		
		$server = Server::getInstance();
		$banList = $server->getNameBans(); // TODO TopMoney Command
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
		$sender->sendMessage($output);
		return true;
	}
}