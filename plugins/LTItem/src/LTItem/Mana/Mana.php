<?php


namespace LTItem\Mana;


use pocketmine\inventory\BaseInventory;
use pocketmine\Player;

interface Mana
{
    /**
     * 判断这个玩家是否可以使用
     * @param Player $player
     * @param bool $playerCheck
     * @return bool
     */
    public function canUse(Player $player, $playerCheck = true) : bool;

    /**
     * 获取绑定
     * @return string
     */
    public function getOwner() : string ;

    /**
     * 获取最大Mnaa
     * @return int
     */
    public function getMaxMana() : int;

    /**
     * 获取剩余Mana
     * @return int
     */
    public function getMana() : int;

    /**
     * 增加Mana
     * @param int $mana
     * @return mixed
     */
    public function addMana(int $mana);

    /**
     * 扣Mana
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

    /**
     * 是否可以取Mana
     * @return bool
     */
    public function canPutMana(): bool;
}