<?php
namespace LTCraft;

use LTItem\Mana\Mana;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\entity\{Entity, Item as eItem, Effect, Creature, DroppedItem, Human, Painting};
use pocketmine\event\Listener;
use pocketmine\nbt\tag\{ByteTag, CompoundTag, FloatTag, StringTag};
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\CallbackTask;
use pocketmine\event\player\{PlayerInteractEvent, PlayerCommandPreprocessEvent, PlayerDeathEvent, PlayerJoinEvent, PlayerPreLoginEvent, PlayerQuitEvent, PlayerDropItemEvent, PlayerChatEvent, PlayerMoveEvent, PlayerItemHeldEvent};
use pocketmine\event\entity\{EntityLevelChangeEvent, EntityInventoryChangeEvent, EntityDamageEvent, EntityDamageByEntityEvent, EntityTeleportEvent, ExplosionPrimeEvent};
use pocketmine\block\{Stair, Portal, EndGateway, EndPortal};
use pocketmine\math\Vector3;
use pocketmine\level\{Position, Level};
use pocketmine\item\Item;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\network\protocol\{InteractPacket,ContainerSetSlotPacket, AddEntityPacket, RemoveEntityPacket};
use pocketmine\command\{Command, CommandSender};
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\utils\Config;
use pocketmine\utils\Utils;
use pocketmine\event\block\{BlockPlaceEvent, BlockBreakEvent};
use pocketmine\tile\Sign;
use pocketmine\event\inventory\InventoryOpenEvent;
use LTEntity\entity\BaseEntity;
use onebone\economyapi\EconomyAPI;
use LTPet\Pets\Pets;
use pocketmine\level\particle\{GenericParticle,
    HeartParticle,
    HugeExplodeSeedParticle,
    DestroyBlockParticle,
    DustParticle};
use LTLogin\Events;
use MyPlot\MyPlot;
use LTVIP\CEntity;
use pocketmine\level\sound\ExplodeSound;
use LTSociety\Society;
use LTSociety\Main as LTSociety;
use LTGrade\Main as LTGrade;
use LTPopup\Popup;
use LTItem\Main as LTItem;
use LTEntity\Main as LTEntity;
use LTMenu\Main as LTMenu;
use LTItem\SpecialItems\Weapon;
use LTItem\SpecialItems\Armor;
use LTItem\SpecialItems\Material;
use LTItem\SpecialItems\BaseOrnaments;
use LTMenu\Open;
/*
	LTCraft 核心类 这个类包含了基本小功能！
*/
class Main extends PluginBase implements Listener{
	public $Teleport=[];
	private $give=array();
	public $HeadRanking;
	// public $onlineTime=[];
	//private $locks=[];
	private $sign=[];
	public $status=[];
	public $notDrop=false;
	public $autoRestart=false;
	public static $allLevelUpdate=true;
	public static $instance=null;
	public static $eventID=0;
	public $events=[];
	public $target;
	/** @var Config */
	public $Head;
	public $FloatingTexts = [];
	/** @var array */
	public $Tutorials = [];
    /**
     * @var Config
     */
    private $playerConfig;

    /**
     * @return Main
     */
	public static function getInstance(){
		return self::$instance;
	}
	public function onDisable(){
		// $this->Data->save(false);
		if(isset($this->Head))$this->Head->save(false);
		$this->config->save(false);
		$this->playerConfig->save(false);
		// if(count($this->TutorialRecordA)>0){
			// $this->TutorialRecord->setAll($this->TutorialRecordA);
			// $this->TutorialRecord->save(false);
		// }
	}
	public static function save(){
		$LTCraft = self::getInstance();
		if(isset($LTCraft->Head))$LTCraft->Head->save(false);
		$LTCraft->config->save(false);
        $LTCraft->playerConfig->save(false);
		$LTGrade = \LTGrade\Main::getInstance();
		$LTGrade->PlayerTaskConf->save(false);
		/** @var \LTMenu\Main $LTMenu */
		$LTMenu = \LTMenu\Main::getInstance();
		$LTMenu->getExchange()->save(false);
		$LTEntity = \LTEntity\Main::getInstance();
		if(isset($LTEntity->WeeksExp))$LTEntity->WeeksExp->save(false);
	}
	public function onEnable(){
		self::$instance=$this;
		$this->getServer()->getPluginManager()->registerEvents($this,$this);
		if(!file_exists($this->getDataFolder())){
			mkdir($this->getDataFolder());
			mkdir($this->getDataFolder().'Saves');
			mkdir($this->getDataFolder().'Players');
			mkdir($this->getDataFolder().'HeadCount');
		}else{
			if(!file_exists($this->getDataFolder().'Saves')){
				mkdir($this->getDataFolder().'Saves');
			}
			if(!file_exists($this->getDataFolder().'Players')){
				mkdir($this->getDataFolder().'Players');
			}
			if(!file_exists($this->getDataFolder().'HeadCount')){
				mkdir($this->getDataFolder().'HeadCount');
			}
		}
		//$this->getServer()->getScheduler()->scheduleDelayedRepeatingTask(new CallbackTask([$this,"cleanTime"],[20]),32000,32000);
		$this->ops=new Config($this->getDataFolder().'Ops.yml',Config::YAML,array());
		$this->Data=new Config($this->getDataFolder()."Data.yml",Config::YAML,array());
		$this->Head=new Config($this->getDataFolder()."Head.yml",Config::YAML,array());
		$this->TutorialRecord = new Config($this->getDataFolder()."TutorialRecord.yml",Config::YAML,array());
		Tutorial::init($this->TutorialRecord);
//		$this->r=new Config($this->getDataFolder()."R.yml",Config::YAML,array());
		$this->config=new Config($this->getDataFolder()."Config.yml",Config::YAML,array(
			'自动重启'=>false,
			'模式'=>0,
			'刷新全部世界开关'=>true,
			'爱心粒子'=>[],
			'受伤爆炸'=>[],
			'传送方块'=>[],
			'悬浮字'=>[],
			'世界欢迎标题'=>[],
			'击杀特效'=>[]
		));
		$this->playerConfig = new Config($this->getDataFolder()."playerConfig.yml",Config::YAML,array(
            '爱心粒子'=>[],
            '受伤爆炸'=>[],
            '击杀特效'=>[]
        ));
		$this->autoRestart=$this->config->get('自动重启');
		self::$allLevelUpdate=$this->config->get('刷新全部世界开关',true);
		$this->initFloatingText();
		$this->TutorialRecordA = [];
		// $this->getServer()->getScheduler()->scheduleDelayedRepeatingTask(new CallbackTask([$this,"record"],[]),1,1);
		$this->getServer()->getScheduler()->scheduleDelayedRepeatingTask(new CallbackTask([$this,"updateTutorial"],[]),1,1);
	}
	public function getMode(){
		return $this->config->get("模式", 0);
	}

    /**
     * @param $playerName
     * @return int
     */
	public function getViewDistance($playerName){
		$all = $this->playerConfig->get("视野范围", []);
		return $all[strtolower($playerName)]??5;
	}
	public function record(){
		$player = $this->getServer()->getPlayerExact('angel_xx');
		if($player instanceof Player and isset($this->sj)){
			$this->sj++;
			$this->TutorialRecordA[] = $player->getLevel()->getName().':'.$player->getX().':'.$player->getY().':'.$player->getZ().':'.$player->getYaw().':'.$player->getPitch();
		}
	}
	public function updateTutorial(){
		foreach($this->Tutorials as $Tutorial){
			$Tutorial->progressUpdate();
		}
	}
	public function initFloatingText(){
		$floatingTexts=$this->config->get('悬浮字');
		foreach($this->getServer()->getLevels() as $level){
			if(isset($floatingTexts[$level->getName()])){
				foreach($floatingTexts[$level->getName()] as $floatTextInfo){
					$FloatingText=new FloatingText(new Position($floatTextInfo['x'], $floatTextInfo['y'], $floatTextInfo['z'], $level), $floatTextInfo['content']);
					$this->FloatingTexts[]=$FloatingText;
					switch($floatTextInfo['content']){
						case '富豪榜':
							EconomyAPI::getInstance()->setTopFloatingText($FloatingText);
						break;
						case '人头榜':
							$this->HeadRanking=$FloatingText;
							$this->updateRanking();
						break;
						case '捐赠榜':
							Society::$BalanceRanking=$FloatingText;
							LTSociety::getInstance()->updateGuildBalance();
						break;
						case '积分榜':
							Society::$IntegralRanking=$FloatingText;
							LTSociety::getInstance()->updateGuildIntegral();
						break;
						case '人数榜':
							Society::$CountRanking=$FloatingText;
							LTSociety::getInstance()->updateGuildCount();
						break;
						case '经验榜':
                            LTEntity::getInstance()->setExpRanking($FloatingText);
						break;
					}
				}
			}
		}
	}
	public function closeFloatingTexts(){
		foreach($this->FloatingTexts as $FloatingText){
			$FloatingText->getLevel()->removeFloatingText($FloatingText);
		}
	}
	//开始新手教程
	public function startTutorial(Player $player){
		$this->Tutorials[spl_object_hash($player)] = new Tutorial($player);
	}

    /**
     * 结束新手教程
     * @param Player $player
     * @param string $type
     * @return bool
     */
	public function endTutorial(Player $player, $type = 'exit'){
		if(isset($this->Tutorials[spl_object_hash($player)])){
			$this->Tutorials[spl_object_hash($player)]->endTutorial($type);
			unset($this->Tutorials[spl_object_hash($player)]);
			return true;
		}else{
			return false;
		}
	}
	public function hasTutorial(Player $player){
		return isset($this->Tutorials[spl_object_hash($player)]);
	}

