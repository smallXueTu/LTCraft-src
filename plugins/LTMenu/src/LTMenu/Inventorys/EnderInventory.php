<?php
namespace LTMenu\Inventorys;

use pocketmine\inventory\InventoryType;
use pocketmine\inventory\BaseInventory;
use pocketmine\inventory\ContainerInventory;
use pocketmine\inventory\FakeBlockMenu;
use pocketmine\entity\Human;
use pocketmine\Player;
use LTMenu\Menus\Menu;
use pocketmine\level\Position;

class EnderInventory extends MenuInventory{
	public function __construct(Human $owner, Menu $menu){
		$this->owner = $owner;
		$this->menu = $menu;
		ContainerInventory::__construct(new FakeBlockMenu($this, $owner->getPosition()), InventoryType::get(InventoryType::ENDER_CHEST), $owner->getEnderChestInventory()->getContents());
	}

	public function jumpPage($action){
		
	}

	public function event($event, $open){
		
	}
	
	public function StaticClose(Player $who){
		$this->save();
		parent::StaticClose($who);
	}
	public function onClose(Player $who){
		$this->save();
		parent::onClose($who);
	}
	public function save(){
		$this->owner->getEnderChestInventory()->setContents($this->getContents());
	}
}