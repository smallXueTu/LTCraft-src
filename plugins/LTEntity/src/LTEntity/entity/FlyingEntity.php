<?php

namespace LTEntity\entity;

use LTEntity\entity\monster\flying\ABlaze;
use pocketmine\math\Math;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\entity\Creature;

abstract class FlyingEntity extends BaseEntity{
    protected function checkTarget()
    {
        if($this->isKnockback() or isset($this->Blocks))return;
		$target = $this->baseTarget;
		if(($this->enConfig['怪物模式'] !== 2 or $this->isAnger) and (!($target instanceof Player) or $this->distance($target) > $this->enConfig['边界范围半径'] or !$target->isSurvival() or $target->closed)) {
			if($this->enConfig['团队']){
				foreach($this->getLevel()->getPlayers() as $creature) {
					$distance = $this->distance($creature);
					if($distance > $this->enConfig['边界范围半径'] or !$creature->isSurvival() or !$creature->canSelected())continue;
					$this->moveTime = 0;
					$this->baseTarget = $creature;
					return;
				}
				if($this->moveTime <= 0)$this->baseTarget = null;
			}else{
				if($this->server->getTick()-($this->lastAttackEntity===null?-300:$this->lastAttackEntity[1])>300 or !($target instanceof Player)){
					foreach($this->getLevel()->getPlayers() as $creature) {
						$distance = $this->distance($creature);
						if($distance > $this->enConfig['边界范围半径'] or !$creature->isSurvival() or $creature->closed or !$creature->canSelected())continue;
						if($this->lastAttackEntity!==null and ($this->server->getTick()-$this->lastAttackEntity[1]<300 and $this->lastAttackEntity[0]!==$creature))continue;
						$this->moveTime = 0;
						$this->baseTarget = $creature;
						return;
					}
					if($this->moveTime <= 0)$this->baseTarget = null;
				}elseif($this->distance($target) > $this->enConfig['边界范围半径'] or !$target->isSurvival() or $target->closed)$this->baseTarget = null;
			}
		}
		
        if($this->baseTarget instanceof Player)return;
        if($this->moveTime <= 0 or !($this->baseTarget instanceof Vector3)) {
            $x = mt_rand(5, 10);
            $z = mt_rand(5, 10);
            $this->moveTime = mt_rand(20, 100);
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, 0, mt_rand(0, 1) ? $z : -$z);
        }
	}
	public function updateMove(){
        if($this->age % 3 !== 0) {
            return $this->baseTarget;
        }
		$this->checkTarget();
		if($this->baseTarget instanceof Player){
			$x = $this->baseTarget->x - $this->x;
			$y = $this->baseTarget->y - $this->y;
			$z = $this->baseTarget->z - $this->z;
			$diff = abs($x) + abs($z);
			if($x==0 and $z==0){
				$yaw = 0;
				$pitch = $y>0?-90:90;
				if($y==0)$pitch=0;
			}else{
				$this->yaw = rad2deg(-atan2($x / $diff, $z / $diff));
				$this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
			}
		}
		$this->updateMovement();
		return $this->baseTarget;	
	}
}