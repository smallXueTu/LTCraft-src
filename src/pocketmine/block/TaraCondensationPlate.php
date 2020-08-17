<?php

/*
 *
 *  _____   _____   __   _   _   _____  __    __  _____
 * /  ___| | ____| |  \ | | | | /  ___/ \ \  / / /  ___/
 * | |     | |__   |   \| | | | | |___   \ \/ /  | |___
 * | |  _  |  __|  | |\   | | | \___  \   \  /   \___  \
 * | |_| | | |___  | | \  | | |  ___| |   / /     ___| |
 * \_____/ |_____| |_|  \_| |_| /_____/  /_/     /_____/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author iTX Technologies
 * @link https://itxtech.org
 *
 */

namespace pocketmine\block;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Tile;

class TaraCondensationPlate extends Transparent {
	protected $id = self::TARA_CONDENSATION_PLATE;
    public function __construct()
    {
        parent::__construct($this->id, 0);
    }
    //protected $hasStartedUpdate = false;

	/**
	 * @return string
	 */
	public function getName() : string{
		return '泰拉凝聚板';
	}

	/**
	 * @return \pocketmine\math\AxisAlignedBB
	 */
	public function getBoundingBox(){
		if($this->boundingBox === null){
			$this->boundingBox = $this->recalculateBoundingBox();
		}
		return $this->boundingBox;
	}

    /**
     * @return AxisAlignedBB
     */
    protected function recalculateBoundingBox(){
        return new AxisAlignedBB(
            $this->x,
            $this->y,
            $this->z,
            $this->x + 1,
            $this->y + 0.35,
            $this->z + 1
        );
    }
	/**
	 * @return bool
	 */
	public function canBeFlowedInto(){
		return false;
	}

	/**
	 * @return bool
	 */
	public function canBeActivated() : bool{
		return true;
	}

	/**
	 * @return \pocketmine\tile\TaraCondensationPlate
	 */
	protected function getTile(){
		$t = $this->getLevel()->getTile($this);
		if($t instanceof \pocketmine\tile\TaraCondensationPlate){
			return $t;
		}else{
			$nbt = new CompoundTag("", [
				new StringTag("id", Tile::TARA_CONDENSATION_PLATE),
				new IntTag("x", $this->x),
				new IntTag("y", $this->y),
				new IntTag("z", $this->z)
			]);
			return Tile::createTile(Tile::TARA_CONDENSATION_PLATE, $this->getLevel(), $nbt);
		}
	}

	/**
	 * @param Item        $item
	 * @param Player|null $player
	 *
	 * @return bool
	 */
	public function onActivate(Item $item, Player $player = null){
	    $tile = $this->getTile();
	    if ($player!=null and $player->isSurvival()){
            $count = $item->getCount();
            if ($count > 1){
                $count--;
                $item->setCount($count);
                $player->getInventory()->setItemInHand($item);
                $item = clone $item;//见鬼..
                $item->setCount(1);
                $itemE = $this->getLevel()->dropItem($this->add(0.5, 1, 0.5), $item, new Vector3(0, 0,0), 10, false);
                $itemE->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_IMMOBILE, true);
                $itemE->spawnToAll();
            }elseif($count == 1){
                $player->getInventory()->setItemInHand(Item::get(0));
                $itemE = $this->getLevel()->dropItem($this->add(0.5, 1, 0.5), $item, new Vector3(0, 0,0), 10, false);
                $itemE->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_IMMOBILE, true);
                $itemE->spawnToAll();
            }else{//不可能发生的事..
                return true;
            }
            $tile->scheduleUpdate();//加入更新队列
        }
		return true;
	}

	/**
	 * @return float
	 */
	public function getHardness(){
		return 0.2;
	}

	/**
	 * @return int
	 */
	public function getResistance(){
		return 1;
	}

	/**
	 * @param Item $item
	 *
	 * @return array
	 */
	public function getDrops(Item $item) : array{
		return [
			['材料', '泰拉凝聚板', 1]
		];
	}
}