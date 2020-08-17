<?php
namespace LTMenu\Inventorys;

use LTItem\Ornaments;
use pocketmine\inventory\InventoryType;
use pocketmine\inventory\BaseInventory;
use pocketmine\inventory\ContainerInventory;
use pocketmine\inventory\FakeBlockMenu;
use pocketmine\entity\Human;
use pocketmine\item\Item;
use pocketmine\Player;
use LTMenu\Menus\Menu;
use pocketmine\level\Position;
use LTItem\SpecialItems\BaseOrnaments;
use pocketmine\entity\Item as entityItem;

class OrnamentsInventory extends MenuInventory{
	public function __construct(Human $owner, Menu $menu){
		$this->owner = $owner;
		$this->menu = $menu;
		ContainerInventory::__construct(new FakeBlockMenu($this, $owner->getPosition()), InventoryType::get(InventoryType::MENU));
		$this->setContents($owner->getOrnamentsInventory()->getItems());
		$owner->getOrnamentsInventory()->onUse = true;
	}
	
	public function event($event, $open){
		if($open->isDisable())return;
		$packet=$event->getPacket();
		if($packet->slot==25 or $packet->slot==26){
			$this->setCancelled($event);
			if(!$this->owner->getInventory()->isNoFull())return $open->invError();
			if($open->getLastClick()!==$packet->slot)return $open->setLastClick($packet->slot);
			if($packet->slot==25){
				return;
			}elseif($packet->slot==26){
				$open->setLastClick(null);
				$open->closeMultiLevel();
				return;
			}
		}else $open->setLastClick(null);
	}
	
	public function StaticClose(Player $who){
		$this->save();
		$who->getBuff()->updateBuff();
		parent::StaticClose($who);
	}
	public function onClose(Player $who){
		$this->save();
		$who->getBuff()->updateBuff();
		parent::onClose($who);
	}
	public function save(){
		for($i=0;$i<25;$i++){
			$item=$this->getItem($i);
			if(!($item instanceof Ornaments) and $item->getId()!=0){
				if($this->owner->getInventory()->canAddItem($item)){
					$this->owner->getInventory()->addItem($item);
				}else{
					$dropItem=$this->owner->level->dropItem($this->owner, $item);
					if($dropItem instanceof entityItem)$dropItem->setOwner(strtolower($this->owner->getName()));
				}
				if($item->getId()!=0){
					$this->owner->getOrnamentsInventory()->setItem($i, Item::get(0));
				}
				continue;
			}
			$this->owner->getOrnamentsInventory()->setItem($i, $item);
		}
        $this->owner->getOrnamentsInventory()->onUse = false;
	}
}