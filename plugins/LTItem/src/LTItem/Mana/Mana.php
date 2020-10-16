<?php


namespace LTItem\Mana;


use pocketmine\inventory\BaseInventory;
use pocketmine\Player;

interface Mana
{
    /**
     * @param Player $player
     * @param bool $playerCheck
     * @return bool
     */
    public function canUse(Player $player, $playerCheck = true) : bool;

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
     * tick 当次物品在背包内会每tick调用一次
     * @param Player $player
     * @param int $index
     * @param BaseInventory $inventory
     * @return bool
     */
    public function onTick(Player $player, int $index, BaseInventory $inventory):bool ;

}