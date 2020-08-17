<?php


namespace LTItem\Mana;


class EternalManaRing extends ManaOrnaments
{
    /**
     * @return int
     */
    public function getMana(): int
    {
        return PHP_INT_MAX;
    }

    /**
     * @return int
     */
    public function getMaxMana(): int
    {
        return PHP_INT_MAX;
    }

    /**
     * @param int $Mana
     * @return bool
     */
    public function consumptionMana(int $Mana): bool
    {
        return true;
    }
}