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
use pocketmine\Player;
use pocketmine\Server;

class ShiZhongji extends ManaFlower {
	protected $id = self::SHIZHONGJI;
	/**
	 * ShiZhongji constructor.
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return '石中姬';
	}


	/**
	 * @param int $type
	 *
	 * @return bool|int
	 */
	public function onUpdate($type){
	    if (Server::getInstance()->getTick() - $this->lastUpdate > 10){
            $this->lastUpdate = Server::getInstance()->getTick();
            $blocks = [];
            $blocks[] = $this->level->getBlock($this->add(1));
            $blocks[] = $this->level->getBlock($this->add(-1));
            $blocks[] = $this->level->getBlock($this->add(0, 0, 1));
            $blocks[] = $this->level->getBlock($this->add(0, 0, -1));
            if ($this->mana < self::MAX_MANA)foreach ($blocks as $block){
                if ($block instanceof Stone or $block instanceof Cobblestone){
                    $this->mana += min(15, self::MAX_MANA - $this->mana);
                    $this->level->setBlock($block, new Air());
                }
                if ($this->mana >= self::MAX_MANA)break;
            }
            $this->exportMana();
        }
		return true;
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
		if($down->isTransparent() === false){
			$this->getLevel()->setBlock($block, $this, true, true);

			return true;
		}

		return false;
	}
}