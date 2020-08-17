<?php

namespace LTEntity\entity\projectile;

use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\level\Level;
use pocketmine\network\protocol\AddEntityPacket;

use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use LTEntity\entity\projectile\ABlazeFireball;
use pocketmine\level\MovingObjectPosition;


class AEnderCrystal extends Creature
{
	const DATA_SHOOTER_ID = 17;
    public $damage = 0;
    public $angle = 0;
    public $pspeed = 0;
	public $shootingEntity = null;
    public $width = 1;
    public $m = 120;
    public $length = 1;
    public $name;
    public $height = 1;
    public $radius = 3;
    public $speed = 5;
	public $skill=[null, null];
    const NETWORK_ID = 71;
    public function saveNBT() {}
	public function knockBack(Entity $attacker, $damage, $x, $z, $base = 0.4, $force=false){}
	public function __construct(Level $level, CompoundTag $nbt, Entity $shootingEntity = null){
		$this->shootingEntity = $shootingEntity;
		if($shootingEntity !== null){
			$this->setDataProperty(self::DATA_SHOOTER_ID, self::DATA_TYPE_LONG, $shootingEntity->getId());
		}
		parent::__construct($level, $nbt);
	}
/*public function attack($damage, EntityDamageEvent $source){

			Entity::attack($damage, $source);
	}*/
    public function onUpdate($currentTick)
    {
        if($this->closed or !$this->shootingEntity->isAlive()) {
            return false;
        }


        $tickDiff = $currentTick - $this->lastUpdate;
//  $hasUpdate = $this->entityBaseTick($tickDiff);
		if($this->attackTime > 0){
			$this->attackTime -= $tickDiff;
		}
		$this->justCreated = false;
        if($tickDiff <= 0)return true;
        $this->lastUpdate = $currentTick;
        $movingObjectPosition = null;
		if(isset($this->noCanMove))return true;
        $this->pspeed += $this->speed;
        if($this->pspeed > 360)$this->pspeed = 0;
        $cosv = cos(($this->pspeed + $this->angle) * M_PI / 180) * $this->radius;
        $sinv = sin(($this->pspeed + $this->angle) * M_PI / 180) * $this->radius;
        $this->motionX = $cosv - $sinv;
        $this->motionY = 0;
        $this->motionZ = $sinv + $cosv;
        $moveVector = new Vector3($this->shootingEntity->x + $this->motionX, $this->shootingEntity->y + $this->motionY, $this->shootingEntity->z + $this->motionZ);
        $list = $this->getLevel()->getCollidingEntities($this->boundingBox->addCoord($this->motionX, $this->motionY, $this->motionZ)->expand(1, 1, 1), $this);

        $nearDistance = PHP_INT_MAX;
        $nearEntity = null;
        foreach($list as $entity) {
            if($entity === $this->shootingEntity)continue;

            $axisalignedbb = $entity->boundingBox->grow(0.3, 0.3, 0.3);
            $ob = $axisalignedbb->calculateIntercept($this, $moveVector);

            if($ob === null) {
                continue;
            }

            $distance = $this->distanceSquared($ob->hitVector);

            if($distance < $nearDistance) {
                $nearDistance = $distance;
                $nearEntity = $entity;
            }
        }

        if($nearEntity !== null) {
            $movingObjectPosition = MovingObjectPosition::fromEntity($nearEntity);
            $this->movingObjectPosition = $movingObjectPosition;
        }

        if($movingObjectPosition !== null) {
            if($movingObjectPosition->entityHit !== null) {
                $results = $this->attackOccurred($movingObjectPosition->entityHit, $this->damage);
            }
        }
        $this->move($this->motionX, $this->motionY, $this->motionZ);
        $this->updateMovement();
        return true;
    }
	public function setHealth($amount){
		parent::setHealth($amount);
		$this->setNameTag($this->getName().' §b§l[§e'.$this->getHealth().'§f/§4'.$this->getMaxHealth().'§b§l]§f');
	}
    public function move($dx, $dy, $dz): bool
    {
        $radius = $this->width / 2;
        $this->setComponents($this->shootingEntity->x + $dx, $this->shootingEntity->y, $this->shootingEntity->z + $dz);
        $this->boundingBox->setBounds($this->x - $radius, $this->y, $this->z - $radius, $this->x + $radius, $this->y + $this->height, $this->z + $radius);
		return true;
    }

    public function updateMovement()
    {
        $this->level->addEntityMovement($this->chunk->getX(), $this->chunk->getZ(), $this->id, $this->x, $this->y, $this->z, 0, 0, 0);
    }
    public function kill()
    {
        parent::kill();
		if(isset($this->noCanMove)){
			unset($this->shootingEntity->crystal[$this->noCanMove]);
		}
		unset($this->shootingEntity->crystal[$this->angle/$this->m]);
		if(count($this->shootingEntity->crystal)<=0){
			unset($this->shootingEntity->crystal);
			if(isset($this->shootingEntity->notMove)){
				unset($this->shootingEntity->notMove);
				$this->shootingEntity->nextSkillTime = mt_rand(10, 30);
				$this->shootingEntity->lastReleaseSkill = time();
			}
		}
        $this->close();
    }
    public function attackOccurred($target, $damage)
    {

        if($target === null) return false;

        if($this->shootingEntity === null) {
            $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
        } else {
            $ev = new EntityDamageByChildEntityEvent($this->shootingEntity, $this, $target, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
        }

		if($target instanceof Creature){
			switch($this->skill[0]){
			case 'Freeze':
				$target->setFreeze((int)$this->skill[1]);
				if($target instanceof \pocketmine\Player)$target->sendTitle(('§c你被§e'.($this->shootingEntity instanceof BaseEntity?$this->shootingEntity->enConfig['名字']:$this->shootingEntity->getName()).'§c冰冻了'));
				if($this->shootingEntity instanceof \pocketmine\Player)$this->shootingEntity->sendTitle(('§a成功冰冻§e'.($target instanceof BaseEntity?$target->enConfig['名字']:$target->getName())));
			break;
			}
		}
        if($target->attack($ev->getFinalDamage(), $ev) === true) {
            $ev->useArmors();
            return true;
        }

        return false;
    }

	public function setName($name){
		$this->name=$name;
	}
	public function getName(){
		return $this->name;
	}
public function setDamage($v){
$this->damage=$v;
}
    public function spawnTo(Player $player)
    {

        $pk = new AddEntityPacket();
        $pk->eid = $this->getId();
        $pk->type = self::NETWORK_ID;
        $pk->x = $this->x;
        $pk->y = $this->y;
        $pk->z = $this->z;
        $pk->speedX = 0;
        $pk->speedY = 0;
        $pk->speedZ = 0;
        $pk->yaw = $this->yaw;
        $pk->pitch = $this->pitch;
        $pk->metadata = $this->dataProperties;
        $player->dataPacket($pk);

        parent::spawnTo($player);
    }

}