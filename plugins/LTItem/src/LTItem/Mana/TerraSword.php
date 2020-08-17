<?php


namespace LTItem\Mana;


use pocketmine\entity\Entity;

class TerraSword extends BaseMana
{
    /**
     * @param Entity $entity
     * @return float|int
     */
    public function getModifyAttackDamage(Entity $entity)
    {
        return 12;
    }
}