<?php
namespace LTPet\Commands;
class Recome{
	public function __construct($plugin){
		$this->plugin=$plugin;
		unset($plugin);
	}
	public function run($args ,$player){
		if(count($args)<1)return $player->sendMessage('§l§a[LT宠物系统]§c用法:/pet recome 宠物名字');
		$petName=preg_replace('#§.#','',strtolower($args[0]));
		if(!isset($this->plugin->playerPets[$player->getName()][$petName]))return $player->sendMessage('§l§a[LT宠物系统]§c你没有这个宠物或者没召唤，输入/pet comes查看你召唤的宠物吧');
		$this->plugin->playerPets[$player->getName()][$petName]->close();
		return $player->sendMessage('§l§a[LT宠物系统]§a回收宠物成功！');
	}
}