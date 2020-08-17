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

namespace pocketmine\inventory;

use pocketmine\block\TrappedChest;
use pocketmine\level\Level;
use pocketmine\network\protocol\BlockEventPacket;
use pocketmine\network\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\tile\Chest;
use pocketmine\NBT\tag\LongTag;
use pocketmine\item\Item;
use pocketmine\entity\Item as entityItem;

use LTItem\SpecialItems\Material;

class ChestInventory extends ContainerInventory {
	/**
	 * ChestInventory constructor.
	 *
	 * @param Chest $tile
	 */
	public function __construct(Chest $tile){
		parent::__construct($tile, InventoryType::get(InventoryType::CHEST));
	}

	/**
	 * @return Chest
	 */
	public function getHolder(){
		return $this->holder;
	}
	// public function setItem($index, Item $item, $send = true){
		// if(parent::setItem($index, $item, $send) and $this->getHolder()->isRewardBox()){
			// if($item instanceof Material and in_array($item->getLTName(), ['神秘盔甲奖励'])){
				
			// }
		// }
		
	// }

	/**
	 * @param Player $who
	 */
	public function onOpen(Player $who){
		parent::onOpen($who);

		if(count($this->getViewers()) === 1){
			$pk = new BlockEventPacket();
			$pk->x = $this->getHolder()->getX();
			$pk->y = $this->getHolder()->getY();
			$pk->z = $this->getHolder()->getZ();
			$pk->case1 = 1;
			$pk->case2 = 2;
			if(($level = $this->getHolder()->getLevel()) instanceof Level){
				$level->broadcastLevelSoundEvent($this->getHolder(), LevelSoundEventPacket::SOUND_CHEST_OPEN);
				$level->addChunkPacket($this->getHolder()->getX() >> 4, $this->getHolder()->getZ() >> 4, $pk);
			}
		}

		if($this->getHolder()->getLevel() instanceof Level){
			/** @var TrappedChest $block */
			$block = $this->getHolder()->getBlock();
			if($block instanceof TrappedChest){
				if(!$block->isActivated()){
					$block->activate();
				}
			}
		}
	}

	/**
	 * @param Player $who
	 */
	public function onClose(Player $who){
		if($this->getHolder()->getLevel() instanceof Level){
			/** @var TrappedChest $block */
			$block = $this->getHolder()->getBlock();
			if($block instanceof TrappedChest){
				if($block->isActivated()){
					$block->deactivate();
				}
			}
		}

		if(count($this->getViewers()) === 1){
			$pk = new BlockEventPacket();
			$pk->x = $this->getHolder()->getX();
			$pk->y = $this->getHolder()->getY();
			$pk->z = $this->getHolder()->getZ();
			$pk->case1 = 1;
			$pk->case2 = 0;
			if(($level = $this->getHolder()->getLevel()) instanceof Level){
				$level->broadcastLevelSoundEvent($this->getHolder(), LevelSoundEventPacket::SOUND_CHEST_CLOSED);
				$level->addChunkPacket($this->getHolder()->getX() >> 4, $this->getHolder()->getZ() >> 4, $pk);
			}
			
			if(($this->getHolder()->getRewardBoxType()=='空奖励箱' or $this->getHolder()->getRewardBoxType()=='empty') and ($item=$this->getItem(0)) instanceof Material and in_array($item->getLTName(), ['神秘盔甲材料奖励', '神秘饰品奖励', '神秘武器材料奖励', '史诗盔甲图纸奖励'])){
				$tile=$this->getHolder();
				foreach($this->getContents() as $i=>$item){
					if($i==0 or ($i==1 and $item instanceof Material and $item->getLTName()=='祝福水晶'))continue;
					$dropItem=$level->dropItem($tile, $item);
					if($dropItem instanceof entityItem)$dropItem->setOwner(strtolower($who->getName()));
					$this->setItem($i, Item::get(0));
				}
				$tile->namedtag->OpenTime = new LongTag('OpenTime', time()+14400);
				$tile->namedtag->Lucky = new LongTag('Lucky', 0);
			}
		}
		parent::onClose($who);
	}
}