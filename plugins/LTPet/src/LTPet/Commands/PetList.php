<?php
namespace LTPet\Commands;
class PetList{
	public function __construct($plugin){
		$this->plugin=$plugin;
		unset($plugin);
	}
	public function run($args ,$player){
		$pets=$this->plugin->players[$player->getName()] ?? false;
		if(count($player->getPets())<=0)return $player->sendMessage('§l§a[LT宠物系统]§c你没有宠物哟！');
		$player->sendMessage('§l§a[LT宠物系统]§e你的全部宠物:');
		foreach($player->getPets() as $name=>$mess)
			$player->sendMessage(('§d名字:'.$name."§r属性: \n§3★饥饿度:".$mess['hunger']."❤爱心度:".($mess['love']??0)));
	}
}
