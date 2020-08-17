<?php

namespace LTEntity\entity\monster\walking\EMods;

use LTEntity\entity\monster\WalkingMonster;
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\IntTag;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;


class APigZombie extends WalkingMonster{
 
 const NETWORK_ID = 36;

 public $width = 0.6;
 public $height = 1.8;
 public $eyeHeight = 1.62;

 public function getSpeed() : float{
  
  return isset($this->namedtag["Speed"])? $this->namedtag["Speed"] : 1.2;
 }



 /*public function saveNBT(){
  parent::saveNBT();
   
  $this->namedtag->Angry = new IntTag("Angry", $this->angry);
 }*/

 public function getName(){
   
  return "PigZombie";
 }


/* public function targetOption(Creature $creature, float $distance) : bool{

  return $this->isAngry() && parent::targetOption($creature, $distance);
 }
*/
 public function attack($damage, EntityDamageEvent $source){
 
  parent::attack($damage, $source);
/*
  if(!$source->isCancelled()){
   
   $this->setAngry(1000);
  }*/
 }

 public function spawnTo(Player $player){
  parent::spawnTo($player);
  foreach($this->Equipments as $pk)
	$player->dataPacket($pk);
 }
 public function attackEntity(Entity $player){
  if($this->attackDelay > 10 && ($this->distanceSquared($player) < 1.44 or ($this->distanceSquaredNoY($player) < 1.44 and abs($player->y - $this->y)<1.5))){
   $this->attackDelay = 0;

   $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
   $player->attack($ev->getFinalDamage(), $ev);
  }
 }

 public function getDrops(){

  return [];
 }

}