    /**
     * @param $player Player
     */
	public function giveR($player){
		$name=strtolower($player->getName());
		self::sendItem($name, ['材料', '新春红包', 1]);
		self::sendItem($name, ['材料', '神秘字符', 10]);
		if(!$player->isVIP()){
			$player->setVIP(1);
			Server::getInstance()->dataBase->pushService('30'.$name.' 1 7');
		}
		$player->sendMessage('§a§l恭喜你完成新春在线任务，奖励已发到你的邮箱！');
		$all=$this->r->get($name, []);
		$all[date("d")]=true;
		$this->r->set($name, $all);
		$this->r->save(false);
		unset($this->onlineTime[$player->getName()]);
	}
	public static function PlayerUpdateGradeTo30($name){
		$name=strtolower($name);
		$sql="SELECT * FROM server.recommended WHERE username='{$name}'";//查询记录 然后回调 self::PlayerUpdateGradeTo30Callback()
		Server::getInstance()->dataBase->pushService('2'.chr(8).$sql);
	}
	public static function PlayerUpdateGradeTo30Callback($data){
		$username=strtolower($data['username']);
		$recommended=strtolower($data['recommended']);
		$id=$data['ID'];
		Server::getInstance()->dataBase->pushService('30'.$username.' 1 7');
		Server::getInstance()->dataBase->pushService('30'.$recommended.' 1 7');
		if(($UPlayer=Server::getInstance()->getPlayerExact($username))){
			if(!$UPlayer->isVIP()){
				$UPlayer->setVIP(1);
			}
		}
		if(($RPlayer=Server::getInstance()->getPlayerExact($recommended))){
			if(!$RPlayer->isVIP()){
				$RPlayer->setVIP(1);
			}
		}
		$sql="update server.recommended set status=1 WHERE ID='{$id}'";
		Server::getInstance()->dataBase->pushService('1'.chr(2).$sql);
	}
	public static function rjb($r,$g,$b,$rr,$gg,$bb,$step,$n){
		$rrr=$r+($bb-$b)*$n/$step;
		$ggg=$g+($gg-$g)*$n/$step;
		$bbb=$b+($bb-$b)*$n/$step;
		$rgb=array($rrr,$ggg,$bbb);
		return $rgb;
	}

    /**
     * 死亡粒子 为什么不直接传一个 @var Position 呢？
     *
     * @param $x
     * @param $y
     * @param $z
     * @param $level
     */
	public static function dieParticle($x,$y,$z,$level){
		$r = 2;
		$yy = $y-1.1;
		for($i=1;$i<=360;$i++){
			$a=$x+$r*cos($i*3.14/90) ;
			$b=$z+$r*sin($i*3.14/90) ;
		if($i<60){
			$rgb=self::rjb(230,30,12,236,30,246,60,$i);
			$level->addParticle(new DustParticle(new Vector3($a,$yy+1.2,$b),$rgb[0],$rgb[1],$rgb[2]));
			}elseif($i>180 and $i<240){
				$rgb=self::rjb(11,207,214,55,226,15,60,$i-180);
				$level->addParticle(new DustParticle(new Vector3($a,$yy+1.2,$b),$rgb[0],$rgb[1],$rgb[2]));
			}
			$aa=$x+$r*cos($i*3.14/90) ;
			$bb=$z+$r*sin($i*3.14/90) ;
			if($i>60 and $i<120){
				$rgb=self::rjb(244,13,209,33,21,234,60,$i-60);
				$level->addParticle(new DustParticle(new Vector3($aa,$yy+1.2,$bb),$rgb[0],$rgb[1],$rgb[2]));
			}elseif($i>240 and $i<300){
				$rgb=self::rjb(42,227,41,212,219,17,60,$i-240);
				$level->addParticle(new DustParticle(new Vector3($aa,$yy+1.2,$bb),$rgb[0],$rgb[1],$rgb[2]));
			}
			$aaa=$x+$r*cos($i*3.14/90) ;
			$bbb=$z+$r*sin($i*3.14/90) ;
			if($i>120 and $i<180){
				$rgb=self::rjb(19,67,246,7,249,215,60,$i-120);
				$level->addParticle(new DustParticle(new Vector3($aaa,$yy+1.2,$bbb),$rgb[0],$rgb[1],$rgb[2]));
			}elseif($i>300 and $i<360){
				$rgb=self::rjb(216,235,11,233,55,10,60,$i-300);
				$level->addParticle(new DustParticle(new Vector3($aaa,$yy+1.2,$bbb),$rgb[0],$rgb[1],$rgb[2]));
			}
			$yy=$yy+0.00694;
		}
	}
	public function updateRanking(){
		if($this->HeadRanking instanceof FloatingText){
			$Data = $this->Head->getAll();
			arsort($Data);
			$n=1;
			$text='§l§e周人头排行榜'."\n";
			foreach($Data as $name => $count){
				if($name=='配置日期')continue;
				$text.='§a'.$n .'#玩家名字'.$name .'§d人头数:'.$count ."\n";
				if(++$n==11)break;
			}
			$this->HeadRanking->updateAll($text.'§3人头数会在周一进行结算 排行越高奖励越高');
		}
	}
	public static function calculateS($name){
		return false;
		$name=strtolower($name);
		if(self::$instance->Data->get('配置日期')===false)
			self::$instance->Data->set('配置日期', date('Y-m-d'));
		elseif(self::$instance->Data->get('配置日期')!==date('Y-m-d')){
			self::$instance->Data->setAll([]);
			self::$instance->Data->set('配置日期', date('Y-m-d'));
		}
		$s=self::$instance->Data->get($name, 0);
		if($s>=10){
			return false;
		}
		self::$instance->Data->set($name, ++$s);
		return true;
	}
	public function getHeadCount($name){
		$name=strtolower($name);
		return $this->Head->get($name, 10);
	}

