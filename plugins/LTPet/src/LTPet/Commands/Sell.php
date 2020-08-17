<?php
namespace LTPet\Commands;
class Sell{
	public function __construct($plugin){
		$this->plugin=$plugin;
		unset($plugin);
	}
	public function run($args ,$player){
		return $player->sendMessage('§l§c[LT宠物系统]§c敬请期待');
		if(count($args)<1)return $player->sendMessage('§l§c[LT宠物系统]§c用法:/pet sell 宠物名字，来出售宠物！');
		//TODO
	}
}