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

class ChestInventory extends MenuInventory{
	public function __construct(Human $owner, Menu $menu){
		$this->owner = $owner;
		$this->menu = $menu;
		ContainerInventory::__construct(new FakeBlockMenu($this, $owner->getPosition()), InventoryType::get(InventoryType::MENU));
		$this->setContents($owner->getMenuInventory()->getItems(0));
	}
	
	public function jumpPage($action){
		$this->save();
		if($action)
			$this->page++;
		else
			$this->page--;
		$this->setContents($this->owner->getMenuInventory()->getItems($this->page));
	}
	public function event($event, $open){
		if($open->isDisable())return;
		$packet=$event->getPacket();
		$page=$this->page+1;
		if($page>$this->owner->isVIP() and $packet->item->getID()!==0){
			$this->setCancelled($event);
			return;
		}
		if($packet->slot==25 or $packet->slot==26){
			$this->setCancelled($event);
			if(!$this->owner->getInventory()->isNoFull())return $open->invError();
			if($open->getLastClick()!==$packet->slot)return $open->setLastClick($packet->slot);
			if($packet->slot==25){
				$open->setLastClick(null);
				if($this->page>0){
					$this->jumpPage(false);
				}else{
					$open->closeMultiLevel();
				}
				return;
			}elseif($packet->slot==26){
				if($this->page<2){
					$this->jumpPage(true);
					$open->setLastClick(null);
				}
				return;
			}
		}else $open->setLastClick(null);
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
		for($i=0;$i<25;$i++){
			$item=$this->getItem($i);
			$this->owner->getMenuInventory()->setItem($i+$this->page*25, $item);
		}
	}
}