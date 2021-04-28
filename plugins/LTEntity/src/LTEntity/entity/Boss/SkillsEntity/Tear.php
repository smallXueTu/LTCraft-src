<?php


namespace LTEntity\entity\Boss\SkillsEntity;


use LTEntity\entity\monster\walking\EMods\ANPC;
use LTEntity\entity\ProjectileEntity;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;

class Tear extends Entity
{
    const NETWORK_ID = 105;
    private int $nextEmission = 10;
    private ?Entity $owner = null;
    public function __construct(Level $level, CompoundTag $nbt, Entity $owner)
    {
        $this->owner = $owner;
        parent::__construct($level, $nbt);
    }

    /**
     * @param $currentTick
     * @return bool|void
     */
    public function onUpdate($currentTick)
    {
        $this->age++;
        if ($this->age >= $this->nextEmission){
            $nbt = new CompoundTag('', [
                'Pos' => new ListTag('Pos', [
                    new DoubleTag('', $this->x),
                    new DoubleTag('', $this->y + 0.4),
                    new DoubleTag('', $this->z)
                ]),
                'Motion' => new ListTag('Motion', [
                    new DoubleTag('', (-sin($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI) / 2)),
                    new DoubleTag('', -sin($this->pitch / 180 * M_PI) / 2),
                    new DoubleTag('', (cos($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI) / 2))
                ])
            ]);
            (new TearMissile($this->level, $nbt))->setOwner($this->owner);
            $this->nextEmission = $this->age + mt_rand(40, 80);
        }
        $this->setRotation($this->owner->getYaw(), $this->owner->getPitch());
        $this->updateMovement();
        return true;
    }

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
        $this->level->addEntityMovement($this->chunk->getX(), $this->chunk->getZ(), $this->id, $this->x, ($this instanceof ANPC) ? ($this->y + 1.62) : $this->y, $this->z, $yaw, $this->pitch, $yaw);
    }
    /**
     * TODO：用粒子绘制出竖着圆形
     */
    public function drawCircle(){

    }
    public function move($dx, $dy, $dz)
    {
        $this->setComponents($this->x + $dx, $this->y + $dy, $this->z + $dz);
    }

    public function spawnTo(Player $player){

        $pk = new AddEntityPacket();
        $pk->eid = $this->getId();
        $pk->type = self::NETWORK_ID;
        $pk->x = $this->x;
        $pk->y = $this->y;
        $pk->z = $this->z;
        $pk->speedX = $this->motionX;
        $pk->speedY = $this->motionY;
        $pk->speedZ = $this->motionZ;
        $pk->yaw = $this->yaw;
        $pk->pitch = $this->pitch;
        $pk->metadata = $this->dataProperties;
        $player->dataPacket($pk);

        parent::spawnTo($player);
    }

    public function saveNBT()
    {

    }
}