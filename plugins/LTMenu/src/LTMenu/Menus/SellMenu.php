<?php
namespace LTMenu\Menus;

use pocketmine\item\Item;
use LTMenu\MenuItem;
use LTMenu\Menus\Menu;

class SellMenu extends Menu{
	public function __construct($items, $conf){
		$this->MenuName=$conf->get('MenuName');
		$this->config=$conf;
		$this->type=$conf->get('InventoryType');
		$this->initFunction();
	}
	public function initFunction(){
		$item=MenuItem::getMenuItem('339:0:1');
		$item->setEvent(['multilevel'=>true]);
//var_dump(Menu::getMenu($this->config->get('items', []), $this->config));
		$conf=clone $this->config;
		$conf->set('InventoryType', 0);
		$conf->set('MenuType', 0);
		$item->setMultilevel(Menu::getMenu($this->config->get('items', []), $conf));
		if($this->config->get('name')=='Sell'){
			\LTMenu\Inventorys\SellInventory::$priceMenu=$item->getMultilevel();
		}
		$item->setCustomName("§l§o§d价格表,再次点击打开!");
		$this->functions[]=$item;
		$item=Item::get(339,0,1);
		$item->setCustomName("§l§o§d说明\n§a将你需要出售或分解的物品放入菜单,然后点击雪球确认");
		$this->functions[]=$item;
		$item=Item::get(404,0,1);
		$item->setCustomName("§l§o§d上一页\n§a再次点击返回上一页");
		$this->functions[]=$item;
		$item=Item::get(332,0,1);
		$item->setCustomName("§l§o§d确认出售\n§a再次点击执行");
		$this->functions[]=$item;
	}
}