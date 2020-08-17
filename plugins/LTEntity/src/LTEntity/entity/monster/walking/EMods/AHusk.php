<?php

namespace LTEntity\entity\monster\walking\EMods;

use LTEntity\entity\monster\WalkingMonster;
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;

class AHusk extends WalkingMonster{

 const NETWORK_ID = 47;

 public $width = 0.6;
 public $height = 1.8;
 public $eyeHeight = 1.62;
    

 public function getName(){
 
  return "Husk";
 }
    
 public function spawnTo(Player $player){
  parent::spawnTo($player);

  foreach($this->Equipments as $pk)
	$player->dataPacket($pk);
 }

 public function attackEntity(Entity $player){
 
  if($this->attackDelay > 10 &&($this->distanceSquared($player) < 1 or ($this->distanceSquaredNoY($player) < 1 and abs($player->y - $this->y)<1.5))){
  
   $this->attackDelay = 0;
   $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
   $player->attack($ev->getFinalDamage(), $ev);
  }
 }

 public function getDrops(){
 
  /*if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
  
   switch(mt_rand(0, 2)){
   
    case 0:
     return [Item::get(Item::FEATHER, 0, 1)];
    case 1:
     return [Item::get(Item::CARROT, 0, 1)];
    case 2:
     return [Item::get(Item::POTATO, 0, 1)];
    }
   }*/
  return [];
 }

}