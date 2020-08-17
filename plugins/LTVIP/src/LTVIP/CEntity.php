<?php
namespace LTVIP;

use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\level\format\Chunk;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
class CEntity extends Creature{

	public $entityID;
	public $master;
	public $plugin;
	public function getSpeed() {
		return 1.1;
	}
	public function __construct(Level $level,$nbt,$id,Player $master,Main $plugin){
		$this->entityID=$id;
		$this->master=$master;
		$this->plugin=$plugin;
		parent::__construct($level,$nbt);
	}
	public function spawnTo(Player $player) {
		if(!$this->closed ) {
			if (!isset($this->hasSpawned[$player->getId()]) and isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
				$pk = new AddEntityPacket();
				$pk->eid = $this->getId();
				$pk->type = $this->entityID;
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
				$this->hasSpawned[$player->getId()] = $player;
			}
		}
	}
	public function updateMovement() {
		if (
			$this->lastX !== $this->x or $this->lastY !== $this->y or $this->lastZ !== $this->z or $this->lastYaw !== $this->yaw or $this->lastPitch !== $this->pitch
		) {
			$this->lastX = $this->x;
			$this->lastY = $this->y;
			$this->lastZ = $this->z;
			$this->lastYaw = $this->yaw;
			$this->lastPitch = $this->pitch;
		}
		if(!($this->chunk instanceof Chunk))return $this->close();
		$this->level->addEntityMovement($this->chunk->getX(), $this->chunk->getZ(), $this->id, $this->master->x, $this->master->y, $this->master->z, $this->yaw, $this->pitch);
	}
	public function saveNBT(){}
	public function attack($damage,EntityDamageEvent $source){}
	public function getName(){
		return '伪装实体';
	}
	public function close(){
		$pk=new RemoveEntityPacket();
		$pk->eid=$this->getId();
		$this->server->broadcastPacket($this->level->getPlayers(),$pk);
		parent::close();
		$this->master->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, false);
		unset($this->plugin->wz[$this->master->getName()]);
	}
	public function onUpdate($tick){}
	}
