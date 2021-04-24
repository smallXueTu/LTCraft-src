<?php

namespace LTEntity\entity\Gaia;

use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\level\particle\DustParticle;
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
use pocketmine\Server;


class GaiaCrystal extends Creature
{
	const DATA_SHOOTER_ID = 17;
	public $shootingEntity = null;
    const NETWORK_ID = 71;

    private $lastProgress = 0;
    private $lastColor = 60;

    /**
     * GaiaCrystal constructor.
     * @param Level $level
     * @param CompoundTag $nbt
     * @param Entity|null $shootingEntity
     */
	public function __construct(Level $level, CompoundTag $nbt, Entity $shootingEntity = null){
		$this->shootingEntity = $shootingEntity;
//		$this->shootingEntity = Server::getInstance()->getPlayer("a");
		if($shootingEntity !== null){
			$this->setDataProperty(self::DATA_SHOOTER_ID, self::DATA_TYPE_LONG, $shootingEntity->getId());
		}
		parent::__construct($level, $nbt);
	}

    /**
     *
     */
    public function saveNBT() {

    }

    /**
     * @param Entity $attacker
     * @param $damage
     * @param $x
     * @param $z
     * @param float $base
     * @param bool $force
     */
    public function knockBack(Entity $attacker, $damage, $x, $z, $base = 0.4, $force=false){

    }
    /**
     * @param $currentTick
     * @return bool
     */
    public function onUpdate($currentTick)
    {
        if($this->closed or !$this->shootingEntity->isAlive()) {
            $this->despawnFromAll();
            return false;
        }
        if ($this->shootingEntity instanceof GaiaGuardians and ($this->shootingEntity->getAge() < 20*20 or ($this->shootingEntity->onSky!=0 and $this->shootingEntity->onSky < 20*30))){
            $this->spawnParticle();
        }
        return true;
    }

    /**
     * 粒子效果
     */
    public function spawnParticle(){
        $xr=$this->shootingEntity->x - $this->x;
        $yr=$this->shootingEntity->y - $this->y - 1.62;
        $zr=$this->shootingEntity->z - $this->z;
        $yy = $this->getY() + 0.7 + ($yr / 360) * $this->lastProgress;
        for($i=0;$i<=12;$i++){
            $a=($this->getX()+($xr/360*$this->lastProgress))+cos($this->lastProgress);
            $b=($this->getZ()+($zr/360*$this->lastProgress))+sin($this->lastProgress);
            $rgb = $this->generateGradientColor($this->lastColor, 60);
            if($this->lastProgress % 6 == 0){
                $this->lastColor--;
                if ($this->lastColor < 0){
                    $this->lastColor = 60;
                }
            }
            $this->getLevel()->addParticle(new DustParticle(new Vector3($a,$yy+1.2,$b),$rgb[0],$rgb[1],$rgb[2]));
            $yy=$yy+$yr/360;
            $this->lastProgress++;
        }
        if ($this->lastProgress>=360){
            $this->lastProgress = 0;
        }
    }

    /**
     * @param $x
     * @param $threshold
     * @param int $brightness
     * @return array
     */
    public function generateGradientColor($x, $threshold, $brightness = 1){
        return [intval(min(255, ($x * 1.0 / $threshold * 255)) * $brightness), intval(max(0, min(255, (2 - $x * 1.0 / $threshold) * 255)) * $brightness), 0];
    }
    /**
     * @param Player $player
     */
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

    /**
     * @return mixed
     */
    public function getName()
    {
        return '盖亚水晶';
    }
}