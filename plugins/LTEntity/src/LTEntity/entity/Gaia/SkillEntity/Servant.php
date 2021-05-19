<?php


namespace LTEntity\entity\Gaia\SkillEntity;

use LTEntity\entity\Gaia\GaiaGuardiansIII;
use pocketmine\entity\Creature;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\NamedTag;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;
use pocketmine\Server;

class Servant extends Creature
{
    private GaiaGuardiansIII $owner;
    private int $nextLaunch = 50;
    private int $lastUpdateSee = 0;
    /** @var ?Position  */
    private ?Position $baseTarget = null;
	public ?string $index = null;

    /**
     * Servant constructor.
     * @param Level $level
     * @param CompoundTag $nbt
     * @param GaiaGuardiansIII $gaiaGuardiansIII
     */
    public function __construct(Level $level, CompoundTag $nbt, GaiaGuardiansIII $gaiaGuardiansIII)
    {
        $this->owner = $gaiaGuardiansIII;
        parent::__construct($level, $nbt);
    }
    protected function initEntity()
    {
        $this->setNameTagAlwaysVisible(true);
        $this->setNameTagVisible(true);
        $this->setNameTag("§d仆从\n[".$this->getHealth()."/".$this->getMaxHealth());
        parent::initEntity();
    }
    public function setHealth($amount)
    {
        parent::setHealth($amount);
        $this->setNameTag("§d仆从\n[".$this->getHealth()."/".$this->getMaxHealth());
    }
    public function close()
    {
        parent::close();
		$this->owner->removeServant($this->index);
    }

    public function onUpdate($tick)
    {
        $this->age++;
        $this->updateTarget();
        $this->updateMovement();
        if ($this->age == $this->nextLaunch){
            $this->launchBomb();
            $this->nextLaunch = $this->age + mt_rand(80, 140);
        }
        if ($this->noDamageTicks > 0){
            $this->noDamageTicks--;
        }
        if ($this->attackTime > 0){
            $this->attackTime--;
        }
        if (!$this->isAlive()){
            $this->close();
            return false;
        }
        return true;
    }

    /**
     * 更新看的目标
     */
    public function updateTarget(){
        $this->checkCeeTarget();
        //$this->baseTarget = Server::getInstance()->getPlayer("A");
        if ($this->baseTarget !== null){
            $x = $this->baseTarget->x - $this->x;
            $y = $this->baseTarget->y - $this->y;
            $z = $this->baseTarget->z - $this->z;
            $diff = abs($x) + abs($z);
            if($x==0 and $z==0) {
                $this->yaw= 0;
                $this->pitch = $y > 0 ? -90 : 90;
            }else{
                $this->yaw = -atan2($x / $diff, $z / $diff) * 180 / M_PI;
                $this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
            }
        }
    }

    /**
     * 检查看的目标
     */
    protected function checkCeeTarget()
    {
        if(Server::getInstance()->getTick() - $this->lastUpdateSee > 100) {
            $this->lastUpdateSee = Server::getInstance()->getTick() + mt_rand(-20, 20);
            $x = mt_rand(2, 5);
            $z = mt_rand(2, 5);
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, mt_rand(-5, 5), mt_rand(0, 1) ? $z : -$z);
        }
    }
    /**
     * 发射炸弹
     */
    public function launchBomb(){
        $nbt = new CompoundTag;
        $nbt->Pos = new ListTag("Pos", [
            new DoubleTag("", $this->x),
            new DoubleTag("", $this->y + $this->eyeHeight),
            new DoubleTag("", $this->z)
        ]);
        $nbt->Rotation = new ListTag('Rotation', [
            new FloatTag('', 0),
            new FloatTag('', -90)
        ]);
        /** @var Player $player */
        foreach ($this->owner->getPresencePlayer() as $player){
            $nbt->Motion = new ListTag('Motion', [
                new DoubleTag('', ($player->x - $this->x) / 20),
                new DoubleTag('', 0.4),
                new DoubleTag('', ($player->z - $this->z) / 20)
            ]);
            $bomb = new Bomb($this->getLevel(), $nbt, $this->owner);
            $bomb->setHitFun(function (){
               $this->owner->heal($this->owner->getMaxHealth() * 0.02, new EntityRegainHealthEvent($this->owner, $this->owner->getMaxHealth() * 0.02, EntityRegainHealthEvent::CAUSE_MAGIC));
            });
            $bomb->spawnToAll();
        }
    }
    public function spawnTo(Player $player)
    {
        if(!isset($this->hasSpawned[$player->getLoaderId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])){
            $pk = new AddEntityPacket();
            $pk->eid = $this->getId();
            $pk->type = 89;
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
     * @inheritDoc
     */
    public function getName()
    {
        return '仆从';
    }
    public function saveNBT()
    {

    }
}