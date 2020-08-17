<?php

namespace LTEntity\entity\monster\walking;

use LTEntity\entity\monster\WalkingMonster;
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\item\Potion;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\network\protocol\{AddEntityPacket,MobEquipmentPacket};

class AWitch extends WalkingMonster{
 const NETWORK_ID = 45;

 public $width = 0.72;
 public $height = 1.8;

 public function getName(){
  return "Witch";
 }
 
 public function spawnTo(Player $player){
  parent::spawnTo($player);

  $pk = new MobEquipmentPacket();
  $pk->eid = $this->getId();
  $pk->item = Item::get(373,22);
  $pk->slot = 10;
  $pk->selectedSlot = 10;
  $player->dataPacket($pk);
 }

 public function attackEntity(Entity $player){
  if($this->attackDelay > 20 && mt_rand(1, 32) < 4 && $this->distanceSquared($player) <= 18){
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
 
    "Motion" => new ListTag("Motion", [
     new DoubleTag("", -sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * $f),
     new DoubleTag("", -sin($pitch / 180 * M_PI) * $f),
     new DoubleTag("", cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * $f)
    ]),
 
    "Rotation" => new ListTag("Rotation", [
     new FloatTag("", $yaw),
     new FloatTag("", $pitch)
    ]),
 
    "PotionId" => new ShortTag("PotionId", 25)
   ]);

   $Thrown = Entity::createEntity("ThrownPotion", (\pocketmine\API_VERSION === "3.0.1")? $this->level: $this->chunk, $nbt, $this);
   
   $Thrown->spawnToAll();
  }
 }

 public function getDrops(){
  return [];
 }

}


























