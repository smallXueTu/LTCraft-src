<?php
namespace LTPet\Pets;

use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\entity\Rideable;
use pocketmine\entity\Attribute;
use pocketmine\entity\Item as eItem;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\Level;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\network\protocol\SetEntityLinkPacket;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\scheduler\CallbackTask;
use LTPet\Main;
use LTPet\Ride;
use pocketmine\utils\TextFormat as TF;
use pocketmine\level\particle\HeartParticle;
use LTPet\Pets\WalkingPets\LTNPC;

abstract class Pets extends Creature implements Rideable
{
    protected $owner;
    protected $att;
    private $distanceMess = false;
	/* var int*/
	protected $RedeMaxPlayer;
	/* var Ride*/
	protected $RideObject;
	/* var String*/
	protected $PetName;
	/* var Ride*/
	protected $RideVector3 = [];
	/* var bool*/
    protected $follow = true;
    public function attack($damage, EntityDamageEvent $source)
    {
        return true;
    }
    public function saveNBT() {}
    public function getAtt()
    {
        return $this->att;
    }
    public function getPetName()
    {
        return $this->PetName;
    }
    public function __construct(Level $level, $nbt, Player $owner, $att)
    {
        $this->owner = $owner;
        $this->att = new Att($owner, $this, $att);
		$this->PetName=$att['name'];
        $this->setNormalName('§a'.$this->owner->getName().'的'.(($this instanceof LTNPC) ? '女仆' : '宠物').':§d'.$att['name']);
        parent::__construct($level, $nbt);
		if($this instanceof MountPet){
			$this->attributeMap->addAttribute(new Attribute(0, 'minecraft:fall_damage', 0, 3, 1, 1, 1));
			$this->attributeMap->addAttribute(new Attribute(1, 'minecraft:luck', -1024, 1024, 0, 1, 1));
			$this->attributeMap->addAttribute(new Attribute(2, 'minecraft:movement', 0, 3, 1, 1, 1));
			$this->attributeMap->addAttribute(new Attribute(3, 'minecraft:absorption', 0, 3, 0, 1, 1));
			$this->attributeMap->addAttribute(new Attribute(4, 'minecraft:health', 0, 20, 20, 1, 1));
		}
		$this->setNameTagAlwaysVisible(true);
    }
    public function getOwner()
    {
        return $this->owner;
    }
    public function updateMovement()
    {
        if(
            $this->lastX !== $this->x or $this->lastY !== $this->y or $this->lastZ !== $this->z or $this->lastYaw !== $this->yaw or $this->lastPitch !== $this->pitch
        ) {
            $this->lastX = $this->x;
            $this->lastY = $this->y;
            $this->lastZ = $this->z;
            $this->lastYaw = $this->yaw;
            $this->lastPitch = $this->pitch;
        }
        $this->level->addEntityMovement($this->chunk->getX(), $this->chunk->getZ(), $this->id, $this->x, ($this instanceof LTNPC) ? $this->y +$this->getEyeHeight() : $this->y, $this->z, $this->yaw, $this->pitch);
    }
    /*  public function attack($damage, EntityDamageEvent $source) {
    	if(!($source instanceof EntityDamageByEntityEvent) or (!$source->getDamager() instanceof Player))return;
    $this->owner->sendMessage('§l§a['.$this->getName().']§l§d主人有人想打我');
    if($source->getDamager()===$this->owner)$this->owner->sendMessage('§l§a['.$this->getName().']§l§c呜呜呜，原来是主人你想打我(╥﹏╥)');
    	return false;
    }*/
	/*骑乘坐标*/
	public function getRedeVector3($seat=0){
		return $this->RideVector3[$seat]??null;
	}
	
