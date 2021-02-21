<?php
namespace LTItem;
use LTEntity\entity\Boss\SkillsEntity\Sakura;
use LTEntity\entity\Boss\SkillsEntity\SpaceTear;
use LTItem\Mana\Mana;
use pocketmine\block\Air;
use pocketmine\entity\Creature;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityCombustEvent;
use pocketmine\event\entity\EntityCombustByEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\level\Explosion;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\PlayerActionPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;
use pocketmine\network\protocol\UseItemPacket;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityArmorChangeEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\scheduler\CallbackTask;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\item\Armor as ItemArmor;
use LTItem\SpecialItems\Weapon;
use LTItem\SpecialItems\Armor;
use LTItem\SpecialItems\Material;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\block\Redstone;
use pocketmine\event\Listener;
use pocketmine\entity\Effect;
use pocketmine\entity\Attribute;
use LTLogin\Events;
use LTEntity\entity\BaseEntity;
use LTGrade\FloatingText;
use pocketmine\Server;

class EventListener implements Listener
{
    public static ?EventListener $instance = null;
    private Main $plugin;
    private Server $server;

    public static function getInstance(){
		return self::$instance;
	}
	public function __construct($plugin, $server)
	{
		self::$instance=$this;
		$this->plugin = $plugin;
		$this->server = $server;
	}

