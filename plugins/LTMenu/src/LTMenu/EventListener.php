<?php
namespace LTMenu;

use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\player\{PlayerInteractEvent,PlayerQuitEvent};
use pocketmine\event\inventory\{InventoryCloseEvent};
use pocketmine\event\Listener;
use pocketmine\network\protocol\{ContainerSetSlotPacket,RemoveEntityPacket};
use LTLogin\Events;
use LTItem\Main as LTItem;
use LTItem\SpecialItems\Material;
class EventListener implements Listener{
	public function __construct(Main $plugin){
		$this->plugin=$plugin;
	}
	public function onDataPacketReceive(DataPacketReceiveEvent $event){
		$packet=$event->getPacket();
		// var_dump(get_class($packet));
		if($packet instanceof ContainerSetSlotPacket){
			$player=$event->getPlayer();
			// var_dump($this->plugin->getOpen($player));
			if(($open=$this->plugin->getOpen($player))!==null){
				if($packet->windowid==$open->getId()){
					$open->event($event);
				}
			}
		}
	}
	public function onInventoryClose(InventoryCloseEvent $event){
		$player=$event->getPlayer();
		if(($open=Main::getInstance()->getOpen($player->getName()))!==null){
			$player->removeWindow($event->getInventory());
			$player->dataPacket($open->getClosePacket());
			$open->runCloseEvents();
			if($open->getDie()){
				$player->isDie=60;
				$player->setGamemode(3, false, true);
				$player->addTitle('§l§c你死了！','§l§d3秒后复活',50,100,50);
			}
			unset($this->plugin->opens[$player->getName()]);
		}
	}
	public function onPlayerQuit(PlayerQuitEvent $event){
		$player=$event->getPlayer();
		if(($open=Main::getInstance()->getOpen($player->getName()))!==null){
			$open->close();
			if($open->getDie()){
				$player->setGamemode($player->dieMessage[0], false, true);
				$player->isDie=false;
				$player->extinguish();
				$player->setHealth($player->getMaxHealth()); 
				$player->setFood(20); 
				$player->getServer()->removePlayerListData($player->dieMessage[1], $player->dieMessage[3]);
				$pk = new RemoveEntityPacket();
				$pk->eid = $player->dieMessage[2];
				$player->getServer()->broadcastPacket($player->dieMessage[3],$pk);
			}
			unset($this->plugin->opens[$player->getName()]);
		}
	}
	public function onInteractEvent(PlayerInteractEvent $event){
		$player=$event->getPlayer();
		if(Events::$status[strtolower($player->getName())]!==true)return;
		$item=$player->getItemInHand();
		if($item instanceof Material){
			switch($item->getLTName()){
				case '§a点击地面打开菜单':
					$this->plugin->openMenu($player,'Menu');
					$event->setCancelled();
				break;
			}
		}
	}
}