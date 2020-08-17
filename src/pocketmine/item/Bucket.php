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

namespace pocketmine\item;

use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\Liquid;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\level\Level;
use pocketmine\Player;

class Bucket extends Item {
	/**
	 * Bucket constructor.
	 *
	 * @param int $meta
	 * @param int $count
	 */
	public function __construct($meta = 0, $count = 1){
		parent::__construct(self::BUCKET, $meta, $count, "桶");
	}

	/**
	 * @return int
	 */
	public function getMaxStackSize() : int{
		return $this->meta==0?16:1;
	}

	/**
	 * @return bool 可以被激活。
	 */
	public function canBeActivated() : bool{
		return true;
	}

	/**
	 * @param Level  $level
	 * @param Player $player
	 * @param Block  $block
	 * @param Block  $target
	 * @param        $face
	 * @param        $fx
	 * @param        $fy
	 * @param        $fz
	 *
	 * @return bool
	 */
	public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
		$targetBlock = Block::get($this->meta);

		if($targetBlock instanceof Air){
			if($target instanceof Liquid and $target->getDamage() === 0){//目标方块属于流体 和是中心
				$result = clone $this;
				$id = $target->getId();
				if($id == self::STILL_WATER){//水
					$id = self::WATER;
				}
				if($id == self::STILL_LAVA){//岩浆
					$id = self::LAVA;
				}
				$result->setDamage($id);
				// $player->getServer()->getPluginManager()->callEvent($ev = new PlayerBucketFillEvent($player, $block, $face, $this, $result));
				// if(!$ev->isCancelled()){
					$player->getLevel()->setBlock($target, new Air(), true, true);
					if($player->isSurvival()){
						$result->setCount(1);
						if($this->getCount() -1 == 0){
							$player->getInventory()->setItemInHand($result);
						}else{
							$this->count--;
							$player->getInventory()->addItem($result);
						}
					}
					return true;
				// }else{
					// $player->getInventory()->sendContents($player);
				// }
			}
		}elseif($targetBlock instanceof Liquid){
			$result = clone $this;
			$result->setDamage(0);
			// $player->getServer()->getPluginManager()->callEvent($ev = new PlayerBucketEmptyEvent($player, $block, $face, $this, $result));
			// if(!$ev->isCancelled()){
				//Only disallow water placement in the Nether, allow other liquids to be placed
				//In vanilla, water buckets are emptied when used in the Nether, but no water placed.
				if(!($player->getLevel()->getDimension() === Level::DIMENSION_NETHER and $targetBlock->getId() === self::WATER)){
					$player->getLevel()->setBlock($block, $targetBlock, true, true);
				}
				if($player->isSurvival()){
					$result->setCount(1);
					if($this->getCount() -1 == 0){
						$player->getInventory()->setItemInHand($result);
					}else{
						$this->count--;
						$player->getInventory()->addItem($result);
					}
				}
				return true;
			// }else{
				// $player->getInventory()->sendContents($player);
			// }
		}

		return false;
	}
}