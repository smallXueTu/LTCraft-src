<?php
namespace LTPet\Commands;

use LTPet\Main;

class FixPetConfig{
	public function __construct($plugin){
		$this->plugin=$plugin;
		unset($plugin);
	}
	public function run($args ,$player){
		if(count($player->getPets())<=0)return $player->sendMessage('§l§a[LT宠物系统]§c你没有宠物哟！');
		foreach($this->plugin->playerPets[$player->getName()]??[] as $pet)$pet->close();
		foreach($player->getPets() as $name=>$arr){
			$player->removePet($name);
			$name=Main::getCleanName($name);
			$player->setPet($name, $arr);
		}
		$player->sendMessage('§l§a[LT宠物系统]§aOK');
	}
}