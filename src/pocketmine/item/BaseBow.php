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


use pocketmine\entity\Entity;
use pocketmine\entity\Arrow;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\Player;

class BaseBow extends Tool implements Bow{
	/**
	 * Bow constructor.
	 *
	 * @param int $meta
	 * @param int $count
	 */
	public function __construct($meta = 0, $count = 1){
		parent::__construct(self::BOW, $meta, $count, "弓");
	}

    public function spawnArrow(Player $player): ?Entity
    {
        $item = $this->getResources($player);
        if ($item==null){
            return null;
        }
        $nbt = new CompoundTag('', [
            'Pos' => new ListTag('Pos', [
                new DoubleTag('', $player->x),
                new DoubleTag('', $player->y + $player->getEyeHeight()),
                new DoubleTag('', $player->z)
            ]),
            'Motion' => new ListTag('Motion', [
                new DoubleTag('', -sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
                new DoubleTag('', -sin($player->pitch / 180 * M_PI)),
                new DoubleTag('', cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI))
            ]),
            'Rotation' => new ListTag('Rotation', [
                new FloatTag('', $player->yaw),
                new FloatTag('', $player->pitch)
            ]),
            'Fire' => new ShortTag('Fire', $player->isOnFire() ? 45 * 60 : 0),
            'Potion' => new ShortTag('Potion', $item->getDamage())
        ]);
        $diff = ($player->getServer()->getTick() - $player->startAction);
        $p = $diff / 20;
        $f = min((($p ** 2) + $p * 2) / 3, 1) * 2;
        $entity = new Arrow($player->getLevel(), $nbt, $player, 2 == $f);
        $entity->f = $f;
        $entity->diff = $diff;
        if($this->getEnchantmentLevel(Enchantment::TYPE_BOW_INFINITY)!=false)$entity->setCanBePickedUp(false);
        return $entity;
    }
    public function getResources(Player $player): Item{
        $arrow = null;
        $index = $player->getInventory()->first(Item::get(Item::ARROW, 0));
        if($index !== -1){
            $arrow = $player->getInventory()->getItem($index);
            $arrow->setCount(1);
        }elseif($player->isCreative()){
            $arrow = Item::get(Item::ARROW, 0, 1);
        }else{
            $player->getInventory()->sendContents($player);
        }
        return $arrow;
    }
    public function deductResources(Player $player): bool
    {
        $item = $this->getResources($player);
        if($player->isSurvival()){
            if(!$this->getEnchantmentLevel(Enchantment::TYPE_BOW_INFINITY)!==false)$player->getInventory()->removeItem($item);
            if(!$this->isUnbreakable()){
                $this->setDamage($this->getDamage() + 1);
                if($this->getDamage() >= 385){
                    $player->getInventory()->setItemInHand(Item::get(Item::AIR, 0, 0));
                }else{
                    $player->getInventory()->setItemInHand($this);
                }
            }
        }
        return true;
    }
}