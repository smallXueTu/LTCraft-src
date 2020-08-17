<?php
namespace LTPet;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\block\Air;
use pocketmine\block\Liquid;
use pocketmine\block\Stair;
use pocketmine\block\Slab;
use pocketmine\item\Food;
use pocketmine\entity\Entity;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\SetEntityLinkPacket;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\network\protocol\InteractPacket;
use pocketmine\network\protocol\PlayerInputPacket;
use pocketmine\math\Math;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\network\protocol\PlayerActionPacket;
use pocketmine\event\server;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use LTPet\Pets\Pets;
use pocketmine\scheduler\CallbackTask;
use pocketmine\Player;
use pocketmine\utils\UUID;
use LTPet\Preview\Preview;
use LTPet\Preview\NPCSkinPreview;
use LTMenu\Open;
use LTPet\Pets\MountPet;
use pocketmine\event\player\PlayerDeathEvent;
use LTPet\Pets\WalkingPets\{
	LTRabbit,LTChicken,LTPig,LTSheep,LTWolf,LTVillager,LTOcelot,LTHorse,LTSilverfish,LTSlime,LTCreeper,LTNPC,LTLlama,LTSpider,LTSkeleton
};
use LTPet\Pets\FlyingPets\{
	LTWitherBoss,LTEnderDragon
};
class Events implements Listener{
	private $buyIng=[];
	private $preview=[];
	private $NPCSkinPreview=[];
	private $server;
	private $plugin;
	public function __construct($server ,$plugin){
		$this->server=$server;
		$this->plugin=$plugin;
	}
	/*public function onDamageEvent(EntityDamageEvent $event){
		$player=$event->getEntity();
		if(!($player instanceof Player))return;
		if($event->getCause()==EntityDamageEvent::CAUSE_FALL AND isset($this->plugin->ride[$player->getName()]))$event->setCancelled();
	}*/
	public function onJoinEvent(PlayerJoinEvent $event){
		$player=$event->getPlayer();
		$this->plugin->comes[$player->getName()]=new Comes($player);
		if($player->getLevel()->getName()=='zc'){
			$this->preview[$player->getName()]=new Preview($player);
			$this->NPCSkinPreview[$player->getName()]=new NPCSkinPreview($player);
		}
	}
	public function onQuitEvent(PlayerQuitEvent $event){
		$player=$event->getPlayer();
		$name=$player->getName();
		if(isset($this->preview[$name]))$this->preview[$name]->remove();
		if(isset($this->NPCSkinPreview[$name]))$this->preview[$name]->remove();
		if(isset($this->NPCSkinPreview[$name]))$this->NPCSkinPreview[$name]->remove();
		if(isset($this->plugin->comes[$name]))$this->plugin->comes[$name]->closePets();
		unset($this->preview[$name],$this->plugin->comes[$name], $this->NPCSkinPreview[$name]);
	}
	public function onDeath(playerDeathEvent $event){
		$player=$event->getPlayer();
		if($player->getLinkedEntity() instanceof MountPet){
			$player->getLinkedEntity()->cancelLinkEntity($player);
		}elseif($player->getLinkedEntity() instanceof LTNPC){
			$player->getLinkedEntity()->stopPapa();
		}
		foreach($this->plugin->comes[$player->getName()]->getPets() as $pet){
			$player->sendMessage('§l§a['.$pet->getName().'§r§a§l]主人你死的好惨啊！');
		}
	}
	public function onInteractEvent(PlayerInteractEvent $event){
		$block=$event->getBlock();
		if($block->getLevel()->getName()!=='zc')return;
		$player=$event->getPlayer();
		$name=$player->getName();
		if(($this->preview[$name]??null)===null)return;
		$xyz=$block->getX().':'.$block->getY().':'.$block->getZ();
		if($xyz=='743:3:58'){
			$this->preview[$name]->updateType(true);
		}elseif($xyz=='743:3:62'){
			$this->preview[$name]->updateType(false);
		}
		elseif($xyz=='743:12:70'){
			$this->NPCSkinPreview[$name]->updateSkin(false);
		}elseif($xyz=='743:12:66'){
			$this->NPCSkinPreview[$name]->updateSkin(true);
		}
	}
	public function inTPEvent(EntityTeleportEvent $event){
		if($event->isCancelled() or !($event->getEntity() instanceof Player))return;
		$player=$event->getEntity();
		if($event->getTo()->distanceNoY($event->getFrom())> 40 or $event->getTo()->getLevel()!==$event->getFrom()->getLevel()){
			if($player->getLinkedEntity() instanceof MountPet){
				$player->getLinkedEntity()->cancelLinkEntity($player);
			}elseif($player->getLinkedEntity() instanceof LTNPC){
				$player->getLinkedEntity()->stopPapa();
			}
			if(isset($this->plugin->comes[$player->getName()])){
				foreach($this->plugin->comes[$player->getName()]->getPets() as $pet){
					$pet->teleport($event->getTo());
				}
			}
		}
	}
	public function EntityLevelEvent(EntityLevelChangeEvent $event){
		$player=$event->getEntity();
		if(!($player instanceof Player))return;
		$target=$event->getTarget();
		if($target->getName()==='zc'){
			$this->preview[$player->getName()]=new Preview($player);
			$this->NPCSkinPreview[$player->getName()]=new NPCSkinPreview($player);
		}
		if($target->getName()!=='zc' and isset($this->preview[$player->getName()])){
			$this->preview[$player->getName()]->remove();
			$this->preview[$player->getName()]=null;
			$this->NPCSkinPreview[$player->getName()]->remove();
			$this->NPCSkinPreview[$player->getName()]=null;
		}
	}
	public function PlayerMove(PlayerMoveEvent $event){
		if($event->isCancelled())return;
		$player=$event->getPlayer();
		if(isset($this->preview[$player->getName()])){
			$this->preview[$player->getName()]->updateSeeTarget();
		}
	}
	public function onPacketEvent(DataPacketReceiveEvent $event){
		$player=$event->getPlayer();
		$packet=$event->getPacket();
		if($packet instanceof InteractPacket){
			if($packet->action===InteractPacket::ACTION_LEFT_CLICK){
				$target=$this->preview[$player->getName()]??null;
				if($target!==null and $packet->target===$target->getEid()){
					if($target->getNeed()==false)return $player->sendMessage('§l§a[LT宠物系统]§e该宠物仅限活动获得！');
					$player->sendMessage('§l§a[LT宠物系统]§e给你的宠物起个名字吧！输入 exit退出');
					$this->buyIng[$player->getName()]=$target->getCurrent()->getName();
					return;
				}
				$target=$this->NPCSkinPreview[$player->getName()]??null;
				if($target!==null and $packet->target===$target->getEid()){
					if($target->getNeed()==false)return $player->sendMessage('§l§a[LT宠物系统]§e该皮肤仅限活动获得！');
					if($target->WaitingConfirm===false){
						$target->WaitingConfirm=true;
						$player->sendMessage('§l§a[LT宠物系统]§c再次点击确认');
						return;
					}else{
						$count=$target->getNeed();
						if(!Open::getNumber($player, ['材料',$count[0], $count[1]])){
							$player->sendMessage('§l§a[LT宠物系统]§c皮肤碎片不足');
							return $event->setCancelled();
						}
						Open::removeItem($player, ['材料',$count[0],$count[1]]);
						$all=$this->plugin->PlayerSkins->get(strtolower($player->getName()), []);
						$all[$target->getName()]=true;
						$this->plugin->PlayerSkins->set(strtolower($player->getName()), $all);
						$target->despawnFrom();
						$target->UUID = UUID::fromRandom();
						$target->updateInfo();
						$target->spawnTo();
						$player->sendMessage('§l§a[LT宠物系统]§a兑换成功！！');
					}
				}
			}
		}elseif($packet instanceof PlayerInputPacket and $player->getLinkedEntity() instanceof MountPet){
			$entity=$player->getLinkedEntity();//获取骑乘的坐骑
			if($entity->getOwner()===$player){
				if($packet->motionY==1){//如果按的是前进
					$entity->motionX = -sin($player->getYaw()/180*M_PI);//计算运动X
					 $entity->motionZ = cos($player->getYaw()/180*M_PI);//计算运动Z
					  $level=$player->getLevel();
					 if($entity instanceof LTEnderDragon){//如果属于末影龙
						$entity->motionY = -sin($player->getPitch()/180*M_PI);//抬头向上飞低头向下飞
						$block = $level->getBlock($entity->add($entity->motionX, $entity->motionY, $entity->motionZ));//获取前方方块
							if (!($block instanceof Air) and !($block instanceof Liquid)){//如果方块不能穿透
								$entity->motionX = 0;
								$entity->motionY = 0;
								$entity->motionZ = 0;
								return;//阻止运动
							}
					 }else{//如果不属于末影龙
						  $block = $level->getBlock($entity->add($entity->motionX, 0, $entity->motionZ));//获取前方方块
							if (!($block instanceof Air) and !($block instanceof Liquid)){//如果不能穿透
								if (!$entity->checkJump($entity->motionX,0 ,$entity->motionZ)) {//判断是否可以跳跃
									$entity->motionX = 0;
									$entity->motionY = 0;
									$entity->motionZ = 0;
									return;//如果不可以阻止运动
								}else{
									if (!$entity->checkJump($entity->motionX,1 ,$entity->motionZ)) {//判断是否可以跳跃
										$entity->motionX = 0;
										$entity->motionY = 0;
										$entity->motionZ = 0;
										return;//如果不可以阻止运动
									}
								}
							} else {//如果可以
								$block = $level->getBlock($entity->add($entity->motionX, -1, $entity->motionZ));//判断前面是不是坑
								if (!($block instanceof Air) and !($block instanceof Liquid)) {//前面不是坑
									$blockY = Math::floorFloat($entity->y);//计算重力
									if ($entity->y -  0.08  > $blockY) {//如果脚着地
										$entity->motionY = - 0.08;//向下拉一点防止脚飘起
									} else {//如果脚没着地
										$entity->motionY = ($entity->y - $blockY) > 0 ? ($entity->y - $blockY) : 0;//向下拉到地面的位置
									}
								} else {
									$entity->motionY -= 0.08 ;
								}
							}
					 }
					$entity->getAtt()->updateHunger();
					$entity->setPosition($player->temporalVector->setComponents($entity->x+$entity->motionX, $entity->y+$entity->motionY, $entity->z+$entity->motionZ));//向前方运动
				}
			}
		}
	}
	public function PlayerRunCommand(PlayerCommandPreprocessEvent $event){
		$player=$event->getPlayer();
		$name=$player->getName();
		if(!isset($this->buyIng[$name]))return;
		$ms=$event->getMessage();
		$event->setCancelled();
		if($ms=='exit'){
			$player->sendMessage('§l§a[LT宠物系统]§a你退出了。');
			unset($this->buyIng[$name]);
		}elseif($player->getPet(Main::getCleanName($ms))!==false){
			$player->sendMessage('§l§a[LT宠物系统]§c你已经有个宠物叫这个名字了。换一个吧！');
		}else{
			$count=Pet::getCount($this->buyIng[$name]);
			if(!Open::getNumber($player, ['材料',$count[0], $count[1]])){
				$player->sendMessage('§l§a[LT宠物系统]§c宠物碎片不足');
				unset($this->buyIng[$name]);
				return $event->setCancelled();
			}
			Open::removeItem($player, ['材料',$count[0],$count[1]]);
			$this->plugin->addPet($player, $this->buyIng[$name], Main::getCleanName($ms));
			$player->sendMessage('§l§a[LT宠物系统]§a兑换成功！！');
		}
	}
}