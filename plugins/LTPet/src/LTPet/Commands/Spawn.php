<?php
namespace LTPet\Commands;

use LTPet\Main;

class Spawn{
	public function __construct($plugin){
		$this->plugin=$plugin;
		unset($plugin);
	}
	public function run($args ,$player){
		if(count($args)<1){
			$player->sendMessage('§l§c[LT宠物系统]§c用法§d/宠物 复活 宠物名');
		}elseif(count($player->getPets())<=0){
			$player->sendMessage('§l§c[LT宠物系统]§c你还没有宠物哟！');
		}else{
			$name=Main::getCleanName($args[0]);
			if($player->getPet($name)===false){
				$player->sendMessage('§l§c[LT宠物系统]§c这个宠物不存在，输入§d/宠物 列表§c查看你的全部宠物！');
			}elseif($player->getPet($name)['hunger']>0){
				$player->sendMessage('§l§c[LT宠物系统]§c这个宠物很健康不需要复活！');
			}elseif($this->plugin->eAPI->myMoney($player)<100000){
				$player->sendMessage('§l§c[LT宠物系统]§c你的钱不够复活你的宠物！');
			}else{
				$info=$player->getPet($name);
				$info['hunger']=10000;
				$this->plugin->eAPI->reduceMoney($player, 100000, '宠物复活');
				$player->setPet($name, $info);
				$player->sendMessage('§l§c[LT宠物系统]§a复活成功！');
			}
		}
	}
}