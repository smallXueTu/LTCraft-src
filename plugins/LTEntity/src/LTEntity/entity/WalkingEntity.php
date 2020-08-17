<?php

namespace LTEntity\entity;

use LTEntity\entity\monster\walking\PigZombie;
use LTEntity\entity\monster\walking\EMods\NPC;
use LTEntity\Main;
use pocketmine\block\Liquid;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\Player;
use pocketmine\math\Math;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\entity\Creature;
use LTEntity\entity\monster\flying\AEnderDragon;
use LTEntity\entity\monster\walking\EMods\ANPC;

abstract class WalkingEntity extends BaseEntity
{
    protected function checkTarget()
    {
        if($this->isKnockback() or isset($this->Blocks))return;
		if($this->checkBack()){
			$this->baseTarget=$this->spawnPos;
			if($this->distanceNoY($this->baseTarget)<1){
				$this->teleport($this->spawnPos);
			}
			return;
		}
		$target = $this->baseTarget;
		if(($this->enConfig['怪物模式'] !== 2 or $this->isAnger) and (!($target instanceof Player) or $this->distance($target) > $this->enConfig['边界范围半径'] or !$target->isSurvival() or $target->closed or Main::getCount($target->getName())>=10)) {
			if($this->enConfig['团队']){
				foreach($this->getLevel()->getPlayers() as $creature) {
					$distance = $this->distance($creature);
					if($distance > $this->enConfig['边界范围半径'] or !$creature->isSurvival() or !$creature->canSelected() or Main::getCount($creature->getName())>=10)continue;
					$this->moveTime = 0;
					$this->restTime = 0;
					$this->seeTime = 0;
					$this->baseTarget = $creature;
					return;
				}
				if($this->moveTime <= 0 and $this->restTime<=0)$this->baseTarget = null;
			}else{
				if($this->server->getTick()-($this->lastAttackEntity===null?-300:$this->lastAttackEntity[1])>300 or !($target instanceof Player)){
					foreach($this->getLevel()->getPlayers() as $creature) {
						$distance = $this->distance($creature);
						if($distance > $this->enConfig['边界范围半径'] or !$creature->isSurvival() or $creature->closed or !$creature->canSelected() or Main::getCount($creature->getName())>=10)continue;
						if($this->lastAttackEntity!==null and ($this->server->getTick()-$this->lastAttackEntity[1]<300 and $this->lastAttackEntity[0]!==$creature))continue;
						$this->moveTime = 0;
						$this->restTime = 0;
						$this->seeTime = 0;
						$this->baseTarget = $creature;
						return;
					}
					if($this->moveTime <= 0 and $this->restTime<=0)$this->baseTarget = null;
				}elseif($this->distance($target) > $this->enConfig['边界范围半径'] or !$target->isSurvival() or $target->closed)$this->baseTarget = null;
			}
		}
        if($this->baseTarget instanceof Player)return;
        if($this->restTime<=0 and ($this->moveTime <= 0 or !($this->baseTarget instanceof Vector3))) {
			if(!mt_rand(0,2)){
				$this->restTime = mt_rand(60, 200);
				return;
			}
            $x = mt_rand(2, 5);
            $z = mt_rand(2, 5);
            $this->moveTime = mt_rand(20, 100);
			$this->restTime = 0;
			$this->seeTime = 0;
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, 0, mt_rand(0, 1) ? $z : -$z);
        }
    }
	public function checkSee(){
		if($this->seeTime>0)return;
		$this->seeTime = mt_rand(20, 80);
		if(mt_rand(0,2))
			foreach($this->getLevel()->getPlayers() as $player){
				if($this->distance($player) > 5 or $player->closed or !$player->canSelected() or $player->isSpectator())continue;
				$this->baseTarget = $player;
				return;
			}
		$x = mt_rand(2, 5);
		$z = mt_rand(2, 5);
		$this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, 0, mt_rand(0, 1) ? $z : -$z);
		return;
	}
    protected function checkJump($block,$y=0)
    {
        if($y===1){
			if($block->canPassThrough()){
					$this->motionY = 1;
				return true;
			}elseif($block instanceof Slab || $block instanceof Stair) {
				$this->motionY = 1.5;
				return true;
			}
		}elseif($block instanceof Slab || $block instanceof Stair) {
            $this->motionY = 0.5;
            return true;
        }
        return false;
	}

	public function updateMove(){
		if($this->isKnockback() and $this->enConfig['怪物模式'] !== 0) {
			$this->move($this->motionX, 0, $this->motionZ);
			$this->updateMovement();
			return null;
		}
        if($this->age % 3 !== 0) {
            return null;
        }
        $tickDiff = 3;
		$before = $this->baseTarget;
		$this->checkTarget();
		if(!($this->baseTarget instanceof Vector3)){
			$this->checkTarget();
			return null;
		}
		if($this->restTime>0)$this->checkSee();
		$x = $this->baseTarget->x - $this->x;
		$y = $this->baseTarget->y - $this->y;
		$z = $this->baseTarget->z - $this->z;
		$diff = abs($x) + abs($z);
		if($x ** 2 + $z ** 2 < 0.7) {
			$this->motionX = 0;
			$this->motionZ = 0;
			$this->motionY = 0;
		} else {
			$this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
			$this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
			$this->motionY = 0;
		}
		if($x==0 and $z==0){
			$yaw = 0;
			$pitch = $y>0?-90:90;
			if($y==0)$pitch=0;
		}else{
			$this->yaw = -atan2($x / $diff, $z / $diff) * 180 / M_PI;
			$this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
		}
		if($this->enConfig['怪物模式'] == 0) {
			$this->updateMovement();
			return $this->baseTarget;
		}
		$dx = $this->motionX * $tickDiff;
		$dz = $this->motionZ * $tickDiff;
		$newX = Math::floorFloat($this->x + $dx);
		$newZ = Math::floorFloat($this->z + $dz);
		if($this->y < 0) {
			if($this->enConfig['刷怪点']=='傀儡'){
				$this->kill();
				return null;
			}
			$this->transferTo($this->spawnPos);
			return null;
		}
		$block = $this->level->getBlock(new Vector3($this->x, Math::floorFloat($this->y - 1), $this->z));
		if(!$block->canPassThrough()) {
			if($this->restTime>0){
				$this->updateMovement();
				return null;
			}
			$block = $this->level->getBlock($this);
			if(!$block->canPassThrough() and !($block instanceof Slab)) {
				$this->motionY = $block->y+1-$this->y;
				$this->motionX = 0;
				$this->motionZ = 0;
			}else{
				$block = $this->level->getBlock(new Vector3($newX, Math::floorFloat($this->y), $newZ));
				if(!$block->canPassThrough()) {
					if(!$this->checkJump($block)) {
						$block=$this->level->getBlock(new Vector3($newX, Math::floorFloat($this->y+1), $newZ));
						if(!$this->checkJump($block,1)) {
							if($this->checkBack()){
								$this->teleport($this->spawnPos);
								return null;
							}
							$this->motionY = 0;
							$this->motionX = 0;
							$this->motionZ = 0;
							$this->updateMovement();
							return $this->baseTarget;
						}
					}
				}else{
					$blockY = Math::floorFloat($this->y);
					for($i=1;$i<=$tickDiff;$i++){
						if($this->y - $this->gravity * $i  > $blockY) {
							$this->motionY = -($this->gravity * $i);
							continue;
						} else {
							$this->motionY = -($this->y - $blockY);
							break;
						}
					}
				}
			}
		} else {
			// $this->motionY = -($this->gravity  * $tickDiff);
			$this->motionY = -1;
			if($this->restTime>0){
				$this->move(0, $this->motionY ,0);
				$this->updateMovement();
				return null;
			}
		}
		$dy = $this->motionY;
		$this->move($this->motionX === 0 ? 0 : $dx, $dy, $this->motionZ === 0 ? 0 : $dz);
		$this->updateMovement();
		return $this->baseTarget;
	}
}