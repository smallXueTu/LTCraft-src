<?php
namespace LTPet\Commands;

use LTPet\Main;

class Recovery{
	public function __construct($plugin){
		$this->plugin=$plugin;
		unset($plugin);
	}
	public function run($args ,$player){
		if(count($args)<1){
			$player->sendMessage('§l§a[LT宠物系统]§c用法:§d/宠物 回收 宠物名字');
		}elseif(($pet=$this->plugin->comes[$player->getName()]->getPet(Main::getCleanName($args[0])))==null){
			$player->sendMessage('§l§a[LT宠物系统]§c你没有这个宠物或者没召唤，输入§d/宠物 召唤列表§c查看你召唤的宠物吧');
		}else{
			$pet->close();
			$player->sendMessage('§l§a[LT宠物系统]§a回收宠物成功！');
		}
	}
}