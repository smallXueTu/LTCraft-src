<?php


namespace LTEntity\entity\Process;


use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;

class FloatItem extends \pocketmine\entity\Item
{
    /** @var Position */
    public ?Position $center = null;
    /** @var int */
    public int $progress = 0;
    /** @var int */
    public int $speed = 1;
    /** @var Fusion */
    public ?Fusion $fusion = null;
    public function __construct(Level $level, CompoundTag $nbt)
    {
        parent::__construct($level, $nbt);
        $this->setDataProperty(self::DATA_LEAD_HOLDER_EID, self::DATA_TYPE_LONG, 0);
        $this->setDataProperty(self::DATA_SCALE, self::DATA_TYPE_FLOAT, 0);
        $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_IMMOBILE, true);
    }

    /**
     * @param Fusion $fusion
     */
    public function setFusion(Fusion $fusion): void
    {
        $this->fusion = $fusion;
    }

    /**
     * @return Fusion
     */
    public function getFusion(): Fusion
    {
        return $this->fusion;
    }

    /**
     * @return int
     */
    public function getSpeed(): int
    {
        return $this->speed;
    }

    /**
     * @param int $speed
     */
    public function setSpeed(int $speed): void
    {
        $this->speed = $speed;
    }
    /**
     * @param Position $center
     */
    public function setCenter(Position $center): void
    {
        $this->center = $center;
    }

    /**
     * @param int $progress
     */
    public function setProgress(int $progress): void
    {
        $this->progress = $progress;
    }
    /**
     * @return int
     */
    public function getProgress(): int
    {
        return $this->progress;
    }
    /**
     * @return Position
     */
    public function getCenter(): Position
    {
        return $this->center;
    }
    public function onUpdate($currentTick)
    {
        if ($this->getSpeed() == 0)return true;
        $this->move(1.5 * cos($this->getProgress() * 3.14 / 90), 0, 1.5 * sin($this->getProgress() * 3.14 / 90));
        $this->progress += $this->getSpeed();
        if ($this->progress > 360) {
            $this->progress -= 360;
        }
        return true;
    }
    public function saveNBT()
    {

    }

    public function move($dx, $dy, $dz): bool
    {
        $this->setComponents($this->center->x + $dx, $this->center->y, $this->center->z + $dz);
        $this->updateMovement();
        return true;
    }

    public function updateMovement()
    {
        $this->level->addEntityMovement($this->chunk->getX(), $this->chunk->getZ(), $this->id, $this->x, $this->y, $this->z, 0, 0, 0);
    }
}