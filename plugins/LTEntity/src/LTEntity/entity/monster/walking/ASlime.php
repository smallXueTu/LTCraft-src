<?php

namespace LTEntity\entity\monster\walking;

use LTEntity\entity\monster\walkingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;

class ASlime extends WalkingMonster{
 const NETWORK_ID = 37;

 public $width = 0.2;
 public $height = 0.2;

 public function getName(){
  return "Slime";
 }

 public function attackEntity(Entity $player){
    if($this->attackDelay > 10 && ($this->distanceSquared($player) < 1 or ($this->distanceSquaredNoY($player) < 1 and abs($player->y - $this->y)<1.5))){
   $this->attackDelay = 0;
   $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
   $player->attack($ev->getFinalDamage(), $ev);
  }
 }

 // public function targetOption(Creature $creature, float $distance) : bool{
  // TODO
  // return false;
 // }
 
 public function getDrops(){
  return [];
 }
}