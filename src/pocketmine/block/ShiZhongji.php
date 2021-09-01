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

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Tile;

class ShiZhongji extends Transparent {
	protected $id = self::SHIZHONGJI;
	/**
	 * ShiZhongji constructor.
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
	}


    /**
     * @param int $type
     *
     * @return bool|int
     */
    public function onUpdate($type){
        if($type === Level::BLOCK_UPDATE_NORMAL){
            if($this->getSide(0)->isTransparent() === true){
                $this->getLevel()->useBreakOn($this);
                return Level::BLOCK_UPDATE_NORMAL;
            }
            $this->getLevel()->getTile($this)->scheduleUpdate();
        }

        return false;
    }
	/**
	 * @return string
	 */
	public function getName() : string{
		return '石中姬';
	}

	/**
	 * @param Item        $item
	 * @param Block       $block
	 * @param Block       $target
	 * @param int         $face
	 * @param float       $fx
	 * @param float       $fy
	 * @param float       $fz
	 * @param Player|null $player
	 *
	 * @return bool
	 */
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$down = $this->getSide(0);
        if($down->getId() === 2 or $down->getId() === 3 or $down->getId() === 60){
            $this->getLevel()->setBlock($block, $this, true, true);
            $nbt = new CompoundTag("", [
                new StringTag("id", Tile::SHIZHONGJI),
                new IntTag("x", $this->x),
                new IntTag("y", $this->y),
                new IntTag("z", $this->z)
            ]);

            Tile::createTile("ShiZhongji", $this->getLevel(), $nbt);
            return true;
        }

		return false;
	}
	public function getBoundingBox()
    {
        return null;
    }

    public function getDrops(Item $item): array
    {
        return [['材料', '石中姬', 1]];
    }
}