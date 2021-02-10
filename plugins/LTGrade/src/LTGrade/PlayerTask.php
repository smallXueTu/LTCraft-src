<?php
namespace LTGrade;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\scheduler\CallbackTask;
use pocketmine\item\Item;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use onebone\economyapi\EconomyAPI;
use LTItem\Main as LTItem;
use LTGrade\Main as LTGrade;
use LTEntity\Main as LTEntity;
class PlayerTask{
    /** @var Player */
    public ?Player $player = null;
	public $task=0;
	public $DailyTask=[];
	/** @var Main */
    public ?Main $plugin;
	public static $MainTask=null;
	public static $AllTask=null;
	// public static $Announcement=null;
	public $message='';
	public static function init(){
		self::$MainTask=[
			'§a拿着下界之星,点击地面\n打开菜单,然后找到传送菜单\n任意传送一个世界,然后传送回来',//0
			'§a在主城找到传送暗影岛屿NPC\n传送过去,找到主线副本区\n传送到F1 异化豪宅',//1
			'§a击杀怪物,升级至5级！\n§c注意,打怪请务必走位！！\n不断绕圈远离怪物！\n防止被攻击!!',//2
			'§a击杀怪物,集齐材料木棍16个\n和材料木板32个,然后在\n§cF1出生点-§d材料兑换NPC\n§a中兑换破易桃木剑',//3
			'§a在副本1集齐64个腐肉然后\n击杀副本1Boss获得Level-1之证并\n升级破易桃木剑为桃木剑！',//4
			'§a继续 升级至10级\n如大于10级 升一级即可\n§e再次提醒下\n§2打怪注意远离怪物走位哦！',//5
			'§a打开菜单传送到资源世界\n§d输入/wild随机传送\n§d然后挖块橡木\n§e打开菜单§3双击绿宝石回收商店\n§6把橡木放进去后,双击雪球出售木头',//6
			'§a打开菜单传送到新手地皮\n§6并购买一个新手地皮\n§a流程:传送新手地皮世界\n§e点击寻找无人地皮NPC\n§2站在无人地皮上输入\n§2/p claim即可购买成功',//7
			'§a在菜单中找到我的地皮并传送过去！\n§d菜单-我的地皮-我的第一个地皮\n§c或输入/p h回到自己的地皮',//8
			'§a在暗影岛屿找到镇长！\n§d点击他查看F1毕业要求\n§c然后传送到F1集齐成功毕业',//9
			'§a在暗影岛屿找到镇长！\n§d点击他查看F2毕业要求\n§c然后传送到F2并集齐成功毕业',//10
			'§a在暗影岛屿找到镇长！\n§d点击他查看F3毕业要求\n§c然后传送到F3并集齐成功毕业',//11
			'§a在暗影岛屿找到镇长！\n§d点击他查看F4毕业要求\n§c然后传送到F4并集齐成功毕业',//12
			'§a继续完成毕业F5\n完成主线任务！',//13
			// '§a到T1集齐材料合成武器熔炼坛\n然后到T1兑换\n合成高级武器图纸\n成功锻造一个高级武器！',//14
		];
		self::$AllTask=[
			['卖掉100块橡木', 100],//0
			['挖10块钻石原矿', 10],//1
			['参与或杀死6只T系列BOSS', 6],//2
			['在PVP获得10次击杀', 10],//3
			['获得5个人头', 5],//4
			['击杀2次T系列团队BOSS', 2],//5
			['挖30块铁原矿', 30],//6
			['消耗500点饥饿值', 500],//7
			['开启2个神秘礼包', 2],//8
			['参与击杀一次末影龙', 1],//9
			['开启一个神秘奖励', 1],//10
			['击杀60只任意怪物', 60],//11
			['挖掉50块煤矿', 50],//12
		];
		// self::$Announcement=[
			// '§d商城请访问:\n§awww.ltcraft.cn/shop\n§a随身自定义药水,PVE,PVP武器\n§2雷击,吸血,自定义攻击力,\n§6反伤,闪避,自定义击杀提示\n§dVIP+等',
		// ];
	}
	public function __construct(Player $player){
		$this->player=$player;
		$this->plugin =LTGrade::getInstance();
		$this->task=$player->getMainTask();
	}
	public function chechTask(){
		$name=strtolower($this->player->getName());
		if($this->task>13 and $this->plugin->PlayerTaskConf->get($name)==false){
			$allTask=self::$AllTask;
			$task=[];
			for($i=0;$i<(3+($this->player->isVIP()===false?0:$this->player->isVIP()));$i++){
				$index=array_rand($allTask);
				$info=$allTask[$index];
				unset($allTask[$index]);
				$task[$index] =  [0, $info[1]];
			}
			$this->DailyTask = $task;
			$this->plugin->PlayerTaskConf->set($name, $task);
		}else{
			$this->DailyTask = $this->plugin->PlayerTaskConf->get($name);
		}
	}
	public function giveReward(){
		if(!mt_rand(0, 2)){
			$this->player->addMoney(($money=mt_rand(10000, 100000)), '任务奖励');
			$this->player->sendMessage('§l§a恭喜你完成了一个任务 奖励'.$money.'橙币！');
		}else{
			$rand=mt_rand(1, 100);
			switch(true){
				case $rand<10:
					$item=['材料', '觉醒石碎片', 1];
				break;
				case $rand<30:
					$item=['材料', '高级盔甲经验水晶', 1];
				break;
				case $rand<50:
					$item=['材料', '高级武器经验水晶', 5];
				break;
				default:
					$item=['材料', '宝箱之钥', 1];
				break;
			}
			\LTCraft\Main::sendItem($this->player->getName(), $item);
			$this->player->sendMessage('§l§a恭喜你完成了一个任务 奖励'.$item[0].'类型'.$item[1].'×'.$item[2].'已发送至你的邮箱！');
		}
        if ($this->getDoneCount() == 1){
            if ($this->plugin->getTaskDoneCount($this->player)>=2){
                $item=['魔法', '灵魂圣布', 1];
                $this->plugin->reset($this->player);
                \LTCraft\Main::sendItem($this->player->getName(), $item);
                $this->player->sendMessage('§l§a你连续三天完成两个任务，获得'.$item[0].'类型'.$item[1].'×'.$item[2].'已发送至你的邮箱！');
            }
            $this->plugin->taskDoneCounter($this->player);
            \LTCraft\Main::sendItem($this->player->getName(), ['材料', '§aT', 2]);
            $this->player->sendMessage('§l§a恭喜你今天完成了两个任务 活动奖励'.$item[0].'类型'.$item[1].'×'.$item[2].'已发送至你的邮箱！');
        }
	}
	public function checkDailyTaskComplete(){
		foreach($this->DailyTask as $id=>$info){
			if($info[0]<$info[1])return false;
		}
		return true;
	}
	public function getDoneCount(){
        $count = 0;
        foreach($this->DailyTask as $id=>$Tinfo) {
            if ($Tinfo[0] >= $Tinfo[1]) {
                $count++;
            }
        }
        return $count;
    }
	public function updateDailyTask(){
		$info='§6每日任务:';
		foreach($this->DailyTask as $id=>$Tinfo){
			$task=self::$AllTask[$id];
			if($Tinfo[0]>=$Tinfo[1]){
				$info.='\n§e'.$task[0]. '('.$Tinfo[0].'/'.$Tinfo[1].')';
			}else{
				$info.='\n§d'.$task[0]. '('.$Tinfo[0].'/'.$Tinfo[1].')';
			}
		}
		$this->message=$info.'\n§aVIP每日可附加同等级数量任务哦~';
	}
	public function setTask($task){
		if($task===$this->task)return;
		$this->task = $task;
		if($this->task>13){
			$this->chechTask();
			$this->updateTaskMessage();
		}else{
			$this->message='§6主线任务:\n'.self::$MainTask[$this->task];
		}
		$this->player->setMainTask($this->task);
		if($this->player->getAPI() instanceof API)$this->player->getAPI()->update(API::TASK);
	}
	public function updateTaskMessage(){
		if($this->task>13){
			if($this->checkDailyTaskComplete()){
				$this->message='§d商城请访问:\n§awww.ltcraft.cn/shop\n§a随身自定义药水,PVE,PVP武器\n§2雷击,吸血,自定义攻击力,\n§6反伤,闪避,自定义击杀提示\n§dVIP+等';
			}else{
				$this->updateDailyTask();
			}
		}else{
			$this->message='§6主线任务:\n'.self::$MainTask[$this->task];
		}
		if($this->player->getAPI() instanceof API)$this->player->getAPI()->update(API::TASK);
	}
	public function getTaskMessage(){
		return $this->message;
	}
	public function nextTask(){
		if(++$this->task<13){
			if($this->player->isOnline()){
				$this->updateTaskMessage();
				$this->player->setMainTask($this->task);
			}else{//如果玩家掉线了
				if(($player=Server::getInstance()->getPlayerExact($this->player->getName()))!==null){//如果玩家被顶下去了
					$player->getTask()->nextTask();
				}elseif(file_exists(\pocketmine\DATA .'players/'.strtolower($this->player->getName()).'.dat')){//这是正常退出游戏
					$nbt=Server::getInstance()->getOfflinePlayerData(strtolower($this->player->getName()));
					$nbt->MainTask=new StringTag('MainTask', ++$this->task);
					Server::getInstance()->saveOfflinePlayerData(strtolower($this->player->getName()), $nbt, true);
				}
			}
		}
	}
	public function action($type, $result = null){
		if($this->task<=13){
			switch($this->task){
				case 0:
					if($type==='菜单传送'){
						$this->player->setMainTask($this->task+1);
						$this->message='§d棒棒哒！\n你已经知道如何用菜单传送世界了！\n如果您不在主城，那么\n接下来我们将把您传送回去！';
						$this->player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask(function() {
							if($this->player->isOnline() and $this->player->getLevel()->getName()!=='zc'){
								$this->player->teleport($this->player->getServer()->getDefaultLevel()->getSafeSpawn(), null, null, false);
							}
						}, []), 300);
						$this->player->getAPI()->update(API::TASK);
						$this->player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"nextTask"],[]), 300);
					}
					if($type==='更新世界' and $result==='f1'){
						$this->player->setMainTask($this->task+2);
						if(!$this->player->getAStatusIsDone('新手教程')){
							$this->player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战','新手铁剑',strtolower($this->player->getName())));
							$this->message='§d恭喜你，已经完成了第二个任务\n§3送你你一把武器\n已发送到你的背包。\n§e另外说一句，欢迎来到LTCtaft';
						}else{
							EconomyAPI::getInstance()->addMoney($this->player, 10000, '任务奖励');
							$this->message='§d恭喜你，已经完成了第二个任务\n§3送你10000橙币\n已到你的钱包。\n§e另外说一句，欢迎来到LTCtaft';
						}
						$this->player->getAPI()->update(API::TASK);
						$this->player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"nextTask"],[]), 300);
					}
				break;
				case 1:
					if($type==='更新世界' and $result==='f1'){
						$this->player->setMainTask($this->task+1);
						if(!$this->player->getAStatusIsDone('新手教程')){
							$this->player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战','新手铁剑',strtolower($this->player->getName())));
							$this->message='§d恭喜你，已经完成了第二个任务\n§3送你你一把武器\n已发送到你的背包。\n§e另外说一句，欢迎来到LTCtaft';
						}else{
							EconomyAPI::getInstance()->addMoney($this->player, 10000, '任务奖励');
							$this->message='§d恭喜你，已经完成了第二个任务\n§3送你10000橙币\n已到你的钱包。\n§e另外说一句，欢迎来到LTCtaft';
						}
						$this->player->getAPI()->update(API::TASK);
						$this->player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"nextTask"],[]), 300);
					}
				break;
				case 2:
					if($type==='升级' and $result>=5){
						$this->player->setMainTask($this->task+1);
						$this->message='§a§d恭喜你，已经完成了第三个任务\n§a继续加油哦！';
						$this->player->getAPI()->update(API::TASK);
						$this->player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"nextTask"],[]), 100);
					}
				break;
				case 3:
					if($type==='兑换完成' and LTItem::getInstance()->isEquals($result, ['近战','破易桃木剑'])){
						$this->player->setMainTask($this->task+1);
						$this->message='§a§d恭喜你，已经完成了第四个任务\n§a现在开始你的游戏之旅吧\n有任何问题可以加群了解哦';	
						$this->player->getAPI()->update(API::TASK);
						$this->player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"nextTask"],[]), 300);
					}
				break;
				case 4:
					if($type==='兑换完成' and LTItem::getInstance()->isEquals($result, ['近战','桃木剑'])){
						if($this->player->getGrade()>=10){
							$this->player->setMainTask($this->task+2);
							$this->message='§d恭喜你，已经完成了第五个任务\n你当前等级大于10级,已为你跳过第六个任务！\n§3继续加油！';
						}else{
							$this->player->setMainTask($this->task+1);
							$this->message='§d恭喜你，已经完成了第五个任务\n§3继续加油！';
						}
						$this->player->getAPI()->update(API::TASK);
						$this->player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"nextTask"],[]), 100);
					}
				break;
				case 5:
					if($type==='升级' and $result>=10){
						$this->player->setMainTask($this->task+1);
						$this->message='§a§d恭喜你，已经完成了第六个任务\n§3继续加油！';
						$this->player->getAPI()->update(API::TASK);
						$this->player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"nextTask"],[]), 60);
					}
				break;
				case 6:
					if($type==='出售完成' and $result[0]->getID()==17){
						$this->player->setMainTask($this->task+1);
						$this->message='§d恭喜你，已经完成了第七个个任务\n§2奖励你一万橙币\n§5继续下一个任务吧!\n§3继续加油！';	
						$this->player->getAPI()->update(API::TASK);
						EconomyAPI::getInstance()->addMoney($this->player, 10000, '任务奖励');
						$this->player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"nextTask"],[]), 300);
					}
				break;
				case 7:
					if($type==='成功购买地皮' and $result==='jm'){
						$this->player->setMainTask($this->task+1);
						$this->message='§d恭喜你，已经完成了第八个任务\n§3继续努力噢,你已经了解这个服务器\n§6的基础玩法了';	
						$this->player->getAPI()->update(API::TASK);
						$this->player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"nextTask"],[]), 300);
					}
				break;
				case 8:
					if($type==='回到地皮'){
						$this->player->setMainTask($this->task+1);
						$this->message='§d恭喜你，已经完成了第久个任务\n§3继续努力哦';	
						$this->player->getAPI()->update(API::TASK);
						$this->player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"nextTask"],[]), 300);
					}
				break;
				case 9:
					if($type==='毕业' and $result>=1){
						$this->player->setMainTask($this->task+1);
						$this->message='§d漂亮 你现在已经知道如何毕业副本了！\n§3继续毕业另外四个完成主线吧！';	
						// \LTCraft\Main::sendItem($this->player->getName(), ['材料', '中秋节月饼', 1]);
						$this->player->getAPI()->update(API::TASK);
						$this->player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"nextTask"],[]), 200);
					}
				break;
				case 10:
					if($type==='毕业' and $result>=2){
						$this->player->setMainTask($this->task+1);
						$this->message='§d恭喜你完成F2毕业！\n§3距离完成主线还剩3个！';	
						// \LTCraft\Main::sendItem($this->player->getName(), ['材料', '中秋节月饼', 1]);
						$this->player->getAPI()->update(API::TASK);
						$this->player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"nextTask"],[]), 200);
					}
				break;
				case 11:
					if($type==='毕业' and $result>=3){
						$this->player->setMainTask($this->task+1);
						$this->message='§d恭喜你完成F3毕业！\n§3距离完成主线还剩2个！';	
						// \LTCraft\Main::sendItem($this->player->getName(), ['材料', '中秋节月饼', 1]);
						$this->player->getAPI()->update(API::TASK);
						$this->player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"nextTask"],[]), 200);
					}
				break;
				case 12:
					if($type==='毕业' and $result>=4){
						$this->player->setMainTask($this->task+1);
						$this->message='§d恭喜你完成F4毕业！\n§3距离完成主线还剩1个！';	
						// \LTCraft\Main::sendItem($this->player->getName(), ['材料', '中秋节月饼', 1]);
						$this->player->getAPI()->update(API::TASK);
						$this->player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"nextTask"],[]), 200);
					}
				break;
				case 13:
					if($type==='毕业' and $result>=5){
						$this->player->setMainTask($this->task+1);
						$this->message='§d恭喜你，已经完成了主线任务！';	
						// \LTCraft\Main::sendItem($this->player->getName(), ['材料', '中秋节月饼', 1]);
						$this->player->sendMessage('§l§a[镇长]§a冒险者好样的，你得到了证实。现在你应该升级自己的装备打败最终boss了！');
						// $this->player->sendMessage('§l§a[镇长]§a看你每一件像样的装备，请将升级自己的装备升级成高级的吧~');
						$this->player->getAPI()->update(API::TASK);
						$this->player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"nextTask"],[]), 200);
					}
				break;
				// case 13:
					// if($type==='锻造成功' and $result=='高级武器'){
						// $this->player->setMainTask($this->task+1);
						// $this->message='§d干的漂亮！\n你以后就需要这样锻造武器了！\n最终锻造一把史诗武器！\n证明我看吧！\n§c史诗武器需先锻造终极武器哦！';	
						// $this->player->getAPI()->update(API::TASK);
						// $this->player->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"updateTaskMessage"],[]), 1000);
						// $player=$this->player;
						// $name=strtolower($player->getName());
						// if($this->plugin->PlayerTaskConf->get($name)==false){
							// $allTask=self::$AllTask;
							// $task=[];
							// for($i=0;$i<=(3+($player->isVIP()===false?0:$player->isVIP()));$i++){
								// $index=array_rand($allTask);
								// $info=$allTask[$index];
								// unset($allTask[$index]);
								// $task[$index] =  [0, $info[1]];
							// }
							// $this->DailyTask = $task;
							// $this->plugin->PlayerTaskConf->set($name, $task);
						// }
					// }
				// break;
			}
		}else{
			/*
			['§a卖掉500块木头', 500],//0
			['§a挖30块钻石原矿', 30],//1
			['§a参与或杀死10只T系列BOSS', 10],//2
			['§a在PVP获得30次击杀', 30],//3
			['§a获得10个人头', 10],//4
			['§a击杀3次T系列BOSS', 20],//5
			['§a挖100块铁原矿', 100],//6
			['§a消耗500点饥饿值', 500],//7
			['§a开启8个神秘礼包', 8],//8
			['§a兑换三个神秘武器奖励', 3],//9
			['§a兑换三个神秘盔甲奖励', 3],//10
			['§a击杀100只任意怪物', 100],//11
			['§a挖掉200块煤矿', 200],//12
			*/
			switch($type){
				case '破坏方块':
					if(isset($this->DailyTask[1]) and $result==56 and $this->DailyTask[1][0]<$this->DailyTask[1][1]){
						$arr=$this->DailyTask[1];
						$arr[0]++;
						if($arr[0]>=$this->DailyTask[1][1]){
							$arr[0]=$this->DailyTask[1][1];
							$this->giveReward();
						}
						$this->DailyTask[1]=$arr;
						$this->updateTaskMessage();
					}elseif(isset($this->DailyTask[6]) and $result==15 and $this->DailyTask[6][0]<$this->DailyTask[6][1]){
						$arr=$this->DailyTask[6];
						$arr[0]++;
						if($arr[0]>=$this->DailyTask[6][1]){
							$arr[0]=$this->DailyTask[6][1];
							$this->giveReward();
						}
						$this->DailyTask[6]=$arr;
						$this->updateTaskMessage();
					}elseif(isset($this->DailyTask[12]) and $result==16 and $this->DailyTask[12][0]<$this->DailyTask[12][1]){
						$arr=$this->DailyTask[12];
						$arr[0]++;
						if($arr[0]>=$this->DailyTask[12][1]){
							$arr[0]=$this->DailyTask[12][1];
							$this->giveReward();
						}
						$this->DailyTask[12]=$arr;
						$this->updateTaskMessage();
					}
				break;
				case '出售完成':
					if(isset($this->DailyTask[0]) and $result[0]->getId()==17 and $result[0]->getDamage()==0 and $this->DailyTask[0][0]<$this->DailyTask[0][1]){
						$arr=$this->DailyTask[0];
						$arr[0]+=$result[1];
						if($arr[0]>=$this->DailyTask[0][1]){
							$arr[0]=$this->DailyTask[0][1];
							$this->giveReward();
						}
						$this->DailyTask[0]=$arr;
						$this->updateTaskMessage();
					}
				break;
				case '击杀怪物':
					if(isset($this->DailyTask[2]) and LTEntity::isTBoss($result) and $this->DailyTask[2][0]<$this->DailyTask[2][1]){
						$arr=$this->DailyTask[2];
						$arr[0]++;
						if($arr[0]>=$this->DailyTask[2][1]){
							$arr[0]=$this->DailyTask[2][1];
							$this->giveReward();
						}
						$this->DailyTask[2]=$arr;
						$this->updateTaskMessage();
					}
					if(isset($this->DailyTask[5]) and LTEntity::isTBoss($result) and $this->DailyTask[5][0]<$this->DailyTask[5][1]){
						$arr=$this->DailyTask[5];
						$arr[0]++;
						if($arr[0]>=$this->DailyTask[5][1]){
							$arr[0]=$this->DailyTask[5][1];
							$this->giveReward();
						}
						$this->DailyTask[5]=$arr;
						$this->updateTaskMessage();
					}
					if(isset($this->DailyTask[11]) and $this->DailyTask[11][0]<$this->DailyTask[11][1]){
						$arr=$this->DailyTask[11];
						$arr[0]++;
						if($arr[0]>=$this->DailyTask[11][1]){
							$arr[0]=$this->DailyTask[11][1];
							$this->giveReward();
						}
						$this->DailyTask[11]=$arr;
						$this->updateTaskMessage();
					}
				break;
				case '参与击杀怪物':
					if(isset($this->DailyTask[2]) and LTEntity::isTBoss($result) and $this->DailyTask[2][0]<$this->DailyTask[2][1]){
						$arr=$this->DailyTask[2];
						$arr[0]++;
						if($arr[0]>=$this->DailyTask[2][1]){
							$arr[0]=$this->DailyTask[2][1];
							$this->giveReward();
						}
						$this->DailyTask[2]=$arr;
						$this->updateTaskMessage();
					}
				break;
				case '击杀玩家':
					if(isset($this->DailyTask[3]) and $this->DailyTask[3][0]<$this->DailyTask[3][1]){
						$arr=$this->DailyTask[3];
						$arr[0]++;
						if($arr[0]>=$this->DailyTask[3][1]){
							$arr[0]=$this->DailyTask[3][1];
							$this->giveReward();
						}
						$this->DailyTask[3]=$arr;
						$this->updateTaskMessage();
					}
				break;
				case '获得人头':
					if(isset($this->DailyTask[4]) and $this->DailyTask[4][0]<$this->DailyTask[4][1]){
						$arr=$this->DailyTask[4];
						$arr[0]++;
						if($arr[0]>=$this->DailyTask[4][1]){
							$arr[0]=$this->DailyTask[4][1];
							$this->giveReward();
						}
						$this->DailyTask[4]=$arr;
						$this->updateTaskMessage();
					}
				break;
				case '击败末影龙':
					if(isset($this->DailyTask[9]) and $this->DailyTask[9][0]<$this->DailyTask[9][1]){
						$arr=$this->DailyTask[9];
						$arr[0]++;
						if($arr[0]>=$this->DailyTask[9][1]){
							$arr[0]=$this->DailyTask[9][1];
							$this->giveReward();
						}
						$this->DailyTask[9]=$arr;
						$this->updateTaskMessage();
					}
				break;
				case '开启神秘奖励':
					if(isset($this->DailyTask[10]) and $this->DailyTask[10][0]<$this->DailyTask[10][1]){
						$arr=$this->DailyTask[10];
						$arr[0]++;
						if($arr[0]>=$this->DailyTask[10][1]){
							$arr[0]=$this->DailyTask[10][1];
							$this->giveReward();
						}
						$this->DailyTask[10]=$arr;
						$this->updateTaskMessage();
					}
				break;
				case '开启神秘礼包':
					if(isset($this->DailyTask[8]) and $this->DailyTask[8][0]<$this->DailyTask[8][1]){
						$arr=$this->DailyTask[8];
						$arr[0]++;
						if($arr[0]>=$this->DailyTask[8][1]){
							$arr[0]=$this->DailyTask[8][1];
							$this->giveReward();
						}
						$this->DailyTask[8]=$arr;
						$this->updateTaskMessage();
					}
				break;
				case '消耗饥饿':
					if(isset($this->DailyTask[7]) and $this->DailyTask[7][0]<$this->DailyTask[7][1]){
						$arr=$this->DailyTask[7];
						$arr[0]++;
						if($arr[0]>=$this->DailyTask[7][1]){
							$arr[0]=$this->DailyTask[7][1];
							$this->giveReward();
						}
						$this->DailyTask[7]=$arr;
						$this->updateTaskMessage();
					}
				break;
			}
		}
	}
}