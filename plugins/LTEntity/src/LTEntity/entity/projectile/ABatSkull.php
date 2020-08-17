<?php

namespace LTEntity\entity\projectile;

use LTEntity\entity\ProjectileEntity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\network\protocol\AddEntityPacket;

use LTEntity\entity\projectile\ABlazeFireball;

class ABatSkull extends ABlazeFireball{
 
 const NETWORK_ID = 82;
 
 
 public function spawnTo(Player $player){
  
  $pk = new AddEntityPacket();
  $pk->eid = $this->getId();
  $pk->type = self::NETWORK_ID;
  $pk->x = $this->x;
  $pk->y = $this->y;
  $pk->z = $this->z;
  $pk->speedX = $this->motionX;
  $pk->speedY = $this->motionY;
  $pk->speedZ = $this->motionZ;
  $pk->yaw = $this->yaw;
  $pk->pitch = $this->pitch;
  $pk->metadata = $this->dataProperties;
  $player->dataPacket($pk);
  
  ProjectileEntity::spawnTo($player);
 }
 
}