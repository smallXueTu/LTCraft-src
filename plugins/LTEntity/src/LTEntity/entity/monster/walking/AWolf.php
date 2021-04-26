<?php

namespace LTEntity\entity\monster\walking;

use LTEntity\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;

class AWolf extends WalkingMonster
{
    const NETWORK_ID = 14;

    public $width = 0.72;
    public $height = 0.9;

    public $eyeHeight = 0.7;

    public function getSpeed(): float
    {
        return isset($this->namedtag['Speed']) ? $this->namedtag['Speed'] : 1.2;
    }

    public function initThis()
    {
        parent::initThis();
        if ($this->enConfig['怪物模式'] == 1 or $this->enConfig['怪物模式'] == 0)
            $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ANGRY, true);
    }

    public function setAnger($target = null)
    {
        if ($this->isAnger == false) {
            $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ANGRY, true);
            parent::setAnger($target);
        }
    }

    public function getName()
    {
        return 'Wolf';
    }

    public function attackEntity(Entity $player)
    {
        if ($this->attackDelay > 10 && ($this->distanceSquared($player) < 1 or ($this->distanceSquaredNoY($player) < 1 and abs($player->y - $this->y) < 1.5))) {
            $this->attackDelay = 0;

            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
        }
    }

    public function getDrops()
    {
        return [];
    }

}
