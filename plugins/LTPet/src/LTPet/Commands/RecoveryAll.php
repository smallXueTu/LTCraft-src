<?php
namespace LTPet\Commands;
class RecoveryAll{
	public function __construct($plugin){
		$this->plugin=$plugin;
		unset($plugin);
	}
	public function run($args ,$player){
		foreach($this->plugin->comes[$player->getName()]->getPets() as $pet)
			$pet->close();
		return $player->sendMessage('§l§a[LT宠物系统]§a成功回收你的全部宠物');
	}
}
