<?php

namespace LTEntity\entity\monster\flying;

use LTEntity\entity\BaseEntity;
use LTEntity\entity\monster\FlyingMonster;
use LTEntity\entity\projectile\ABatSkull;
use pocketmine\block\Liquid;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\entity\ProjectileSource;
use pocketmine\item\Item;
use pocketmine\level\Location;
use pocketmine\level\sound\LaunchSound;
use pocketmine\math\Math;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\nbt\tag\{ByteTag,CompoundTag,DoubleTag,FloatTag,ListTag,StringTag,IntTag};

class ABat extends FlyingMonster implements ProjectileSource{
 const NETWORK_ID = 19;

 public $width = 1.8;
 public $height = 1.8;
 public $gravity = 0.04;

 public function getName(){
  return "Blaze";
 }

  public function attackEntity(Entity $player){
  if($this->attackDelay > 20 && mt_rand(1, 32) < 4 && $this->distance($player) <= $this->enConfig['边界范围半径']){
   $this->attackDelay = 0;
   $nbt = new CompoundTag("", [
   			"Pos" => new ListTag("Pos",[
   				new DoubleTag("", $this->x),
   				new DoubleTag("", $this->y + 1.2),
   				new DoubleTag("", $this->z)
   				]),
   				"Motion" => new ListTag("Motion",[
   				new DoubleTag("", -sin($this->yaw / 180 * M_PI)  * cos($this->pitch / 180 * M_PI)),
   				new DoubleTag("", -sin($this->pitch / 180 * M_PI)),
   				new DoubleTag("", cos($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI))
   				]),
   				"Rotation" => new ListTag("Rotation",[
   				new FloatTag("", $this->yaw),
   				new FloatTag("", $this->pitch)
   				]),
			]);
   $pk = new ABatSkull($this->level, $nbt, $this);
   $pk->setTranslation(true);
	 // $pk->setMotion($pk->getMotion()->multiply(2));
   $pk->setDamage($this->enConfig["攻击"]);
   $pk->spawnToAll();
   $this->level->addSound(new LaunchSound($this), $this->getViewers());
  }
 }

 public function getDrops(){
/*  if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
   return [Item::get(369, 0, mt_rand(0, 1))];
  }*/
  return [];
 }

}