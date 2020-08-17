<?php
namespace LTMenu\Inventorys;

use LTMenu\Menus\Menu;
use pocketmine\inventory\BaseInventory;
use pocketmine\inventory\FakeBlockMenu;
use pocketmine\inventory\ContainerInventory;
use pocketmine\entity\Human;
use pocketmine\level\Position;
use pocketmine\inventory\InventoryType;

class DynamicInventory extends MenuInventory{
	//动态菜单
	public function __construct(Human $owner, Menu $menu, Position $pos){
		$this->owner = $owner;
		$this->menu = $menu;
		ContainerInventory::__construct(new FakeBlockMenu($this, $pos), InventoryType::get(InventoryType::MENU));
		$this->load();
	}
	public function jumpPage($action){
		if($action){
			if($this->page<$this->getMaxPage()-1){
				$this->page++;
			}
		}else
			$this->page--;
		$this->load();
	}
}