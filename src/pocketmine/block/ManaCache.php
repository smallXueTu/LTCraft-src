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

use LTItem\Mana\EternalManaRing;
use LTItem\Mana\Mana;
use LTItem\Mana\TerraShatterer;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Tile;

//TODO: check orientation
class ManaCache extends Solid {

	protected $id = self::MANACACHE;

	/**
	 * Stonecutter constructor.
	 *
	 * @param int $meta
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
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
        $this->getLevel()->setBlock($block, $this, true, true);
        $nbt = new CompoundTag("", [
            new StringTag("id", Tile::MANACACHE),
            new IntTag("x", $this->x),
            new IntTag("y", $this->y),
            new IntTag("z", $this->z)
        ]);

        Tile::createTile("ManaCache", $this->getLevel(), $nbt);

        return true;
    }
    public function canBeActivated(): bool
    {
        return true;
    }

    /**
     * @param Item $item
     * @param Player|null $player
     * @return bool|void
     */
    public function onActivate(Item $item, Player $player = null)
    {
        if ($player->isSurvival()){
            /** @var \pocketmine\tile\ManaCache $tile */
            $tile = $this->level->getTile($this);
            if (!($tile instanceof \pocketmine\tile\ManaCache)) {
                $nbt = new CompoundTag("", [
                    new StringTag("id", Tile::MANACACHE),
                    new IntTag("Mana", 0),
                    new IntTag("x", $this->x),
                    new IntTag("y", $this->y),
                    new IntTag("z", $this->z)
                ]);

                $tile = Tile::createTile("ManaCache", $this->getLevel(), $nbt);
            }
            /*
            $mana = $item->getMana();
            if ($mana>0){
                if ($item->consumptionMana($mana)) {
                    $tile->addMana($mana);
                    $player->getInventory()->setItemInHand($item);
                }
            }elseif($tile->getMana()>0){
                if ($tile->putMana($item->getMaxMana())) {
                    $item->addMana($item->getMaxMana());
                }else{
                    $tileMana = $tile->getMana();
                    if ($tile->putMana($tileMana)){
                        $item->addMana($tileMana);
                    }
                }
                $player->getInventory()->setItemInHand($item);
            }
            */
        }
        return true;
    }

    /**
	 * @return string
	 */
	public function getName() : string{
		return '魔力缓存器';
	}

	/**
	 * @return int
	 */
	public function getToolType(){
		return Tool::TYPE_PICKAXE;
	}

	/**
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function onBreak(Item $item){
		$t = $this->getLevel()->getTile($this);
		if($t instanceof TileChest){
            $t->unpair();
            $t->close();
		}
		$this->getLevel()->setBlock($this, new Air(), true, true);

		return true;
	}

    /**
     * @return float
     */
    public function getHardness(){
        return 1.5;
    }

	/**
	 * @param Item $item
	 *
	 * @return array
	 */
	public function getDrops(Item $item) : array{
		if($item->isPickaxe() >= 1){
			return [
				['材料', '魔力缓存器', 1],
			];
		}else{
			return [];
		}
	}
}