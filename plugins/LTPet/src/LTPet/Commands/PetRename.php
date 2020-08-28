<?php
namespace LTPet\Commands;
use LTMenu\Open;
use LTPet\Main;
class PetRename{
	public function __construct($plugin){
		$this->plugin=$plugin;
		unset($plugin);
	}
	public function run($args ,$player){
		if(count($args)<2){
			return $player->sendMessage('§l§c[LT宠物系统]§c用法§d/宠物 改名 宠物名 新宠物名');
		}
		$name=Main::getCleanName($args[0]);
		if($player->getPet($name)===false){
			$player->sendMessage('§l§c[LT宠物系统]§c这个宠物不存在，输入§d/宠物 列表§c查看你的全部宠物！');
		}elseif(!Open::getNumber($player, ['材料','宠物改名卡',1])){
			$player->sendMessage('§l§c[LT宠物系统]§c你没有改名卡，不能重命名你的宠物！');
		}else{
			if($this->plugin->comes[$player->getName()]->getPet($name)!==false){
				$this->plugin->comes[$player->getName()]->getPet($name)->close();
			}
			$conf=$player->getPet($name);
			$player->removePet($name);
			$conf['name']=$args[1];
			Open::removeItem($player, ['材料','宠物改名卡',1]);
			$player->setPet(Main::getCleanName($args[1]),$conf);
			$player->sendMessage('§l§c[LT宠物系统]§a更名成功！');
		}
	}
}