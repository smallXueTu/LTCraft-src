<?php

namespace LTEntity\entity\projectile;

use LTEntity\entity\ProjectileEntity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\entity\Effect;
use pocketmine\network\protocol\AddEntityPacket;

use LTEntity\entity\projectile\ABlazeFireball;

class AShulkerSkull extends ABlazeFireball{
 
 const NETWORK_ID = 76;
 
 public $target = null;
 
 protected $gravity = 0;
 
 public function __construct($world, CompoundTag $nbt, Entity $shootingEntity, Entity $target, bool $critical = false){
  
  $world = (\pocketmine\API_VERSION === "3.0.1")? $world: $shootingEntity->chunk;
  
  parent::__construct($world, $nbt, $shootingEntity);
  
  $this->target = $target;
  $this->isCritical = $critical;
 }
 
 public function onUpdate($currentTick){
  if($this->closed or $this->shootingEntity->closed){
   return false;
  }

  $this->timings->startTiming();
  
  $z = $this->target->z - $this->z;
  $y = ($this->target->y + 1) - $this->y;
  $x = $this->target->x - $this->x;
    
  $atn = atan2($z, $x);
  
  $this->setRotation(rad2deg($atn -M_PI_2),rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2))));
  
  $this->motionX = $x / 12;
  $this->motionY = $y;
  $this->motionZ = $z / 12;

  $hasUpdate = ProjectileEntity::onUpdate($currentTick);
  
  if($this->onGround){
   $this->isCritical = false;
  }
  
  if($this->age > 1200 or $this->isCollided){   
   if($this->isCollided and $this->canExplode){
   
    $this->attackOccurred($this->movingObjectPosition->entityHit, $this->damage);
   }
   $this->kill();
   $hasUpdate = true;
  }
  $this->timings->stopTiming();
  return $hasUpdate;
 }
 
 public function attackOccurred($target, $damage){
  
  ProjectileEntity::attackOccurred($target, $damage);
  
  $effect=Effect::getEffect(24);
  $target->addEffect($effect->setDuration(mt_rand(15, 30)*20));
 }
 
 public function spawnTo(Player $player){
  
  $pk = new AddEntityPacket();
  $pk->eid = $this->getId();
  $pk->type = self::NETWORK_ID;
  $pk->x = $this->x;
  $pk->y = $this->y;
  $pk->z = $this->z;
 /* $pk->speedX = $this->motionX;
  $pk->speedY = $this->motionY;
  $pk->speedZ = $this->motionZ;*/
$pk->speedX = 0;
  $pk->speedY = 0;
  $pk->speedZ = 0;
  $pk->yaw = $this->yaw;
  $pk->pitch = $this->pitch;
  $pk->metadata = $this->dataProperties;
  $player->dataPacket($pk);
  
  ProjectileEntity::spawnTo($player);
 }
 
}












