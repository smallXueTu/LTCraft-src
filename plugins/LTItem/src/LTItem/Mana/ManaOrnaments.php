<?php


namespace LTItem\Mana;


use LTItem\Ornaments;
use pocketmine\nbt\tag\CompoundTag;

class ManaOrnaments extends BaseMana implements Ornaments
{
    public function __construct(array $conf, int $count, CompoundTag $nbt, $init = true)
    {
        parent::__construct($conf, $count, $nbt, $init);
    }

    public function getControlReduce(): int
    {
        return 0;
    }

    public function getPVPDamage(): int
    {
        return 0;
    }

    public function getPVEDamage(): int
    {
        return 0;
    }

    public function getPVEMedical(): int
    {
        return 0;
    }

    public function getPVPMedical(): int
    {
        return 0;
    }

    public function getGroupOfBack(): int
    {
        return 0;
    }

    public function getTough(): int
    {
        return 0;
    }

    public function getRealDamage(): int
    {
        return 0;
    }

    public function getPVPArmour(): int
    {
        return 0;
    }

    public function getPVEArmour(): int
    {
        return 0;
    }

    public function getArmorV(): int
    {
        return 0;
    }

    public function getLucky(): int
    {
        return 0;
    }

    public function getMiss(): int
    {
        return 0;
    }
}