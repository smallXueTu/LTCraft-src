<?php


namespace LTItem\SpecialItems\Material;


use LTItem\SpecialItems\Material;

class SmeltingStone extends Material
{
    public function canBePlaced(): bool
    {
        return false;
    }
}