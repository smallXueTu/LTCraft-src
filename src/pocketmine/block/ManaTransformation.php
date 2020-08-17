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

use LTItem\Mana\Mana;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\Player;
use pocketmine\tile\Chest as TileChest;
use pocketmine\tile\ManaChest;
use pocketmine\tile\Tile;


class ManaTransformation extends Solid {
	protected $id = self::MANACHEST;

	/**
	 * NetherQuartzOre constructor.
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return '魔力转换器';
	}

	/**
	 * @return int
	 */
	public function getToolType(){
		return Tool::TYPE_PICKAXE;
	}

    /**
     * @return bool
     */
	public function canBeActivated(): bool
    {
        return true;
    }


    /**
     * @return float
     */
    public function getHardness(){
        return 1.4;
    }

	/**
	 * @return int
	 */
	public function getResistance(){
		return 15;
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
            new ListTag("Items", []),
            new StringTag("id", Tile::MANACHEST),
            new StringTag("CustomName", "魔力转换器"),
            new IntTag("x", $this->x),
            new IntTag("y", $this->y),
            new IntTag("z", $this->z)
        ]);
        $nbt->Items->setTagType(NBT::TAG_Compound);

        Tile::createTile("ManaChest", $this->getLevel(), $nbt);

        return true;
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
        }
        if ($item instanceof Mana){
            $this->getLevel()->setBlock($this, new Air(), false, true);
        }else{
            $this->getLevel()->setBlock($this, new Air(), true, true);
        }

        return true;
    }

    public function close(Player $player){
        $pk = new UpdateBlockPacket();
        $pk->x = (int)$this->x;
        $pk->z = (int)$this->z;
        $pk->y = (int)$this->y;
        $pk->blockId = $this->getId();
        $pk->blockData = 0;
        $player->dataPacket($pk);

        $t = $this->getLevel()->getTile($this);
        $chest = null;
        if($t instanceof ManaChest){
            $chest = $t;
        }
        $chest->scheduleUpdate();
    }

    /**
     * @param Item        $item
     * @param Player|null $player
     *
     * @return bool
     */
    public function onActivate(Item $item, Player $player = null){
        if($player instanceof Player){
            $t = $this->getLevel()->getTile($this);
            $chest = null;
            if($t instanceof ManaChest){
                $chest = $t;
            }else{
                $nbt = new CompoundTag("", [
                    new ListTag("Items", []),
                    new StringTag("id", Tile::MANACHEST),
                    new StringTag("CustomName", "魔力转换器"),
                    new IntTag("x", $this->x),
                    new IntTag("y", $this->y),
                    new IntTag("z", $this->z)
                ]);
                $nbt->Items->setTagType(NBT::TAG_Compound);
                $chest = Tile::createTile("ManaChest", $this->getLevel(), $nbt);
            }
            $this->level->sendBlocks([$player], [
                $this->level->getBlock($this->add(1, 0, 0)),
                $this->level->getBlock($this->add(-1, 0, 0)),
                $this->level->getBlock($this->add(0, 1, 0)),
                $this->level->getBlock($this->add(-0, -1, 0)),
                $this->level->getBlock($this->add(-0, 0, 1)),
                $this->level->getBlock($this->add(-0, 0, -1)),
                ], UpdateBlockPacket::FLAG_ALL_PRIORITY);//直接点
            if($player->isCreative() and $player->getServer()->limitedCreative){
                return true;
            }
            $pk = new UpdateBlockPacket();
            $pk->x = (int)$this->x;
            $pk->z = (int)$this->z;
            $pk->y = (int)$this->y;
            $pk->blockId = 54;
            $pk->blockData = 0;
            $player->dataPacket($pk);
            $chest->spawnTo($player);
            //$chest->beOpened();
            $player->addWindow($chest->getInventory());
        }

        return true;
    }

	/**
	 * @param Item $item
	 *
	 * @return array
	 */
	public function getDrops(Item $item) : array{
		if($item->isPickaxe() >= Tool::TIER_WOODEN){
            return [
                ['材料', '魔力转换器', 1],
            ];

            return [];
		}else{
			return [];
		}
	}
}
