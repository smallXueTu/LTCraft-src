<?php


namespace LTEntity\entity\Gaia\SkillEntity;

use LTEntity\entity\Gaia\GaiaGuardiansIII;
use pocketmine\entity\Projectile;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\MovingObjectPosition;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;

class Bomb extends Projectile
{
    protected $gravity = 0.1;
    /** @var GaiaGuardiansIII $owner */
    private GaiaGuardiansIII $owner;

    /** @var callable */
    protected $callable = null;

    /** @var array */
    protected $args;
    public function __construct(Level $level, CompoundTag $nbt, GaiaGuardiansIII $gaiaGuardiansIII)
    {
        $this->owner = $gaiaGuardiansIII;
        parent::__construct($level, $nbt);
    }

    public function onUpdate($currentTick)
    {
        if($this->closed) {
            return false;
        }

        $this->age++;
        if($this->isAlive()) {
            if($this->age == 10){
                $this->motionY = 0 - $this->motionY * 1.5;
            }
            if ($this->age > 15){
                $movingObjectPosition = null;

                $moveVector = new Vector3($this->x + $this->motionX, $this->y + $this->motionY, $this->z + $this->motionZ);

                $list = $this->getLevel()->getCollidingEntities($this->boundingBox->addCoord($this->motionX, $this->motionY, $this->motionZ)->expand(1, 1, 1), $this);

                $nearDistance = PHP_INT_MAX;
                $nearEntity = null;

                foreach($list as $entity) {
                    if($entity === $this->owner or !($entity instanceof Player)) { //是发射的实体就进入下次循环
                        continue;
                    }

                    $axisalignedbb = $entity->boundingBox->grow(0.3, 0.3, 0.3);//碰撞盒
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

                if($nearEntity !== null) { //近
                    $movingObjectPosition = MovingObjectPosition::fromEntity($nearEntity);
                    $this->movingObjectPosition = $movingObjectPosition;
                }

                if($movingObjectPosition !== null) {
                    if($movingObjectPosition->entityHit !== null) {
                        $this->attackOccurred($movingObjectPosition->entityHit);
                        $this->hadCollision = true;
                        $this->close();
                    }
                }
            }

            $this->move($this->motionX, $this->motionY, $this->motionZ);
            if ($this->onGround){
                $this->close();
            }

            $this->updateMovement();

        }
        if($this->age > 30) { //是相撞的。
            $this->close();
        }
        return true;
    }
    public function saveNBT()
    {

    }

    /**
     * @return bool|void
     */
    public function updateMovement()
    {
        if(!($this->chunk instanceof Chunk)){
            $this->close();
            return false;
        }
        if($this->lastX !== $this->x || $this->lastY !== $this->y || $this->lastZ !== $this->z || $this->lastYaw !== $this->yaw || $this->lastPitch !== $this->pitch) {
            $this->lastX = $this->x;
            $this->lastY = $this->y;
            $this->lastZ = $this->z;
            $this->lastYaw = $this->yaw;
            $this->lastPitch = $this->pitch;
        }
        $yaw = $this->yaw;
        $this->level->addEntityMovement($this->chunk->getX(), $this->chunk->getZ(), $this->id, $this->x, $this->y, $this->z, $yaw, $this->pitch, $yaw);
    }

    /**
     * @return GaiaGuardiansIII
     */
    public function getOwner(): GaiaGuardiansIII
    {
        return $this->owner;
    }

    /**
     * @param GaiaGuardiansIII $owner
     */
    public function setOwner(GaiaGuardiansIII $owner): void
    {
        $this->owner = $owner;
    }

    /**
     * @param $target Player
     */
    public function attackOccurred($target)
    {
        if($target === null) return;
        $ev = new EntityDamageByChildEntityEvent($this->owner, $this, $target, EntityDamageEvent::CAUSE_PROJECTILE, $target->getMaxHealth() * 0.3);
        $target->attack($ev->getFinalDamage(), $ev);
        if ($this->callable!=null)call_user_func_array($this->callable, []);
    }

    /**
     * @param $callable callable 回调函数
     */
    public function setHitFun($callable){
        $this->callable = $callable;
    }

    /**
     * @param Player $player
     */
    public function spawnTo(Player $player){
        $pk = new AddEntityPacket();
        $pk->type = 76;
        $pk->eid = $this->getId();
        $pk->x = $this->x;
        $pk->y = $this->y;
        $pk->z = $this->z;
        $pk->speedX = $this->motionX;
        $pk->speedY = $this->motionY;
        $pk->speedZ = $this->motionZ;
        $pk->metadata = $this->dataProperties;
        $player->dataPacket($pk);

        parent::spawnTo($player);
    }
}