    /**
     * 每日更新 更新人头和任务
     */
	public function updateHeadCountConfig(){
		$this->Head->save(false);
		copy($this->Head->getFile(), $this->getDataFolder().'HeadCount/'.date('Y-m-d', time()-43200).'.yml');
		if(date("w")==1){//看来今天试周一
			$keep=[];
			foreach($this->Head->getAll() as $name=>$count){
				if($count>10){
					$keep[$name]=$count;
				}
			}
			arsort($keep);//排名
			$i=0;
			foreach($keep as $name=>$count){
				if($i<4){
					self::sendItem($name, ['材料', '觉醒石', 4-$i]);
					self::sendItem($name, ['材料', '武器精髓', 4-$i]);
				}
				self::sendItem($name, ['材料', '皮肤碎片', 11-$i]);
				if(++$i==11)break;
			}
			$this->getServer()->getScheduler()->scheduleAsyncTask(new RankingsReward($keep, RankingsReward::PVP_HEADER));
			$this->Head->setAll([]);
		}else{
			$keep=[];
			foreach($this->Head->getAll() as $name=>$count){
				if($count>10){
					$keep[$name]=$count;
				}
			}
			$this->Head->setAll($keep);
		}
	}
	public static function isNeedUpdate(Level $level){
		if(self::$allLevelUpdate)return true;
		$name=$level->getName();
		if(in_array($name,['zc','zy' ,'dp' , 'jm', 'land', 'ender', 'nether', 'create', 'login']))
			return true;
		else return false;
	}
	public function onItemHeldEvent(PlayerItemHeldEvent $event){
		// $player=$event->getPlayer();
		// if($player->getName()==='Angel_XX' and isset($this->sj) and $this->sj>10){
			// unset($this->sj);
			// return $player->sendMessage('取消路程记录');
		// }
	}
	public function onTeleportEvent(EntityTeleportEvent $event){
		if($event->isCancelled())return;
		$entity=$event->getEntity();
		if($entity instanceof Player){
			$to=$event->getTo();
			$from=$event->getFrom();
			if($to->getLevel()===$from->getLevel())return;
			$toName=$to->getLevel()->getName();
			$grade=$entity->getGrade();
			if($entity->onTutorial())return;
			if($to instanceof Position){
				if($toName==='pvp' AND $grade<50){
					$event->setCancelled(true);
					$entity->sendMessage('§l§a[提示]§c你需要等级大于等于50才可以通往这个世界！');
					return;
				}elseif($toName==='s1' AND $grade<120){
					$event->setCancelled(true);
					$entity->sendMessage('§l§a[提示]§c你需要等级大于等于120才可以通往这个世界！');
					return;
				// }elseif($toName==='create' AND $grade<30){
				}elseif($toName==='create'){
					$event->setCancelled(true);
					// $entity->sendMessage('§l§a[提示]§c你需要等级大于等于30才可以通往这个世界！');
					$entity->sendMessage('§l§a[提示]§c暂不开放！');
					return;
				}elseif($toName==='s2' AND $entity->getGTo()<6){
					$event->setCancelled(true);
					$entity->sendMessage('§l§a[提示]§c你需要完成主线任务才可以进入这个世界！');
					return;
				}elseif($toName==='boss'){
					if($grade<180){
						$entity->sendMessage('§l§a[提示]§c你需要等级180才可以通往这个世界！');
						$event->setCancelled(true);
						return;
					}elseif(count($to->getLevel()->getPlayers())>=5){
						$canJoin=false;
						foreach($to->getLevel()->GamePlayers as $name=>$key){
							if($name==strtolower($entity->getName()))$canJoin=true;
						}
						if(!$canJoin){
							$entity->sendMessage('§l§a[提示]§cBoss世界人数已经满了！');
							$event->setCancelled(true);
							return;
						}
					}
					$tmp=&LTEntity::getInstance()->spawnTmp['boss'];
					if($tmp['数量']>0){
						$canJoin=false;
						foreach($to->getLevel()->GamePlayers as $name=>$key){
							if($name==strtolower($entity->getName()))$canJoin=true;
						}
						if(!$canJoin){
							$entity->sendMessage('§l§a[提示]§cBoss战斗已经开始了！');
							$event->setCancelled(true);
							return;
						}
					}else{
						$to->getLevel()->GamePlayers[strtolower($entity->getName())]=5;
						$to->getLevel()->addSound(new \pocketmine\level\sound\DoorSound(new Vector3(67, 94, 31)));
					}
				}
				if(in_array($toName, ['t1', 't2', 't3', 't4', 't5', 't6', 's1'])){
					if($entity->getGTo()<6){
						$entity->sendMessage('§l§a[提示]§c你需要完成主线任务才可以进入这个世界！');
						$event->setCancelled(true);
						return;
					}
					switch($toName){
						case 't1':
							if($entity->getMaxDamage()<500){
								$entity->sendMessage('§l§a[提示]§c你的伤害不足与进入这个世界！');
								$event->setCancelled(true);
							}else{
								$entity->setFlying(false);
							}
						break;
						case 't2':
							if($entity->getMaxDamage()<1000){
								$entity->sendMessage('§l§a[提示]§c你的伤害不足与进入这个世界！');
								$event->setCancelled(true);
							}else{
								$entity->setFlying(false);
							}
						break;
						case 't3':
							if($entity->getMaxDamage()<1200){
								$entity->sendMessage('§l§a[提示]§c你的伤害不足与进入这个世界！');
								$event->setCancelled(true);
							}else{
								$entity->setFlying(false);
							}
						break;
						case 't4':
							if($entity->isVIP()<2){
								$entity->sendMessage('§l§a[提示]§c你需要VIP2及以上才可以进入这个世界！');
								$event->setCancelled(true);
							}else{
								$entity->setFlying(false);
							}
						break;
						case 't5':
							if($entity->getMaxDamage()<1500){
								$entity->sendMessage('§l§a[提示]§c你的伤害不足与进入这个世界！');
								$event->setCancelled(true);
							}else{
								$entity->setFlying(false);
							}
						break;
						case 't6':
							if($entity->getMaxDamage()<2000){
								$entity->sendMessage('§l§a[提示]§c你的伤害不足与进入这个世界！');
								$event->setCancelled(true);
							}else{
								$entity->setFlying(false);
							}
						break;
					}
					return;
				}
				if(in_array($toName, ['f2', 'f3', 'f4', 'f5', 'f6', 'f7', 'f8', 'f9']) and $toName[1] >$entity->getGTo()){
					$entity->sendMessage(('§l§a[提示]§c你需要毕业F'.($toName[1] -1) .'才可以进入这个世界！'));
					$event->setCancelled(true);
					return;
				}
				// if(in_array($toName, ['f6', 'f7', 'f8']) and 6>$entity->getGTo()){
					// $entity->sendMessage('§l§a[提示]§c你需要毕业F5才可以进入这个世界！');
					// $event->setCancelled(true);
					// return;
				// }
			}elseif($from instanceof Position){
				if($from->getLevel()->getName()==='boss'){
					unset($from->getLevel()->GamePlayers[strtolower($entity->getName())]);
					$from->getLevel()->addSound(new \pocketmine\level\sound\DoorSound(new Vector3(67, 94, 31)));
				}
			}
		}
	}
	public function onEntityDamage(EntityDamageEvent $event){
		if($event->isCancelled())return;
		if($event instanceof EntityDamageByEntityEvent){
			$player=$event->getEntity();
			$damager=$event->getDamager();
			if($player instanceof Player && $damager instanceof Player){
				if($player->getRole()=='战士' and $player->PassiveCooling<time() and $player->getGeNeAwakening()>0){
					foreach($player->level->getPlayers() as $entity){
						if($player->distanceSquared($entity)>30 or $entity===$player)continue;
						$deltaX = $entity->x - $player->x;
						$deltaZ = $entity->z - $player->z;
						$entity->knockBack($player, 0, $deltaX, $deltaZ, 1);
						$entity->sendMessage('§l§a你受到了玩家§e'.$player->getName().'§a职业基因觉醒效果:§c爆灭');
					}
					$player->level->addParticle(new HugeExplodeSeedParticle($player));
					$player->level->addSound(new ExplodeSound($player));
					$player->PassiveCooling = time()+(300-$player->getGeNeAwakening()*50);
				}elseif($player->getRole()=='法师' and $player->PassiveCooling<time() and $player->getGeNeAwakening()>0){
					$damager->addEffect(Effect::getEffect(15)->setDuration(20*$player->getGeNeAwakening()));
					$damager->addEffect(Effect::getEffect(9)->setDuration(20*$player->getGeNeAwakening()));
					$damager->addEffect(Effect::getEffect(16)->setDuration(20*$player->getGeNeAwakening()));
					$player->PassiveCooling = time()+(300-$player->getGeNeAwakening()*50);
					$damager->sendMessage('§l§a你受到了玩家§e'.$player->getName().'§a职业基因觉醒效果:§c暗灭');
				}
			}
		}
	}

