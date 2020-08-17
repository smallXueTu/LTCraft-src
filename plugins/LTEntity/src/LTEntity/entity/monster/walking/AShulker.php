<?php

namespace LTEntity\entity\monster\walking;

use LTEntity\entity\monster\WalkingMonster;
use LTEntity\entity\projectile\AShulkerSkull;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;

class AShulker extends WalkingMonster{
 const NETWORK_ID = 54;

 public $width = 0.72;
 public $height = 1.12;
 public $eyeHeight = 1.2;

 public function getSpeed() : float{
  return 0.01;
 }

 public function getName(){
  return "Shulker";
 }

 public function attackEntity(Entity $player){
  if($this->attackDelay > 20 && mt_rand(1, 32) < 4 && $this->distanceSquared($player)  <= 18){
   $this->attackDelay = 0;
  
   $f = 1.2;
   $yaw = $this->yaw + mt_rand(-220, 220) / 10;
   $pitch = $this->pitch + mt_rand(-120, 120) / 10;
   $nbt = new CompoundTag("", [
   
    "Pos" => new ListTag("Pos", [
     new DoubleTag("", $this->x + (-sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5)),
     new DoubleTag("", $this->y + 1.62),
     new DoubleTag("", $this->z +(cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5))
    ]),
 
    "Rotation" => new ListTag("Rotation", [
     new FloatTag("", $yaw),
     new FloatTag("", $pitch)
    ])
   ]);

   $skull = new AShulkerSkull((\pocketmine\API_VERSION === "3.0.1")? $this->level: $this->chunk, $nbt, $this, $player);
   $skull->setDamage($this->enConfig["攻击"]);
   $skull->spawnToAll();
  }
 }

 public function getDrops(){
  return [];
 }

}



















