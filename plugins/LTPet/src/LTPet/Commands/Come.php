<?php
namespace LTPet\Commands;

use LTPet\Main;
use LTPet\Pet;

class Come{
	public function __construct($plugin){
		$this->plugin=$plugin;
		unset($plugin);
	}
	public function run($args ,$player){
		if(count($args)<1){
			return $player->sendMessage('§l§c[LT宠物系统]§c用法§d/宠物 召唤§c宠物名');
		}
		$name=Main::getCleanName($args[0]);
		if($player->getPet($name)===false){
			$player->sendMessage('§l§a[LT宠物系统]§c这个宠物不存在，输入§d/宠物 列表§c查看你的全部宠物！');
		}elseif($player->getPet($name)['hunger']<=0){
			$player->sendMessage('§l§a[LT宠物系统]§c这个宠物已经死亡了，输入§d/宠物 复活 '.$name.'§c来复活这个宠物！');
		}elseif(\LTCraft\Main::getInstance()->getMode()==1){
			$player->sendMessage('§l§a[LT宠物系统]§c当前不可以召唤宠物！');
		}elseif(in_array($player->level->getName(), ['boss', 'pvp'])){
			$player->sendMessage('§l§a[LT宠物系统]§c这个世界不能召唤宠物！');
		}elseif($this->plugin->comes[$player->getName()]->getPet($name)!==null){
			$player->sendMessage('§l§a[LT宠物系统]§c你已经召唤过这个宠物了！');
		}elseif(Pet::Come($player,$player->getPet($name))){
			$player->sendMessage('§l§a[LT宠物系统]§a成功召唤宠物:'.$name);
		}else{
			$player->sendMessage('§l§a[LT宠物系统]§a召唤失败! 未知类型');
		}
	}
}