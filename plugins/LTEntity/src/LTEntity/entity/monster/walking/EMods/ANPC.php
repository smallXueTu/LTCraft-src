<?php

namespace LTEntity\entity\monster\walking\EMods;

use LTEntity\entity\monster\WalkingMonster;
use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\utils\UUID;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\protocol\PlayerListPacket;
use pocketmine\network\protocol\AnimatePacket;

class ANPC extends WalkingMonster{

 const NETWORK_ID = 63;

 public $width = 0.6;
 public $height = 1.8;
 public $eyeHeight = 1.62;
 

	public function getName(){
		return $this->getNameTag();
	}

	public function getUniqueId(){
		return $this->uid ?? $this->uid=UUID::fromData($this->getId(), $this->namedtag['Skin']['Data'], $this->enConfig['名字']);
	}
	public function despawnFrom(Player $player, bool $send = true){
		if(isset($this->hasSpawned[$player->getLoaderId()])){
			if($send){
				$pk = new PlayerListPacket();
				$pk->type = PlayerListPacket::TYPE_REMOVE;
				$pk->entries[] = [$this->getUniqueId()];
				$player->dataPacket($pk);
				$pk = new RemoveEntityPacket();
				$pk->eid = $this->id;
				$player->dataPacket($pk);
			}
			unset($this->hasSpawned[$player->getLoaderId()]);
		}
	}
	public function spawnTo(Player $player){
		if(isset($this->hasSpawned[$player->getLoaderId()]) or !isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())]))return;
		if(isset($this->namedtag['Skin'])){
			$pk = new PlayerListPacket();
			$pk->type = PlayerListPacket::TYPE_ADD;
			$pk->entries[] = [$this->getUniqueId(), $this->getId(), '§a'.$this->enConfig['名字'], $this->namedtag['Skin']['Name'], $this->namedtag['Skin']['Data']];
			$player->dataPacket($pk);
			if($this->enConfig['披风']){
                $skin = ['Minecon_MineconSteveCape2011', 'Minecon_MineconSteveCape2012', 'Minecon_MineconSteveCape2013', 'Minecon_MineconSteveCape2015', 'Minecon_MineconSteveCape2016',];
				$pk = new PlayerListPacket();
				$pk->type = PlayerListPacket::TYPE_ADD;
				$pk->entries[] = [$this->getUniqueId(), $this->getId(), '§a'.$this->enConfig['名字'], $skin[mt_rand(0, 4)], $this->namedtag['Skin']['Data']];
				$player->dataPacket($pk);
			}
		}
		$pk = new AddPlayerPacket();
		$pk->uuid = $this->getUniqueId();
		$pk->username = $this->getName();
		$pk->eid = $this->getId();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = 0;
		$pk->speedY = 0;
		$pk->speedZ = 0;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		if($this->enConfig['手持ID']!==false){
			$ids=explode(':', $this->enConfig['手持ID']);
			$item=Item::get($ids[0], $ids[1]);
			if($ids[2]==true)$this->addEnchant($item);
			$pk->item = $item;
		}else $pk->item=Item::get(0);
		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk);
		  foreach($this->Equipments as $pk)
			$player->dataPacket($pk);
		$this->hasSpawned[$player->getLoaderId()] = $player;
	}

	public function kill(){
		parent::kill();
		if(isset($this->namedtag['Skin']))$this->server->removePlayerListData($this->getUniqueId(), $this->server->getOnlinePlayers());
	}
	public function close(){
		if(isset($this->namedtag['Skin']))$this->server->removePlayerListData($this->getUniqueId(), $this->server->getOnlinePlayers());

		parent::close();
	}
	public function attackEntity(Entity $player){
		if($this->enConfig['怪物模式']==0)$v=$this->enConfig['边界范围半径'];else $v=1;
		if($this->attackDelay > $this->enConfig['攻击间隔'] && ($this->distance($player) < $v or ($this->distanceNoY($player) < $v and abs($player->y - $this->y)<1.5))){
			$this->attackDelay = 0;

			if($this->enConfig['名字']==='骑士'){
				if($this->Mode===1 or $this->Mode===2)$this->attackDelay = 5;
				elseif($this->Mode===3)return;
			}
			$ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage(),$this->enConfig['怪物模式']==0?0:0.4);
			$player->attack($ev->getFinalDamage(), $ev);
			$pk = new AnimatePacket();
			$pk->eid = $this->getId();
			$pk->action = 1;
			$this->server->broadcastPacket($this->getViewers(), $pk);
		}
	}
	public function getDrops(){
		return [];
	}
}

