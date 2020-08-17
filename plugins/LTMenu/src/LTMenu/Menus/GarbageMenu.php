<?php
namespace LTMenu\Menus;

use pocketmine\item\Item;

class GarbageMenu extends Menu{
	public function initFunction(){
		$item=Item::get(404,0,1);
		$item->setCustomName("§l§o§d上一页\n§a再次点击返回上一页");
		$this->functions[]=$item;
		$item=Item::get(332,0,1);
		$item->setCustomName("§l§o§d清空垃圾箱\n§a再次点击清空");
		$this->functions[]=$item;
	}
}