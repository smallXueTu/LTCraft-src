<?php
namespace LTPet\Commands;
class Comes{
	public function __construct($plugin){
		$this->plugin=$plugin;
		unset($plugin);
	}
	public function run($args ,$player){
	$player->sendMessage('§l§a[LT宠物系统]§e你的宠物:');
	if(!isset($this->plugin->playerPets[$player->getName()]))return $player->sendMessage('§l§a[LT宠物系统]§c你还没有宠物被召唤！');
		foreach($this->plugin->playerPets[$player->getName()] as $name=>$pet){
			$player->sendMessage('§l§a[LT宠物系统]§d宠物名字:'.$name);
		}
	}
}