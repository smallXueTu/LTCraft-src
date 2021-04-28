<?php


namespace LTItem\Mana;


use pocketmine\entity\Entity;
use pocketmine\Player;

class HundredMiddleBow extends BaseMana
{
    /**
     * @param Entity $entity
     * @return float|int
     */
    public function getModifyAttackDamage(Entity $entity)
    {
        if ($entity instanceof Player){
            return $entity->getMaxHealth() * 0.2;
        }
        return 18;
    }
}