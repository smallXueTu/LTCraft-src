<?php
namespace LTMenu\Inventorys;

use LTMenu\MenuItem;
use LTEntity\Main as LTEntity;

class ValidationInventory extends DynamicInventory{
	public function load(){
		$index = mt_rand(0, 26);
		for($i=0;$i<27;$i++){
			if($i==$index){
				$item=MenuItem::getMenuItem('388');
				$item->setEvent(array(
					'commands'=>null,
					'teleport'=>null,
					'cost_item'=>null,
					'give_item'=>null,
					'multilevel'=>false,
					'price'=>0
				));
				$this->setItem($i,$item->setCustomName('§a验证成功！'));
			}else{
				$item=MenuItem::getMenuItem('35:14');
				$item->setEvent(array(
					'commands'=>null,
					'teleport'=>null,
					'cost_item'=>null,
					'give_item'=>null,
					'multilevel'=>false,
					'price'=>0
				));
				$this->setItem($i,$item->setCustomName('§c请点击绿宝石！'));
			}
		}
	}
	public function event($event, $open){
		$this->setCancelled($event);
		if($open->isDisable())return;
		$packet=$event->getPacket();
		if(!$this->owner->getInventory()->isNoFull())return $open->invError();
		if($open->getLastClick()!==$packet->slot)return $open->setLastClick($packet->slot);
		if($this->getItem($packet->slot)->getID()==388){
            LTEntity::resetCount($this->owner->getName());
			$this->owner->sendTitle('§a重置成功！');
			$open->close();
            LTEntity::resetErrorCount($this->owner->getName());
		}else{
			$this->load();
            LTEntity::addErrorCount($this->owner);
		}
		$open->setLastClick($packet->slot);
	}
}