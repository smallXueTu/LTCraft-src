<?php
namespace LTItem\SpecialItems\Armor;


interface ReduceMana
{
    public function getReduce(): float;
    public function setReduceMana(int $value): ReduceMana;
    public function getReduceMana();
    public function getMaxReduce(): int;
}