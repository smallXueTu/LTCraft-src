<?php


namespace LTItem\Mana;


use pocketmine\inventory\BaseInventory;
use pocketmine\Player;

interface Mana
{
    /**
     * @param Player $player
     * @return bool
     */
    public function canUse(Player $player) : bool;

    /**
     * @return string
     */
    public function getOwner() : string ;

    /**
     * @return int
     */
    public function getMaxMana() : int;

    /**
     * @return int
     */
    public function getMana() : int;

    /**
     * @param int $mana
     * @return mixed
     */
    public function addMana(int $mana);

    /**
     * @param int $mana
     * @return bool
     */
    public function consumptionMana(int $mana):bool ;

    /**
     * @param Player $player
     * @param int $index
     * @param BaseInventory $inventory
     * @return bool
     */
    public function onTick(Player $player, int $index, BaseInventory $inventory):bool ;

}