    /**
     * 实体医疗事件
     * @param EntityRegainHealthEvent $event
     */
    public function onRegainHealth(EntityRegainHealthEvent $event){
        /** @var Player $player */
        $player = $event->getEntity();
        if ($player instanceof Player){//如果是玩家
            $hand = $player->getItemInHand();
            if ($hand instanceof Weapon\Trident){
                /** @var Weapon\Trident $hand */
                if ($hand->containWill('卡拉森的意志')){//如果玩家卡拉森的意志 取消所有治疗效果
                    $player->setHealth($player->getHealth());
                    $event->setCancelled(true);
                }
            }
        }
    }
	public function onLevelChange(EntityLevelChangeEvent $event)
	{
		$p = $event->getEntity();
		if(!($p instanceof Player))return;
		if(\LTCraft\Main::isFairLevel($event->getTarget()->getName()) and $p->isOp()){
			$p->removeAllEffects();
			$p->getBuff()->runEffect();
		}
	}
	public function onJoinEvent(PlayerJoinEvent $event)
	{
		$event->getPlayer()->getBuff()->runEffect();
		$event->getPlayer()->getBuff()->updateBuff();
        Cooling::onPlayerJoin($event->getPlayer());
	}
	public function playerQuit(PlayerQuitEvent $event){
        Cooling::onPlayerQuit($event->getPlayer());
	}
	public function onArmorChangeEvent(EntityArmorChangeEvent $event)
	{
		$player=$event->getEntity();
		if($player instanceof Player) {
			$oldItem=$event->getOldItem();
			if($oldItem instanceof Armor and $oldItem->canUse($player)){
				$player->delArmorV($oldItem->getArmorV());
				$attr = $player->getAttributeMap()->getAttribute(Attribute::MOVEMENT_SPEED);
				$attr->setValue($attr->getValue() / ($oldItem->getSpeed()));
				$player->setMaxHealth($player->getYMaxHealth()-$oldItem->getHealth());
				if($player->getHealth()>$player->getMaxHealth())
					$player->setHealth($player->getMaxHealth());
				else
					$player->setHealth($player->getHealth());
				$player->getBuff()->delThorns($oldItem->getThorns());
				$player->getBuff()->delMiss($oldItem->getMiss());
				$player->getBuff()->delLucky($oldItem->getLucky());
				$player->getBuff()->delTough($oldItem->getTough());
				$player->getBuff()->delControlReduce($oldItem->getControlReduce());
				if($oldItem->getEffects()!==''){
					foreach(explode('@',$oldItem->getEffects()) as $effect) {
						$eff=explode(':',$effect);
						$player->removeEffect($eff[0]);
					}
					$player->getBuff()->delEffect($oldItem->getEffects());
				}
			}elseif($oldItem instanceof ItemArmor) $player->delArmorV($oldItem->getArmorValue());
			$newItem=$event->getNewItem();
			if($newItem instanceof Armor and $newItem->canUse($player)){
				$player->addArmorV($newItem->getArmorV());
				$attr = $player->getAttributeMap()->getAttribute(Attribute::MOVEMENT_SPEED);
				$attr->setValue($attr->getValue() * ($newItem->getSpeed()));
				$player->setMaxHealth($newItem->getHealth()+$player->getYMaxHealth());
				$player->setHealth($player->getHealth());
				$player->getBuff()->addThorns($newItem->getThorns());
				$player->getBuff()->addMiss($newItem->getMiss());
				$player->getBuff()->addLucky($newItem->getLucky());
				$player->getBuff()->addTough($newItem->getTough());
				$player->getBuff()->addControlReduce($newItem->getControlReduce());
				if($newItem->getEffects()!==''){
					foreach(explode('@',$newItem->getEffects()) as $effect) {
						$eff=explode(':',$effect);
						$player->addEffect(Effect::getEffect($eff[0])->setAmplifier($eff[1])->setDuration(60*20));
					}
					$player->getBuff()->addEffect($newItem->getEffects());
				}
			}elseif($newItem instanceof ItemArmor)$player->addArmorV($newItem->getArmorValue());
		}
	}
	public function onProjectileHit(ProjectileHitEvent $event)
	{
		$entity = $event->getEntity();
		$player = $entity->shootingEntity;
		if(!($player instanceof Player) or $player->closed)return;
		$pname = strtolower($player->getName());
		$Hand = $player->getItemInHand();
		if($Hand instanceof Weapon and $Hand->canUse($player) and $Hand->getWeaponType()!=='近战'){
			$player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask(function($entity){
				$entity->close();
			}, [$entity]), 5);
			$all = $this->plugin->config->get('爆炸', []);
			if($Hand->getBoom() !== false and (!isset($all[$pname]) or (isset($all[$pname]) and $all[$pname] === true))) {
				$ex = new Explosion($entity, 3, $entity);
				$ex->booom($Hand->getBoom(), $player, $Hand->iURD());
			}
		}
	}

    /**
     * @param BlockPlaceEvent $event
     */
	public function onBlockPlace(BlockPlaceEvent $event){
		$player=$event->getPlayer();
		$Hand = $player->getItemInHand();
		if($Hand instanceof LTItem and $player->getGamemode()!=0){//只有生存模式可以放置
			$event->setCancelled();
		}
	}
	public function onInteractEvent(PlayerInteractEvent $event){
		$player=$event->getPlayer();
		$Hand = $player->getItemInHand();
		if($Hand instanceof Weapon){
			switch($Hand->getSkillName()){
				case '致盲':
					if(!isset(Cooling::$weapon[$player->getName()][$Hand->getLTName()]) or Cooling::$weapon[$player->getName()][$Hand->getLTName()]<time()){
						foreach($player->level->getEntities() as $entity){
							if($entity instanceof BaseEntity and $player->distance($entity)<=3){
								$entity->setBlindnessArmor($Hand->SkillCTime());
							}
						}
						$player->sendMessage('§a释放技能成功~');
                        Cooling::$weapon[$player->getName()][$Hand->getLTName()] = time() + $Hand->getSkillCD()-30;
					}
                    break;
                    case '樱花的誓约':
                        if(!isset(Cooling::$weapon[$player->getName()][$Hand->getLTName()]) or Cooling::$weapon[$player->getName()][$Hand->getLTName()]<time()){
                            if (!$player->getBuff()->consumptionMana(500)){
                                $player->sendMessage('§cMana不足！');
                                return;
                            }
                            $nbt = new CompoundTag;
                            $nbt->Pos = new ListTag("Pos", [
                                new DoubleTag("",  $player->x+0.5),
                                new DoubleTag("",  $player->y+0.5),
                                new DoubleTag("",  $player->z+0.5)
                            ]);
                            $nbt->Rotation = new ListTag('Rotation', [
                                new FloatTag('', 0),
                                new FloatTag('', 0)
                            ]);
                            new Sakura( $player->getLevel(), $nbt,  $player);
                            Cooling::$weapon[$player->getName()][$Hand->getLTName()] = time() + $Hand->getSkillCD();
                        }
                    break;
                    case '时空撕裂':
                        if(!isset(Cooling::$weapon[$player->getName()][$Hand->getLTName()]) or Cooling::$weapon[$player->getName()][$Hand->getLTName()]<time()){
                            if (!$player->getBuff()->consumptionMana(500)){
                                $player->sendMessage('§cMana不足！');
                                return;
                            }
                            $nbt = new CompoundTag;
                            $nbt->Pos = new ListTag("Pos", [
                                new DoubleTag("",  $player->x+0.5),
                                new DoubleTag("",  $player->y+0.5),
                                new DoubleTag("",  $player->z+0.5)
                            ]);
                            $nbt->Rotation = new ListTag('Rotation', [
                                new FloatTag('', 0),
                                new FloatTag('', 0)
                            ]);
                            new SpaceTear($player->getLevel(), $nbt, $player);
                            Cooling::$weapon[$player->getName()][$Hand->getLTName()] = time() + $Hand->getSkillCD();
                         }
                    break;
			}
		}
	}
	public function onEntityCombust(EntityCombustEvent $event){
		$entity=$event->getEntity();
		if($entity instanceof Player){
		    if($entity->getInventory()->getChestplate() instanceof Armor and $entity->getInventory()->getChestplate()->isResistanceFire()){
		        $event->setCancelled();
            }
        }
	}
	public function onDataPacketReceive(DataPacketReceiveEvent $event)
	{
		if($event->getPacket() instanceof UseItemPacket and $event->getPacket()->face === -1) {
			$player = $event->getPlayer();
			if(!isset(Events::$status[strtolower($player->getName())]) or Events::$status[strtolower($player->getName())]!==true)return;
			if(Cooling::$launch[$player->getName()] >= $this->server->getTick())return;
			$Hand = $player->getItemInHand();
			if($Hand instanceof Material){
				$nbt = new CompoundTag("", [
				   "Pos" => new ListTag("Pos", [
					   new DoubleTag("", $player->x),
					   new DoubleTag("", $player->y + $player->getEyeHeight()),
					   new DoubleTag("", $player->z),
				   ]),
					"Motion" => new ListTag("Motion", [
						new DoubleTag("", -sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
						new DoubleTag("", -sin($player->pitch / 180 * M_PI)),
						new DoubleTag("", cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
					]),
					"Rotation" => new ListTag("Rotation", [
						new FloatTag("", $player->yaw),
						new FloatTag("", $player->pitch),
					]),
				]);
                /*
                switch($Hand->getLTName()){
                    case '重击水晶球'://projectile hit player add damage effect
                        $entity = Entity::createEntity("EnderPearl", $player->getLevel(), $nbt, $player);
                        $entity->setMotion($entity->getMotion()->multiply(1.5));
                        $entity->skill=['Injured', ceil($player->getGrade()/15)];
                        $entity->spawnToAll();
                        $Hand->setCount($Hand->getCount()-1);
                        $player->getInventory()->setItemInHand($Hand);
                        Cooling::$launch[$player->getName()] = $this->server->getTick()+20;
                    break;
                    case '虚弱水晶球':
                        $entity = Entity::createEntity("EnderPearl", $player->getLevel(), $nbt, $player);
                        $entity->setMotion($entity->getMotion()->multiply(1.5));
                        $entity->skill=['weak', ceil($player->getGrade()/15)];
                        $entity->spawnToAll();
                        $Hand->setCount($Hand->getCount()-1);
                        $player->getInventory()->setItemInHand($Hand);
                        Cooling::$launch[$player->getName()] = $this->server->getTick()+20;
                    break;
				}
                */
				$event->setCancelled(true);
			}
			return;
		}elseif($event->getPacket() instanceof PlayerActionPacket){
			if($event->getPacket()->action!==1 and $event->getPacket()->action!==18)return;
			$player = $event->getPlayer();
			if(!isset(Events::$status[strtolower($player->getName())]) or Events::$status[strtolower($player->getName())]!==true)return;
			if(Cooling::$launch[$player->getName()] >= $this->server->getTick())return;
			$Hand = $player->getItemInHand();
			if($Hand instanceof Weapon and $Hand->canUse($player)){
				switch($Hand->getSkillName()){
					case '凝冻雪球':
						if(isset(Cooling::$weapon[$player->getName()][$Hand->getLTName()]) and Cooling::$weapon[$player->getName()][$Hand->getLTName()]>time())return;
						if (!$player->getBuff()->consumptionMana(1000)){
						    $player->sendMessage('§cMana不足！');
							return;
                        }
						if($event->getPacket()->action===18)return;
						$nbt = new CompoundTag("", [
						   "Pos" => new ListTag("Pos", [
							   new DoubleTag("", $player->x),
							   new DoubleTag("", $player->y + $player->getEyeHeight()),
							   new DoubleTag("", $player->z),
						   ]),
							"Motion" => new ListTag("Motion", [
								new DoubleTag("", -sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
								new DoubleTag("", -sin($player->pitch / 180 * M_PI)),
								new DoubleTag("", cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
							]),
							"Rotation" => new ListTag("Rotation", [
								new FloatTag("", $player->yaw),
								new FloatTag("", $player->pitch),
							]),
						]);
						$entity = Entity::createEntity("Snowball", $player->getLevel(), $nbt, $player);
						$entity->setMotion($entity->getMotion()->multiply(1.5));
						$entity->skill=['Freeze',1+ 0.25*$Hand->SkillCTime()];
						Cooling::$weapon[$player->getName()][$Hand->getLTName()] = time() + $Hand->getSkillCD();
					break;
					case '时空穿梭':
						if(isset(Cooling::$weapon[$player->getName()][$Hand->getLTName()]) and Cooling::$weapon[$player->getName()][$Hand->getLTName()]>time())return;
						if($event->getPacket()->action===18)return;
						if($player->getFood()<=0){
							$player->sendMessage('§a饥饿度不足！！');
							return;
						}
						if (!$player->getBuff()->consumptionMana(500)){
                            $player->sendMessage('§cMana不足！');
							return;
						}
						$dx = -sin($player->getYaw()/180*M_PI)*6;//计算运动X
						$dz = cos($player->getYaw()/180*M_PI)*6;//计算运动Z
						$dy = -sin($player->getPitch()/180*M_PI)*6;//抬头向上 低头向下
						$pk = new SetEntityMotionPacket();
						$pk->eid = $player->getId();
						$pk->motionX = $dx;
						$pk->motionY = $dy;
						$pk->motionZ = $dz;
						$player->dataPacket($pk);
						Cooling::$weapon[$player->getName()][$Hand->getLTName()] = time() + $Hand->getSkillCD();
						$player->setFood($player->getFood()-1);
						$player->sendMessage('§e释放技能成功！');
						// Cooling::$weapon[$player->getName()][$Hand->getLTName()] = time()+1;
					break;
					case '风暴之力':
						if(isset(Cooling::$weapon[$player->getName()][$Hand->getLTName()]) and Cooling::$weapon[$player->getName()][$Hand->getLTName()]>time())return;
						if($event->getPacket()->action===18)return;
                        if (!$player->getBuff()->consumptionMana(1000)){
                            $player->sendMessage('§cMana不足！');
							return;
                        }
						$nbt = new CompoundTag("", [
						   "Pos" => new ListTag("Pos", [
							   new DoubleTag("", $player->x),
							   new DoubleTag("", $player->y + $player->getEyeHeight()),
							   new DoubleTag("", $player->z),
						   ]),
							"Motion" => new ListTag("Motion", [
								new DoubleTag("", -sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
								new DoubleTag("", -sin($player->pitch / 180 * M_PI)),
								new DoubleTag("", cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
							]),
							"Rotation" => new ListTag("Rotation", [
								new FloatTag("", $player->yaw),
								new FloatTag("", $player->pitch),
							]),
						]);
						$entity = Entity::createEntity("EnderPearl", $player->getLevel(), $nbt, $player);
						$entity->setMotion($entity->getMotion()->multiply(1.5));
						$entity->skill=['Vertigo',1+ 0.25*$Hand->SkillCTime()];
						Cooling::$weapon[$player->getName()][$Hand->getLTName()] = time() + $Hand->getSkillCD();
					break;
					case '猪年神器':
						if(isset(Cooling::$weapon[$player->getName()][$Hand->getLTName()]) and Cooling::$weapon[$player->getName()][$Hand->getLTName()]>time())return;
						if($event->getPacket()->action===1)return;
						$model = $Hand->getNamedTag()['model']??0;
						if(++$model>3)$model=0;
						$nbt=$Hand->getNamedTag();
						$nbt->model = new ByteTag('model', $model);
						$Hand->setNamedTag($nbt);
						$player->getInventory()->setItemInHand($Hand);
						switch($model){
							case 0:
								$player->sendMessage('§e切换形态为:§c攻击');
							break;
							case 1:
								$player->sendMessage('§e切换形态为:§a恢复');
							break;
							case 2:
								$player->sendMessage('§e切换形态为:§d魔法');
							break;
							case 3:
								$player->sendMessage('§e切换形态为:§2重伤');
							break;
						}
						Cooling::$weapon[$player->getName()][$Hand->getLTName()]=time()+1;
					break;
				}
				if($event->getPacket()->action===18 or $Hand->getWeaponType()=='近战')return;
				$all = $this->plugin->config->get('远程', []);
				if(isset($all[strtolower($player->getName())]) and $all[strtolower($player->getName())] === false)return;

                if ($Hand->getLTName()=='时空劫持者' and !$player->getBuff()->consumptionMana(1000)){
                    $player->sendMessage('§cMana不足！');
					return;
                }
				switch($Hand->getBulletType()) {
					case 'arrow':
						$nbt = new CompoundTag("", [
					   "Pos" => new ListTag("Pos", [
						   new DoubleTag("", $player->x),
						   new DoubleTag("", $player->y + $player->getEyeHeight()),
						   new DoubleTag("", $player->z)
					   ]),
						"Motion" => new ListTag("Motion", [
							new DoubleTag("", -sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
							new DoubleTag("", -sin($player->pitch / 180 * M_PI)),
							new DoubleTag("", cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI))
						]),
						"Rotation" => new ListTag("Rotation", [
								new FloatTag("", $player->yaw),
								new FloatTag("", $player->pitch)
							]),
							"Fire" => new ShortTag("Fire", $player->isOnFire() ? 45 * 60 : 0),
							"Potion" => new ShortTag("Potion", 0)
						]);
						$entity = Entity::createEntity("falseArrow", $player->getLevel(), $nbt, $player, true);
						$entity->setMotion($entity->getMotion()->multiply(2));
						break;
					case 'snowball':
						$nbt = new CompoundTag("", [
					   "Pos" => new ListTag("Pos", [
						   new DoubleTag("", $player->x),
						   new DoubleTag("", $player->y + $player->getEyeHeight()),
						   new DoubleTag("", $player->z),
					   ]),
						"Motion" => new ListTag("Motion", [
							new DoubleTag("", -sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
							new DoubleTag("", -sin($player->pitch / 180 * M_PI)),
							new DoubleTag("", cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
						]),
						"Rotation" => new ListTag("Rotation", [
								new FloatTag("", $player->yaw),
								new FloatTag("", $player->pitch),
							]),
						]);
						$entity = Entity::createEntity("Snowball", $player->getLevel(), $nbt, $player);
						$entity->setMotion($entity->getMotion()->multiply(1.5));
						break;
					case 'EnderPearl'://add new projectile type: ender pearl
						$nbt = new CompoundTag("", [
					   "Pos" => new ListTag("Pos", [
						   new DoubleTag("", $player->x),
						   new DoubleTag("", $player->y + $player->getEyeHeight()),
						   new DoubleTag("", $player->z),
					   ]),
						"Motion" => new ListTag("Motion", [
							new DoubleTag("", -sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
							new DoubleTag("", -sin($player->pitch / 180 * M_PI)),
							new DoubleTag("", cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
						]),
						"Rotation" => new ListTag("Rotation", [
								new FloatTag("", $player->yaw),
								new FloatTag("", $player->pitch),
							]),
						]);
						$entity = Entity::createEntity("EnderPearl", $player->getLevel(), $nbt, $player);
						$entity->setMotion($entity->getMotion()->multiply(1.5));
						$entity->skill=['LTItem',0];
						break;
					default: //TODO More..
						return;
				}
				$entity->spawnToAll();
				$entity->setDamage($Hand->getPVPDamage());
				$entity->setCalculate(false);
				Cooling::$launch[$player->getName()] = $this->server->getTick()+$Hand->getSpeed();
			}
		}
	}

    /**
     * 修复击杀提示消失
     * @param PlayerDeathEvent $event
     */
	public function onPlayerDeathEvent(PlayerDeathEvent $event){
        $this->onDeathEvent($event);
    }
	public function onDeathEvent(EntityDeathEvent $event)
	{
		$entity = $event->getEntity();
		$cause = $entity->getLastDamageCause();
		if($cause === null)return;
        if($cause instanceof EntityDamageByEntityEvent) {
            if($entity instanceof Player){
                $damager = $cause->getDamager();
                if($damager instanceof Player) {
                    $hand = $damager->getItemInHand();
                    if($hand instanceof Weapon and $hand->canUse($damager) and ($mess=$hand->getKillMessage($damager, $entity))!==false){
                        $event->setDeathMessage($mess);
                    }
               }
            }else{
                if ($entity instanceof BaseEntity){
                    /** @var Player $damager */
                    $damager = $cause->getDamager();
                    if($damager instanceof Player){
                        $hand = $damager->getItemInHand();
                        if ($hand instanceof Weapon\DrawingKnife){
                            if ($hand->getDurable() > 0) {
                                $hand->addKills(1);
                                $hand->addGlory(mt_rand(5, 10));
                                $hand->setDurable($hand->getDurable() - 1);
                                if($hand->getDurable() == 0){
                                    $item = Main::getInstance()->createMaterial('耀魂碎片');
                                    $count = 3 + ((int)$hand->getGlory() / 100);
                                    if($count > 64) $count = 64;
                                    $item->setCount($count);
                                    /** @var \pocketmine\entity\Item $en */
                                    $en = $entity->getLevel()->dropItem($entity, $item, new Vector3(0, 0, 0));
                                    $en->setOwner($damager->getName());
                                    $hand->setDurable(-3);
                                }
                                $damager->getInventory()->setItemInHand($hand);
                            }else{
                                $damager->sendMessage('§c你的'.$hand->getLTName().'无耐久了，无法获得荣耀值和击杀数。');
                            }
                        }
                        if (\LTCraft\Main::getCNumber($damager->getName()) < 10){
                            $probability = 1;
                            $add = $entity->getMaxHealth() / 50000;
                            if ($add > 29)$add = 29;
                            if (mt_rand(1, 100) <= $probability + $add){
                                $c = '§a'.'Craft'[mt_rand(0, 4)];
                                $item = Main::getInstance()->createMaterial($c);
                                $itemE = $entity->getLevel()->dropItem($entity, $item);
                                $itemE->setOwner($damager);
                                $damager->sendMessage('§c你击杀了'.$entity->getNormalName().'掉落了一个字符'.$c.'。');
                                \LTCraft\Main::addCNumber($damager->getName());
                            }
                        }
                    }
                }
            }
        }
	}
	public static function canCalculate($cause){
		return !in_array($cause, [EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, EntityDamageEvent::CAUSE_THORNS, EntityDamageEvent::CAUSE_SECONDS_KILL]);
	}
	public function onEntityDEvent(EntityDamageEvent $event)
	{
		if($event->isCancelled())return;
		$entity = $event->getEntity();
		if($event instanceof EntityDamageByEntityEvent and self::canCalculate($event->getCause())) {
			$damager = $event->getDamager();
			if(($entity instanceof Player and $damager instanceof Player and $entity->getBuff()->miss()) or ($damager instanceof Creature and $damager->getBlindness())) {
				new FloatingText($entity, '§c未命中~', 0.8);
                $entity->newProgress('抱歉，今天不行。', '躲避一次攻击。');
				return $event->setCancelled(true);
			}
			if(!($damager instanceof Player))return;
			if($entity instanceof Player)
				$entity->getBuff()->addToEntityEffect($entity, $damager);
			$hand = $damager->getItemInHand();
			if($hand instanceof Weapon and $hand->canUse($damager)){
				if($hand->getArmour($entity)>0 or $damager->getBuff()->getArmour($entity)>0)$event->setRateDamage($hand->getArmour($entity)+$damager->getBuff()->getArmour($entity), EntityDamageEvent::MODIFIER_ARMOUR);
				if($hand->getParticle() and $entity instanceof Player)$entity->getLevel()->addParticle(new DestroyBlockParticle($entity, new Redstone()));
				if($entity instanceof Player) {
					if($hand->getWeaponType() !== '通用' and ($event->getCause() === EntityDamageEvent::CAUSE_PROJECTILE and $hand->getWeaponType() == '近战') or ($event->getCause() !== EntityDamageEvent::CAUSE_PROJECTILE and $hand->getWeaponType() == '远程'))return $event->setCancelled(true); //fix bugs..
					$hand->addEffect($entity, $damager);
					if($hand->getInjury()>0)$entity->setInjured($hand->getInjury());
					if($hand->getRealDamage()>0){
						$event->setDamage($hand->getRealDamage(), EntityDamageEvent::MODIFIER_REAL_DAMAGE);
					}
					$hand->fire($entity, $damager);
					$hand->knockBackAndblowFly($damager, $entity, $event);
					$hand->Lightning($damager, $entity);
					$hand->freeze($damager, $entity);
				} elseif($entity instanceof BaseEntity) {
					if($event->getCause() === EntityDamageEvent::CAUSE_PROJECTILE)return $event->setCancelled(true); //projectile cannot attack mob
					$hand->addEffect($entity, $damager);
					$hand->fire($entity, $damager);
					$hand->Lightning($damager, $entity);
				}
			}
		}else{
		    if ($entity instanceof Player){
		        if($entity->getBuff()->checkOrnamentsInstall('奥丁之戒')!==false){
		            if (in_array($event->getCause(), [EntityDamageEvent::CAUSE_SUFFOCATION, EntityDamageEvent::CAUSE_HT, EntityDamageEvent::CAUSE_FALL, EntityDamageEvent::CAUSE_FIRE, EntityDamageEvent::CAUSE_FIRE_TICK, EntityDamageEvent::CAUSE_LAVA, EntityDamageEvent::CAUSE_DROWNING, EntityDamageEvent::CAUSE_STARVATION])){
		                $event->setCancelled();
                    }
                }

            }
        }
	}
}