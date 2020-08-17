<?php
namespace LTMenu\Inventorys;

use pocketmine\item\Item;

class GarbageInventory extends MenuInventory{//垃圾菜单
	public function event($event, $open){
		$packet=$event->getPacket();
		if($this->getItem($packet->slot)->getId() == 0)return;
		if(!$this->getOwner()->getInventory()->isNoFull())return $open->invError();
		if($packet->slot==25 or $packet->slot==26){
			$this->setCancelled($event);
			if($open->getLastClick()!==$packet->slot)return $open->setLastClick($packet->slot);
			if($packet->slot==25){
				$open->closeMultiLevel();
			}else{
				$index=0;
				while($index<=24)
					$this->setItem($index++,item::get(0));
				$open->setLastClick(null);
			}
		}
		return;
	}
}