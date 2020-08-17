<?php

namespace LTEntity\entity\monster\walking\EMods;

use LTEntity\entity\monster\WalkingMonster;
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\entity\ProjectileSource;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\sound\LaunchSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;


class ASkeleton extends WalkingMonster implements ProjectileSource{

 const NETWORK_ID = 34;

 public $width = 0.6;
 public $height = 1.8;

 public function getName(){
  return 'Skeleton';
 }

	public function attackEntity(Entity $player){
		if($this->attackMove==0){
			if($this->attackDelay > 10 &&($this->distanceSquared($player) < 1 or ($this->distanceSquaredNoY($player) < 1 and abs($player->y - $this->y)<1.5))){
				$this->attackDelay = 0;
				$ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
				$player->attack($ev->getFinalDamage(), $ev);
			}
		}else{
			if($this->attackDelay > 30 && mt_rand(1, 32) < 4 && $this->distanceSquared($player) <= 55){
				$this->attackDelay = 0;
				$f = 1.2;
				$yaw = $this->yaw + mt_rand(-220, 220) / 10;
				$pitch = $this->pitch + mt_rand(-120, 120) / 10;
				$nbt = new CompoundTag('', [
					'Pos' => new ListTag('Pos', [
					new DoubleTag('', $this->x + (-sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5)),
					new DoubleTag('', $this->y + 1.62),
					new DoubleTag('', $this->z +(cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5))
					]),
					'Motion' => new ListTag('Motion', [
					new DoubleTag('', -sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * $f),
					new DoubleTag('', -sin($pitch / 180 * M_PI) * $f),
					new DoubleTag('', cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * $f)
					]),
					'Rotation' => new ListTag('Rotation', [
					new FloatTag('', $yaw),
					new FloatTag('', $pitch)
					]),
				]);
				$arrow = Entity::createEntity('falseArrow', $this->level, $nbt, $this);
				$arrow->setDamage($this->getDamage());
				$arrow->spawnToAll();
				$this->level->addSound(new LaunchSound($this), $this->getViewers());
			}
		}
	}

 public function spawnTo(Player $player){
  parent::spawnTo($player);

  foreach($this->Equipments as $pk)
	$player->dataPacket($pk);
 }
 public function getDrops(){
  /*if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
   return [
    Item::get(Item::BONE, 0, mt_rand(0, 2)),
    Item::get(Item::ARROW, 0, mt_rand(0, 3)),
   ];
  }*/
  return [];
 }

}
