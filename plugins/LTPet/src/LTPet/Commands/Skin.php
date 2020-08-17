<?php
namespace LTPet\Commands;

use LTPet\Main;
use LTPet\Pet;

class Skin{
	public function __construct($plugin){
		$this->plugin=$plugin;
		unset($plugin);
	}
	public function run($args ,$player){
		if(count($args)<1)return $player->sendMessage('§l§c[LT宠物系统]§c用法§d/宠物 皮肤 [购买/使用] 皮肤名字 女仆名字');
		switch($args[0]){
			case '使用':
				if(count($args)<3)return $player->sendMessage('§l§c[LT宠物系统]§c用法§d/宠物 皮肤 使用 皮肤名字 女仆名字');
				$all=$this->plugin->PlayerSkins->get(strtolower($player->getName()), []);
				if(is_array($all) and isset($all[$args[1]])){
					$name=Main::getCleanName($args[2]);
					if(($conf=$player->getPet($name))!==false and $conf['type']==='女仆'){
						$isComae=$this->plugin->comes[$player->getName()]->closePet($name);
						$conf['skin']=$args[1];
						$player->setPet($name, $conf);
						if($isComae)Pet::Come($player,$player->getPet($name));
						$player->sendMessage('§l§c[LT宠物系统]§a更换皮肤成功！');
					}else{
						$player->sendMessage('§l§a[LT宠物系统]§c这个女仆不存在，输入§d/宠物 列表§c查看你的全部宠物！');
					}
				}else{
					$player->sendMessage('§l§c[LT宠物系统]§c你不存在这个皮肤！');
				}
			break;
			case '购买':
				switch($args[1]){
					case '':
						
					break;
				}
			break;
			default:
				return $player->sendMessage('§l§c[LT宠物系统]§c用法§d/宠物 皮肤 [购买/使用] 皮肤名字 女仆名字');
			break;
		}
	}
}