<?php
namespace LTPet\Commands;
class CList{
	public function __construct($plugin){
		$this->plugin=$plugin;
		unset($plugin);
	}
	public function run($args ,$player){
		$player->sendMessage('§l§a[LT宠物系统]§e你的宠物:');
		if($this->plugin->comes[$player->getName()]->getCount()<=0){
			return $player->sendMessage('§l§a[LT宠物系统]§c你还没有宠物被召唤！');
		}
		foreach($this->plugin->comes[$player->getName()]->getPets() as $name=>$pet){
			$player->sendMessage('§l§a[LT宠物系统]§d宠物名字:'.$name.'§r 饥饿度:'.$pet->getAtt()->getHunger()/100 .'%');
		}
	}
}