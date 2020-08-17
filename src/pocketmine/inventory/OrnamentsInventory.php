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

use pocketmine\entity\Human;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\protocol\BlockEventPacket;
use pocketmine\Player;

class OrnamentsInventory extends ContainerInventory {

	/** @var Human|Player */
	private $owner;

	public $onUse = false;

	/**
	 * EnderChestInventory constructor.
	 *
	 * @param Human $owner
	 * @param null  $contents
	 */
	public function __construct(Human $owner, $contents = null){
		$this->owner = $owner;
		$items=[];
		if($contents !== null){
			if($contents instanceof ListTag){ //Saved data to be loaded into the inventory
				foreach($contents as $item){
					$items[$item["Slot"]]=Item::nbtDeserialize($item);
				}
			}else{
				throw new \InvalidArgumentException("Expecting ListTag, received " . gettype($contents));
			}
		}
		parent::__construct(new FakeBlockMenu($this, $owner->getPosition()), InventoryType::get(InventoryType::ORNAMENTS), $items, 25);
	}

	/**
	 * @return Human|Player
	 */
	public function getOwner(){
		return $this->owner;
	}

	public function getSize(){
		return $this->size;
	}

	public function getItems(){
		$slots = [];
		for($i = 0;$i < 25; ++$i){
			$slots[] = $this->getItem($i);
		}
		$item=Item::get(339,0,1);
		$item->setCustomName("§l§o§d说明\n§a你可以吧你们的饰品放到这里\n来发挥它的作用\n§c注意：饰品只生效前六个！");
		$slots[]=$item;
		$item=Item::get(404,0,1);
		$item->setCustomName("§l§o§d上一页\n§a再次点击返回上一页");
		$slots[]=$item;
		return $slots;
	}

	public function getAvailableItems(){
		$slots = [];
		for($i = 0;$i < 25; ++$i){
			$slots[] = $this->getItem($i);
			if ($i==5)break;//饰品默认只生效前6个
		}
		return $slots;
	}

	/**
	 * @return FakeBlockMenu
	 */
	public function getHolder(){
		return $this->holder;
	}
	/**
	 * Set the fake block menu's position to a valid tile position
	 * and send the inventory window to the owner
	 *
	 * @param Position $pos
	 */
	public function openAt(Position $pos){
		$this->getHolder()->setComponents($pos->x, $pos->y, $pos->z);
		$this->getHolder()->setLevel($pos->getLevel());
		$this->owner->addWindow($this);
	}

	/**
	 * @param Player $who
	 */
	public function onOpen(Player $who){
		parent::onOpen($who);
		$pk = new BlockEventPacket();
		$pk->x = $this->getHolder()->getX();
		$pk->y = $this->getHolder()->getY();
		$pk->z = $this->getHolder()->getZ();
		$pk->case1 = 1;
		$pk->case2 = 2;
		if(($level = $this->getHolder()->getLevel()) instanceof Level){
			$level->addChunkPacket($this->getHolder()->getX() >> 4, $this->getHolder()->getZ() >> 4, $pk);
		}
	}

	/**
	 * @param Player $who
	 */
	public function onClose(Player $who){
		$pk = new BlockEventPacket();
		$pk->x = $this->getHolder()->getX();
		$pk->y = $this->getHolder()->getY();
		$pk->z = $this->getHolder()->getZ();
		$pk->case1 = 1;
		$pk->case2 = 0;
		if(($level = $this->getHolder()->getLevel()) instanceof Level){
			$level->addChunkPacket($this->getHolder()->getX() >> 4, $this->getHolder()->getZ() >> 4, $pk);
		}

		parent::onClose($who);
	}

}