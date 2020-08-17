<?php
namespace LTMultiTeleport;

use pocketmine\event\Listener;
use pocketmine\scheduler\CallbackTask;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;          
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender; 
use onebone\economyapi\EconomyAPI;
use pocketmine\level\Position;        
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\math\Math;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\NBT;
use LTGrade\Main as LTGrade;
class Main extends PluginBase implements Listener{
	private $Request=[],$warps=[],$lastTeleportTick=[];
	private static $instance;
	public static function getInstance(){
		return self::$instance;
	}
    public function onEnable(){
		self::$instance=$this;
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->upDateWarps();
	}
	
	private function isOnline($n){
		$n = strtolower($n);
		foreach( $this->getServer()->getOnlinePlayers() as $P ){
			if(strtolower($P->getName())==$n){
				return true;
			}
		}return false;
	}
	
	public function isWarp($warpname){
	    if($this->warp->get($warpname)==null)
			 return false;
		else
	         return true;
    } 
	
    public function onJoin(playerJoinEvent $event){
		$this->Request[$event->getPlayer()->getName()]=['Request'=>null,'RequestTime'=>null,'RequestType'=>null];
	}
	public function upDateWarps($l=null){
		$this->warps=[];
		foreach($this->getServer()->getLevels() as $level){
			if($l!==null and $level===$l)continue;
			$levelWarps=$level->getProvider()->getWarps();
			$this->warps=array_merge($this->warps,$levelWarps);
		}
	}
	public function onQuitEvent(PlayerQuitEvent $event){
		unset($this->Request[$event->getPlayer()->getName()], $this->lastTeleportTick[$event->getPlayer()->getName()]);
	}
	public function onCommand(CommandSender $player, Command $cmd, $label, array $args)
	{  
	    $name = strtolower($player->getName());
	    $NAME = strtoupper($player->getName());
		$yname=$player->getName();
	    if(!($player instanceof Player))
			$player->sendMessage('§l§a[LTcraft温馨提示]§e请不要在控制台输入本指令');
	    else{
    	switch(strtolower($cmd)){
			case '设置地标':
			case 'setwarp':
				if(!$player->isOp())return $player->sendMessage('§l§a[LTcraft温馨提示]§c只有管理员才可以使用这个指令');
				if(!isset($args[0]))return $player->sendMessage('§l§a[LTcraft温馨提示]§c格式错误, 缺少地标名, §2/setwarp|设置地标 地标名');
				if($player->level->getProvider()->addWarp($args[0], $player->asPosition())===true)
					$player->sendMessage('§l§a[LTcraft温馨提示]§a地标设置成功! ');
				else
					$player->sendMessage('§l§a[LTcraft温馨提示]§c地标设置失败，请检查是否存在 ');
				$this->upDateWarps();
				return;
			case '删除地标':
			case 'delwarp':
				if(!$player->isOp())return $player->sendMessage('§l§a[LTcraft温馨提示]§c只有管理员才可以使用这个指令');
				if(!isset($args[0]))return $player->sendMessage('§l§a[LTcraft温馨提示]§c格式错误, 缺少地标名, §2/delwarp|删除地标 地标名');
				if($player->level->getProvider()->delWarp($args[0])===true)
					$player->sendMessage('§l§a[LTcraft温馨提示]§a地标删除成功! ');
				else
					$player->sendMessage('§l§a[LTcraft温馨提示]§c地标删除失败，请检查是否存在 ');
				$this->upDateWarps();
				return;
			case '地标':
			case 'warp':
				if(!isset($args[0]))return $player->sendMessage('§l§a[LTcraft温馨提示]§c请输入地标名, 输入§2/warplist|地标列表 §7来查看所有的地标');
				if(!isset($this->warps[$args[0]]))return $player->sendMessage('§l§a[LTcraft温馨提示]§c不存在这个地标 输入§2/warplist|地标列表 §7来查看所有的地标');
				if($player->teleport($this->warps[$args[0]]))
				$player->sendMessage('§l§a[LTcraft温馨提示]§a传送成功! 已将你传送到'.$args[0]);
				return;
			case '地标列表':
			case 'warplist':
				if(!isset($args[0]))$args[0]=1;
			    if(!is_numeric($args[0]) or $args[0]<1 or floor($args[0])!=$args[0])return $player->sendMessage('§l§a[LTcraft温馨提示]§c/warplist|地标列表 页码(数字) §7查看所有地标');
				if($args[0]>ceil(count($this->warps)/7))$args[0]=ceil(count($this->warps)/7);
				$player->sendMessage('§6-------< 地标列表 第§b'.$args[0].'/'. ceil(count($this->warps)/7) .'§6页 -------< ');
			    $send = null;
				$c = 0;
				$page = 1;
				foreach(array_keys($this->warps) as $warpname){
					if(++$c%7==0)
						 $page++;
					if($page==$args[0]){
						 $data = $this->warps[$warpname];
						 $send .= "§3名称: §7{$warpname} §3所在世界: §7{$this->warps[$warpname]->level->getName()} \n";
					}
				}
				if($send===null)
					$send = "§3管理员尚未保存任何坐标";
				$player->sendMessage($send);
			    return;
			case '全体传送':
			case 'tpall':
				if($player->getGrade()<130)return $player->sendMessage('§l§a[LTcraft温馨提示]§c你需要130级才可以使用这个功能！');
				$Money  = EconomyAPI::getInstance()->myMoney($name);				
				if($Money < 50000)return $player->sendMessage('§l§a[LTcraft温馨提示]§c余额不足全,体传送需要 §d50000 §c橙币而你只有 §d'.$Money.' §c橙币');
				foreach($this->getServer()->getOnlinePlayers() as $p){
				    $n = $p->getName();
					if(!isset($this->Request[$n]) or ($p->distance($player)<10 and $p->getLevel()===$player->getLevel()))continue;
	    			if( $this->Request[$n]['RequestTime'] < time()  ){
						$this->Request[$n]['Request']=$name;
						$this->Request[$n]['RequestTime']=time()+45;
						$this->Request[$n]['RequestType']=2;
						$p->sendMessage('§6-------< 全体传送 >-------');
						$p->sendMessage('§4> §3'.$yname.' §7同意该请求将使你传送到他的位置');
						$p->sendMessage('§4> §7输入 §2/同意 §7来同意他的请求');
						$p->sendMessage('§4> §7你有 §245 §7秒的时间考虑');
					}
                }
				$player->sendMessage('§l§a[LTcraft温馨提示]§c请求成功！');
				EconomyAPI::getInstance()->reduceMoney($name, 5000, '全员结合消耗');			
			    return;
			case '传送帮助':
			case 'tpahelp':
			    if(!isset($args[0]) or $args[0]==1){
				    $player->sendMessage('§6-------< 传送帮助 §b1/2 §6>-------');
			        $player->sendMessage('§f- §a/传送|tpa 玩家名     §7//请求传送到该玩家的位置');
			        $player->sendMessage('§f- §a/返回|back             §7//回到上次死亡点');
			        $player->sendMessage('§f- §a/传送帮助|tpahelp 页码   §7//查看传送帮助');
			        $player->sendMessage('§f- §a/传送到这|tpahere 玩家名   §7//请求该玩家传送到你的位置');
				}else if($args[0]==2){
				     $player->sendMessage('§6-------< 传送帮助 §b2/2 §6>-------');
			         $player->sendMessage('§f- §a/warp|地标 地标名|地标ID   §7//传送到一个地标位置');
			         $player->sendMessage('§f- §a/setwarp|设置地标 地标名   §7//以当前位置设置一个地标');
			         $player->sendMessage('§f- §a/warplist|地标列表 页码   §7//查看所有地标');
			         $player->sendMessage('§f- §a/安家|sethome        §7//设置家的位置');
			         $player->sendMessage('§f- §a/回家|home           §7//回到设置的家的位置');					
					 $player->sendMessage('§f- §a/全体传送|tpall      §7//同意拒绝当前请求');
				}	
			    return true;
			case '随机传送':
			case 'wild':
			    if ($player->getLevel()->getName()!='zy'){
                    $player->sendMessage('§l§a[LTcraft温馨提示]§c你只能在资源世界使用这个命令。');
                    return true;
                }
			    if (isset($this->lastTeleportTick[$yname]) and $this->server->getTick() - $this->lastTeleportTick[$yname] <= 20*10){
                    $this->lastTeleportTick[$yname] = $this->server->getTick();
                    $player->sendMessage('§l§a[LTcraft温馨提示]§c冷却中...');
			        return true;
                }
				$x=mt_rand(-10000,10000);
				$z=mt_rand(-10000,10000);
				$level = $player->getLevel();
                $level->generateChunk($x >> 4, $z>>4, true);
                $this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function (Player $player, $x, $z) {
                    if (!$player->isOnline())return;
                    if($player->teleport(new Position($x,$player->getLevel()->getHighestBlockAt($x,$z) + 1,$z,$player->getLevel()), null, null, true, false, true))
                        $player->sendMessage('§l§a[LTcraft温馨提示]§a传送成功');
                    else
                        $player->sendMessage('§l§a[LTcraft温馨提示]§c传送失败');
                }, [$player, $x, $z]), 5);
                return true;
			case 'tpahere':
			case '传送到这':
			    if(!isset($args[0]) || $args[0]==null)return $player->sendMessage('§l§a[LTcraft温馨提示]§c输入错误 格式§2/传送到这|tpahere 玩家名 §c请求该玩家传送到你的位置');
				$target=$this->getServer()->getPlayer($args[0]);
                if(!$target){
					$player->sendMessage('§l§a[LTcraft温馨提示]§c该玩家不存在或不在线, 请检查名称拼写');
					$player->sendMessage('§l§a[LTcraft温馨提示]§c你可以简写玩家名字，比如Angel_XX你只需要输入/tpahere an 要确保服务器只有这一个an开头的！不然就要输入/tpahere ang or /tpahere ange');
					return;
					}
					$n=$target->getName();
				if($this->Request[$n]['RequestTime'] >= time()){
					$player->sendMessage('§l§a[LTcraft温馨提示]§c已经有人提早邀请他了');
					$player->sendMessage('§l§a[LTcraft温馨提示]§c请等待他的选择或者等待一段时间当前邀请过期后再发出请求');
					return;
					}
						$target->sendMessage('§6-------< 单人传送 >-------');
						$target->sendMessage('§4> §3'.$yname.' §7请求你传送至他的位置');
						$target->sendMessage('§4> §7输入 §2/同意 §7来同意他的请求');
						$target->sendMessage('§4> §7你有 §230 §7秒的时间考虑');
						$this->Request[$n]['Request']=$yname;
						$this->Request[$n]['RequestTime']=time()+30;
						$this->Request[$n]['RequestType']=2;
						$player->sendMessage('§l§a[LTcraft温馨提示]§a已成功发送请求给'.$n.', 请等待结果.');
                unset($data,$Money,$TpCost,$player);				
			    return;
			case 'tpa':
			case '传送':
			    if(!isset($args[0]) || $args[0]==null)return $player->sendMessage('§l§a[LTcraft温馨提示]§c输入错误 格式§2/传送|tpa 玩家名 §c请求到该玩家的位置');
					$target=$this->getServer()->getPlayer($args[0]);
                if(!$target){
					$player->sendMessage('§l§a[LTcraft温馨提示]§c该玩家不存在或不在线, 请检查名称拼写');
					$player->sendMessage('§l§a[LTcraft温馨提示]§c你可以简写玩家名字，比如Angel_XX你只需要输入/tpa an 要确保服务器只有这一个an开头的！不然就要输入/tpa ang or /tpa ange');
					return;
				}
				$n=$target->getName();
				if($this->Request[$n]['RequestTime'] >= time()){
					$player->sendMessage('§l§a[LTcraft温馨提示]§c已经有人提早邀请他了');
					$player->sendMessage('§l§a[LTcraft温馨提示]§c请等待他的选择或者等待一段时间当前邀请过期后再发出请求');
					return;
				}
						$target->sendMessage('§6-------< 单人传送 >-------');
						$target->sendMessage('§4> §3'.$yname.' §7请求传送到你这');
						$target->sendMessage('§4> §7输入 §2/同意 §7来同意他的请求');
						$target->sendMessage('§4> §7你有 §230 §7秒的时间考虑');
						$this->Request[$n]['Request']=$yname;
						$this->Request[$n]['RequestTime']=time()+30;
						$this->Request[$n]['RequestType']=1;
						$player->sendMessage('§l§a[LTcraft温馨提示]§a已成功发送请求给'.$n.', 请等待结果.');
                unset($data,$Money,$TpCost);				
			    break;
			case '安家':
			case 'sethome':
				if(!isset($args[0]))return $player->sendMessage('§l§a[LTcraft温馨提示]§e用法/sethome|安家 家的名字');
				$Money=EconomyAPI::getInstance()->myMoney($name);			
				if($Money < 500)return $player->sendMessage('§l§a[LTcraft温馨提示]§c您的余额不足设置家的位置,需要§2'. 500 .' §c而你只有 §2'.$Money);
				EconomyAPI::getInstance()->reduceMoney($name, 500, '设置家消耗');	
				if($player->addHome($args[0],$player->asPosition())){
					if($player->getAStatusIsDone('设置家')==false){
						$player->sendMessage('§l§a[LTcraft温馨提示]§a恭喜你解锁了菜单新功能:§c家列表§a你现在可以在菜单中快捷回家了！');
						$player->addAStatus('设置家');
                        $player->newProgress('温暖的家', '设置一个家');
					}
					return $player->sendMessage('§l§a[LTcraft温馨提示]§a已保存家的位置, 输入§2/回家 '.$args[0].'§a即可回到温暖的家了');
					
				}else
					return $player->sendMessage('§l§a[LTcraft温馨提示]§c你已经有这个家了，换个名字吧');
				break;
			case '回家':
			case 'home':
				if(!isset($args[0]))return $player->sendMessage('§l§a[LTcraft温馨提示]§e用法/home|回家 家的名字');
					if(($home=$player->getHome($args[0]))!==false){
						if(!$home->level->isClosed()){
							if($player->teleport($home))
								$player->sendMessage('§l§a[LTcraft温馨提示]§a传送成功');
						}else 
							return $player->sendMessage('§l§a[LTcraft温馨提示]§c目标失效，请检查世界是否存在');
				}else return $player->sendMessage('§l§a[LTcraft温馨提示]§c这个家不存在');
			    break;
			case '返回':
			case 'back':
				if($player->getLastDie()===false)return $player->sendMessage('§l§a[LTcraft温馨提示]§c没有死亡记录');
				if($player->teleport($player->getLastDie(),null,null,false))
					$player->sendMessage('§l§a[LTCraft温馨提示]§a传送成功, 已将您传送到上次死亡地.');
			    break;
			case '同意':
				if($this->Request[$yname]['RequestTime']==null){
					$player->sendMessage('§l§a[LTcraft温馨提示]§e还木有任何人对你发出传送请求呢');
					return;}
				
				if($this->Request[$yname]['RequestTime'] < time()){
					$player->sendMessage('§l§a[LTcraft温馨提示]§e当前请求已失效, 请让对方重新发送请求.');
					return;}
				
				if(!$this->isOnline($this->Request[$yname]['Request']))return $player->sendMessage('§l§a[LTcraft温馨提示]§c该玩家已离线.');
				if($this->Request[$yname]['RequestType']==1){
					foreach($this->getServer()->getOnlinePlayers() as $P){
						if(strtolower($P->getName())==strtolower($this->Request[$yname]['Request'])){
							if($P->teleport($player)){
								$player->sendMessage('§l§a[LTcraft温馨提示]§a传送'.$P->getName().'成功.');
								$P->sendMessage('§l§a[LTcraft温馨提示]§a传送'.$player->getName().'成功.');
							}else{
								$player->sendMessage('§l§a[LTcraft温馨提示]§c传送'.$P->getName().'失败了！');
								$P->sendMessage('§l§a[LTcraft温馨提示]§c传送'.$player->getName().'失败了！.');
							}
						}
					}
				}else if($this->Request[$yname]['RequestType']==2){
					foreach($this->getServer()->getOnlinePlayers() as $P){
						if(strtolower($P->getName())==strtolower($this->Request[$yname]['Request'])){
							//$player是请求传送到$P这的人
							if($player->teleport($P)){
								$P->sendMessage('§l§a[LTcraft温馨提示]§a传送'.$player->getName().'成功.');
								$player->sendMessage('§l§a[LTcraft温馨提示]§a传送'.$P->getName().'成功.');
							}else{
								$P->sendMessage('§l§a[LTcraft温馨提示]§a传送'.$player->getName().'失败了！');
								$player->sendMessage('§l§a[LTcraft温馨提示]§a传送'.$P->getName().'失败了！');
							}
						}
					}
				}
				$this->Request[$yname]['Request']=null;
				$this->Request[$yname]['RequestTime']=null;
				$this->Request[$yname]['RequestType']=null;
				break;
			case '家列表':
			case 'homelist':
				if(!isset($args[0]))$args[0]=1;
				if(!is_numeric($args[0]) or $args[0]<1 or floor($args[0])!=$args[0])return $player->sendMessage('§l§a[LTcraft温馨提示]§c/homelist|家列表 页码(数字) §7查看所有家');
				$homes=$player->getHomes();
				if($args[0]>ceil(count($homes)/7))$args[0]=ceil(count($homes)/7);
				$player->sendMessage('§6-------< 家列表 第§b'.$args[0].'/'. ceil(count($homes)/7) .'§6页 -------< ');
				$send = null;
				$c = 0;
				$page = 1;
				foreach(array_keys($homes) as $homename){
						if(++$c%7==0)
							 $page++;
						if($page==$args[0])
							if($homes[$homename]->level->isClosed())
								$send .= "§3名称: §7{$homename} §3所在世界: §7{$homes[$homename]->level->getName()} §c注意，这个家所在世界没有被加载！\n";
							else
								$send .= "§3名称: §7{$homename} §3所在世界: §7{$homes[$homename]->level->getName()} \n";
				}
				if($send===null)
					$send = "你还没有家呢！";
				$player->sendMessage($send);
				return;
					/*
					if(count($homes)<=0)return $player->sendMessage('§l§a[LTcraft温馨提示]§c你还没家呢');
					$home='';
					$i=0;
					foreach($homes as $name=>$pos){
						if($pos->level->isClosed())$name='§c'.$name.'§e';
						if(++$i==5){
							$i=0;
							$home.=$name."\n";
						}else
							$home.=$name.",";
					}
					$player->sendMessage("§l§a[LTcraft温馨提示]§e你的家:\n".substr($home,0,strlen($home)-1));
				break;
				*/
				case '删除家':
				case 'delhome':
					if(!isset($args[0]))return $player->sendMessage('§l§a[LTcraft温馨提示]§c用法：/删除家|delhome 家的名字');
					if($player->delHome($args[0]))
						$player->sendMessage('§4> §7成功删除家：'.$args[0]);
					else
						$player->sendMessage('§l§a[LTcraft温馨提示]§c家'.$args[0].'不存在');
					break;
			}
		}
	}
}