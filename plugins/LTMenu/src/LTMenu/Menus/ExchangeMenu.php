<?php
namespace LTMenu\Menus;

use pocketmine\item\Item;

class ExchangeMenu extends Menu{
	public function initFunction(){
		$item=Item::get(404,0,1);
		$item->setCustomName("§l§o§d上一页\n§a再次点击返回上一页");
		$this->functions[]=$item;
		$item=Item::get(339,0,1);
		$item->setCustomName("§l§o§d刷新交易所");
		$this->functions[]=$item;
		$item=Item::get(356,0,1);
		$item->setCustomName("§l§o§d下一页\n§a再次点击前往下一页");
		$this->functions[]=$item;
	}
}