	public function onMove(PlayerMoveEvent $event){
		$player=$event->getPlayer();
		$name=$player->getName();
		if(isset($this->playerConfig->get('爱心粒子',[])[strtolower($player->getName())]) and !$player->isSpectator())
			$player->getLevel()->addParticle(new HeartParticle($player));
		// if($player->getLevel()->getName()==='zc' and (int)$player->getX()==776 and (int)$player->getY()==5 and ((int)$player->getZ()==15 or (int)$player->getZ()==16) and (!isset($this->lastTimet[$player->getName()]) or $this->lastTimet[$player->getName()]+3<time())){
			// $motion = new Vector3(5, 1.8, 0);
			// $player->setMotion($motion);
			// $this->lastTimet[$name]=time();
		// }
	}
	public function playerBlockTouch(PlayerInteractEvent $event){
		$player=$event->getPlayer();
		/*
        if (!isset($player->aa))$player->aa = 40;
        else $player->aa++;
        if (in_array($player->aa, [4, 39, 12, 18, 44]))$player->aa++;
        $player->sendMessage($player->aa."a");
		*/
		$block=$event->getBlock();
		$name=$player->getName();
		if(isset($player->lastClicken) and $player->lastClicken===null){
			if($player->lastClicken!==$block){
				$player->lastClicken=null;
			}
		}
		/* 已废除
		if(isset($this->locks[$name])){
			$id=$event->getBlock()->getID();
			if($id!='54'){
				$player->sendMessage('§l§a[提示]§e已取消锁箱子操作!');
				$event->setCancelled();
				unset($this->locks[$name]);
				return;
			}
			if($event->isCancelled())return $player->sendMessage('§l§a[提示]§e你没有权限锁这个的箱子');
			if($this->locks[$name]=='lock'){
				if($block->lock($player))
					$player->sendMessage('§l§a[提示]§a锁箱子成功！');
				else
					$player->sendMessage('§l§a[提示]§c锁箱子失败，可能是这个箱子被锁了！');
				$event->setCancelled();
				if($this->getName()!=='Angel_XX')unset($this->locks[$name]);
				return;
			}else{
				if($block->unlock($player))
					$player->sendMessage('§l§a[提示]§a解锁箱子成功！');
				else
					$player->sendMessage('§l§a[提示]§c解锁箱子失败，可能是这个箱子没被锁了！');
				$event->setCancelled();
				if($this->getName()!=='Angel_XX')unset($this->locks[$name]);
				return;
			}
			return;
		}
		*/
		$evItem=$event->getItem();
		if($evItem->getID() == 0 && $block instanceof Stair and !in_array($player->getLevel()->getName(), ['pvp', 'boss', 'pve', 'f1', 'f2', 'f3', 'f4', 'f5', 't1', 't2', 't3', 't4', 't5', 't6', 't7', 't8', 't9'])){
			if($player->getLinkedEntity() instanceof Chair){
				$player->getLinkedEntity()->unlinkPlayer();
			}elseif($player->getLinkedEntity()!==null){
				return;
			}
			if(!$player->getPleasureEvent())new Chair($block, $player);
		}
		$id=$block->getID();
		if($event->getBlock() instanceof \pocketmine\block\SignPost){
			if(isset($this->sign[$name])){
				$sign = $player->getLevel()->getTile($block);
				if(!($sign instanceof Sign))return;
				$sign->setLineText((int)$this->sign[$player->getName()][0], $this->sign[$name][1]);
				unset($this->sign[$name]);
				$player->sendMessage('§l§a[提示]§a修改完成！');
				return;
			}else{
				$xyz=$block->getLevel()->getName().':'.$block->getX().':'.$block->getY().':'.$block->getZ();
				switch($xyz){
					case 'zc:742:4:78'://维修木牌
						if($player->getGamemode()===1)return $player->sendMessage('§l§a[提示]§c创造模式不能维修装备');
						$hand=$player->getInventory()->getItemInHand();
						$itemid=$hand->getID();
						if($itemid===0)return $player->sendMessage('§l§a[提示]§c你手上啥都没拿呢');
						if($hand->getMaxDurability()===false)return $player->sendMessage('§l§a[提示]§c这个物品不支持维修');
						if($hand->getDamage()===0)return $player->sendMessage('§l§a[提示]§c这个装备不需要维修');
						if(EconomyAPI::getInstance()->myMoney($player)<=5000)return $player->sendMessage('§l§a[提示]§c你没有足够的钱来维修装备');
						$hand->setDamage(0);
						$player->getInventory()->setItemInHand($hand);
						EconomyAPI::getInstance()->reduceMoney($player, 5000, '耐久维修');
						$player->sendMessage("§l§a[提示]§a维修完成，扣除5000金币");
					break;
					case 'zc:742:4:77'://更改绑定
						if($player->getGamemode()===1)return $player->sendMessage('§l§a[提示]§c创造模式不能维升级装备');
						$hand=$player->getInventory()->getItemInHand();
						if($hand instanceof Weapon or $hand instanceof Armor){
							$this->status[$player->getName()]='binding';
							$player->sendMessage("§l§a[提示]§a您想把这个装备转让给谁? 请在聊天框打出来~");
							$player->sendMessage("§l§a[提示]§a输入exit退出更改绑定！");
						}else{
							$player->sendMessage("§l§a[提示]§c更换绑定仅限武器和盔甲哦~");
						}
					break;
					case 'zc:742:4:76'://命名
						if($player->getGamemode()===1)return $player->sendMessage('§l§a[提示]§c请切换到生存模式来命名');
						$this->status[$player->getName()]='rename';
						$player->sendMessage('§l§a[提示]§e请在聊天框输入你要命名的名字！输入exit退出!');
					break;
					/*
					case 'zc:741:4:73'://飞行商店
						if($player->getFlyTime()===0){
							$player->sendMessage("§l§a[提示]§e请输入需要购买的天数：");
							$player->sendMessage("§l§a[提示]§e输入exit或0取消购买");
							$this->status[$name]='buyFly';
						}else $player->sendMessage("§l§a[提示]§c你已经拥有飞行权限");
					break;
					*/
					case 'zc:741:4:71'://修改称号商店
						if($player->getPrefix()=='无称号')
							return $player->sendMessage("§l§a[提示]§c请先购买称号");
						$c=Popup::getInstance()->cfg->get('聊天格式');
						$player->sendMessage("§l§a[提示]§e请输入新称号：");
						$player->sendMessage("§l§a[提示]§e在0-30字符，中文占3个字符 输入exit退出");
						$this->status[$name]='ModifyPreFix';
						break;
					case 'zc:741:4:72'://称号商店
						if($player->getPrefix()=='无称号'){
							$player->sendMessage("§l§a[提示]§e请输入称号：");
							$player->sendMessage("§l§a[提示]§e在0-30字符，中文占3个字符 输入exit退出");
							$this->status[$name]='buyPreFix';
						}else return $player->sendMessage("§l§a[提示]§c您已经购买了！");
					break;
					default:
						$sign = $event->getPlayer()->getLevel()->getTile($block);
						if(!($sign instanceof Sign))return;
						$sign = $sign->getText();
						if($sign[1] == 'plcmd' and isset($sign[2])){
							$command = $sign[2].$sign[3];
							if(in_array(explode(' ', $command)[0], ['me', '支付']))return;
							$this->getServer()->dispatchCommand($event->getPlayer(), $command);
						}
					break;
				}
			}
		}elseif(isset($this->sign[$name])){
			$player->sendMessage('§l§a[提示]§e已取消修改木牌!');
			$event->setCancelled();
			unset($this->sign[$name]);
			return;
		}
//		elseif(($id=='61' or $id=='62' or $id=='154' or $id=='125' or $id=='23' or $id=='117' or $id=='146' or $id=='130' or $id=='138') and $event->getPlayer()->isOp()){
//			if($event->isCancelled()==true or $name=='Angel_XX')return;
//			$player->sendCenterTip('§l§c管理员禁止使用这个东西！！');
//			$event->setCancelled();
//		}
		elseif($evItem->getID()=='351' and ($player->isOp() or $player->getGamemode()!=0))
			$event->setCancelled();
	}
	public function onPlayerQuit(PlayerQuitEvent $ev){
		$player=$ev->getPlayer();
		$this->endTutorial($player, 'quit');
		$name=$player->getName();
		unset($this->Teleport[$name],$this->sign[$name],$this->status[$name]);
		// unset($this->onlineTime[$name]);
		if(!($player->getLevel() instanceof Level))return;
		if($player->getLevel()->getName()==='create')$player->setGamemode(0,false,true);
		if($this->autoRestart and count($this->getServer()->getOnlinePlayers())===1 and current($this->getServer()->getOnlinePlayers())===$player){
		    /*
			$mUsage = Utils::getSystemMemoryUsage();
			if($mUsage[4]<1500){
				$this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"reboot"],[]), 1);
			}
		    */
		}
	}
	public function reboot(){
		$this->getServer()->shutdown();
	}
	public function SittingAndStanding(DataPacketReceiveEvent $ev){
		$player = $ev->getPlayer();
		$packet = $ev->getPacket();
		if(!$packet instanceof InteractPacket)return;
		if($packet->action === InteractPacket::ACTION_LEAVE_VEHICLE){
			if($player->getLinkedEntity() instanceof Chair){
				$player->getLinkedEntity()->unlinkPlayer();
			}
		}
	}
	 public function onDropItemEvent(PlayerDropItemEvent $event){
		 if($event->isCancelled()==true)return;
		 $player=$event->getPlayer();
		 if($this->notDrop){
			 $player->sendCenterTip('§l§a清理系统要清理掉落物了呢，这段时间不能丢弃物品哦');
			 $event->setCancelled(true);
		 }elseif(!$player->isA() or $player->onTeleport()){
			 $event->setCancelled(true);
		 }elseif(abs($player->lastDieTime-time())<5){
			 $player->sendCenterTip('§l§a死亡后5秒内无法丢弃物品哦~');
			 $event->setCancelled(true);
		 }else{
//			 if($event->getItem() instanceof \LTItem\LTItem){
//				 if(!isset($this->drop[$player->getName()]) or time()-$this->drop[$player->getName()]>30){
//					$player->sendCenterTip('§l§a丢去特殊物品请输入/drop');
//					return $event->setCancelled(true);
//				 }
//				$this->getServer()->getLogger()->addData($player, $event->getItem(), 'drop');
//			 }else{
				$this->getServer()->getLogger()->addData($player, $event->getItem(), 'drop');
			 //}
		 }
	 }
	public function PreLoginEvent(PlayerPreLoginEvent $event){
		$name=strtolower($event->getPlayer()->getName());
		if(!file_exists($this->getServer()->getDataPath().'players'.DIRECTORY_SEPARATOR.$name.'.dat')){
			$this->give[$name]=true;
		}
		unset($event,$name);
	}
	public function triggerTeleport(Player $player){
		$all=$this->config->get('传送方块');
		$blocks = $player->getBlocksAround();
		foreach($blocks as $block){
			if(($block instanceof Portal or $block instanceof EndGateway or $block instanceof EndPortal) and isset($all[$block->getLevel()->getName().':'.(int)$block->getX().':'.(int)$block->getY().':'.(int)$block->getZ()]) and ($level=$this->getServer()->getLevelByName($all[$block->getLevel()->getName().':'.(int)$block->getX().':'.(int)$block->getY().':'.(int)$block->getZ()])) instanceof Level){
				return $player->teleport($level->getSafeSpawn());
			}
		}
	}
	public function onPlaceEvent(BlockPlaceEvent $event){
		if($event->isCancelled()==true)return;
		$player=$event->getPlayer();
		$level=$player->getLevel()->getName();
		// if($block->getID()==90 or $block->getID()==209 or $block->getID()==119){
			// $all=$this->conf->get('传送方块');
			// $all[$block->getLevel()->getName().':'.$block->getX().':'.$block->getY().':'.$block->getZ()]=$this->target;
			// $this->conf->set('传送方块',$all);
			// $this->conf->save(false);
		// }
		if(!in_array($level, ['zy', 'mt', 'land', 'dp', 'jm']))return;
		if($player->getGamemode()===1){
			$block=$event->getBlock();
			$item=$event->getItem();
			if($item->getId()=='296' or $item->getId()=='361' or $item->getId()=='362' or $item->getId()=='392' or $item->getId()=='391' or $item->getId()=='372'){//这些方块 是会被影响掉落的
				$event->setCancelled();
				return;
			}
			//添加这个坐标的方块为不可掉落方块
            $block->getLevel()->getChunk($block->getX() >> 4,  $block->getZ() >> 4)->getSubChunk($block->getY() >> 4)->setBlockDrop($block->getX() & 0x0f, $block->getY() & Level::Y_MASK, $block->getZ() & 0x0f, false);
		}
	}

	public function BlockBreakCallback($id, $count){
		if(isset($this->events[$id])){
			$data=$this->events[$id];
			$pos=$data[1];
			$drops=$data[0];
			if($count>0){
				$player=$data[2];
//				if($player->getName()==='Angel_XX')
//					foreach($drops as $drop)
//						 $pos->level->dropItem($pos->add(0.5, 0.5, 0.5), $drop);
//				if(!$player->isOp() and $player->getGamemode()!=1 and !$player->closed)
				if($player->getGamemode()!=1 and !$player->closed)
					$player->sendCenterTip('§l§a[提示]§e该方块为op or 创造放置的！');
				$sql="delete FROM ".$pos->level->getName()."_b WHERE X='{$pos->getX()}' AND Y='{$pos->getY()}' AND Z='{$pos->getZ()}'";
				$this->getServer()->dataBase->pushService('1'.chr(2).$sql);
			}else{
				$player=$data[2];
				$item=$player->getItemInHand();
				if(LTItem::isSendToInvItem($item)){
					foreach($drops as $item){
						$player->getInventory()->addItem($item);
					}
				}elseif(LTItem::isAutoSellItem($item)){
					foreach($drops as $item){
						$money=LTMenu::getInstance()->getMoney($item, \LTMenu\Inventorys\SellInventory::$priceMenu);
						if($money<=0){
							$Ritem=$this->getServer()->getCraftingManager()->matchFurnaceRecipe($item);
							if($Ritem==null){
								$player->getInventory()->addItem($item);
								return;
							}
							$item=$Ritem->getResult();
							$money=LTMenu::getInstance()->getMoney($item, \LTMenu\Inventorys\SellInventory::$priceMenu);
							if($money>0){
								EconomyAPI::getInstance()->addMoney($player, $money, '售卖物品获得');
							}else{
								$player->getInventory()->addItem($item);
								return;
							}
						}else{
							EconomyAPI::getInstance()->addMoney($player, $money, '售卖物品获得');
						}
					}
				}else{
					foreach($drops as $drop){
						$pos->level->dropItem($pos->add(0.5, 0.5, 0.5), $drop);
					}
				}
			}
			unset($this->events[$id]);
		}
	}

