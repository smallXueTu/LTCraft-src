<?php
namespace LTGrade;

use LTItem\Cooling;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\entity\Effect;
use pocketmine\entity\Attribute;
use pocketmine\scheduler\CallbackTask;
use pocketmine\level\particle\HugeExplodeSeedParticle;
use pocketmine\level\sound\ExplodeSound;
use LTItem\Main as LTItem;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\level\sound\BlazeShootSound;
use LTEntity\LTEntity as LTEntityMain;
use LTEntity\entity\BaseEntity;
use LTItem\SpecialItems\Weapon;
use LTItem\SpecialItems\Armor;
use LTItem\SpecialItems\Material;

class EventListener implements Listener
{
    public static ?EventListener $instance = null;

	public static $eventID=0;
	public $events=[];
	public function __construct(Main $plugin)
	{
		$this->plugin=$plugin;
		self::$instance=$this;
	}
	public static function getInstance()
	{
		return self::$instance;
	}
	public function onDeathEvent(PlayerDeathEvent $event)
	{
	    /*
		$player=$event->getPlayer();
		$cause=$player->getLastDamageCause();
		if($cause instanceof EntityDamageByEntityEvent and $cause->getDamager() instanceof Player){
			$cause->getDamager()->addExp($player->getGrade());
		}
	    */
	}
	public function onQuitEvent(PlayerQuitEvent $event)
	{
		$player=$event->getPlayer();
		if($player->getTask() instanceof PlayerTask and is_array($player->getTask()->DailyTask) and count($player->getTask()->DailyTask)>0){
			$this->plugin->PlayerTaskConf->set(strtolower($player->getName()), $player->getTask()->DailyTask);
		}
	}
	public function onInteractEvent(PlayerInteractEvent $event){
		$player=$event->getPlayer();
		$item=$player->getItemInHand();
		if($player->getItemInHand() instanceof Material and $player->getItemInHand()->getLTName()=='魔法棍' and $player->level->getName()!=='zc'){
			if(isset(Cooling::$material[$player->getName()][$player->getItemInHand()->getLTName()]) and Cooling::$material[$player->getName()][$player->getItemInHand()->getLTName()]>time())return;
			$grade=$player->getGrade();
			switch($player->getRole()){
				case '战士':
				foreach($player->level->getPlayers() as $entity){
					if($player->distanceSquared($entity)>50 or $entity===$player)continue;
					$deltaX = $entity->x - $player->x;
					$deltaZ = $entity->z - $player->z;
					$entity->knockBack($entity, 0, $deltaX, $deltaZ, 1);
				}
				$player->addArmorV($grade);
				$player->level->addParticle(new HugeExplodeSeedParticle($player));
				$player->level->addSound(new ExplodeSound($player));
				$player->sendMessage('§l§a释放技能成功,并获得'.$grade .'护甲,持续10秒');
				$this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask(function($player){
					$player->delArmorV($player->getGrade());
				},[$player]), 200);
				break;
				case '刺客':
					$nbt = new CompoundTag("", [
						"Pos" => new ListTag("Pos", [
						   new DoubleTag("", $player->x),
						   new DoubleTag("", $player->y + $player->getEyeHeight()),
						   new DoubleTag("", $player->z)
						]),
						"Fire" => new ShortTag("Fire", 0),
						"Potion" => new ShortTag("Potion", 0)
					]);
					for($i=0;$i<30;$i++){
						$nbt->Motion = new ListTag("Motion", [
							new DoubleTag("", -sin($i*12 / 180 * M_PI)),
							new DoubleTag("", 0),
							new DoubleTag("", cos($i*12 / 180 * M_PI))
						]);
						$nbt->Rotation= new ListTag("Rotation", [
							new FloatTag("", $i*12),
							new FloatTag("", 0)
						]);
						$entity = Entity::createEntity("falseArrow", $player->getLevel(), $nbt, $player, true);
						$entity->setDamage($grade/2);
						$entity->setMotion($entity->getMotion()->multiply(2));
						$entity->zs = true;
						$entity->spawnToAll();
						$player->level->addSound(new BlazeShootSound($player), $player->getViewers());
					}
					$player->addEffect(Effect::getEffect(1)->setDuration(200)->setAmplifier(150>>5));
					$player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, true,Entity::DATA_TYPE_LONG,true);
					$this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask(function($player){
						$player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, false,Entity::DATA_TYPE_LONG, true);
					},[$player]), 60);
					$player->sendMessage('§l§a释放技能成功,速度效果10秒和隐身3秒');
				break;
				case '法师':
					foreach($player->level->getPlayers() as $entity){
						if($player->distanceSquared($entity)>300 or !$entity->isSurvival() or $entity===$player)continue;
						$player->getLevel()->spawnLightning($entity,$grade/2,$player);
					}
					if(!$player->getAllowFlight()){
						$this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask(function(Player $player){
							$player->setAllowFlight(false, true);
							$player->setFlying(false);
                            $player->forceFlying = false;
						},[$player]), 100);
						$player->setAllowFlight(true, true);
						$player->forceFlying = true;
						$player->setFlying(true);
						$player->sendMessage('§l§a释放技能成功,获得飞行5秒');
					}else
					$player->sendMessage('§l§a释放技能成功！');
				break;
				case '牧师':
					$player->heal($grade,new EntityRegainHealthEvent($player, $grade, EntityRegainHealthEvent::CAUSE_MAGIC));
					$player->sendMessage('§l§a释放技能成功,恢复'.$grade .'生命值！');
				break;
			}
            Cooling::$material[$player->getName()][$player->getItemInHand()->getLTName()]=time()+(200-((int)$grade/2));
		}
	}
	public function onPlaceEvent(BlockPlaceEvent $event) //放置
	{
		if($event->isCancelled())return;
		$block=$event->getBlock();
		if(in_array($block->getId(), [14, 15, 16, 73, 21, 129, 56])){
			$sql="INSERT INTO ExpBlocks(X,Y,Z,L) VALUES ('".$block->getX()."','".$block->getY()."','".$block->getZ()."','".$block->getLevel()->getName()."')";
			$this->plugin->getServer()->dataBase->pushService('1'.chr(2).$sql);
		}
	}
	public function thorns($entity,$targer,$damage)
	{
		$thornsEvent = new EntityDamageByEntityEvent($entity, $targer, EntityDamageEvent::CAUSE_THORNS,(int)$damage, 0);
		$targer->attack((int)$damage, $thornsEvent);
	}
	public function onDamageEvent(EntityDamageEvent $event)
	{
		$entity=$event->getEntity();
		if($event instanceof EntityDamageByEntityEvent and $entity instanceof Player and ($damager=$event->getDamager()) instanceof Player and $damager->getItemInHand() instanceof Material and $damager->getItemInHand()->getLTName()=='魔法棍' and $damager->getRole()==='医疗'){
		    /** @var Player $damager */
			if(isset(Cooling::$material[$damager->getName()][$damager->getItemInHand()->getLTName()]) and Cooling::$material[$damager->getName()][$damager->getItemInHand()->getLTName()]>microtime(true)){
				$damager->sendTitle('§c技能冷却中...');
				return $event->setCancelled();
			}
			$entity->heal($damager->getGrade()*2,new EntityRegainHealthEvent($entity, $damager->getGrade()*2, EntityRegainHealthEvent::CAUSE_MAGIC));
			$entity->sendTitle('§a玩家'.$damager->getName(),'§d为你医疗了'.$damager->getGrade()*2 .'生命值!');
			$damager->sendTitle('§a成功医疗目标');
            Cooling::$material[$damager->getName()][$damager->getItemInHand()->getLTName()]=microtime(true)+(300-$damager->getGrade());
			return $event->setCancelled();
		}
		if($event->isCancelled())return;
		if(!($entity instanceof Creature))return;
		$armor=$entity->getArmorV();
		if($armor!==0){
			$armor=$armor/($armor+300);
			if($armor<0)$armor=0;
			$event->setRateDamage($armor, EntityDamageEvent::MODIFIER_OFFSET);
		}
		if($event instanceof EntityDamageByEntityEvent  and !in_array($event->getCause() , [EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, EntityDamageEvent::CAUSE_SECONDS_KILL])) {
			$damager=$event->getDamager();
			if($damager instanceof Player){
				if($entity instanceof Player){
					//免伤 事实上2%=1%
					$event->setRateDamage(intval($entity->getBuff()->getArmor())/200, EntityDamageEvent::MODIFIER_OFFSET);
				}
				if($event->getCause()==EntityDamageEvent::CAUSE_THORNS)return;
				/* 已移除  这个是防止高等级打低等级boss的减伤
				if($entity instanceof BaseEntity and ARPGMain::isBoss($entity->enConfig['名字'])){
					if($level=ARPGMain::getBossLevel($entity->enConfig['名字'])!==false){
						$grade=$damager->getGrade();
						if($grade>$level){
							$event->setDamage($event->getDamage()*(1-ceil(($grade-$level)/150)));
						}
					}
				}
				*/
				if($damager->getRole()=='刺客' and $damager->PassiveCooling<time() and $damager->getGeNeAwakening()>0){
					$damager->addEffect(Effect::getEffect(1)->setDuration($damager->getGeNeAwakening()*40)->setAmplifier(2));
					$damager->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, true, Entity::DATA_TYPE_LONG,true);
					$this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask(function($damager){
						$damager->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, false,Entity::DATA_TYPE_LONG, true);
					},[$damager]), $damager->getGeNeAwakening()*40);
					$damager->sendMessage('§l§a触发职业基因觉醒效果:§c隐身');
					$damager->PassiveCooling = time()+(300-$damager->getGeNeAwakening()*50);
				}elseif($damager->getRole()=='牧师' and $damager->PassiveCooling<time() and $damager->getGeNeAwakening()>0){
					if($damager->getMaxHealth()>$damager->getHealth())$damager->heal(($damager->getMaxHealth()-$damager->getHealth())/2 ,new EntityRegainHealthEvent($damager, ($damager->getMaxHealth()-$damager->getHealth())/2, EntityRegainHealthEvent::CAUSE_MAGIC));
					$damager->sendMessage('§l§a触发职业基因觉醒效果:§c恢复');
					$damager->PassiveCooling = time()+(300-$damager->getGeNeAwakening()*50);
				}
				if(($weapon=$damager->getItemInHand()) instanceof Weapon){
					if($weapon->getGene()==='刺客' and $damager->getRole()=='刺客'){
						$event->setRateDamage($weapon->getGeneLevel()*0.15, EntityDamageEvent::MODIFIER_GAIN);
					}
					if($weapon->getGene()==='法师' and $damager->getRole()=='法师'){
						if($entity instanceof Player){
							$event->setDamage(($damager->getGrade()/8)+$event->getDamage(EntityDamageEvent::MODIFIER_REAL_DAMAGE), EntityDamageEvent::MODIFIER_REAL_DAMAGE);
						}elseif($entity instanceof BaseEntity){
							$entity->setSunderArmor(1*$weapon->getGeneLevel());
						}
					}
					if($weapon->getGene()==='牧师' and $damager->getRole()=='牧师'){
						if($entity instanceof Player){
							$entity->setInjured(ceil($damager->getGrade()/20));
						}elseif($entity instanceof BaseEntity){
							$addGroupOfBack = (int)$weapon=getGroupsOfBack()/(4-$weapon->getGeneLevel());
						}
					}
					if($weapon instanceof Weapon\Trident){
					    /** @var Weapon\Trident $weapon */
					    if ($weapon->containWill('亚瑟的意志')){
					        $health = $damager->getHealth();
					        $maxHealth = $damager->getMaxHealth();
					        $add = 1 - $health / $maxHealth;
                            $event->setRateDamage($add, EntityDamageEvent::MODIFIER_GAIN);
                        }
					    if ($weapon->containWill('加斯的意志')){
					        if (!isset($damager->counter['加斯的意志']))
					            $damager->counter['加斯的意志'] = 1;
					        else
                                $damager->counter['加斯的意志']++;
					        if ($damager->counter['加斯的意志'] == 18){
					            if ($damager->getBuff()->consumptionMana(300)){

                                    $ab = floor($damager->getMaxHealth() / 4) - 1;
                                    if ($ab <= 0) $ab = 1;
                                    $damager->addEffect(Effect::getEffect(Effect::ABSORPTION)->setDuration(20*5)->setAmplifier($ab));
                                    $damager->addTitle('§l§d触发被动:','§l§a加斯的意志',50,100,50);
                                    $damager->counter['加斯的意志'] = 0;
                                }else {
                                    $this->sendMessage('§cMana不足300，无法释放被动技能。');
                                }
                            }
                        }
                    }
					$weapon->vampire($damager, $entity, $event->getFinalDamage(), 0, $damager->getBuff()->getVampire($entity), $damager->getBuff()->getGroupOfBack()+($addGroupOfBack??0));//吸血
				}
			}
			if($entity instanceof Player and $entity->getBuff()->getTough()>0 and $event->getKnockBack()>0){
				/*
					如果对方手持不是特殊武器就计算受伤者坚韧度
					如果是特殊武器 则在Weapon类已经计算
				*/
				$event->setKnockBack($event->getKnockBack()*(100-$entity->getBuff()->getTough())/100);
			}
			$thorns=$event->getFinalDamage()-$event->getDamage(EntityDamageEvent::MODIFIER_REAL_DAMAGE);
			if((int)$thorns>0){//属性：反伤
				if($entity instanceof Player and $entity->getBuff()->getThorns()>0 and $event->getCause()!==EntityDamageEvent::CAUSE_SECONDS_KILL){
					if($thorns>=$entity->getHealth())
						$da=$entity->getHealth();
					else
						$da=$thorns;
					$this->thorns($entity,$damager,(int)($da*($entity->getBuff()->getThorns() /100)));
				}
			}
		}
	}
	public function onRegainHealthEvent(EntityRegainHealthEvent $event){
		$player=$event->getEntity();
		if($player instanceof Player){
			if($player->injuredTime>0){
				$event->setAmount($event->getAmount()/2);
			}
		}
	}
	public function BlockBreakCallback($id, $count){
		if(isset($this->events[$id])){
			$data=$this->events[$id];
			$pos=$data[1];
			unset($this->events[$id]);
			if($count>=1) {
				$sql="delete FROM ExpBlocks WHERE X='{$pos->getX()}' AND Y='{$pos->getY()}' AND Z='{$pos->getZ()}' AND L='{$pos->getLevel()->getName()}'";
				$this->plugin->getServer()->dataBase->pushService('1'.chr(2).$sql);
				return;
			}
			$id=$data[0];
			$player=$data[2];
			if($player->getGamemode()!==0)return;
			switch($id) {
				case '14'://金矿
					$player->addExp(20);
					// if(mt_rand(0, 100)>97 and \LTCraft\Main::calculateS($player->getName()))$pos->level->dropItem($pos->add(0.5, 0.5, 0.5), LTItem::getInstance()->createMaterial('LTCraft'{mt_rand(0, 6)}));
				break;
				case '15'://铁矿
					$player->addExp(10);
					// if(mt_rand(0, 100)>97 and \LTCraft\Main::calculateS($player->getName()))$pos->level->dropItem($pos->add(0.5, 0.5, 0.5), LTItem::getInstance()->createMaterial('LTCraft'{mt_rand(0, 6)}));
				break;
				case '16'://煤矿
					$player->addExp(8);
					// if(mt_rand(0, 200)>199 and \LTCraft\Main::calculateS($player->getName()))$pos->level->dropItem($pos->add(0.5, 0.5, 0.5), LTItem::getInstance()->createMaterial('LTCraft'{mt_rand(0, 6)}));
				break;
				case '73'://红石矿
					$player->addExp(20);
					// if(mt_rand(0, 100)>98 and \LTCraft\Main::calculateS($player->getName()))$pos->level->dropItem($pos->add(0.5, 0.5, 0.5), LTItem::getInstance()->createMaterial('LTCraft'{mt_rand(0, 6)}));
				break;
				case '21'://青晶石矿
				   $player->addExp(30);
					// if(mt_rand(0, 100)>98 and \LTCraft\Main::calculateS($player->getName()))$pos->level->dropItem($pos->add(0.5, 0.5, 0.5), LTItem::getInstance()->createMaterial('LTCraft'{mt_rand(0, 6)}));
				break;
				case '129'://绿宝石矿
					$player->addExp(50);
					// if(mt_rand(0, 1000)>95 and \LTCraft\Main::calculateS($player->getName()))$pos->level->dropItem($pos->add(0.5, 0.5, 0.5), LTItem::getInstance()->createMaterial('LTCraft'{mt_rand(0, 6)}));
				break;
				case '56'://钻石矿
					$player->addExp(30);

					// if(mt_rand(0, 100)>98 and \LTCraft\Main::calculateS($player->getName()))$pos->level->dropItem($pos->add(0.5, 0.5, 0.5), LTItem::getInstance()->createMaterial('LTCraft'{mt_rand(0, 6)}));
				break;
			}
			$player->getTask()->action('破坏方块', $id);
		}
	}
	public function onBlockBreak(BlockBreakEvent $event)
	{
		if($event->isCancelled())return;
		$player=$event->getPlayer();
		$block=$event->getBlock();
		if(in_array($block->getId(), [14, 15, 16, 73, 21, 129, 56])){
			$eventID=self::$eventID++;
			$this->events[$eventID]=[$block->getId(), $block->asPosition(), $player];
			$sql="SELECT * FROM ExpBlocks WHERE X='{$block->getX()}' AND Y='{$block->getY()}' AND Z='{$block->getZ()}' AND L='{$block->getLevel()->getName()}' LIMIT 1";
			$this->plugin->getServer()->dataBase->pushService('0'.chr(3).chr(strlen($eventID)).$eventID .$sql);
		}
	}
}