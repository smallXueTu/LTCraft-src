<?php


namespace LTItem\SpecialItems\Material;


use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;

class RewardBoxGene extends \LTItem\SpecialItems\Material
{
    public function __construct(array $conf, int $count, CompoundTag $nbt)
    {
        $nbt->BlockEntityTag = new CompoundTag("BlockEntityTag", [
            'Name'=>new StringTag('Name', ''),
            'Type'=>new StringTag('Type', $nbt['material']),
            'NeedTime'=>new StringTag('NeedTime', 14400),
        ]);
        parent::__construct($conf, $count, $nbt);
    }
}