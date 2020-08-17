<?php
namespace LTPet\Commands;

use LTPet\Main;

class Give{
	public function __construct($plugin){
		$this->plugin=$plugin;
		unset($plugin);
	}
	public function run($args ,$player){
		if(count($args)<2)return $player->sendMessage('§l§a[LT宠物系统]§c用法§d/宠物 赠送 玩家名字 宠物名字');
		$name=Main::getCleanName($args[1]);
		if($player->getPet($name)===false){
			$player->sendMessage('§l§a[LT宠物系统]§c这个宠物不存在，输入§d/宠物 列表§c查看你的全部宠物！');
		}elseif(($p=$this->plugin->server->getPlayer($args[0]))==null){
			$player->sendMessage('§l§a[LT宠物系统]§c目标玩家不在线！');
		}else{
			$this->plugin->comes[$player->getName()]->closePet($name);
			$info=$player->getPet($name);
			$player->removePet($name);
			if($p->getPet($name)!==false)$name=$name.mt_rand(0,100);
			$p->setPet($name,$info);
			$p->sendMessage('§l§a[LT宠物系统]§a有人给了你一个宠物 名字为:'.$name.',输入§d/宠物 列表§c查看吧');
			$player->sendMessage('§l§a[LT宠物系统]§a成功赐予玩家宠物。');
		}
	}
}