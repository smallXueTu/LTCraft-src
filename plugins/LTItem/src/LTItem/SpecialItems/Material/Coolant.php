<?php


namespace LTItem\SpecialItems\Material;


use LTItem\SpecialItems\Material;

class Coolant extends Material
{
    public function canBePlaced(): bool {
        return false;
    }
}