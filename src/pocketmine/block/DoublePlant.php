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
use pocketmine\Player;

class DoublePlant extends Flowable {

	protected $id = self::DOUBLE_PLANT;

	const SUNFLOWER = 0;
	const LILAC = 1;
	const DOUBLE_TALLGRASS = 2;
	const LARGE_FERN = 3;
	const ROSE_BUSH = 4;
	const PEONY = 5;

	/**
	 * DoublePlant constructor.
	 *
	 * @param int $meta
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	/**
	 * @return bool
	 */
	public function canBeReplaced(){
		return true;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		static $names = [
			0 => '向日葵',
			1 => '丁香花',
			2 => '高草丛',
			3 => '大型蕨',
			4 => '玫瑰丛',
			5 => '牡丹'
		];
		return $names[$this->meta & 0x07];
	}

	/**
	 * @param int $type
	 *
	 * @return bool|int
	 */
	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			if($this->getSide(0)->isTransparent() === true && !$this->getSide(0) instanceof DoublePlant){ //Replace with common break method
				$this->getLevel()->setBlock($this, new Air(), false, false);

				return Level::BLOCK_UPDATE_NORMAL;
			}
		}

		return false;
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
		$up = $this->getSide(1);
		if($down->getId() === self::GRASS or $down->getId() === self::DIRT){
			$this->getLevel()->setBlock($block, $this, true);
			$this->getLevel()->setBlock($up, Block::get($this->id, $this->meta ^ 0x08), true);
			return true;
		}
		return false;
	}

	/**
	 * @param Item $item
	 *
	 * @return mixed|void
	 */
	public function onBreak(Item $item){
		$up = $this->getSide(1);
		$down = $this->getSide(0);
		if(($this->meta & 0x08) === 0x08){ // This is the Top part of flower
			if($up->getId() === $this->id and $up->meta !== 0x08){ // Checks if the block ID and meta are right
				$this->getLevel()->setBlock($up, new Air(), true, true);
			}elseif($down->getId() === $this->id and $down->meta !== 0x08){
				$this->getLevel()->setBlock($down, new Air(), true, true);
			}
		}else{ // Bottom Part of flower
			if($up->getId() === $this->id and ($up->meta & 0x08) === 0x08){
				$this->getLevel()->setBlock($up, new Air(), true, true);
			}elseif($down->getId() === $this->id and ($down->meta & 0x08) === 0x08){
				$this->getLevel()->setBlock($down, new Air(), true, true);
			}
		}
	}

	/**
	 * @param Item $item
	 *
	 * @return array
	 */
	public function getDrops(Item $item) : array{
		if(($this->meta & 0x08) !== 0x08){
			return [[Item::DOUBLE_PLANT, $this->meta, 1]];
		}else{
			return [];
		}
	}
}
