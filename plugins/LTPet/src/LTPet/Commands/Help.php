<?php
namespace LTPet\Commands;
class Help{
	public function __construct($plugin){
		$this->plugin=$plugin;
		unset($plugin);
	}
	public function run($args ,$player){
		$player->sendMessage('§l§1--§2--§3--§4--§5--§d宠物帮助§5--§4--§3--§2--§1--');
		$player->sendMessage('§l§e查看你购买的全部宠物:§d/宠物 列表');
		$player->sendMessage('§l§e查看你召唤的全部宠物:§d/宠物 召唤列表');
		$player->sendMessage('§l§e收回你的宠物:§d/宠物 回收 宠物名字');
		$player->sendMessage('§l§e修改宠物名字:§d/宠物 改名 旧名字 新名字');
		$player->sendMessage('§l§e召唤你的宠物:§d/宠物 召唤 宠物名字');
		$player->sendMessage('§l§e复活你的宠物:§d/宠物 复活 宠物名字 需要100000橙币');
		$player->sendMessage('§l§e赠送你的宠物:§d/宠物 赠送 宠物名字');
		$player->sendMessage('§l§e收回你全部召唤的宠物:§d/宠物 回收全部');
		$player->sendMessage('§l§e更换女仆皮肤:§d/宠物 皮肤 女仆名字');
		$player->sendMessage('§l§e修复宠物名字:§d/宠物 修复');
	}
}