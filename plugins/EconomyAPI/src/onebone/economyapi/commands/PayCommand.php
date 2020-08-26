<?php

namespace onebone\economyapi\commands;

use pocketmine\Server;
use pocketmine\command\CommandSender;
use pocketmine\Player;

use onebone\economyapi\EconomyAPI;

class PayCommand extends EconomyAPICommand{
	private $plugin;
	
	public function __construct(EconomyAPI $plugin, $cmd = 'pay'){
		parent::__construct($cmd, $plugin);
		$this->setUsage('/支付 <玩家> <数量>');
		$this->setPermission('economyapi.command.pay');
		$this->setDescription('给其他玩家橙币');
	}
	
	public function execute(CommandSender $sender, $label, array $params){
		$plugin = $this->getPlugin();
		if(!$plugin->isEnabled()){
			return false;
		}
		if(!$this->testPermission($sender)){
			return false;
		}
		if(!$sender instanceof Player){
			$sender->sendMessage('请在游戏里运行该命令');
			return true;
		}
		
		$player = array_shift($params);
		$amount = array_shift($params);
		
		if(trim($player) === '' or trim($amount) === '' or !is_numeric($amount)){
			$sender->sendMessage('用法: '.$this->getUsage());
			return true;
		}
//        if($sender->getName() !== 'Angel_XX' AND $sender instanceof \pocketmine\Player and $sender->isOp()){
//			return $sender->sendMessage('§l§a[提示]§cOP不能用这个命令！');
//		}
		$amount = (int)$amount;
		$server = Server::getInstance();
		//  Player finder  //
		$p = $server->getPlayer($player);
		if($p instanceof Player){
			$player = $p->getName();
		}
		// END //
		$name=$player instanceof Player?$player->getName():$player;
		$result = $plugin->reduceMoney($sender, $amount, '支付给'.$name);
		if($result === 1){
			$sender->sendMessage('§l§a[提示]§c请输入正整数 !');
			return;
		}elseif($result === 2){
			$sender->sendMessage('§l§a[提示]§c错误了 报告给管理员 错误码 2 !');
			return;
		}elseif($result === 3){
			$sender->sendMessage('§l§a[提示]§c你没有怎么多钱！ !');
			return;
		}
		$result=$plugin->addMoney($player, $amount, '玩家'.$sender->getName().'支付');
		if($result !== true){
			if($result === 1){
				$sender->sendMessage('§l§a[提示]§c请输入正整数 !');
				$plugin->addMoney($sender, $amount, '支付给'.$name.'失败回滚');
				return;
			}elseif($result === 2){
				$sender->sendMessage('§l§a[提示]§c服务器不存在这个玩家 !');
				$plugin->addMoney($sender, $amount, '支付给'.$name.'失败回滚');
				return;
			}
			return true;
		}
		$sender->sendMessage('§l§a[提示]§a支付了'.$amount.'橙币给'.$player);
		if($p instanceof Player)$p->sendMessage('§l§a[提示]§a'.$sender->getName().'给您支付了'.$amount.'橙币');
	}
}