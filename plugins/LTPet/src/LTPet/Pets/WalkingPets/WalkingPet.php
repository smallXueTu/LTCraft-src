<?php
namespace LTPet\Pets\WalkingPets;

use LTPet\Pets\Pets;
use LTPet\Pets\MountPet;
use pocketmine\block\Air;
use pocketmine\block\Liquid;
use pocketmine\block\Stair;
use pocketmine\block\Slab;
use pocketmine\math\Math;
use pocketmine\math\Vector3;

class WalkingPet extends Pets{
    public function checkJump($dx, $y, $dz)
    {
        $block = $this->level->getBlock($this->add($dx >= 0 ? ceil($dx) : -1, $y, $dz >= 0 ? ceil($dz) : -1));
        if($block->canPassThrough()) {
            if($y == 0)$this->motionY = 0;
            else {
                $this->motionY = 1;
                $this->motionX = 0;
                $this->motionZ = 0;
            }
            return true;
        }
        elseif($block instanceof Slab || $block instanceof Stair) {
            if($y == 0)$this->motionY = 0.5;
            else $this->motionY = 1.5;
            $this->motionX = 0;
            $this->motionZ = 0;
            return true;
        }
        elseif(!in_array($block->getId(), [0, 6, 31, 32, 37, 38, 39, 40, 50, 51, 69, 76, 77])) {
            if($y == 0)$this->motionY = 0;
            if($y == 0)return true;
            else return false;
        }
        elseif(in_array($block->getId(), [27, 28, 66, 70, 72, 78, 147, 148])) {
            if($y == 0)$this->motionY = 0.1;
            else {
                $this->motionY = 1.1;
                $this->motionX = 0;
                $this->motionZ = 0;
            }
            return true;
        }
        return false;
    }
    public function updateMove($tickDiff)
    {
        if($this->server->getTick() % 2 !== 0)return;
        if($this instanceof MountPet and $this->follow==false) {
            $this->pitch = $this->owner->pitch;
            $this->yaw = $this->owner->yaw;
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
            $this->updateMovement();
            return;
        }
        $diff = abs($x) + abs($z);
        $this->motionX = 0.27 * ($x / $diff)*2;
        $this->motionZ = 0.27 * ($z / $diff)*2;
        $this->yaw = -atan2($this->motionX, $this->motionZ) * 180 / M_PI;
        $this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
        $dx = $this->motionX * $tickDiff;
        $dz = $this->motionZ * $tickDiff;
        $newX = Math::floorFloat($this->x + $dx);
        $newZ = Math::floorFloat($this->z + $dz);
        if($this->y < 0){
			return $this->close();
		}
		$block = $this->level->getBlock(new Vector3($newX, Math::floorFloat($this->y), $newZ));
		if(!$block->canPassThrough()) {
			if(!$this->checkJump($dx, 0, $dz)) {
				$this->motionY = 0;
				$this->motionX = 0;
				$this->motionZ = 0;
				return $this->returnToOwner();
			} else {
				if(!$this->checkJump($dx, 1, $dz)) {
					$this->motionY = 0;
					$this->motionX = 0;
					$this->motionZ = 0;
					return $this->returnToOwner();
				}
			}
		} else {
			$block = $this->level->getBlock(new Vector3($newX, Math::floorFloat($this->y - 1), $newZ));
			if(!$block->canPassThrough()) {
				$blockY = Math::floorFloat($this->y);
				if($this->y - $this->gravity  > $blockY) {
					$this->motionY = -$this->gravity ;
				} else {
					$this->motionY = ($this->y - $blockY) > 0 ? ($this->y - $blockY) : 0;
				}
			} else {
				$this->motionY -= $this->gravity ;
			}
		}
		$dy = $this->motionY * $tickDiff;
		$this->move($this->motionX === 0 ? 0 : $dx, $dy, $this->motionZ === 0 ? 0 : $dz);
        $this->updateMovement();
    }
}