<?php
namespace LTPopup;
use LTItem\SpecialItems\Armor\ManaArmor;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\item\item;
use pocketmine\entity\entity;
use pocketmine\utils\TextFormat;
use pocketmine\event\entity\ItemSpawnEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageByEntity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\entity\Snowball;
use onebone\economyapi\event\money\ReduceMoneyEvent;
use pocketmine\inventory\Inventory;
use pocketmine\scheduler\CallbackTask;
use onebone\economyapi\EconomyAPI;
use LTCraft\Main as LTCraft;
use LTItem\Main as LTItem;
use LTLogin\Events;
use LTEntity\Main as LTEntity;
class Popup extends PluginBase implements Listener{
	private $SayManager=array();
	public $restart=false;
    private $cfg;
    private int $index = PHP_INT_MAX - 100;

    public function restart($send){
		if($this->restart!==false){
			if($send==0){
				foreach($this->getServer()->getOnlinePlayers() as $player){
					$player->sendPopup('§l§aL§dT§4c§et§ba§6f§9t§b温§8馨§5提§3示§e重§a启§c中...');
				}
				$this->getServer()->shutdown();
			}else{
				foreach($this->getServer()->getOnlinePlayers() as $player)
					$player->sendPopup('§l§aL§dT§4c§et§ba§6f§9t§b温§8馨§5提§3示§e服§a务§c器§7'.$send.'秒§d重§s启！');
			}
		}
	}
	public static function getSayManager($player){
		$name=$player->getName();
		return self::$instance->SayManager[$name]??false;
	}
	public static $instance=null;
	public static function getInstance(){
		return self::$instance;
	}
	public function onEnable(){
		self::$instance=$this;
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this,'update']), 20);//重复任务
		@mkdir($this->getDataFolder(),0777,true);
		$this->cfg=new Config($this->getDataFolder().'config.yml', Config::YAML, array(
			'聊天同步' => false,
			'聊天格式' => [],
			'头衔' => [],
		));
		$this->getServer()->getPluginManager()->registerEvents($this,$this); 
  	}

  	public function update(){
        LTEntity::getInstance()->onRefresh();
		LTItem::updateArmorColor();
		//这是为了解决Boss倒计时问题！
		if($this->restart!==false){
			$this->restart--;
			if($this->restart<=30){
				$this->restart($this->restart);
				return;
			}
		}
		$colorArray = ['§a','§b','§c','§d','§e','§6','§2','§3','§1','§4','§5'];
		$length = count($colorArray);
        $index = $this->index--;
        $top=$colorArray[++$index % $length].'§l☞'.$colorArray[++$index % $length].'L'.$colorArray[++$index % $length].'T'.$colorArray[++$index % $length].'C'.$colorArray[++$index % $length].'r'.$colorArray[++$index % $length].'a'.$colorArray[++$index % $length].'f'.$colorArray[++$index % $length].'t '.$colorArray[++$index % $length].'S'.$colorArray[++$index % $length].'e'.$colorArray[++$index % $length].'r'.$colorArray[++$index % $length].'v'.$colorArray[++$index % $length].'e'.$colorArray[++$index % $length].'r'.$colorArray[++$index % $length].'☜'.PHP_EOL;
		$QQ=$colorArray[++$index % $length].'★QQ群号'.$colorArray[++$index % $length].':862859409★';
		$time=date('Y-m-d').' '.date('H:i:s', time());
		foreach($this->getServer()->getOnlinePlayers() as $player){
			if($player->isOnline()){
				if($player->onTutorial()){
					$player->sendPopup('§l§a输入:exit 退出新手教程。'.PHP_EOL .'§l§c看完新手教程将会获得武器奖励哟！');
					continue;
				}
				if(isset($player->startTutorialTime)){
					if($player->startTutorialTime--<=0){
						unset($player->startTutorialTime);
						LTCraft::getInstance()->startTutorial($player);
						continue;
					}
					$player->sendPopup('§l§a新手教程将会在'.$player->startTutorialTime .'秒后开始'.PHP_EOL .'§l§c看完新手教程将会获得新手奖励！');
					continue;
				}
				if($player->getAPI()!==null)$player->getAPI()->upDateTitleAndPercentage($time);
				$name = $player->getName();
				 if(isset(LTCraft::getInstance()->onlineTime[$name]) and (time() - LTCraft::getInstance()->onlineTime[$name])>600){
					 LTCraft::getInstance()->giveR($player);
				 }
				if(!isset(Events::$status[strtolower($name)]) or Events::$status[strtolower($name)]!==true)continue;
				if(isset($this->cfg->get('变换头衔', [])[strtolower($name)]) and isset($this->cfg->get('头衔', [])[strtolower($name)])){
					$this->updateNameTag($player);
				}
				if($player->level->getName()==='boss' and isset(LTEntity::getInstance()->spawnTmp['boss'])){
					$tmp=&LTEntity::getInstance()->spawnTmp['boss'];
					if($player->freezeTime>0 or $player->vertigoTime>0){
						$additional='';
						if($player->freezeTime>0){
							$ftime=ceil($player->freezeTime/20);
							$additional='§d冰冻剩余时间:§3'.$ftime;
						}
						if($player->vertigoTime>0){
							$ftime=ceil($player->vertigoTime/20);
							$additional.='§3眩晕剩余时间:§3'.$ftime;
						}
					}elseif(($tip=LTItem::getInstance()->getTip($player))!=false)
						$additional='§e手持:'.$tip;
					else 
						$additional='';
					if($tmp['剩余时间']>0 and $tmp['数量']<=0){
						$mess='§d§l暗黑影龙还有'.$tmp['剩余时间'].'秒刷新'.PHP_EOL .'§e注意！Boss刷新此地图进入关闭状态'.PHP_EOL .'§c任何人不可进入！';
						if($tmp['剩余时间']<10)
							$player->getLevel()->addSound(new \pocketmine\level\sound\ButtonClickSound($player), [$player]);
					}elseif($tmp['数量']>0){
						$tip='§e当前Boss没有释放技能前兆！';
						if(isset($tmp['tip']) and $tmp['tip'][1]-microtime(true)>0)$tip=$tmp['tip'][0];
						$mess='§l§d回合目标:§3杀死Boss'.PHP_EOL .$tip;
					}
					$player->sendPopup($mess.PHP_EOL .$additional);
					continue;
				}
				$m = EconomyAPI::getInstance()->myMoney($name);
				$sy=floor($player->getY());
				$sx=floor($player->getX());
				$sz=floor($player->getZ());
				if($player->freezeTime>0 or $player->vertigoTime>0){
                    $additional='';
                    if($player->freezeTime>0){
                        $ftime=ceil($player->freezeTime/20);
                        $additional='§d冰冻剩余时间:§3'.$ftime;
                    }
                    if($player->vertigoTime>0){
                        $ftime=ceil($player->vertigoTime/20);
                        $additional.='§3眩晕剩余时间:§3'.$ftime;
                    }
				}elseif(($tip=LTItem::getInstance()->getTip($player))!=false){
					$additional='§e手持:'.$tip;
				}else
					$additional=$QQ;
				if (($i = $player->getBuff()->getOrnamentsInstallIndex("天翼族之冠"))!==false and $player->isSurvival()){
                    $additional.=PHP_EOL.$colorArray[++$index % $length]."飞行能量：".$colorArray[++$index % $length]. $player->getOrnamentsInventory()->getItem($i)->energy;
                }
                $allMana = 0;
                /** @var Player $entity */
                foreach ($player->getInventory()->getArmorContents() as $item){
                    if ($item instanceof ManaArmor and $item->getMana() > 0){
                        $allMana += $item->getMana();
                    }
                }
                if ($allMana >0){
                    $additional.=PHP_EOL.$colorArray[++$index % $length]."盔甲Mana：".$colorArray[++$index % $length].$allMana;
                }
				$player->sendPopup($top.$colorArray[++$index % $length].'◆橙币:'.$colorArray[++$index % $length].$m.' '.$colorArray[++$index % $length].$colorArray[++$index % $length].'◆坐标:'.$colorArray[++$index % $length].$sx.':'.$sy.':'.$sz.$colorArray[++$index % $length].' ◆Mana:'.$colorArray[++$index % $length].$player->getBuff()->getMana().' '.PHP_EOL .$additional);
			}
		}
	}
	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
		$name=$sender->getName();
		switch(strtolower($cmd->getName())){
		case '重启':
//			if($sender->getName()==='Angel_XX' or !($sender instanceof Player)){
				$this->restart=$args[0]??10;
				$sender->sendMessage('§a重启倒计时中');
				$this->getServer()->BroadCastMessage(('§l§a[LTcraft全服公告]§dLTCraft将会在'. ($args[0]??10) .'秒后进行重启，请大家做好准备'));
				return;
//			}
		break;
		case '取消重启':
//			if($sender->getName()==='Angel_XX' or !($sender instanceof Player)){
				$this->restart=false;
				$sender->sendMessage('§a已取消重启');
				$this->getServer()->BroadCastMessage('§l§a[LTcraft全服公告]§d重启已取消');
				return;
//			}
		break;
		case '禁言':
			if(count($args)<2)return $sender->sendMessage('§a[LTcraft温馨提示]§c用法/禁言 玩家 秒');
			$player=$this->getServer()->getPlayer($args[0]);
			if(!$player)return $sender->sendMessage('§l§a[提示]§c目标不在线');
			$this->SayManager[$player->getName()]->setShield((int)$args[1]);
			$player->sendMessage('§a[LTcraft温馨提示]§e你被禁言'.(int)$args[1].'秒');
			$sender->sendMessage('§a[LTcraft温馨提示]§a禁言成功');
		break;
		case '聊天同步':
			if($this->cfg->get('聊天同步')){
				$this->cfg->set('聊天同步', false);
				$sender->sendMessage('§a[LTcraft温馨提示]§a关闭聊天同步');
			}else{
				$this->cfg->set('聊天同步', true);
				$sender->sendMessage('§a[LTcraft温馨提示]§a开启聊天同步');
			}
			$this->cfg->save();
		break;
		case 'ui设置':
			if(!isset($args[0]))return $sender->sendMessage('§a[LTcraft温馨提示]§c用法:/ui设置 [右边显示切换 血量格式切换]');
			switch($args[0]){
				case '右边显示切换':
					if($sender->getAStatusIsDone('右边显示')){
						$sender->removeAStatus('右边显示');
					}else{
						$sender->addAStatus('右边显示');
					}
					// $sender->getAPI()->setShowHealth($sender->getAStatusIsDone());
					// $sender->getAttributeMap()->getAttribute(Attribute::HEALTH)->setMaxValue(1)->setValue(1, true);
					// $sender->getAttributeMap()->getAttribute(Attribute::HEALTH)->setMaxValue($sender->getMaxHealth())->setValue($sender->getHealth(), true);
					$sender->sendMessage('§a[LTcraft温馨提示]§a右边显示状态切换成功');
					if($sender->getAStatusIsDone('右边显示') and !$sender->getAStatusIsDone('血量格式')){
						$sender->getAPI()->removeThis();
					}else{
						$sender->getAPI()->restore();
					}
				break;
				case '血量格式切换':
					if($sender->getAStatusIsDone('血量格式')){
						$sender->removeAStatus('血量格式');
					}else{
						$sender->addAStatus('血量格式');
					}
					$sender->getAPI()->setShowHealth($sender->getAStatusIsDone('血量格式'));
					// $sender->getAttributeMap()->getAttribute(Attribute::HEALTH)->setMaxValue(1)->setValue(1, true);
					// $sender->getAttributeMap()->getAttribute(Attribute::HEALTH)->setMaxValue($sender->getMaxHealth())->setValue($sender->getHealth(), true);
					$sender->sendMessage('§a[LTcraft温馨提示]§a血量格式切换成功');
					if($sender->getAStatusIsDone('右边显示') and !$sender->getAStatusIsDone('血量格式')){
						$sender->getAPI()->removeThis();
					}else{
						$sender->getAPI()->restore();
					}
				break;
				case '设置称号':
//					if($sender->getName()!=='Angel_XX' and $sender instanceof Player)return;
					if(count($args)<3)return $sender->sendMessage('§a[LTcraft温馨提示]§c用法:/ui设置 设置称号 玩家ID 格式');
					if($args[2]==1 or $args[2]==3 or $args[2]==2 or $args[2]==0){
						$m=$this->cfg->get('聊天格式');
						if($args[2]==0)
							unset($m[strtolower($args[1])]);
						else
							$m[strtolower($args[1])]=$args[2];
						$this->cfg->set('聊天格式',$m);
						$this->cfg->save();
						$sender->sendMessage('§a[LTcraft温馨提示]§a设置成功');
					}else return $sender->sendMessage('§a[LTcraft温馨提示]§a未知格式');
				break;
				case '设置头衔':
//					if($sender->getName()!=='Angel_XX' and $sender instanceof Player)return;
					if(count($args)<3)return $sender->sendMessage('§a[LTcraft温馨提示]§c用法:/ui设置 头衔设置 玩家ID 头衔');
					$m=$this->cfg->get('头衔');
					$player=$this->getServer()->getPlayer($args[1]);
					if($args[2]=='false')
						unset($m[strtolower($args[1])]);
					else{
						$tn=strtolower($args[1]);
						unset($args[0],$args[1]);
						$m[$tn]=str_replace('\n','\n',implode(' ',$args));
					}
					$this->cfg->set('头衔',$m);
					$this->cfg->save();
					if($player)$this->updateNameTag($player);
					$sender->sendMessage('§a[LTcraft温馨提示]§a设置成功');
				break;
				case '变换头衔':
//					if($sender->getName()!=='Angel_XX' and $sender instanceof Player)return;
					if(count($args)<2)return $sender->sendMessage('§a[LTcraft温馨提示]§c用法:/ui设置 变换头衔 玩家ID');
					$m=$this->cfg->get('变换头衔');
					if(isset($m[strtolower($args[1])]))
						unset($m[strtolower($args[1])]);
					else{
						$m[strtolower($args[1])]=true;
					}
					$this->cfg->set('变换头衔',$m);
					$this->cfg->save(false);
					$sender->sendMessage('§a[LTcraft温馨提示]§a设置成功');
				break;
				default:
					return $sender->sendMessage('§a[LTcraft温馨示]§c用法:/ui设置 String');
				break;
			}
		}
	}
	public function onPlayerJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();
		$this->SayManager[$name]=new SayManager($player);
		$stylePlayers=$this->cfg->get('头衔');
		if(isset($stylePlayers[strtolower($name)]))return $player->setNameTag($stylePlayers[strtolower($name)]);
		$sex = $player->getGender();
		if($sex==='未选择')$sex='';
		$lv=$player->getGrade();
		$player->setNameTag('§dLV:'.$lv.'★§a'.$name.'♛'.$sex);
	}
	public function updateNameTag($player){
		$name = $player->getName();
		$stylePlayers=$this->cfg->get('头衔');
		if(isset($stylePlayers[strtolower($name)])){
			$title = $stylePlayers[strtolower($name)];
			if(isset($this->cfg->get('变换头衔', [])[strtolower($name)])){
                $colorArray = ['§a','§b','§c','§d','§e','§6','§2','§3','§1','§4','§5'];
				$title=preg_replace('#§.#','',$title);
				$n='';
				foreach (self::mb_str_split($title) as $c){
					$n.=$colorArray[mt_rand(0, 10)].$c;
				}
				$title = $n;
			}
			$player->setNameTag($title);
			return;
		}
		$sex = $player->getGender();
		if($sex==='未选择')$sex='';
		$lv=$player->getGrade();
		if($player->getLevel()->getName()==='pvp')
			$player->setNameTag('§dLV:'.$lv.'★§a'.$name.'♛'.$sex.PHP_EOL .'§3人头数量:'.LTCraft::getInstance()->getHeadCount($name));
		else
			$player->setNameTag('§dLV:'.$lv.'★§a'.$name.'♛'.$sex);
	}
	public function onPlayerChat(PlayerChatEvent $ev){
		$player=$ev->getPlayer();
		$name=$player->getName();
		if($ev->getCheck() and ($r=$this->SayManager[$name]->checkCanChat())!==true){
			if($r===false)return $player->sendMessage('§l§a[LT温馨提示]§c亲 你有刷屏行为哦,请注意发言速度,否者会被禁言噢！');
			return $player->sendMessage('§l§a[LT温馨提示]§c你已被禁言,'.$r.'秒后解除！');
		}
		$colorArray = ['§a','§b','§c','§d','§e','§6','§2','§3'];
		$m=trim(preg_replace('#§.#','',$ev->getMessage()));
		$stylePlayers=$this->cfg->get('聊天格式');
		$ch = $player->getPrefix();
		if($ch === '无称号')$ch = '§a萌新';
		if($this->cfg->get('聊天同步') and $ev->isAll()){
			$this->getServer()->getLogger()->addChatSync($name,$m);
		}
		if(isset($stylePlayers[strtolower($name)])){
				$a='';
				foreach (self::mb_str_split($m) as $c){
					$a.=$colorArray[mt_rand(0, 7)].$c;
				}
			if($stylePlayers[strtolower($name)]==1){
				
				if(isset($ev->say)){
					foreach($ev->say as $p)$p->sendMessage('§e['.$name.']§a'.$ch.'§r: '.$a);
				}else
					$this->getServer()->BroadCastMessage('§e['.$name.']§a'.$ch.'§r: '.$a);
				return;
			}elseif($stylePlayers[strtolower($name)]==2){
				$ch=trim(preg_replace('#§.#','',$ch));
				$n='';
				foreach (self::mb_str_split($ch) as $c){
					$n.=$colorArray[mt_rand(0, 7)].$c;
				}
				if(isset($ev->say)){
					foreach($ev->say as $p)$p->sendMessage('§e['.$name.']§a'.$n.'§r: '.$a);
				}else
					$this->getServer()->BroadCastMessage('§e['.$name.']§a'.$n.'§r: '.$a);
				return;
			}elseif($stylePlayers[strtolower($name)]==3){
				$ch=trim(preg_replace('#§.#','',$ch));
				$n='';
				foreach (self::mb_str_split($ch) as $c){
					$n.=$colorArray[mt_rand(0, 7)].$c;
				}
				if(isset($ev->say)){
					foreach($ev->say as $p)$p->sendMessage($n.'§r: '.$a);
				}else
					$this->getServer()->BroadCastMessage($n.'§r: '.$a);
				return;
			}
		}
		$love=$player->getLove($name);
		if($love=='未婚')
			$love='';
		else
			$love='§c♥情侣:§f'.$love;
		$sex = $player->getGender();
		if($sex==='未选择')$sex='';
		$guild=$player->getGuild();
		if($guild === '无公会')
			$guild = '';
		else
			$guild = '§e♚§6公会:§4'.$guild;
		$vip=$player->isVIP();
		if($vip==='未婚')
			$vip='';
		elseif($vip===1)
			$vip='★§eVIP1';
		elseif($vip===2)
			$vip='★§eVIP2';
		elseif($vip===3)
			$vip='★§eVIP3';
		$lv=$player->getGrade();
		if($lv==0)
			$lv='';
		else
			$lv='§4LV:'.$lv;
		if(strlen($sex.$guild.$love)<=0)
			$mess='';
		else
			$mess='§d[§d'.$sex.''.$guild.'§r'.$love.'§d]§9';
		$this->getServer()->BroadCastMessage($lv.$vip.$mess.'♚§3'.$ch.'§r◆§6'.$name.' §r§b>> §e'.$m, $ev->getRecipients());
	}
	public static function mb_str_split( $string ){
		return preg_split('/(?<!^)(?!$)/u', $string ); 
	}
}