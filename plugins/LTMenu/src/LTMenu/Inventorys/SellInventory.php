<?php
namespace LTMenu\Inventorys;

use pocketmine\entity\Human;
use pocketmine\Player;
use LTItem\SpecialItems\Weapon;
use LTMenu\Menus\Menu;
use pocketmine\item\Item;
use onebone\economyapi\EconomyAPI;

class SellInventory extends OperationInventory{
	public static $priceMenu;
    public int $funCount = 4;
	public function event($event, $open){
		$packet=$event->getPacket();
		if($this->getItem($packet->slot)->getId() == 0)return;
		if(!$this->getOwner()->getInventory()->isNoFull())return $open->invError();
		if($packet->slot==24 or $this->getOwner()->getGamemode()!==0){
			return $this->setCancelled($event);
		}
		if($packet->slot==23 or $packet->slot==25 or $packet->slot==26 or $packet->slot==24){
			$this->setCancelled($event);
			if($open->getLastClick()!==$packet->slot)return $open->setLastClick($packet->slot);
			if($packet->slot==25){
				$open->closeMultiLevel();
			}elseif($packet->slot==26){
				$totalMoney=0;
				$index=0;
				while($index<=22){
					$i=$this->getItem($index++);
					if($i->getId()===0)continue;
					if($i instanceof Weapon){
						if($i->getDecomposition()!==false and $i->getDecomposition() instanceof Item){
							$this->setItem($index-1, $i->getDecomposition());
						}
						continue;
					}
					$money=$open->getPlugin()->getMoney($i, $this->getItem(23)->getMultilevel());
					if($money!==0){
						$this->setItem($index-1, Item::get(0));
						$totalMoney+=$money;
						$this->getOwner()->getTask()->action('出售完成', [$i, $i->getCount()]);
					}
				}
				if($totalMoney!==0)EconomyAPI::getInstance()->addMoney($this->getOwner(), $totalMoney, '售卖物品获得');
				$open->setLastClick(null);
			}elseif($packet->slot==23){
				if($this->getItem(23)->getMultilevel()!==false){
					$open->openMultiLevel($this->getItem(23)->getMultilevel());
				}
			}
		}
		return;
	}
}