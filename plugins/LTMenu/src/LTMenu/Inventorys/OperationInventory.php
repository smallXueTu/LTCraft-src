<?php
namespace LTMenu\Inventorys;

use pocketmine\entity\Human;
use pocketmine\Player;
use LTMenu\Menus\Menu;
use pocketmine\item\Item;
use onebone\economyapi\EconomyAPI;

class OperationInventory extends MenuInventory{
    public int $funCount;//功能物品数量 必须实现的
	public function sendPlayerItem(){
		$index=0;
		$max=25-$this->funCount;
		while($index<=$max){
			if($this->getItem($index) instanceof Item and $this->getItem($index)->getID()>0){
				$this->getOwner()->getInventory()->addItem($this->getItem($index));
				// $this->setItem($index, Item::get(0));
			}
			$index++;
		}
	}
	public function StaticClose(Player $who){
		$this->sendPlayerItem();
		parent::StaticClose($who);
	}

	public function onClose(Player $who){
		$this->sendPlayerItem();
		parent::onClose($who);
	}
}