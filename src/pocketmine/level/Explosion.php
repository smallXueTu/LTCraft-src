<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____  
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \ 
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/ 
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_| 
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 * 
 *
*/

namespace pocketmine\level;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\Creature;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\item\Item;
use pocketmine\level\particle\HugeExplodeSeedParticle;
use pocketmine\level\sound\ExplodeSound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Math;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;
use pocketmine\network\protocol\ExplodePacket;
use pocketmine\utils\Random;
use LTPet\Pets\Pets;

class Explosion {

	private $rays = 16; //Rays
	public $level;
	public $source;
	public $size;
	/**
	 * @var Block[]
	 */
	public $affectedBlocks = [];
	public $stepLen = 0.3;
	/** @var Entity|Block */
	private $what;
	private $dropItem;
	private $player;

	/**
	 * Explosion constructor.
	 *
	 * @param Position $center
	 * @param          $size
	 * @param null     $what
	 * @param bool     $dropItem
	 */
	public function __construct(Position $center, $size, $what = null, bool $dropItem = true,$player=null){
		$this->level = $center->getLevel();
		$this->source = $center;
		$this->size = max($size, 0);
		$this->what = $what;
		$this->dropItem = $dropItem;
		$this->player = $player;
	}

	/**
	 * @return bool
	 */
	public function explodeA() : bool{
		if($this->size < 0.1){
			return false;
		}

		$vector = new Vector3(0, 0, 0);
		$vBlock = new Vector3(0, 0, 0);

		$mRays = intval($this->rays - 1);
		for($i = 0; $i < $this->rays; ++$i){
			for($j = 0; $j < $this->rays; ++$j){
				for($k = 0; $k < $this->rays; ++$k){
					if($i === 0 or $i === $mRays or $j === 0 or $j === $mRays or $k === 0 or $k === $mRays){
						$vector->setComponents($i / $mRays * 2 - 1, $j / $mRays * 2 - 1, $k / $mRays * 2 - 1);
						$vector->setComponents(($vector->x / ($len = $vector->length())) * $this->stepLen, ($vector->y / $len) * $this->stepLen, ($vector->z / $len) * $this->stepLen);
						$pointerX = $this->source->x;
						$pointerY = $this->source->y;
						$pointerZ = $this->source->z;

						for($blastForce = $this->size * (mt_rand(700, 1300) / 1000); $blastForce > 0; $blastForce -= $this->stepLen * 0.75){
							$x = (int) $pointerX;
							$y = (int) $pointerY;
							$z = (int) $pointerZ;
							$vBlock->x = $pointerX >= $x ? $x : $x - 1;
							$vBlock->y = $pointerY >= $y ? $y : $y - 1;
							$vBlock->z = $pointerZ >= $z ? $z : $z - 1;
							if($vBlock->y < 0 or $vBlock->y >= Level::Y_MAX){
								break;
							}
							$block = $this->level->getBlock($vBlock);

							if($block->getId() !== 0){
								$blastForce -= ($block->getResistance() / 5 + 0.3) * $this->stepLen;
								if($blastForce > 0){
									if(!isset($this->affectedBlocks[$index = Level::blockHash($block->x, $block->y, $block->z)])){
										$this->affectedBlocks[$index] = $block;
									}
								}
							}
							$pointerX += $vector->x;
							$pointerY += $vector->y;
							$pointerZ += $vector->z;
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function explodeAA() : bool{
		if($this->size < 0.1){
			return false;
		}

		$vector = new Vector3(0, 0, 0);
		$vBlock = new Vector3(0, 0, 0);

		$mRays = intval($this->rays - 1);
		
		if(!isset($this->affectedBlocks[$index = Level::blockHash($this->what->x, $this->what->y, $this->what->z)])){
			$this->affectedBlocks[$index] = $this->what;
		}
		for($i = 0; $i < $this->rays; ++$i){
			for($j = 0; $j < $this->rays; ++$j){
				for($k = 0; $k < $this->rays; ++$k){
					if($i === 0 or $i === $mRays or $j === 0 or $j === $mRays or $k === 0 or $k === $mRays){
						$vector->setComponents($i / $mRays * 2 - 1, $j / $mRays * 2 - 1, $k / $mRays * 2 - 1);
						$vector->setComponents(($vector->x / ($len = $vector->length())) * $this->stepLen, ($vector->y / $len) * $this->stepLen, ($vector->z / $len) * $this->stepLen);
						$pointerX = $this->source->x;
						$pointerY = $this->source->y;
						$pointerZ = $this->source->z;

						for($blastForce = $this->size * (mt_rand(700, 1300) / 1000); $blastForce > 0; $blastForce -= $this->stepLen * 0.75){
							$x = (int) $pointerX;
							$y = (int) $pointerY;
							$z = (int) $pointerZ;
							$vBlock->x = $pointerX >= $x ? $x : $x - 1;
							$vBlock->y = $pointerY >= $y ? $y : $y - 1;
							$vBlock->z = $pointerZ >= $z ? $z : $z - 1;
							if($vBlock->y < 0 or $vBlock->y >= Level::Y_MAX){
								break;
							}
							$block = $this->level->getBlock($vBlock);
							$tile= $this->level->getTile($block);
							if($block->getId() !== 0 and (!($tile instanceof \pocketmine\inventory\InventoryHolder))){
								$blastForce -= ($block->getResistance() / 5 + 0.3) * $this->stepLen;
								if($blastForce > 0){
									if(!isset($this->affectedBlocks[$index = Level::blockHash($block->x, $block->y, $block->z)])){
										$this->affectedBlocks[$index] = $block;
									}
								}
							}
							$pointerX += $vector->x;
							$pointerY += $vector->y;
							$pointerZ += $vector->z;
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * 爆炸 Boooom!!
	 * @return bool
	 */
	public function booom($damage,$damager,$zs=false) : bool{
		$source = (new Vector3($this->source->x, $this->source->y, $this->source->z))->floor();
		foreach($this->level->getPlayers() as $entity){
			if($this->source->distance($entity)>20 or $entity===$damager or $entity->closed)continue;
			$ev = new EntityDamageByEntityEvent($damager, $entity, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, $damage,0.4,$zs);
			if($zs)$ev->notC=true;
			if($entity->attack($ev->getFinalDamage(), $ev) === true)$ev->useArmors();
			if($ev->isCancelled())continue;
			$deltaX = $entity->x - $source->x;
			$deltaZ = $entity->z - $source->z;
			$entity->knockBack($entity, $damage, $deltaX, $deltaZ, 1);
		}
		$this->level->addParticle(new HugeExplodeSeedParticle($source));
		$this->level->addSound(new ExplodeSound($source));
		return true;
	}

	/**
	 * 爆炸 Boooom!!
     * 百分比伤害
	 * @return bool
	 */
	public function booomB($percentage,$damager, $size = 4,$zs=false) : bool{
        $percentage = $percentage / 100;
		$source = (new Vector3($this->source->x, $this->source->y, $this->source->z))->floor();
		foreach($this->level->getPlayers() as $entity){
			if($this->source->distance($entity)>$size or $entity===$damager or $entity->closed)continue;
			$ev = new EntityDamageByEntityEvent($damager, $entity, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, $entity->getMaxHealth() * $percentage,0.4,$zs);
			if($zs)$ev->notC=true;
			if($entity->attack($ev->getFinalDamage(), $ev) === true)$ev->useArmors();
			if($ev->isCancelled())continue;
			$deltaX = $entity->x - $source->x;
			$deltaZ = $entity->z - $source->z;
			$entity->knockBack($entity, $entity->getMaxHealth() * $percentage, $deltaX, $deltaZ, 0.4);
		}
		$this->level->addParticle(new HugeExplodeSeedParticle($source));
		$this->level->addSound(new ExplodeSound($source));
		return true;
	}
	public function explodeB() : bool{
		$send = [];
		$updateBlocks = [];

		$source = (new Vector3($this->source->x, $this->source->y, $this->source->z))->floor();
		$yield = (1 / $this->size) * 100;

		if($this->what instanceof Entity){
			$this->level->getServer()->getPluginManager()->callEvent($ev = new EntityExplodeEvent($this->what, $this->source, $this->affectedBlocks, $yield));
			if($ev->isCancelled()){
				return false;
			}else{
				$yield = $ev->getYield();
				$this->affectedBlocks = $ev->getBlockList();
			}
		}

		$explosionSize = $this->size * 2;
		$minX = Math::floorFloat($this->source->x - $explosionSize - 1);
		$maxX = Math::ceilFloat($this->source->x + $explosionSize + 1);
		$minY = Math::floorFloat($this->source->y - $explosionSize - 1);
		$maxY = Math::ceilFloat($this->source->y + $explosionSize + 1);
		$minZ = Math::floorFloat($this->source->z - $explosionSize - 1);
		$maxZ = Math::ceilFloat($this->source->z + $explosionSize + 1);

		$explosionBB = new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);

		$list = $this->level->getNearbyEntities($explosionBB, $this->what instanceof Entity ? $this->what : null);
		foreach($list as $entity){
			$distance = $entity->distance($this->source) / $explosionSize;

			if($distance <= 1){
				$motion = $entity->subtract($this->source)->normalize();

				$impact = (1 - $distance) * ($exposure = 1);

				$damage = $this->damage??((int) ((($impact * $impact + $impact) / 2) * 8 * $explosionSize + 1));

				if($this->what instanceof Entity){
					$ev = new EntityDamageByEntityEvent($this->what, $entity, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, $damage);
				}elseif($this->what instanceof Block){
					$ev = new EntityDamageByBlockEvent($this->what, $entity, EntityDamageEvent::CAUSE_BLOCK_EXPLOSION, $damage);
				}else{
					$ev = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_BLOCK_EXPLOSION, $damage);
				}

				if($entity->attack($ev->getFinalDamage(), $ev) === true){
					$ev->useArmors();
				}
				$entity->setMotion($motion->multiply($impact));
			}
		}


		$air = Item::get(Item::AIR);

		foreach($this->affectedBlocks as $block){
			if($block->getId() === Block::TNT){
				$mot = (new Random())->nextSignedFloat() * M_PI * 2;
				$tnt = Entity::createEntity("PrimedTNT", $this->level, new CompoundTag("", [
					"Pos" => new ListTag("Pos", [
						new DoubleTag("", $block->x + 0.5),
						new DoubleTag("", $block->y),
						new DoubleTag("", $block->z + 0.5)
					]),
					"Motion" => new ListTag("Motion", [
						new DoubleTag("", -sin($mot) * 0.02),
						new DoubleTag("", 0.2),
						new DoubleTag("", -cos($mot) * 0.02)
					]),
					"Rotation" => new ListTag("Rotation", [
						new FloatTag("", 0),
						new FloatTag("", 0)
					]),
					"Fuse" => new ByteTag("Fuse", mt_rand(10, 30))
				]),true,$this->player);
				$tnt->spawnToAll();
			}elseif($this->dropItem and mt_rand(0, 100) < $yield){
				foreach($block->getDrops($air) as $drop){
					$this->level->dropItem($block->add(0.5, 0.5, 0.5), Item::get(...$drop));
				}
			}

			$this->level->setBlockIdAt($block->x, $block->y, $block->z, 0);

			$pos = new Vector3($block->x, $block->y, $block->z);

			for($side = 0; $side < 5; $side++){
				$sideBlock = $pos->getSide($side);
				if(!isset($this->affectedBlocks[$index = Level::blockHash($sideBlock->x, $sideBlock->y, $sideBlock->z)]) and !isset($updateBlocks[$index])){
					$this->level->getServer()->getPluginManager()->callEvent($ev = new BlockUpdateEvent($this->level->getBlock($sideBlock)));
					if(!$ev->isCancelled()){
						$ev->getBlock()->onUpdate(Level::BLOCK_UPDATE_NORMAL);
					}
					$updateBlocks[$index] = true;
				}
			}
			$send[] = new Vector3($block->x - $source->x, $block->y - $source->y, $block->z - $source->z);
		}

		$pk = new ExplodePacket();
		$pk->x = $this->source->x;
		$pk->y = $this->source->y;
		$pk->z = $this->source->z;
		$pk->radius = $this->size;
		$pk->records = $send;
		$this->level->addChunkPacket($source->x >> 4, $source->z >> 4, $pk);

		$this->level->addParticle(new HugeExplodeSeedParticle($source));
		$this->level->addSound(new ExplodeSound($source));

		return true;
	}
}
