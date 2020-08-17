<?php
namespace LTMenu\Menus;

use pocketmine\item\Item;

class InlayMenu extends Menu{
	public function initFunction(){
		$item=Item::get(339,0,1);
		$item->setCustomName("§l§o§d说明\n§a菜单第一个放武器\n§a后面放需要镶嵌的材料\n然后点击雪球确认操作\n§c注意！\n§c请到主城查看武器是否支持此操作");
		$this->functions[]=$item;
		$item=Item::get(404,0,1);
		$item->setCustomName("§l§o§d上一页\n§a再次点击返回上一页");
		$this->functions[]=$item;
		$item=Item::get(332,0,1);
		$item->setCustomName("§l§o§d开始镶嵌\n§a再次点击执行\n§c请到主城找武器商人\n查看武器是否支持此操作");
		$this->functions[]=$item;
	}
}