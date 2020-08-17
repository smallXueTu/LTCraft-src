<?php
namespace LTEntity\entity\monster;

use LTEntity\entity\FlyingEntity;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Timings;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\Player;

abstract class FlyingMonster extends FlyingEntity
{

    public $maxheight = 12;

    protected $attackDelay = 0;

    public function onUpdate($currentTick)
    {
        $tickDiff = $currentTick - $this->lastUpdate;
        $this->lastUpdate = $currentTick;
		if(!$this->isAlive())
			if(!$this->entityBaseTick($tickDiff))
				return false;
			else
				return true;
        if(!$this->entityBaseTick($tickDiff))return false;
		if($this->vertigoTime>0){
			--$this->vertigoTime;
			$this->yaw+=30;
			$this->pitch=0;
			$this->updateMovement();
			return true;
		}
		$this->attackDelay += $tickDiff;
        $target = $this->updateMove();
        if($target instanceof Player and $target->getGamemode()==0) {
            $this->attackEntity($target);
        }elseif(
            $target instanceof Vector3
            && (($this->x - $target->x) ** 2 + ($this->z - $target->z) ** 2) <= 1
        ) {
            $this->moveTime = 0;
        }
        return true;
    }
/*
    public function entityBaseTick($tickDiff = 1, $EnchantL = 0)
    {
        Timings::$timerEntityBaseTick->startTiming();

        $hasUpdate = parent::entityBaseTick($tickDiff);

        $this->attackDelay += $tickDiff;
        if(!$this->hasEffect(Effect::WATER_BREATHING) && $this->isInsideOfWater()) {
            $hasUpdate = true;
            $airTicks = $this->getDataProperty(self::DATA_AIR) - $tickDiff;
            if($airTicks <= -20) {
                $airTicks = 0;
                $ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_DROWNING, 2);
                $this->attack($ev->getFinalDamage(), $ev);
            }
            $this->setDataProperty(self::DATA_AIR, self::DATA_TYPE_SHORT, $airTicks);
        } else {
            $this->setDataProperty(self::DATA_AIR, self::DATA_TYPE_SHORT, 300);
        }

        Timings::$timerEntityBaseTick->stopTiming();
        return $hasUpdate;
    }*/
}