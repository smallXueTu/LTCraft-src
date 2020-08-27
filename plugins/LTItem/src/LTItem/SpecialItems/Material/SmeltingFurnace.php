<?php


namespace LTItem\SpecialItems\Material;


use LTItem\SpecialItems\Material;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;

class SmeltingFurnace extends Material
{
    public function __construct(array $conf, int $count, CompoundTag $nbt)
    {
        $nbt->BlockEntityTag = new CompoundTag("BlockEntityTag", [
            'Type'=>new StringTag('Type', $nbt['material']),
        ]);
        parent::__construct($conf, $count, $nbt);
    }
}