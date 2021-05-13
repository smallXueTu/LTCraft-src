<?php


namespace LTItem\SpecialItems\Armor;


class FairyArmor extends \LTItem\SpecialItems\Armor implements ReduceMana
{

    public function getReduce(): float
    {
        return 0.5;
    }
}