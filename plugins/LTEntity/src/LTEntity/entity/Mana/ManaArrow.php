<?php


namespace LTEntity\entity\Mana;


use pocketmine\entity\Arrow;
use pocketmine\entity\Entity;
use pocketmine\Player;

class ManaArrow extends Arrow
{
    protected int $maxAge = 60;
    protected $gravity = 0;
    public function getDamage(Entity $entity = null): int
    {
        if ($entity instanceof Player){
            return $entity->getMaxHealth() * 0.34;
        }else{
            return parent::getDamage($entity);
        }
    }
}