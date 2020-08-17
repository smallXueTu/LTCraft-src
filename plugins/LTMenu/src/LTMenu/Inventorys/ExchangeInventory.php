<?php
namespace LTMenu\Inventorys;

use LTMenu\MenuItem;
use LTMenu\Main as LTMenu;
use pocketmine\item\Item;
use LTMenu\Open;
use onebone\economyapi\EconomyAPI;
use LTItem\Main as LTItem;

class ExchangeInventory extends DynamicInventory{
	public function load(){
		$Exchange=LTMenu::getInstance()->getExchange();
		$Goods=$Exchange->getGoods();
		$this->setContents($this->menu->getItem($this->page));
		$index=$this->page*24;
		$i=0;
		$c=0;
		foreach($Goods as $id=>$Good){
			if($Good->getEvent()['time']<time()){
				\LTCraft\Main::sendItem($Good->getEvent()['player'], explode(':',  $Good->getEvent()['give_item']));
				\LTCraft\Main::sendMessage($Good->getEvent()['player'], '§a§l你在交易所上架的'.$Good->getEvent()['give_item'].'上架时间已经到期！');
				// if((int)$Good->getEvent()['price']!=0 and $Good->getEvent()['price']>0){
					// EconomyAPI::getInstance()->addMoney($Good->getEvent()['player'], abs($Good->getEvent()['price']), '交易退还');
				// }
				LTMenu::getInstance()->getExchange()->removeGood($id);
				continue;
			}
			if($c++<$index)continue;
			if($index++>$this->page*24+24)break;
			$this->setItem($i++, $Good);
			if($i==24)break;
		}
	}
	public function event($event, $open){
		$packet=$event->getPacket();
		if($this->getItem($packet->slot)->getId() == 0)return $this->setCancelled($event); 
		$this->setCancelled($event);
		if(!$this->getOwner()->getInventory()->isNoFull())return $open->invError();
//		if($this->getOwner()->isOp() and $this->getOwner()->getName()!=='Angel_XX')return $open->error();
		if($packet->slot==24){
			if($open->getLastClick()!==$packet->slot)return $open->setLastClick($packet->slot);
			if($this->page>0){
				$this->jumpPage(false);
			}else{
				$open->closeMultiLevel();
			}
		}elseif($packet->slot==25){
			$this->load();
		}elseif($packet->slot==26){
			if($open->getLastClick()!==$packet->slot)return $open->setLastClick($packet->slot);
			$this->jumpPage(true);
		}else{
			$item=$this->getItem($packet->slot);
			// if($item->getID()==0){
				
			// }
			if($open->getLastClick()!==$packet->slot)return $open->setLastClick($packet->slot);
			$this->load();
			if(!($this->getItem($packet->slot) instanceof MenuItem) or !($item instanceof MenuItem) or !$this->getItem($packet->slot)->equalsMenuItem($item))return $open->error();
			$events=$item->getEvent();
			$contents=$this->owner->getInventory()->getContents();
			if($events['cost_item']!=null and strtolower($this->owner->getName())!==strtolower($events['player'])){
				if($this->owner->getGamemode()!==0)return $open->error();
				$CostItems=explode('&',$events['cost_item']);
				$items=[];
				foreach($CostItems as $CostItem){
					$i=explode(':',$CostItem);
					if(in_array($i[0],['近战','远程','通用','材料','盔甲'])){
						$item=$i;
					}else{
						if(isset($i[3]))
							$item=Item::get($i[0],$i[1],$i[2])->setCustomName($i[3]);
						else
							$item=Item::get($i[0],$i[1],$i[2]);
					}
					$items[]=$item;
				}
				if(!Open::CheackItems($this->owner, $items))return $open->error();
				foreach($items as $item){
					if(Open::removeItem($this->owner,$item)!==true){
						$this->owner->getInventory()->setContents($contents);
						return $open->error();
					}
				}
			}
			if((int)$events['price']!=0 and $events['price']>0 and strtolower($this->owner->getName())!==strtolower($events['player'])){
				$money=EconomyAPI::getInstance()->myMoney($this->owner);
				if($money>=$events['price']){
					EconomyAPI::getInstance()->reduceMoney($this->owner,abs($events['price']), '交易消耗');
				}else{
					if($events['cost_item']!=null)$this->owner->getInventory()->setContents($contents);
					return $open->error();
				}
			}
			if($events['give_item']!=null){
				$GiveItems=explode('&',$events['give_item']);
				$items=[];
				foreach($GiveItems as $GiveItem){
					$i=explode(':',$GiveItem);
					if(in_array($i[0],['近战','远程','通用','材料','盔甲', '饰品', '奖励箱'])){
						if($i[0]=='材料')
							$item=LTItem::getInstance()->createMaterial($i[1]);
						elseif($i[0]=='盔甲')
							$item=LTItem::getInstance()->createArmor($i[1],strtolower($this->owner->getName()));
						elseif($i[0]=='饰品')
							$item=LTItem::getInstance()->createOrnaments($i[1]);
						else
							$item=LTItem::getInstance()->createWeapon($i[0],$i[1],strtolower($this->owner->getName()));
						if($item===false){
							$this->owner->getInventory()->setContents($contents);
							return $open->error();
						}
						$item->setCount((int)$i[2]);
					}else{
						if(isset($i[3]))
							$item=Item::get($i[0],$i[1]??0,$i[2]??1)->setCustomName($i[3]);
						else
							$item=Item::get($i[0],$i[1]??0,$i[2]??1);
					}
					$items[]=$item;
				}
				foreach($items as $item){
					if(!$this->owner->getInventory()->canAddItem($item)){
						$this->owner->getInventory()->setContents($contents);
						if(strtolower($this->owner->getName())!==strtolower($events['player'])){
							if((int)$events['price']>0)EconomyAPI::getInstance()->addMoney($this->owner,(int)$events['price'], '菜单退款');
						}
						return $open->invError();
					}
					$this->owner->getInventory()->addItem($item);
				}
			}
			if(strtolower($this->owner->getName())!==strtolower($events['player'])){
				if(isset($CostItems)){
					foreach($CostItems as $item){
						\LTCraft\Main::sendItem($events['player'], explode(':', $item));
					}
				}
				if(($player=$this->owner->getServer()->getPlayerExact($events['player']))!==null){
					$player->sendMessage('§a§l你在交易所上架的'.$events['give_item'].'已被'.$this->owner->getName().'购买！');
				}else{
					\LTCraft\Main::sendMessage($events['player'], '§a§l你在交易所上架的'.$events['give_item'].'已被'.$this->owner->getName().'购买！');
				}
				if((int)$events['price']!=0 and $events['price']>0){
					EconomyAPI::getInstance()->addMoney($events['player'], abs($events['price']), '交易获得');
				}
			}
			LTMenu::getInstance()->getExchange()->removeGood($events['ID']);
			$this->load();
			$open->setLastClick(null);
		}
	}
	public function getMaxPage(){
		$Exchange=LTMenu::getInstance()->getExchange();
		$count=count($Exchange->getGoods());
		return ceil($count/24);
	}
}