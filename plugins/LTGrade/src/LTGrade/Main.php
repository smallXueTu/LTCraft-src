<?php
namespace LTGrade;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\level\sound\ExplodeSound;
use pocketmine\level\sound\ExpPickupSound;
use pocketmine\level\particle\LavaDripParticle;
use pocketmine\level\particle\PortalParticle;
use pocketmine\math\Vector3;
use LTPopup\Popup;
use LTItem\Main as LTItem;
use LTMenu\Open;
use pocketmine\entity\Attribute;
use pocketmine\scheduler\CallbackTask;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
class Main extends PluginBase{
	public static $instance=null;
	public static function getInstance(){
		return self::$instance;
	}
	public static $type=[];
	public static $entity=[];
	public static $PlayerTask=[];
	public static $Number=0;
	public static $dir;
	public $conf;
	public $PlayerTaskConf;
	public function onDisable(){
		$this->PlayerTaskConf->save(false);
	}
	public function onEnable(){
		PlayerTask::init();
		self::$dir=$this->getDataFolder().'players/';
		self::$instance=$this;
		// $this->sql=&$this->server->conn;
		/*
		foreach(scandir(\pocketmine\DATA .'players/') as $afile){
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
			if(strlen($name)<3)continue;
			$nbt=$this->server->getOfflinePlayerData($name);
			if(!($nbt instanceof CompoundTag))continue;
			if(isset($nbt->MainTask) and $nbt->MainTask->getValue()>0 and $nbt->MainTask->getValue()<14)$nbt->MainTask = new ShortTag('MainTask', $nbt->MainTask->getValue()+1);
			$nbt['AStatus'] = new ListTag('AStatus', []);
			$nbt->AStatus->setTagType(NBT::TAG_String);
			$nbt['AStatus']['购买地皮'] = new StringTag('', '购买地皮');
			$nbt['AStatus']['设置家'] = new StringTag('', '设置家');
		
			$this->server->saveOfflinePlayerData($name, $nbt, false);
			echo $name.'更新完成'.PHP_EOL;
		}
		*/
		$Listener=new EventListener($this);
		// $this->getServer()->getScheduler()->scheduleDelayedRepeatingTask(new CallbackTask([$this,"nextMessage"],[]),1200,1200);
		$this->getServer()->getPluginManager()->registerEvents($Listener,$this);
		@mkdir(self::$dir,0777,true);
		$this->conf=new Config($this->getDataFolder().'Config.yml',Config::YAML,[
			'毕业要求'=>[]
		]);
		$this->PlayerTaskConf=new Config($this->getDataFolder().'PlayerTask.yml',Config::YAML,[]);
	}
	// public function nextMessage(){
		// if(self::$Number>=count(PlayerTask::$Announcement)-1)self::$Number=0;
		// else
		// self::$Number++;
		// foreach(self::$entity as $entity)$entity->upDateTitleAndPercentage();
	// }
	public function updateTaskConfig(){
		$this->PlayerTaskConf->save(false);
		copy($this->PlayerTaskConf->getFile(), $this->getDataFolder().'DailyTask/'.date('Y-m-d', time()-43200).'.yml');
		$this->PlayerTaskConf->setAll([]);
		foreach($this->getServer()->getOnlinePlayers() as $player){
			if($player->getTask() instanceof PlayerTask){
				$player->getTask()->chechTask();
				$player->getTask()->updateTaskMessage();
			}
		}
	}
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if($command=='task'){
			$GTo=$sender->getGTo();
			if($GTo>=8)return $sender->sendMessage('§l§a[提示]§a你已经全部毕业了！');
			if($GTo<=0){
				$sender->setGTo(1);
				$GTo=1;
				$sender->sendMessage('§l§a[镇长]§a嗨，冒险者，新来的吗？我怎么没见过你?');
				// $sender->sendMessage('§l§e再次点击继续对话');
				// return;
				$sender->sendMessage('§l§a[镇长]§a好了 现在不是闲聊的时候。要证明自己就集齐以下装备吧！');
			}
			$needItem=$this->conf->getNested(('毕业要求.F'.$GTo));
			$err=[];
			foreach($needItem as $item){
				if(!Open::getNumber($sender,$item)){
					$err[]='找不到装备'.$item[0].':'.$item[1].'×'.$item[2].' 请检查手持查看绑定是否为自己！';
				}
			}
			if(count($err)<=0){
				$sender->setGTo($GTo+1);
				$sender->newProgress('Done Level '.$GTo, '', 'challenge');
				$sender->sendMessage('§l§a[提示]§a恭喜你,你满足F'.$GTo.'毕业要求 成功毕业！');
//				$this->getServer()->BroadCastMessage('§l§a[LTcraft全服公告]§a恭喜'.$sender->getName().'完成F'.$GTo.'毕业要求 成功毕业！');
				if($GTo==1){
					$sender->getTask()->setTask(9);
				}
				switch($GTo){
					case 1:
						$sender->sendMessage('§l§a[提示]§a恭喜你解锁了菜单新功能:§c材料兑换中心§a不用点击NPC打开了,可以直接打开所有副本的兑换菜单！');
					break;
					case 2:
						$sender->sendMessage('§l§a[提示]§a恭喜你解锁了菜单新功能:§c吞垃圾菜单§a当你有垃圾的时候可以在这里丢弃~');
						$sender->sendMessage('§l§a[提示]§a恭喜你解锁了菜单新功能:§c交易所§a你可以购买或者出售一些材料~');
					break;
					case 3:
						$sender->sendMessage('§l§a[提示]§a恭喜你解锁了菜单新功能:§c快捷末影箱§a您可以直接在背包打开末影箱了！');
					break;
					case 4:
						$sender->sendMessage('§l§a[提示]§a恭喜你解锁了菜单新功能:§c菜单随身箱子§a如果你是VIP你可以使用这个菜单！');
						$sender->sendMessage('§l§a[提示]§a恭喜你解锁了菜单新功能:§c装备操作菜单§a当你镶嵌材料的时候，可以在这个菜单完成镶嵌操作！');
					break;
					case 5:
						$sender->sendMessage('§l§a[提示]§a恭喜你解锁了菜单新功能:§c饰品菜单§c当你有饰品的时候可以使用这个菜单！');
						$sender->sendMessage('§l§a[提示]§a恭喜你解锁了菜单新功能:§c武器熔炼坛§c暂时用不到');
						$sender->sendMessage('§l§a[提示]§a恭喜你解锁了菜单新功能:§c盔甲熔炼坛§c暂时用不到');
						$sender->sendMessage('§l§a[提示]§a恭喜你解锁了菜单新功能:§c碎片熔炼坛§c暂时用不到');
					break;
				}
				$sender->getTask()->action('毕业', ++$GTo);
			}else{
				$needMess='§e';
				foreach($needItem as $item){
					if($item[0]=='更多要求'){
						switch($item[1]){
							case '近战':
							case '通用':
							case '远程':
								if($item[2]=='*'){//名字
									if($item[3]=='*'){//类型
										if($item[4]=='*'){//等级大于等于
											$needMess.=PHP_EOL . $item[1].'类型武器×'.$item[5];
										}else{
											$needMess.=PHP_EOL . $item[1].'类型武器等级大于等于'.$item[4].'×'.$item[5];
										}
									}else{
										if($item[4]=='*'){//等级大于等于
											$needMess.=PHP_EOL . $item[1].'类型和'.$item[3].'品质武器×'.$item[5];
										}else{
											$needMess.=PHP_EOL . $item[1].'类型和'.$item[3].'品质武器等级大于等于'.$item[4].'×'.$item[5];
										}
									}
								}else{
									if($item[3]=='*'){//类型
										if($item[4]=='*'){//等级大于等于
											$needMess.=PHP_EOL . $item[1].'类型'.$item[2].'武器×'.$item[5];
										}else{
											$needMess.=PHP_EOL . $item[1].'类型'.$item[2].'的等级大于等于'.$item[4].'×'.$item[5];
										}
									}else{
										if($item[4]=='*'){//等级大于等于
											$needMess.=PHP_EOL . $item[1].'类型和'.$item[3].'品质武器×'.$item[5].'§d';
										}else{
											$needMess.=PHP_EOL . $item[1].'类型和'.$item[3].'品质武器和等级大于等于'.$item[4].'×'.$item[5];
										}
									}
								}
							break;
							case '盔甲':
								if($item[2]=='*'){//名字
									if($item[3]=='*'){//类型
										if($item[4]=='*'){//等级大于等于
											$needMess.=PHP_EOL . $item[1].'类型盔甲×'.$item[5];
										}else{
											$needMess.=PHP_EOL . $item[1].'类型盔甲等级大于等于'.$item[4].'×'.$item[5];
										}
									}else{
										if($item[4]=='*'){//等级大于等于
											$needMess.=PHP_EOL . $item[1].'类型和'.$item[3].'品质盔甲×'.$item[5];
										}else{
											$needMess.=PHP_EOL . $item[1].'类型和'.$item[3].'品质盔甲等级大于等于'.$item[4].'×'.$item[5];
										}
									}
								}else{
									if($item[3]=='*'){//类型
										if($item[4]=='*'){//等级大于等于
											$needMess.=PHP_EOL . $item[1].'类型'.$item[2].'盔甲×'.$item[5];
										}else{
											$needMess.=PHP_EOL . $item[1].'类型'.$item[2].'的等级大于等于'.$item[4].'×'.$item[5];
										}
									}else{
										if($item[4]=='*'){//等级大于等于
											$needMess.=PHP_EOL . $item[1].'类型和'.$item[3].'品质盔甲×'.$item[5];
										}else{
											$needMess.=PHP_EOL . $item[1].'类型和'.$item[3].'品质盔甲和等级大于等于'.$item[4].'×'.$item[5];
										}
									}
								}
							break;
						}
					}else $needMess.=PHP_EOL . $item[0].'类型'.$item[1].'×'.$item[2];
				}
				foreach($err as $info){
					$sender->sendMessage('§l§d'.$info);
				}
				$sender->sendMessage(('§l§a[提示]§c完成F'.$GTo.'毕业要求 你需要集齐一下物品:§d'.$needMess));
				$sender->sendMessage('§l§e[注意]§d请保证自己有权使用以上武器或盔甲，手拿着物品看下面提示即可~');
			}
			return;
		}
		$name=strtolower($sender->getName());
		if(!isset($args[0]))return $sender->sendMessage('§l§a[提示]§c用法/grade me');
		switch($args[0]){
		case 'GT':
			if($sender->getMainTask()<14){
				if($sender->getGTo()==0){
					$sender->getTask()->setTask(9);
				}elseif($sender->getGTo()==1){
					$sender->getTask()->setTask(10);
				}elseif($sender->getGTo()==2){
					$sender->getTask()->setTask(11);
				}elseif($sender->getGTo()==3){
					$sender->getTask()->setTask(12);
				}elseif($sender->getGTo()==4){
					$sender->getTask()->setTask(13);
				}else{
					$sender->getTask()->setTask(14);
				}
				$sender->sendMessage('§l§a[提示]§a已校准主线任务!');
			}else{
				$sender->sendMessage('§l§a[提示]§a无需校准!');
			}
		break;
		case '赐予魔法棍':
			if($sender instanceof Player)return $sender->sendMessage('§l§a[提示]§c这个命令只有控制台可以使用！');
			if(!isset($args[1]))return $sender->sendMessage('§l§a[提示]§c用法:/grade 赐予魔法棍 ID');
			$target=$this->server->getPlayer($args[1]);
			if(!$target)return $sender->sendMessage('§l§a[提示]§c目标不在线！');
			if($target->getAStatusIsDone('获得魔法棍'))return;
			$item=LTItem::getInstance()->createMaterial('魔法棍');
			if($target->getInventory()->canAddItem($item)){
				$target->getInventory()->addItem($item);
				$target->addAStatus('获得魔法棍');
				$target->sendMessage('§l§a[提示]§l§a恭喜你获得魔法棍,已放入背包!');
			}
		break;
		case 'me':
			$sender->sendMessage('§a♚我的信息♚');
			$sender->sendMessage('§c当前等级:'.$sender->getGrade());
			$sender->sendMessage('§e经验:'.$sender->getExp().'/'.self::getUpExp($sender->getGrade()));
			$sender->sendMessage('§6职业:'.$sender->getRole());
			$sender->sendMessage('§d护甲:'.$sender->getArmorV().' 受到伤害减少('.(round($sender->getArmorV()/($sender->getArmorV()+300),2)*100).'%)');
			$v=($sender->getRole()==='刺客')?300/1.3:300;
			$sender->sendMessage('§3伤害加成:'.round($sender->getGrade()/$v, 2)*100 .'%');
		break;
		case 'getT':
			
		break;
		case '选择职业':
			if(!isset($args[1]))return $sender->sendMessage('§l§a[提示]§c用法:/grade 选择职业 [刺客 或 战士 或 法师 或 牧师]');
			if($sender->getRole()!=='未选择'){
				if(in_array($args[1], ['刺客', '战士', '法师', '牧师'])){
					if(!Open::getNumber($sender, ['材料', '职业水晶', 3]))return $sender->sendMessage('§l§a[提示]§c更换失败，你需要职业水晶3个！');
					Open::removeItem($sender, ['材料', '职业水晶', 3]);
				}
			}
			switch($args[1]){
				case '刺客':
					if($sender->getRole()=='刺客')return $sender->sendMessage('§l§a[提示]§c你当前已是这个职业!');
					if($sender->getRole()=='战士'){
						$sender->setMaxHealth($sender->getMaxHealth()-(int)$sender->getGrade());
						$sender->setHealth($sender->getHealth());
					}
					$sender->setRole('刺客');
					$sender->sendMessage('§l§a[提示]§a成功选择刺客为你的职业');
				break;
				case '战士':
					if($sender->getRole()=='战士')return $sender->sendMessage('§l§a[提示]§c你当前已是这个职业!');
					$sender->setRole('战士');
					$sender->setMaxHealth($sender->getMaxHealth()+(int)$sender->getGrade()/2);
					$sender->setHealth($sender->getHealth());
					$sender->sendMessage('§l§a[提示]§a成功选择战士为你的职业');
				break;
				case '法师':
					if($sender->getRole()=='法师')return $sender->sendMessage('§l§a[提示]§c你当前已是这个职业!');
					if($sender->getRole()=='战士'){
						$sender->setMaxHealth($sender->getMaxHealth()-(int)$sender->getGrade()/2);
						$sender->setHealth($sender->getHealth());
					}
					$sender->setRole('法师');
					$sender->sendMessage('§l§a[提示]§a成功选择法师为你的职业');
				break;
				case '牧师':
					if($sender->getRole()=='牧师')return $sender->sendMessage('§l§a[提示]§c你当前已是这个职业!');
					if($sender->getRole()=='战士'){
						$sender->setMaxHealth($sender->getMaxHealth()-(int)$sender->getGrade()/2);
						$sender->setHealth($sender->getHealth());
					}
					$sender->setRole('牧师');
					$sender->sendMessage('§l§a[提示]§a成功选择医疗为你的职业');
				break;
				default:
					return $sender->sendMessage('§l§a[提示]§c职业有 [刺客 或 战士 或 法师 或 牧师]');
			}
		break;
		case 'setTask':
		case 'setGTo':
		case 'setAH':
			if($sender instanceof Player AND $sender->getName()!='Angel_XX')return $sender->sendMessage('§l§a[提示]§c你没有这个权限！');
			if(!isset($args[2]))return $sender->sendMessage('§l§a[提示]§c用法:/grade '.$args[0].' 名字 值');
			$player=$this->getServer()->getPlayer($args[1]);
			if($player){
				switch($args[0]){
					case 'setGTo':
						$player->setGTo($args[2]);
					break;
					case 'setTask':
						$player->setMainTask($args[2]);
						$player->getAPI()->update(API::TASK);
					break;
					case 'setAH':
						$yh=$player->getAdditionalHealth();
						$player->setAdditionalHealth($args[2]);
						$player->setMaxHealth($player->getYMaxHealth()+$player->getAdditionalHealth()-$yh);
					break;
				}
				$sender->sendMessage('§l§a[提示]§a设置成功！');
			}else return $sender->sendMessage('§l§a[提示]§c目标不在线！');
		break;
		case 'getAH':
			if($sender instanceof Player AND $sender->getName()!='Angel_XX')return $sender->sendMessage('§l§a[提示]§c你没有这个权限！');
			if(!isset($args[1]))return $sender->sendMessage('§l§a[提示]§c用法:/grade getAH 名字');
			$player=$this->getServer()->getPlayer($args[1]);
			if(!$player)return $sender->sendMessage('§l§a[提示]§c目标不在线！');
			return $sender->sendMessage(('§l§a[提示]§a目标附加生命值为：'.$player->getAdditionalHealth()));
		break;
		case 'set':
			if($sender instanceof Player AND $sender->getName()!='Angel_XX')return $sender->sendMessage('§l§a[提示]§c你没有这个权限！');
			if(isset($args[2])){
				$player=$this->getServer()->getPlayer($args[1]);
				if($player){
					$player->getLevel()->addSound(new EndermanTeleportSound($player));
					if($player->getRole()==='战士')
						$player->setMaxHealth((int)$args[2]*2.5+($player->getYMaxHealth()-((int)$player->getGrade()*2.5)));
					else
						$player->setMaxHealth($args[2]*2+($player->getYMaxHealth()-($player->getGrade())));
					if($player->getHealth()>$player->getMaxHealth())$player->setHealth($player->getMaxHealth());
					else $player->setHealth($player->getHealth());
					
					$player->addArmorV(((int)$args[2]/2)-((int)$player->getGrade()/2));

					$player->setGrade($args[2]);
					$player->setExp(0);
					$player->getAPI()->update(API::POWER);
					Popup::getInstance()->updateNameTag($player);
					$sender->sendMessage('§l§a[提示]§a成功设置玩家'.$args[1].'的等级为:'.(int)$args[2]);
				}else return $sender->sendMessage('§l§a[提示]§c目标不在线！');
			}else return $sender->sendMessage('§l§a[提示]§c用法:/grade set 名字 等级');
			break;
		}
	}

    /**
     * @param int $grade
     * @return float|int
     */
	public static function getUpExp(int $grade){
        if($grade==0)return 8;
        if($grade<=10)return $grade*4+10;
        elseif($grade<=30)return $grade*30+30-$grade;
        elseif($grade<=100)return $grade*40+80-$grade;
        elseif($grade>100)return $grade*60+100-$grade;
        elseif($grade>150)return $grade*80+100-$grade;
        elseif($grade>200)return $grade*100+100-$grade;
        elseif($grade>250)return $grade*120+100-$grade;
	}
}