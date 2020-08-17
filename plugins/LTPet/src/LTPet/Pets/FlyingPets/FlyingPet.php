<?php
namespace LTPet\Pets\FlyingPets;

use LTPet\Pets\Pets;
use LTPet\Pets\MountPet;
use pocketmine\block\Air;
use pocketmine\block\Liquid;
use pocketmine\block\Stair;
use pocketmine\block\Slab;
use pocketmine\math\Math;
use pocketmine\math\Vector3;

class FlyingPet extends Pets{
    public function updateMove($tickDiff)
    {
        if($this->server->getTick() % 2 !== 0)return;
        if($this instanceof MountPet and $this->follow==false) {
            $this->pitch = $this->owner->pitch;
            $this->yaw = $this->owner->yaw;
            if($this instanceof LTEnderDragon) {
                if($this->yaw > 0) {
                    $this->yaw = $this->yaw - 180;
                }elseif($this->yaw < 0) {
                    $this->yaw = $this->yaw + 180;
                }
            }
            $this->updateMovement();
            return true;
        }
        $x = $this->owner->x - $this->x;
        $z = $this->owner->z - $this->z;
        $y = $this->owner->y - $this->y;
        if($x ** 2 + $z ** 2 < 6) {
            $this->motionX = 0;
            $this->motionZ = 0;
            $this->motionY = 0;
            $x = $this->x - $this->owner->x;
            $y = $this->y - $this->owner->y;
            $z = $this->z - $this->owner->z;
            @$this->yaw = asin($x / sqrt($x*$x + $z*$z)) / 3.14 * 180;
            if($z > 0)$this->yaw = -$this->yaw + 180;
            @$this->pitch = round(asin($y / sqrt($x*$x + $z*$z + $y*$y)) / 3.14 * 180);
            if($this instanceof LTEnderDragon) {
                if($this->yaw > 0) {
                    $this->yaw = $this->yaw - 180;
                }
                elseif($this->yaw < 0) {
                    $this->yaw = $this->yaw + 180;
                }
            }
            $this->updateMovement();
            return;
        }
        $diff = abs($x) + abs($z);
        $this->motionX = 0.27 * ($x / $diff)*2;
        $this->motionZ = 0.27 * ($z / $diff)*2;
        $this->yaw = -atan2($this->motionX, $this->motionZ) * 180 / M_PI;
        if($this instanceof LTEnderDragon) {
            if($this->yaw > 0) {
                $this->yaw = $this->yaw - 180;
            }
            elseif($this->yaw < 0) {
                $this->yaw = $this->yaw + 180;
            }
        }
        $this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
        $dx = $this->motionX * $tickDiff;
        $dz = $this->motionZ * $tickDiff;
        $newX = Math::floorFloat($this->x + $dx);
        $newZ = Math::floorFloat($this->z + $dz);
        if($this->y < 0)return $this->close();//飞行宠物
		if($this->y < ($this->owner->y + 3.5) AND $this->y > ($this->owner->y + 2.5)) {
			$block = $this->level->getBlock(new Vector3($newX, Math::floorFloat($this->y) + 1, $newZ));
			if($block instanceof Air or $block instanceof Liquid) {
				if(($this->owner->y + 3) - $this->y > 1) {
					$this->motionY = 1;
				} else {
					$this->motionY = ($this->owner->y + 3) - ($this->y + $this->motionY);
				}
				if(($this->y + $this->motionY) < ($this->owner->y + 3)) {
					$block = $this->level->getBlock(new Vector3($newX, Math::floorFloat($this->y) + 2, $newZ));
					if($block instanceof Air or $block instanceof Liquid) {
						if(($this->owner->y + 3) - ($this->y + $this->motionY) > 1) {
							$this->motionY += 1;
						} else {
							$this->motionY += ($this->owner->y + 3) - ($this->y + $this->motionY);
						}
						if(($this->y + $this->motionY) < ($this->owner->y + 3)) {
							$block = $this->level->getBlock(new Vector3($newX, Math::floorFloat($this->y) + 3, $newZ));
							if($block instanceof Air or $block instanceof Liquid) {
								if(($this->owner->y + 3) - ($this->y + $this->motionY) > 1) {
									$this->motionY += 1;
								} else {
									$this->motionY += ($this->owner->y + 3) - ($this->y + $this->motionY);
								}
							}
						}
					}
				}
			}
		} else {
			if($this->y != $this->owner->y + 3)$this->motionY = 0 - ($this->y - ($this->owner->y + 3));
		}
		$block = $this->level->getBlock(new Vector3($newX, Math::floorFloat($this->y), $newZ));
		if(!($block instanceof Air) and !($block instanceof Liquid)) {
			$block = $this->level->getBlock(new Vector3($newX, Math::floorFloat($this->y) - 1, $newZ));
			if(!($block instanceof Air) and !($block instanceof Liquid)) {
				$this->motionY = 0;
				$this->returnToOwner();
			} else {
				$this->motionY = -1;
			}
		}
		$dy = $this->motionY * $tickDiff;
		$this->move($dx, $dy, $dz);
        $this->updateMovement();
    }
}