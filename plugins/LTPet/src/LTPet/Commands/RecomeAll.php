<?php
namespace LTPet\Commands;
class RecomeAll{
	public function __construct($plugin){
		$this->plugin=$plugin;
		unset($plugin);
	}
	public function run($args ,$player){
		foreach($this->plugin->playerPets[$player->getName()] as $pet)
			$pet->close();
		return $player->sendMessage('§l§a[LT宠物系统]§a成功杀死你的全部宠物');
	}
}