	public function setRideObject(Ride $obj){
		$this->RideObject=$obj;
	}
	public function getRideObject(){
		return $this->RideObject;
	}
	public function getRedeMaxPlayer(){
		return count($this->RideVector3);
	}
	public function linkEntity(Player $player){
		if($player->getPleasureEvent()){
			return $player->sendMessage('§a你正在跟你的伴侣啪啪啪呢！');
		}
		if($player->getLinkedEntity()){
			return $player->sendMessage('§a你当前是骑乘状态 无法完成这个操作!');
		}
		if($this->getAtt()->getLove()<=10 and false){
			return $player->sendMessage('§a亲密度不足！');
		}
		$seat=$this->getRideObject()->getSeat();
		$vector3=$this->getRedeVector3($seat);
		if($vector3==null)return false;
		$player->setDataProperty(57, 8, $vector3);
		$pk = new SetEntityLinkPacket();
		$pk->from = $this->getId();
		$pk->to = $player->getId();
		$pk->type = 1;
		foreach($this->getViewers() as $p) {
			$p->dataPacket(clone $pk);
		}
		$pk->to = 0;
		$player->dataPacket($pk);
		$player->setLinkedEntity($this);
		$seat=$this->getRideObject()->addRide($seat ,$player);
		if($player === $this->owner){
			$this->follow = false;
		}
	}
	public function cancelLinkEntity(Player $player){
		$seat=$this->getRideObject()->getPlayerSeat($player);
		$this->getRideObject()->removeRide($seat);
		$player->setLinkedEntity(null);
		$pk = new SetEntityLinkPacket();
		$pk->from = $this->getId();
		$pk->to = $player->getId();
		$pk->type = 0;
		foreach($this->getViewers() as $p) {
			$p->dataPacket($pk);
		}
		$player->teleport($this, null, null, false);
		if($player === $this->owner){
			$this->follow = true;
		}
	}
    public function move($dx, $dy, $dz) : bool{
		$this->att->updateHunger();

        $movX = $dx;
        $movY = $dy;
        $movZ = $dz;

        $list = $this->level->getCollisionCubes($this, $this->level->getTickRate() > 1 ? $this->boundingBox->getOffsetBoundingBox($dx, $dy, $dz) : $this->boundingBox->addCoord($dx, $dy, $dz), true, false); //碰撞立方体。
        foreach($list as $bb)
        {
            $dx = $bb->calculateXOffset($this->boundingBox, $dx);
        }
        $this->boundingBox->offset($dx, 0, 0);

        foreach($list as $bb)
        {
            $dz = $bb->calculateZOffset($this->boundingBox, $dz);
        }
        $this->boundingBox->offset(0, 0, $dz);
        foreach($list as $bb)
        {
            $dy = $bb->calculateYOffset($this->boundingBox, $dy);
        }
        $this->boundingBox->offset(0, $dy, 0);

        $this->setComponents($this->x + $dx, $this->y + $dy, $this->z + $dz);
        $this->checkChunks();

        $this->checkGroundState($movX, $movY, $movZ, $dx, $dy, $dz);
        $this->updateFallState($dy, $this->onGround);
        return true;
    }
    public function getName()
    {
        return $this->name;
    }
    public function getSpeed()
    {
        return 1.8;
    }
    public function addFood($h)
    {
		$this->att->addHunger($h);
    }
    public function onUpdate($currentTick)
    {
        if(!($this->owner instanceof Player) or $this->owner->closed) {
            $this->close();
            return false;
        }
        if($this->closed) {
            return false;
        }
        $tickDiff = $currentTick - $this->lastUpdate;
        $this->lastUpdate = $currentTick;
		if(!$this->owner->onTeleport()){
			if($this->distanceNoY($this->owner) > 40 and $this->distanceNoY($this->owner) < 60) {
				if($this->distanceMess==false) {
					$this->distanceMess = true;
					$this->owner->sendMessage('§l§a['.$this->getPetName().'§r§a§l]主人我追不上了，能休息下吗？');
				}
			}elseif($this->distanceNoY($this->owner) > 60) {
				$this->owner->sendMessage('§l§a['.$this->getPetName().'§r§a§l]你的宠物累死了 回归你的背包了~');
				$this->att->setHunger(0);
				$this->close();
				return false;
			}
		}
		if($this->distance($this->owner) < 40){
			$this->distanceMess = false;
		}
        $this->entityBaseTick($tickDiff);
        $this->updateMove($tickDiff);
        return true;
    }
    public function setHealth($h) {}
	public function teleportteleport(Vector3 $pos, $yaw = null, $pitch = null,$crucial=true, $force = false){
		if(parent::teleport($pos, $yaw, $pitch, $crucial, $force) and $this instanceof MountPet){
			foreach($this->getRideObject()->getPlayers() as $player){
				$player->teleport($pos, null, null, false);
			}
		}
	}
    public function returnToOwner($pos = null)
    {
        if($pos === null){
			$this->teleport($this->owner);
		}else{
            $this->teleport($pos);
		}
    }
    public function close()
    {
        if($this->closed)return;
        if($this->getOwner() instanceof Player and !$this->getOwner()->closed){
			$this->getAtt()->save();
		}
		if($this instanceof MountPet){
			$this->getRideObject()->unlinkAll();
		}
		if(isset(Main::getInstance()->comes[$this->getOwner()->getName()]))Main::getInstance()->comes[$this->getOwner()->getName()]->removePet(Main::getCleanName($this->getPetName()));
        parent::close();
    }
    public function kill()
    {
        $this->close();
    }
}