    /**
     * @param BlockBreakEvent $event
     */
	public function onBreakEvent(BlockBreakEvent $event){
		if($event->isCancelled()==true)return;
		$block=$event->getBlock();
        if(in_array($block->getId(), [14, 15, 16, 73, 21, 129, 56])){
            $event->getPlayer()->getTask()->action('破坏方块', $block->getId());
        }
        $player = $event->getPlayer();
        $drops = $event->getDrops();
        $item=$player->getItemInHand();
        if(LTItem::isSendToInvItem($item)){
            foreach($drops as $item){
                $player->getInventory()->addItem($item);
            }
            $event->setDrops([]);
        }elseif(LTItem::isAutoSellItem($item)){
            foreach($drops as $item){
                $money=LTMenu::getInstance()->getMoney($item, \LTMenu\Inventorys\SellInventory::$priceMenu);
                if($money<=0){
                    $Ritem=$this->getServer()->getCraftingManager()->matchFurnaceRecipe($item);
                    if($Ritem==null){
                        $player->getInventory()->addItem($item);
                        return;
                    }
                    $item=$Ritem->getResult();
                    $money=LTMenu::getInstance()->getMoney($item, \LTMenu\Inventorys\SellInventory::$priceMenu);
                    if($money>0){
                        EconomyAPI::getInstance()->addMoney($player, $money, '售卖物品获得');
                    }else{
                        $player->getInventory()->addItem($item);
                        return;
                    }
                }else{
                    EconomyAPI::getInstance()->addMoney($player, $money, '售卖物品获得');
                }
            }
            $event->setDrops([]);
        }
        /* 已废除
        if(!in_array($levelName, ['zy', 'mt', 'land', 'dp', 'jm'])){//在level中 没写关闭tile的方法 所以这里把它关闭了
            if($player->getName()=='Angel_XX')return;
            $tile=$level->getTile($block);
            if($tile!==null)$tile->close();
            return;
        }
        $tile=$level->getTile($block);
        if($tile instanceof \pocketmine\tile\Chest){
            if(isset($tile->namedtag->lockName) and $tile->namedtag->lockName->getValue()!==strtolower($player->getName())){
                $player->sendMessage('§l§a[提示]§a这不是你的箱子哦！');
                $event->setCancelled(true);
                return;
            }
        }
        */
		/* 已废除
		$drops = $event->getDrops();
		if($tile instanceof \pocketmine\inventory\InventoryHolder and !$tile->closed){
			 if ($tile instanceof \pocketmine\tile\Chest) {
				$tile->unpair();
			}
			foreach ($tile->getInventory()->getContents() as $chestItem) {
				$drops[] = $chestItem;
			}
			$tile->close();
		}
		$eventID=self::$eventID++;
		$this->events[$eventID]=[$drops, $block->asPosition(), $player];
		$sql="SELECT * FROM ".$levelName."_b WHERE X='{$block->getX()}' AND Y='{$block->getY()}' AND Z='{$block->getZ()}' LIMIT 1";//查询方块记录 然后回调 $this->BlockBreakCallback()
		$this->getServer()->dataBase->pushService('0'.chr(1).chr(strlen($eventID)).$eventID .$sql);
		*/
	}
	public function onLevelChange(EntityLevelChangeEvent $event){
		if($event->isCancelled()==true)return;
		if(!($event->getEntity() instanceof Player))return;
		$player=$event->getEntity();
		if($player->getAPI()!==null)$player->getAPI()->update(0);
		$tname=$event->getTarget()->getName();
		$oname=$event->getOrigin()->getName();
		switch($oname){
			case 'zc':
				if(!$player->canFly){
					$player->setFlying(false);
					$player->setAllowFlight(false);
				}
			break;
			case 'boss':
			case 'pve':
				if($player->canFly){
					$player->setAllowFlight(true);
				}
			break;
			case 'pvp':
				if($player->canFly){
					$player->setAllowFlight(true);
				}
				Popup::getInstance()->updateNameTag($player);
			break;
			case 'create':
				$player->setGamemode(0,false,true);
			break;
			case 'login':
				$name = strtolower($player->getName());
				if(!isset(LTItem::getInstance()->command->cd[$name])){
					$player->getServer()->dataBase->pushService('0'.chr(7).chr(strlen($name)).$name."SELECT * FROM wed.items WHERE username='{$name}'");
				}
			break;
		}
		switch($tname){
			case 'zc':
				$player->setAllowFlight(true);
				$player->setFlying(true);
			break;
			case 'pvp':
				if($player->getAllowFlight()){
					$player->setAllowFlight(false);
					$player->setFlying(false);
				}
				if($player->getGamemode()==1){
					$player->setGamemode(0);
				}
				Popup::getInstance()->updateNameTag($player);
				$player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, false, Entity::DATA_TYPE_LONG, true);
			break;
			case 'boss':
			case 'pve':
				if($player->getAllowFlight()){
					$player->setAllowFlight(false);
					$player->setFlying(false);
				}
				if($player->getGamemode()==1 and $oname!='create'){
					$player->setGamemode(0);
				}
			break;
			case 'create':
				$player->setGamemode(1,false,true);
				$player->sendMessage('§l§e[注意]§c你可以输入§d/w zc§c返回主城');
			break;
		}
		// var_dump($this->conf->getNested('世界欢迎标题.'.$tname));
		if($this->config->getNested('世界欢迎标题.'.$tname)!==null){
			$this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask(function($player,$args){
				$player->sendTitle($args['主标题'],$args['副标题']);
			},[$player,$this->config->getNested('世界欢迎标题.'.$tname)]), 10);
		}
		$player->getTask()->action('更新世界',$tname);
		unset($player,$event);
	}
	public static function merge_spaces($string){//将字符串分割成数组 兼容中文
		return preg_replace("/\s(?=\s)/","\\1",$string);
	}
	public static function sendItem($name, $item){
		$sql="INSERT INTO wed.items(username, type, name, count, status) VALUES ('".strtolower($name)."','".$item[0]."','".$item[1]."','".$item[2]."', 0)";
		Server::getInstance()->dataBase->pushService('1'.chr(2).$sql);
	}
	public static function sendMessage($name, $mess){
		$sql="INSERT INTO wed.items(username, type, name, count, status) VALUES ('".strtolower($name)."','留言','".$mess."','1', 0)";
		Server::getInstance()->dataBase->pushService('1'.chr(2).$sql);
	}
	public static function offlineCommand($name, $command){
		$sql="INSERT INTO wed.items(username, type, name, count, status) VALUES ('".strtolower($name)."','命令','".$command."','1', 0)";
		Server::getInstance()->dataBase->pushService('1'.chr(2).$sql);
	}
	public static function addInvSlot(Player $player){//增加背包格数
		if($player->getInventory()->getSize()>80)return false;
		$inv=$player->getInventory()->getContents();
		$player->getInventory()->ClearAll();
		$player->getInventory()->setSize($player->getInventory()->getSize()+1);
		$player->setContents($inv);
		return true;
	}
	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
		switch(strtolower($cmd)){
		case 'prefix':
//			if($sender instanceof Player AND $sender->getName()!=='Angel_XX')return $sender->sendMessage("§l§a[提示]§c权限不够");
			if(count($args)<2)return $sender->sendMessage("§l§a[提示]§c用法:/修改称号 ID 称号");
			$target=$this->getServer()->getPlayer($args[0]);
			if($target){
				array_shift($args);
				$prefix=implode(" ", $args);
				$target->setPrefix(self::merge_spaces($prefix));
				Popup::getInstance()->updateNameTag($target);
				$sender->sendMessage("§l§a[提示]§a修改成功!");
			}else $sender->sendMessage("§l§a[提示]§c玩家不在线！");
			break;
		case '强制命令':
//			if($sender instanceof Player AND $sender->getName()!=='Angel_XX')return;
			if(count($args)<2)return $sender->sendMessage('§l§a[提示]§c用法/强制命令 玩家 命令');
			$player=$this->getServer()->getPlayer($args[0]);
			if(!$player)return $sender->sendMessage('§l§a[提示]§c目标不在线');
			array_shift($args);
			$this->getServer()->dispatchCommand($player, implode(" ", $args));
			$sender->sendMessage('§l§a[提示]§a执行成功');
		break;
		case 'setip':
//			if($sender instanceof Player AND $sender->getName()!=='Angel_XX')return;
			if(count($args)<1)return $sender->sendMessage('§l§a[提示]§c用法/setip ip');
			$this->getServer()->getRakLibInterface()->getInterface()->server->pushMainToThreadPacket('WEDsetip€'.$args[0]);
		break;
		case '强制说话':
//			if($sender instanceof Player AND $sender->getName()!=='Angel_XX')return;
			if(count($args)<2)return $sender->sendMessage('§l§a[提示]§c用法/强制说话 玩家 内容');
			$player=$this->getServer()->getPlayer($args[0]);
			if(!$player)return $sender->sendMessage('§l§a[提示]§c目标不在线');
			array_shift($args);
			Popup::getInstance()->onPlayerChat(new PlayerChatEvent($player,implode(" ", $args)));
			$sender->sendMessage('§l§a[提示]§a执行成功');
		break;
		/*
		case '锁':
			if($sender->isOp())return $sender->sendMessage('§l§a[提示]§cOP禁止此操作！');
			$this->locks[$sender->getName()]='lock';
			$sender->sendMessage('§l§a[提示]§a请点击要锁的箱子!');
		break;
		case '开':
			if($sender->isOp() and $sender->getName()!=='Angel_XX')return $sender->sendMessage('§l§a[提示]§cOP禁止此操作！');
			$this->locks[$sender->getName()]='unlock';
			$sender->sendMessage('§l§a[提示]§a请点击要解锁的箱子!');
		break;
		*/
		case 'bt'://上次传送点
			if($sender->lastPos!==null){
				if(!$sender->lastPos->getLevel()->isClosed()){
					if($sender->teleport($sender->lastPos,null,null,true))
					$sender->sendMessage('§l§a[提示]§a传送成功！');
				}else $sender->sendMessage('§l§a[提示]§a目标失效！');
			}else $sender->sendMessage('§l§a[提示]§c没有记录。');
		break;
		case 'sign':
			if(!($sender instanceof Player) or !isset($args[1]) or !is_numeric($args[0]))return $sender->sendMessage('§l§a[提示]§c用法:/sign 行数[1-4] 内容');
			array_shift($args);
			$this->sign[$sender->getName()]=[(int)$args[0], implode(" ", $args)];
			$sender->sendMessage('§l§a[提示]§a请点击木牌!');
		break;
		case 'addcount'://增加地皮数量
			if($sender instanceof Player or !isset($args[0]))return;
			$plot=MyPlot::getInstance();
			$name=strtolower($args[0]);
			$conf=$plot->additionalPlot;
			$conf->set($name,$conf->get($name,0)+1);
			$conf->save();
		break;
		case 'empty'://清空背包
			if(isset($args[0]) and $args[0]==strtolower($sender->getName())){
				$sender->getInventory()->ClearAll();
				$sender->sendMessage('§l§a[提示]§a清理完成');
			}else{
				$sender->sendMessage('§l§a[提示]§a确认清空背包请输入/empty '.strtolower($sender->getName()));
			}
		break;
//		case 'drop'://丢弃开关
//			$this->drop[$sender->getName()] = time();
//			$sender->sendMessage('§l§a[提示]§a开启丢弃特殊物品成功,你可以在30内任意丢弃物品！');
//			$sender->sendMessage('§l§c[警告]§e为什么输入这个指令才能丢弃？§c因为经典UI很容易不小心丢武器！§d这30秒最好原地静止等待结束!');
//		break;
		case 'setname'://设置手持物品名字
//			if($sender instanceof Player AND $sender->getName()!=='Angel_XX')return;
			if(count($args)<2)return $sender->sendMessage('§l§a[提示]§c用法/setname 玩家 名字');
			$player=$this->getServer()->getPlayer($args[0]);
			if($player){
				$hand=$player->getInventory()->getItemInHand();
				$hand->setCustomName($args[1]);
				$hand->setNamedTag($hand->getNamedTag());
				$player->getInventory()->setItemInHand($hand);
				return $sender->sendMessage('§l§a[提示]§a完成');
			}else return $sender->sendMessage('§l§a[提示]§c玩家不在线');
		break;
		case 'setmana'://设置手持物Mana
//			if($sender instanceof Player AND $sender->getName()!=='Angel_XX')return;
			if(count($args)<2)return $sender->sendMessage('§l§a[提示]§c用法/setMana 玩家 Mana');
			$player=$this->getServer()->getPlayer($args[0]);
			if($player){
				$hand=$player->getInventory()->getItemInHand();
				$mana = (int)$args[1];
				if ($hand instanceof Mana){
                    $hand->addMana($mana>$hand->getMaxMana()?$hand->getMaxMana():$mana);
                    $player->getInventory()->setItemInHand($hand);
                    return $sender->sendMessage('§l§a[提示]§a完成');
                }
                return $sender->sendMessage('§l§a[提示]§a清手持魔法物品！');
			}else return $sender->sendMessage('§l§a[提示]§c玩家不在线');
		break;
		case 'id'://手持ID
			return $sender->sendMessage(('§l§a[提示]§a手持:'.$sender->getInventory()->getItemInHand()->getId().':'.$sender->getInventory()->getItemInHand()->getDamage()));
		break;
		case 'sj'://新手教程
		// if(isset($args[0]))
			// if(isset($this->sj))
				// unset($this->sj);
			// else
				// $this->sj = 0;
		// else
			$this->startTutorial($sender);
		break;
		/*
		case 'up':
			foreach(scandir('/home/Server/players/') as $afile){
				$fname=explode('.',$afile);
				if($afile=='.' or $afile=='..' or is_dir(\pocketmine\PATH.'players/'.$afile) or end($fname)!=='dat')continue;
				$name = explode('.', $afile);
				unset($name[count($name)-1]);
				$name=implode('.', $name);
				$len = strlen($name);
				$valid = true;
				for($i = 0; $i < $len and $valid; ++$i){
					$c = ord($name{$i});
					if(($c >= ord("a") and $c <= ord("z")) or ($c >= ord("A") and $c <= ord("Z")) or ($c >= ord("0") and $c <= ord("9")) or $c === ord("_")){
						continue;
					}
					$valid = false;
					break;
				}
				if(!$valid)continue;
				$nbt=$this->server->getOfflinePlayerData($name);
				$Pets = new \pocketmine\nbt\tag\ListTag('Pets', []);
				$Pets->setTagType(\pocketmine\nbt\NBT::TAG_Compound);
				if(!isset($nbt->Pets) or !($nbt->Pets instanceof \pocketmine\nbt\tag\ListTag))continue;
				foreach($nbt->Pets as $petC){
					if($petC->type->getValue()=='羊驼'){
						$Pets[preg_replace('#§.#','',strtolower($petC->name->getValue()))] = new CompoundTag('', [
							'name' => new StringTag('name', $petC->name->getValue()),
							'petName' => new StringTag('petName', $petC->petName->getValue()),
							'type' => new StringTag('type', $petC->type->getValue()),
							'skin' => new StringTag('skin', $petC->skin->getValue()??''),
							'hunger' => new \pocketmine\nbt\tag\IntTag('hunger', $petC->hunger->getValue())
						]);
					}
				}
				$nbt->Pets=$Pets;
				$this->server->saveOfflinePlayerData($name, $nbt, false);
				echo $name.'更新完成'.PHP_EOL;
			}
		break;
		*/
		case 'clean':
			if(isset($args[0])){
				if($args[0]=='true')
					$this->clean(true);
				else
					$this->clean(false);
			}else
				$this->cleanTime(10);
		break;
		case 'admin':
//			if($sender instanceof Player AND $sender->getName()!='Angel_XX')return $sender->sendMessage('§l§a[提示]§c你没有这个权限！');
			if(!isset($args[0]))return $sender->sendMessage('§l§a[提示]§c未知命令！');
			switch(strtolower($args[0])){
				case 'ct'://清空这个世界的tile
					foreach($sender->getLevel()->getTiles() as $tile)$tile->close();
					return $sender->sendMessage('§l§a[提示]§c完成');
				break;
				case 'upc'://每日刷新
					$this->updateHeadCountConfig();
					\LTGrade\Main::getInstance()->updateTaskConfig();
					\LTEntity\Main::getInstance()->updateWeeksExpConfig();
					// foreach($this->server->getOnlinePlayers() as $player){
						// $this->onlineTime[$player->getName()] = time();
					// }
				break;
				case 'addm'://增加菜单使用权
					if(count($args)<3)return $sender->sendMessage('§l§a[提示]§c用法/admin addm id 菜单 天数');
					$player=$this->getServer()->getPlayer($args[1]);
					if($player){
						if($args[3]==0){
							$player->addMenu($args[2], 0);
						}else{
							$player->addMenu($args[2], time()+86400*$args[3]);
						}
					}else return $sender->sendMessage('§l§a[提示]§c玩家不在线');
				break;
				case 'cmode'://切换模式
					$mode = $this->config->get("模式", 0);
					$this->config->set("模式", $mode==0?1:0);
					if($mode==0){
						foreach($this->getServer()->getOnlinePlayers() as $p){
							$p->setAllowFlight(true, true);
						}
						foreach($this->getServer()->getLevels() as $level){
							foreach($level->getEntities() as $e){
								if($e instanceof Pets)$e->close();
							}
						}	
					}else{
						foreach($this->getServer()->getOnlinePlayers() as $p){
							if(!$p->canFly)$p->setAllowFlight(false, true);
						}	
					}
					$this->config->save();
					$sender->sendMessage(('§l§a[提示]§a成功更改模式!'));
				break;
				case 'ais'://为目标增加背包空间
					if(count($args)<2)return $sender->sendMessage('§l§a[提示]§c用法/admin ais id');
					$player=$this->getServer()->getPlayer($args[1]);
					if($player){
						if($player->getGamemode()!==0)return $sender->sendMessage('§l§a[提示]§c目标不是生存！');
						if(!self::addInvSlot($player))
							$sender->sendMessage('§l§a[提示]§c目标背包已达最大值！');
					}else return $sender->sendMessage('§l§a[提示]§c玩家不在线');
				break;
				case 'amt'://添加移动粒子
					if(count($args)<2)return $sender->sendMessage('§l§a[提示]§c用法/admin amt id');
					$all=$this->playerConfig->get('爱心粒子',[]);
					if(isset($all[strtolower($args[1])])){
						unset($all[strtolower($args[1])]);
						$sender->sendMessage('§l§a[提示]§c成功删除！');
					}else{
						$all[strtolower($args[1])]=true;
						$sender->sendMessage('§l§a[提示]§c成功添加！');
					}
					$this->playerConfig->set('爱心粒子',$all);
				break;
				case 'setv'://设置最大视野
					if(count($args)<2)return $sender->sendMessage('§l§a[提示]§c用法/admin setv id 视野[4-14]');
					$all=$this->playerConfig->get('视野范围',[]);
					if($args[2]==4 and isset($all[strtolower($args[1])])){
						unset($all[strtolower($args[1])]);
					}else{
						$all[strtolower($args[1])]=$args[2];
					}
					$sender->sendMessage('§l§a[提示]§c修改成功！');
					$this->playerConfig->set('视野范围',$all);
					$this->playerConfig->save();
				break;
				case 'seth'://设置人头
					if(count($args)<2)return $sender->sendMessage('§l§a[提示]§c用法/admin seth id 人头');
					$this->Head->set(strtolower($args[1]), $args[2]);
					$sender->sendMessage('§l§a[提示]§c修改成功！');
				break;
				case 'amj'://添加移动监测
					if(count($args)<2)return $sender->sendMessage('§l§a[提示]§c用法/admin amt id');
					$player=$this->getServer()->getPlayerExact($args[1]);
					if($player){
						$name=strtolower($player->getName());
						if($player->moveCheck===false){
							$player->moveCheck=true;
							$sender->sendMessage('§l§a[提示]§c成功添加！');
							$this->getServer()->dataBase->pushService('1'.chr(2)."update user set moveCheck=1 where name='{$name}'");
						}else{
							$player->moveCheck=false;
							$sender->sendMessage('§l§a[提示]§c成功删除！');
							$this->getServer()->dataBase->pushService('1'.chr(2)."update user set moveCheck=0 where name='{$name}'");
						}
					}else{
						$sender->sendMessage('§l§a[提示]§c玩家不在线');
					}
				break;
				case 'ast'://添加击杀特效
					if(count($args)<2)return $sender->sendMessage('§l§a[提示]§c用法/admin ast id');
					$all=$this->playerConfig->get('击杀特效',[]);
					if(isset($all[strtolower($args[1])])){
						unset($all[strtolower($args[1])]);
						$sender->sendMessage('§l§a[提示]§c成功删除！');
					}else{
						$all[strtolower($args[1])]=true;
						$sender->sendMessage('§l§a[提示]§c成功添加！');
					}
					$this->playerConfig->set('击杀特效',$all);
					$this->playerConfig->save();
				break;
				// case 'adt'://添加受伤特效
					// if(count($args)<2)return $sender->sendMessage('§l§a[提示]§c用法/admin adt id');
					// $all=$this->conf->get('受伤爆炸',[]);
					// if(isset($all[strtolower($args[1])])){
						// unset($all[strtolower($args[1])]);
						// $sender->sendMessage('§l§a[提示]§c成功删除！');
					// }else{
						// $all[strtolower($args[1])]=true;
						// $sender->sendMessage('§l§a[提示]§c成功添加！');
					// }
					// $this->conf->set('受伤爆炸',$all);
					// $this->conf->save();
				// break;
				case 'at'://自动重启
					if($this->autoRestart){
						$this->autoRestart=false;
						$this->config->set('自动重启',false);
						$sender->sendMessage('§l§a[提示]§a关闭自动重启');
					}else{
						$this->autoRestart=true;
						$this->config->set('自动重启',true);
						$sender->sendMessage('§l§a[提示]§a开启自动重启');
					}
				break;
				case 'alu'://刷新无用世界
					if(self::$allLevelUpdate){
						self::$allLevelUpdate=false;
						$this->config->set('刷新全部世界开关',false);
						$sender->sendMessage('§l§a[提示]§a刷新生存世界');
					}else{
						self::$allLevelUpdate=true;
						$this->config->set('刷新全部世界开关',true);
						$sender->sendMessage('§l§a[提示]§a刷新全部世界');
					}
				break;
				case 'tpall'://将所有人拉倒身边
					foreach($this->getServer()->getOnlinePlayers() as $p){
						if($p==$sender)continue;
						$p->teleport($sender);
					}
				break;
				case 'reloadf'://重载悬浮字
					$this->closeFloatingTexts();
					$this->config->reload();
					$this->initFloatingText();
				break;
				case 'reload'://重载配置文件
					$this->config->reload();
					$this->playerConfig->reload();
				break;
				/*
				case 'setop'://设置op
					if(count($args)<3){
						$sender->sendMessage('§l§a[提示]§c未知命令！');
						unset($sender,$cmd,$label,$args);
						return;
					}
					 $op=strtolower($args[1]);
					if($this->ops->exists(strtolower($args[1]))){
						$player=$this->getServer()->getPlayer($args[1]);
						if($player){
							EconomyAPI::getInstance()->reduceMoney($player, EconomyAPI::getInstance()->myMoney($player->getName()), 'op到期回收');
							$player->setGamemode(0);
							$player->getInventory()->ClearAll();
							$player->getEnderChestInventory()->ClearAll();
							$this->getServer()->removeOp($player->getName());
							$player->setFlyTime(0);
							$sender->sendMessage('§l§a[提示]§c你不再是OP');
							$this->ops->remove(strtolower($args[1]));
							$this->ops->save();
						}else{
							$this->ops->set(strtolower($args[1]),1);
							$this->ops->save();
							$sender->sendMessage('§l§a[提示]§a成功将玩家'.$args[1].'移除');
						}
					}else{
						if($args[2]==0){
							$this->ops->set($op, 0);
						}else{
							$this->ops->set($op, strtotime("+".$args[2]." day"));
						}
						$this->ops->save();
						$this->getServer()->addOp($op);
						$sender->sendMessage('§l§a[提示]§a成功将玩家'.$args[1].'添加至名单');
					}
				break;
				*/
				default:
					return $sender->sendMessage('§l§a[提示]§c未知命令！');
				break;
			}
			break;
		}
		unset($sender,$cmd,$label,$args,$op,$command,$name);
	}
	public function onPlayerJoin(PlayerJoinEvent $event){
		$player=$event->getPlayer();
		$name=strtolower($player->getName());
		unset($this->quit[$name]);
		// if(time() > 1579860000 and time()<1579881599){
			// if(!$this->r->exists($name) or !isset($this->r->get($name, [])[date("d")])){
				// $this->onlineTime[$player->getName()]=time();
			// }
		// }
		if(isset($this->give[$name])){
			$inventory = $player->getInventory();
			$inventory->addItem(LTItem::getInstance()->createMaterial('§a点击地面打开菜单'));
			$inventory->addItem(Item::get(279, 0, 1));
			$inventory->addItem(Item::get(278, 0, 1));
			$inventory->addItem(Item::get(297, 0, 64));
			unset($this->give[$name]);
		}
		/*
		if($this->ops->exists($name)){
			if($this->ops->get($name)===0){
				$this->getServer()->addOp($name);
				goto thisEnd;
				}
			if($this->ops->get($name)<=time()){
				$this->getServer()->removeOp($name);
				$this->ops->remove($name);
				$this->ops->save();
				EconomyAPI::getInstance()->reduceMoney($player, EconomyAPI::getInstance()->myMoney($player->getName()), 'op到期回收');
				$player->setGamemode(0);
				$player->getInventory()->ClearAll();
				$player->getEnderChestInventory()->ClearAll();
				$player->setFlyTime(0);
				$player->sendMessage('§c你的op已到期！', true);
			}else{
				$this->getServer()->addOp($name);
				goto thisEnd;
			}
		}else{
			$this->getServer()->removeOp($name);
		}
		$this->ops->remove($name);
		thisEnd:
		*/
		// $player->setOp(true);
        $player->sendMessage("§l§e欢迎来到§dMana§e至上主义的世界！",true);
        $player->sendMessage("§l§e硬核锻造系统已更新，享受钢铁制炼的乐趣吧~",true);
		unset($event,$player,$name,$inventory);
	}
	public function cleanTime($i){
		$this->notDrop=true;
		Server::getInstance()->broadcastTip('§o§e在§b'.$i.'秒§e后清除所有掉落物和其他生物啦！',Server::getInstance()->getOnlinePlayers(),true);
		if($i==5){
			$this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"clean"],[false]),100);
			unset($i);
			return;
		}else{
			switch($i){
			case 20:
				$t=10;
			break;
			case 10:
				$t=5;
			break;
			}
		}
		$this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"cleanTime"],[$t]),$t*20);
		unset($i,$t);
	}
	 public function clean($all=false){
		 $dropItem = 0;
		 $Creature = 0;
		 foreach($this->getServer()->getLevels() as $level){
			 foreach($level->getEntities() as $entity){
				 if(!($entity instanceof Creature) and !($entity instanceof Painting)){
					$entity->close();
					$dropItem++;
				 }else{
					 if($entity instanceof Player AND $entity->closed){
						 $chunk=$level->getChunk($entity->x >> 4, $entity->z >> 4);
						 $chunk->removeEntity($entity);
						 $level->removeEntity($entity);
						 continue;
					 }
					 if($all){
						 if($entity instanceof Creature and !($entity instanceof Human)){
							$entity->close();
							$Creature++;
						 }
					 }
				 }
			 }
		 }
		$this->notDrop=false;
		Server::getInstance()->broadcastMessage('§o§d[§3服务器清理系统§d] §a扫掉了§d'.$dropItem.'§a个掉落物');
		Server::getInstance()->broadcastMessage('§o§d[§3服务器清理系统§d] §a共杀掉§d'.$Creature.'§a个生物');
	 }
	public function onDeathEvent(PlayerDeathEvent $event)
	{
		$entity = $event->getEntity();
		if($entity->getLinkedEntity() instanceof Chair){
			$entity->getLinkedEntity()->unlinkPlayer();
		}
		$cause = $entity->getLastDamageCause();
		if($cause === null)return;
		if($cause instanceof EntityDamageByEntityEvent) {
			$damager = $cause->getDamager();
			if($damager instanceof Player and $entity instanceof Player) {
				if($entity->getLevel()->getName()=='pvp'){
					$damager->getTask()->action('击杀玩家', $entity);
					$damagerCount=$this->getHeadCount($damager->getName());
					$entityCount=$this->getHeadCount($entity->getName());
					if($damagerCount>0 and $entityCount>0 and $damager->getName()!==$entity->getName()){
						$this->Head->set(strtolower($damager->getName()), ($damagerCount+1));
						$this->Head->set(strtolower($entity->getName()), ($entityCount-1));
						$damager->sendCenterTip(('§l§a你击杀了'.$entity->getName().'获得了一个人头, 当前人头数'.($damagerCount+1)));
						$damager->getTask()->action('获得人头', 1);
						LTSociety::getInstance()->addHead($damager);//增加公会积分
						// $entity->sendCenterTip(('§l§c你被'.$damager->getName().'击杀了失去了一个人头, 当前人头数'.($entityCount-1)));
						Popup::getInstance()->updateNameTag($entity);
						Popup::getInstance()->updateNameTag($damager);
					}
					$this->updateRanking();
				}
                if($entity->getName()=='Angel_XX'){
                    $damager->newProgress('惊天动地', '', 'challenge');
                }
				if(isset($this->playerConfig->get('击杀特效',[])[strtolower($damager->getName())])){
					self::dieParticle($entity->getX(), $entity->getY(), $entity->getZ(), $entity->getLevel());
				}
			}
		}
	}
	public function PlayerRunCommand(PlayerCommandPreprocessEvent $event){
		$player=$event->getPlayer();
		$name=strtolower($player->getName());
		$Yname=$player->getName();
		$ms=$event->getMessage();
		if($ms=='exit'){
			$event->setCancelled();
			if($this->endTutorial($player)){
				$player->teleport($this->getServer()->getDefaultLevel()->getSafeSpawn(), null, null, false);
				$player->addTitle("");
			}
			if(isset($this->status[$Yname])){
				unset($this->status[$Yname]);
				return $player->sendMessage("§l§a[提示]§e你退出了！");
			}
		}
		// $this->target=$ms;
		$this->getServer()->getLogger()->addData($player,$ms);
		if(isset($this->status[$Yname])){
			$event->setCancelled();
			switch($this->status[$Yname]){
				case 'rename':
					$hand=$player->getInventory()->getItemInHand();
					if(\LTItem\Main::isThisItem($hand))return $player->sendMessage("§l§a[提示]§c特殊物品不能命名");
					if(strlen(preg_replace('#§.#','',$ms))>=30)return $player->sendMessage("§l§a[提示]§c长度不能超过30!");
					if(EconomyAPI::getInstance()->myMoney($player)<10000)return $player->sendMessage("§l§a[提示]§c你没有10000橙币！!");
					$hand->setCustomName($ms);
					$hand->setNamedTag($hand->getNamedTag());
					$player->getInventory()->setItemInHand($hand);
					EconomyAPI::getInstance()->reduceMoney($player, 10000, '物品修改名字');
					$player->sendMessage("§l§a[提示]§a命名完成，扣除10000橙币！");
					unset($this->status[$Yname]);
				break;
				/*
				case 'buyFly':
					$ms=(int)$ms;
					if($ms==0){
						unset($this->status[$Yname]);
						return $player->sendMessage("§l§a[提示]§a成功取消购买");
					}
					if($ms<1)return $player->sendMessage("§l§a[提示]§c请输入整数 并且大于等于1");
					$NeedMoney=100000*$ms;
					if(EconomyAPI::getInstance()->myMoney($player)<$NeedMoney)return $player->sendMessage("§l§a[提示]§c你没有足够的钱来飞行");
					EconomyAPI::getInstance()->reduceMoney($player, $NeedMoney, '购买飞行');
					$player->setFlyTime(strtotime("+".$ms." day"));
					$player->checkFly();
					$player->sendMessage("§l§a[提示]§a成功花费{$NeedMoney}金币购买了{$ms}天的飞行");
					unset($this->status[$Yname]);
				break;
				*/
				case 'shelves':
					if($player->isOp() or $player->getGamemode()==1)return $player->sendMessage("§l§a[提示]§cOP或创造不能上架！");
					$hand=$player->getInventory()->getItemInHand();
					if($hand instanceof \LTItem\LTItem and !($hand instanceof \LTItem\Mana\Mana)){
						if($hand instanceof Weapon){
							if(!$hand->canUse($player))return $player->sendMessage("§l§a[提示]§c这个物品不是你的！");
							if($hand->getAttribute('全员可用', true)==false){
								if(Open::getNumber($player, ['材料','武器解绑水晶',1])){
									Open::removeItem($player, ['材料','武器解绑水晶',1]);
									if($hand->getWlevel()=='定制')return $player->sendMessage("§l§a[提示]§a定制武器不可以上架交易所哦~");
								}else{
									return $player->sendMessage("§l§a[提示]§c你背包里没有武器解绑水晶哦~");
								}
							}
						}elseif($hand instanceof Armor){
							if(!$hand->canUse($player))return $player->sendMessage("§l§a[提示]§c这个物品不是你的！");
							if($hand->getAttribute('全员可用', true)==false){
								if(Open::getNumber($player, ['材料','盔甲解绑水晶',1])){
									Open::removeItem($player, ['材料','盔甲解绑水晶',1]);
								}else{
									return $player->sendMessage("§l§a[提示]§c你背包里没有盔甲解绑水晶哦~");
								}
							}
						}
						if($hand instanceof Material and $hand->getLTName()==='§a点击地面打开菜单')return $player->sendMessage("§l§a[提示]§c这个材料禁止上架！~");
						$Exchange=LTMenu::getInstance()->getExchange();
						if(($re=$Exchange->addGood($hand, $player, $ms))===true){
							$player->getInventory()->setItemInHand(Item::get(0));
							$player->sendMessage("§l§a[提示]§c上架成功!");
						}else{
							$player->sendMessage("§l§a[提示]§c上架失败".$re);
						}
					}else{
						return $player->sendMessage("§l§a[提示]§c你只能出售类型:[近战 远程 通用 材料 盔甲 饰品]!");
					}
					unset($this->status[$Yname]);
				break;
				case 'buyPreFix':
					if(EconomyAPI::getInstance()->myMoney($player)<10000)return $player->sendMessage("§l§a[提示]§c你没有足够的钱来称号");
					if(strlen(preg_replace('#§.#','',$ms))>=30)return $player->sendMessage("§l§a[提示]§c长度不能超过30!");
					$player->setPrefix(self::merge_spaces($ms));
					EconomyAPI::getInstance()->reduceMoney($player, 10000, '购买称号');
					$player->sendMessage("§l§a[提示]§a购买成功");
					Popup::getInstance()->updateNameTag($player);
					unset($this->status[$Yname]);
				break;
				case 'binding':
					$hand=$player->getInventory()->getItemInHand();
					if(!($hand instanceof \LTItem\LTItem) or $hand instanceof Material or $hand instanceof BaseOrnaments)return $player->sendMessage("§l§a[提示]§c请手持武器或者盔甲！");
					if(!$hand->canUse($player) and !$player->isOp()){
						unset($this->status[$Yname]);
						return $player->sendMessage("§l§a[提示]§c这个物品不是你的！");
					}
					if($this->getServer()->getPlayerExact($ms)===null){
						unset($this->status[$Yname]);
						return $player->sendMessage("§l§a[提示]§a为了准确性请确认目标玩家在线!");
					}
					if($hand instanceof Weapon){
						if(Open::getNumber($player, ['材料','武器解绑水晶',1])){
							Open::removeItem($player, ['材料','武器解绑水晶',1]);
							if($hand->getWlevel()=='定制'){
								unset($this->status[$Yname]);
								return $player->sendMessage("§l§a[提示]§a定制武器不可以解绑哦~");
							}
							$hand=$hand->setBinding($ms);
							$player->getInventory()->setItemInHand($hand);
							$player->sendMessage("§l§a[提示]§a更换绑定成功~");
						}else{
							$player->sendMessage("§l§a[提示]§c你背包里没有武器解绑水晶哦~");
						}
					}elseif($hand instanceof Armor){
						if(Open::getNumber($player, ['材料','盔甲解绑水晶',1])){
							Open::removeItem($player, ['材料','盔甲解绑水晶',1]);
							$hand=$hand->setBinding($ms);
							$player->getInventory()->setItemInHand($hand);
							$player->sendMessage("§l§a[提示]§a更换绑定成功~");
						}else{
							$player->sendMessage("§l§a[提示]§c你背包里没有盔甲解绑水晶哦~");
						}
					}else{
						$player->sendMessage("§l§a[提示]§c更换绑定仅限武器和盔甲哦~");
					}
					unset($this->status[$Yname]);
				break;
				case 'ModifyPreFix':
					$money=EconomyAPI::getInstance()->myMoney($player);
					if(3000>$money)return $player->sendMessage("§l§a[提示]§c你没有足够的钱来修改称号");
					if(strlen(preg_replace('#§.#','',$ms))>=30)return $player->sendMessage("§l§a[提示]§c长度不能超过30!");
					$player->setPrefix(self::merge_spaces($ms));
					EconomyAPI::getInstance()->reduceMoney($player, 3000, '修改称号');
					$player->sendMessage("§l§a[提示]§a修改成功");
					Popup::getInstance()->updateNameTag($player);
					unset($this->status[$Yname]);
				break;
			}
		}
	}
	public static function isCrucialItem($item){
		return $item instanceof \LTItem\LTItem;
	}
	public static function isFairLevel($levelName){
		return in_array($levelName, ['f1', 'f2', 'f3', 'f4', 'f5', 'f6', 'f7', 'f8', 'f9', 't1', 't2', 't3', 't4', 't5', 't6', 't7', 't8', 't9', 'boss', 'pvp', 's1', 's2', 's3']);
	}
}