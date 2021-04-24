<?php


namespace LTEntity\entity\Gaia\SkillEntity;

use LTEntity\entity\Gaia\GaiaGuardiansIII;
use mysql_xdevapi\CollectionModify;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\entity\Sheep;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\particle\InkParticle;
use pocketmine\level\particle\RedstoneParticle;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;
use pocketmine\utils\BinaryStream;

class Landmine extends Entity
{
    /** @var int */
    public int $size;
    /**
     * @var GaiaGuardiansIII
     */
    public GaiaGuardiansIII $owner;
    /** @var ?AxisAlignedBB */
    public ?AxisAlignedBB $axisAlignedBB = null;
    public function __construct(Level $level, CompoundTag $nbt, $size, GaiaGuardiansIII $gaiaGuardiansIII)
    {
        parent::__construct($level, $nbt);
        $this->owner = $gaiaGuardiansIII;
        $this->size = $size;
        $this->axisAlignedBB = new AxisAlignedBB($this->getX()-$size, $this->getY(), $this->getZ()-$size,$this->getX()+$size, $this->getY()+5, $this->getZ()+$size);
    }

    /**
     * @param $tick
     * @return bool
     */
    public function onUpdate($tick){
        if ($this->age > 10){
            foreach ($this->getLevel()->getCollidingEntities($this->axisAlignedBB) as $entity){
                if ($entity instanceof Player and $entity->canSelected() and $entity->isSurvival()){
                    $entity->setLastDamageCause(new EntityDamageByEntityEvent($this->owner, $entity, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, $entity->getHealth(), 0));
                    $entity->setHealth(0);
                    $this->close();
                    return false;
                }
            }
        }
        $this->age++;
        if ($this->age > 20 * 10){
            $this->close();
            return false;
        }
        return true;
    }

    public function saveNBT()
    {

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
}