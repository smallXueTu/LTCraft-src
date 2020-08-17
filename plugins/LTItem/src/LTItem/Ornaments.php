<?php
namespace LTItem;


use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;

interface Ornaments{
    public function getControlReduce() : int;
    public function getPVPDamage() : int;
    public function getPVEDamage() : int;
    public function getPVEMedical() : int;
    public function getPVPMedical() : int;
    public function getGroupOfBack() : int;
    public function getTough() : int;
    public function getRealDamage() : int;
    public function getPVPArmour() : int;
    public function getPVEArmour() : int;
    public function getArmorV() : int;
    public function getLucky() : int;
    public function getMiss() : int;
}