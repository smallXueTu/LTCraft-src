<?php

namespace LTEntity\entity\monster\walking;

use LTEntity\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\item\Item;

class Aelderguardian extends WalkingMonster{
 const NETWORK_ID = 50;

 public $width = 0.72;
 public $height = 2.4;


 public function getName(){
  return "elderguardian";
 }
 
 public function spawnTo(Player $player){
  if(
   !isset($this->hasSpawned[$player->getLoaderId()])
   && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])
  ){
   $pk = new AddEntityPacket();
   $pk->eid = $this->getID();
   $pk->type = self::NETWORK_ID;
   $pk->x = $this->x;
   $pk->y = $this->y;
   $pk->z = $this->z;
   $pk->speedX = 0;
   $pk->speedY = 0;
   $pk->speedZ = 0;
   $pk->yaw = $this->yaw;
   $pk->pitch = $this->pitch;
   $pk->metadata = [
  Entity::DATA_FLAGS => [28, 1],//0
  Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1],//38
  49 => [7,-1],
  50 => [7,-1],
  51 => [7,-1],
  45 => [2,0],
  46 => [0,0], 
  47 => [2,0],
  53 => [3,1.99], 
  54 => [3,1.99],
  56 => [8,[0,0,0]],
  57 => [0,0], 
  58 => [3,0], 
  59 => [3,0]
   ];
   $player->dataPacket($pk);

   $this->hasSpawned[$player->getLoaderId()] = $player;
  }
 }
 
 public function attackEntity(Entity $player){
  if($this->attackDelay > 10 && ($this->distanceSquared($player) < 1 or ($this->distanceSquaredNoY($player) < 1 and abs($player->y - $this->y)<1.5))){
   $this->attackDelay = 0;
   $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
   $player->attack($ev->getFinalDamage(), $ev);
  }
 }

 public function getDrops(){
  /*if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
   switch(mt_rand(0, 1)){
 case 0:
  return [Item::get(19, 0, mt_rand(1,3))];
 case 1:
  return [Item::get(499, 0, 1)];
   }
  }*/
  return [];
 }

}















