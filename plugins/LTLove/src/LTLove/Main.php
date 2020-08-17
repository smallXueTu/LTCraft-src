<?php
namespace LTLove;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\entity\Effect;
use pocketmine\scheduler\CallbackTask;
use pocketmine\event\Listener;
use pocketmine\nbt\tag\StringTag;
use pocketmine\event\player\{PlayerJoinEvent,PlayerQuitEvent,PlayerMoveEvent, PlayerDeathEvent};
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\level\sound\EndermanTeleportSound;
use LTPopup\Popup as LTPopup;
class Main extends PluginBase implements Listener{
	public static $instance=null;
	public $Request=[];
	public $PaRequest=[];
	public static function getInstance(){
		return self::$instance;
	}
	const HEAD='§l§3[§aL§dO§cV§eE§3]§';
	public function onEnable(){
		$this->dir=$this->getDataFolder().'players/';
		self::$instance=$this;
		$this->server=$this->getServer();
		$this->getServer()->getPluginManager()->registerEvents($this,$this);
	}
	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
		if(!isset($args[0])){
			$sender->sendMessage('§e--------------§a结婚系统§e--------------');
			$sender->sendMessage('§e求婚--/结婚 求婚 目标ID #§d找一个心爱的玩家结婚');
			$sender->sendMessage('§e拒绝--/结婚 拒绝 #§d拒绝一个玩家的求婚');
			$sender->sendMessage('§e同意--/结婚 同意 #§d同意一个玩家的求婚');
			$sender->sendMessage('§e啪啪--/结婚 啪啪 #§d跟你心爱的人啪啪啪');
			$sender->sendMessage('§e同意啪啪啪--/结婚 同意啪啪 #§d同意你心爱的人啪啪啪请求');
			$sender->sendMessage('§e拒绝啪啪啪--/结婚 拒绝啪啪 #§d同意你心爱的人啪啪啪请求');
			$sender->sendMessage('§e自慰--/结婚 自慰 #§d单身的你,欲望来的时候可以试试');
			$sender->sendMessage('§e选择性别--/结婚 性别 [男,女] #§d选择你的性别');
			return;
		}
		switch($args[0]){
			case '修改性别':
//				if($sender->getName()!=='Angel_XX' AND $sender instanceof Player)return $sender->sendMessage(self::HEAD.'c你没有这个权限！');
				if(count($args)<3)return $sender->sendMessage(self::HEAD.'c用法/结婚 修改性别 玩家 性别!');
				if(($target=$this->getServer()->getPlayer($args[1]))!==null){
					$target->setGender($args[2]);
					LTPopup::getInstance()->updateNameTag($target);
					$sender->sendMessage(self::HEAD.'a修改成功!');
					$target->sendMessage(self::HEAD.'a你被强制变性了!');
                    $target->newProgress('强制被变性', ('我'.($args[2]=='女'?'叽霸':'奶子').'呢？'));
				}else return $sender->sendMessage(self::HEAD.'c玩家不在线!');
			break;
			case '自慰':
				if($sender->getPleasureEvent()!==null){
                    $sender->newProgress('我没感觉', '在与对象啪啪啪的途中尝试自慰。(ta真的给不了你吗？)');
					return $sender->sendMessage(self::HEAD.'c你现在现在的状态不能自慰哦！');
				}
				if($sender->getLevel()->getName()==='boss'){
                    $sender->newProgress('刺激的最高境界','在Boss世界尝试进行自慰。');
					return $sender->sendMessage(self::HEAD.'c什么地方还敢自慰？');
				}
				if($sender->getLinkedEntity()!==null)return $sender->sendMessage(self::HEAD.'c请确认不在骑乘状态！');
				$sender->setPleasureEvent(new PleasureEvent($sender));
			break;
			case '求婚':
				if($sender->getGender()==='未选择')return $sender->sendMessage(self::HEAD.'c请选择性别/结婚 性别 [男,女]!');
				if(!isset($args[1]))return $sender->sendMessage(self::HEAD.'c用法/结婚 §d求婚 目标游戏ID');
				$targer=$this->server->getPlayer($args[1]);
				if(!$targer)return $sender->sendMessage(self::HEAD.'c对方不在线!');
				if($targer->getGender()==='未选择')return $sender->sendMessage(self::HEAD.'c对方性别未知!');
				if($targer->getLove()!=='未婚'){
                    $sender->newProgress('强扭的瓜不甜，但是解渴。', '试图向已婚玩家求婚。');
				    return $sender->sendMessage(self::HEAD.'c对方已经结婚了!');
                }
				if($sender->getLove()!=='未婚'){
                    $love = $sender->getServer()->getPlayerExact($sender->getLove());
                    if ($love!==null and $love!=$targer){
                        $love->newProgress('获得绿帽', '对象试图求婚其他玩家。');
                    }
				    return $sender->sendMessage(self::HEAD.'c你已经结婚了!');
				}
				if($targer===$sender)return $sender->sendMessage(self::HEAD.'c不能跟自己结婚！');
				$this->Request[$targer->getName()]=$sender->getName();
				if($targer->getGender()=='男' and $sender->getGender()=='男'){
					$targer->sendMessage(self::HEAD.'e基佬§d'.$sender->getName().'§e向您求婚啦!');
					$this->server->broadcastMessage('§l§a基佬'.$sender->getName().'向'.$targer->getName().'求婚啦(*^ω^*)！');
					$targer->sendMessage('§l§a同意输入/结婚 同意成为基佬 §c拒绝输入/结婚 拒绝');
				}elseif($targer->getGender($targer->getName())=='女' AND $sender->getGender($sender->getName())=='女'){
					$targer->sendMessage(self::HEAD.'e百合§d'.$sender->getName().'§e向您求婚啦!');
					$this->server->broadcastMessage('§l§a百合'.$sender->getName().'向'.$targer->getName().'求婚啦(*^ω^*)！');
					$targer->sendMessage('§l§a同意输入/结婚 同意成为百合 §c拒绝输入/结婚 拒绝');
				}else{
					$targer->sendMessage(self::HEAD.'e玩家§d'.$sender->getName().'§e向您求婚啦!');
					$this->server->broadcastMessage('§l§a玩家'.$sender->getName().'向'.$targer->getName().'求婚啦(*^ω^*)！');
					$targer->sendMessage('§l§a同意输入/结婚 同意成为夫妇 §c拒绝输入/结婚 拒绝');
				}
			break;
			case '同意':
				if($sender->getLove()!=='未婚')return $sender->sendMessage(self::HEAD.'c你已经结婚了!');
				if(!isset($this->Request[$sender->getName()]))return $sender->sendMessage(self::HEAD.'c还没有人跟你求婚呢|･ω･｀)!');
				$targer=$this->server->getPlayer($this->Request[$sender->getName()]);
				if($targer){
					if($targer->getGender()=='男' AND $sender->getGender()=='男')
						$this->server->broadcastMessage('§l§a恭喜玩家'.$sender->getName().'与'.$targer->getName().'成为基佬,恭喜恭喜!');
					elseif($targer->getGender($targer->getName())=='女' AND $sender->getGender($sender->getName())=='女')
						$this->server->broadcastMessage('§l§a恭喜玩家'.$sender->getName().'与'.$targer->getName().'成为百合(*^ω^*)，祝贺他们吧！');
					else
						$this->server->broadcastMessage('§l§a恭喜玩家'.$sender->getName().'与'.$targer->getName().'成为夫妇(*^ω^*)，恭喜他们吧！');
					$sender->setLove($targer->getName());
					$targer->setLove($sender->getName());
				}else{
					$sender->sendMessage(self::HEAD.'c对方已经离线了( •̥́ ˍ •̀ू )!');
				}
				unset($this->Request[$sender->getName()]);
			break;
			case '拒绝':
				if($sender->getLove()!=='未婚')return $sender->sendMessage(self::HEAD.'c你已经结婚了!');
				if(!isset($this->Request[$sender->getName()]))return $sender->sendMessage(self::HEAD.'c还没有人跟你求婚呢|･ω･｀)!');
				$target=$this->server->getPlayer($this->Request[$sender->getName()]);
				if($target){
                    $sender->newProgress('高攀不起');
					$this->server->broadcastMessage('§l§a玩家'.$sender->getName().'拒绝了'.$target->getName().'的请求˃̣̣̥᷄⌓˂̣̣̥᷅,真扫兴!');
					$target->sendMessage(self::HEAD.'c哎呀,对方拒绝了你,别伤心,再来一次吧！');
				}else $sender->sendMessage(self::HEAD.'c对方已经离线了( •̥́ ˍ •̀ू )!');
				unset($this->Request[$sender->getName()]);
			break;
			case '离婚':
				$love=$sender->getLove();
				if($love==='未婚')return $sender->sendMessage(self::HEAD.'e你还没结婚呢,加油噢＾０＾~!');
				$sender->setLove('未婚');
				$target=$this->server->getPlayer($love);
				if($target){
					$target->setLove('未婚');
					$target->sendMessage(self::HEAD.'e伴侣跟你离婚了ಠ~ಠ!');
				}elseif(file_exists(\pocketmine\DATA .'players/'.strtolower($love).'.dat')){
					$nbt=$this->server->getOfflinePlayerData(strtolower($love));
					$nbt->Love=new StringTag('Love', '已离婚');
					$this->server->saveOfflinePlayerData(strtolower($love), $nbt, true);
				}
				$sender->sendMessage(self::HEAD.'a成功离婚ಠ~ಠ!');
				$this->server->broadcastMessage('§l§a玩家'.$sender->getName().'跟'.$love.'离婚了,一片悲哀(ಥ﹏ಥ) !');
			break;
			case '传送':
				$love=$sender->getLove();
				if($love==='未婚'){
                    $sender->sendMessage(self::HEAD.'e你还没结婚呢!');
                    throw new NotFoundGirlFriendException();
                }
				$targer=$this->server->getPlayer($love);
				if($targer){
					$sender->teleport($targer);
					$sender->sendMessage(self::HEAD.'e成功传送到你伴侣的身边!');
					$targer->sendMessage(self::HEAD.'e你的伴侣传送到你身边啦!');
				}else $sender->sendMessage(self::HEAD.'e你的伴侣不在线!');
			break;
			case '啪啪':
				$love=$sender->getLove();
				if($love==='未婚'){
				    $sender->newProgress('扎心了。');
				    $sender->sendMessage(self::HEAD.'e你还没结婚呢,跟谁啪啪啪呢!');
				    throw new NotFoundGirlFriendException();
                }
				if($sender->getPleasureEvent()!==null){
					return $sender->sendMessage(self::HEAD.'c你现在的状态不能啪啪啪哦！');
				}
				if($sender->getLevel()->getName()==='boss'){
					return $sender->sendMessage(self::HEAD.'c什么地方还敢啪啪？');
				}
				$targer=$this->server->getPlayer($love);
				if($targer){
					$targer->sendMessage(self::HEAD.'e你的伴侣要跟你啪啪啪,同意输入/结婚 同意啪啪 拒绝输入/结婚 拒绝啪啪 你同意吗?');
					$sender->sendMessage(self::HEAD.'a已发送啪啪啪请求,请耐心等待吧(≧ω≦)');
					$this->PaRequest[$targer->getName()]=$sender;
				}else $sender->sendMessage(self::HEAD.'c你伴侣不在线！');
			break;
			case '同意啪啪':
				$love=$sender->getLove();
				if($love==='未婚')return $sender->sendMessage(self::HEAD.'e你还没结婚呢,谁跟你啪啪啪呢!');
				if($sender->getPleasureEvent()!==null){
					return $sender->sendMessage(self::HEAD.'c你现在的状态不能啪啪啪哦！');
				}
				if($sender->getLevel()->getName()==='boss'){
					return $sender->sendMessage(self::HEAD.'c什么地方还敢啪啪？');
				}
				if(isset($this->PaRequest[$sender->getName()])){
					if(!$this->PaRequest[$sender->getName()]->closed){
						if($this->PaRequest[$sender->getName()]->getPleasureEvent()!==null){
							return $sender->sendMessage(self::HEAD.'c你状态现在的状态不能啪啪啪哦！');
						}
						if($sender->getLinkedEntity()!==null and $this->PaRequest[$sender->getName()]->getLinkedEntity()!==null)return $sender->sendMessage(self::HEAD.'c请确认双方不在骑乘状态！');
						$sender->teleport($this->PaRequest[$sender->getName()]);
						$sender->setPleasureEvent(new PleasureEvent($this->PaRequest[$sender->getName()], $sender));
						$this->PaRequest[$sender->getName()]->setPleasureEvent($sender->getPleasureEvent());
						$sender->sendMessage(self::HEAD.'a你接受了伴侣的请求!');
						$this->PaRequest[$sender->getName()]->sendMessage(self::HEAD.'a你伴侣接受了你的请求!');
						unset($this->PaRequest[$sender->getName()]);
					}else{
						$sender->sendMessage(self::HEAD.'c你伴侣不在线了！');
						unset($this->PaRequest[$sender->getName()]);
					}
				}else return $sender->sendMessage(self::HEAD.'c你伴侣没申请跟你啪啪啪');
			break;
			case '拒绝啪啪':
				$love=$sender->getLove();
				if($love==='未婚')return $sender->sendMessage(self::HEAD.'e你还没结婚呢,谁跟你啪啪啪呢!');
				if(isset($this->PaRequest[$sender->getName()])){
					if(!$this->PaRequest[$sender->getName()]->closed){
						$sender->sendMessage(self::HEAD.'c你拒绝了伴侣的请求!');
						unset($this->PaRequest[$sender->getName()]);
						if($this->PaRequest[$sender->getName()])$this->PaRequest[$sender->getName()]->sendMessage(self::HEAD.'a你伴侣拒绝了你的请求!');
					}else{
						$sender->sendMessage(self::HEAD.'c你伴侣不在线了！');
						unset($this->PaRequest[$sender->getName()]);
					}
				}else return $sender->sendMessage(self::HEAD.'c你伴侣没申请跟你啪啪啪');
			break;
		case '强奸':
			if(!isset($args[1]))return $sender->sendMessage(self::HEAD.'c用法/结婚 强奸 目标ID');
			if($sender->getPleasureEvent()!==null){
				return $sender->sendMessage(self::HEAD.'c你状态现在的状态不能强奸哦！');
			}
			if($sender->isOp()){
				$targer=$this->getServer()->getPlayer($args[1]);
				if($targer===null)return $sender->sendMessage(self::HEAD.'c目标不在线！');
				$sender->teleport($targer);
				$this->PleasureEvents[]=new PleasureEvent($targer, $sender);
				$sender->sendMessage(self::HEAD.'a成功强奸'.$targer->getName().'!');
				$targer->sendMessage(self::HEAD.'c'.$sender->getName().'强奸了你！!');
			}else{
                $sender->newProgress('忍无可忍', '尝试想强奸一个玩家。');
            }
		break;
		case '性别':
			if(!isset($args[1]) or ($args[1] !== '男' and $args[1] !== '女'))return $sender->sendMessage(self::HEAD.'c请选择男 or 女！');
			if($sender->getGender()!=='未选择'){
                $sender->newProgress('变性', '尝试变性，但这个世界并不允许。');
				return $sender->sendMessage(self::HEAD.'c你已经选过性别了!');
			}
			LTPopup::getInstance()->updateNameTag($sender);
			$sender->setGender($args[1]);
			$sender->sendMessage(self::HEAD.'a成功选择你的性别为:'.$args[1]);
		break;
		default:
			$sender->sendMessage('§e--------------§a结婚系统§e--------------');
			$sender->sendMessage('§e求婚--/结婚 求婚 目标ID #§d找一个心爱的玩家结婚');
			$sender->sendMessage('§e拒绝--/结婚 拒绝 #§d拒绝一个玩家的求婚');
			$sender->sendMessage('§e同意--/结婚 同意 #§d同意一个玩家的求婚');
			$sender->sendMessage('§e啪啪--/结婚 啪啪 #§d跟你心爱的人啪啪啪');
			$sender->sendMessage('§e同意啪啪啪--/结婚 同意啪啪 #§d同意你心爱的人啪啪啪请求');
			$sender->sendMessage('§e拒绝啪啪啪--/结婚 拒绝啪啪 #§d同意你心爱的人啪啪啪请求');
			$sender->sendMessage('§e自慰--/结婚 自慰 #§d单身的你,欲望来的时候可以试试');
			$sender->sendMessage('§e性别--/结婚 性别 [男,女] #§d选择你的性别');
		break;
		}
	}
	public function onQuitEvent(PlayerQuitEvent $event){
		$player=$event->getPlayer();
		$name=$player->getName();
		if($player->getPleasureEvent()!==null){
			$player->getPleasureEvent()->Exception('一方退出了游戏 导致了性行为结束！');
		}
		unset($this->Request[$name],$this->PaRequest[$name]);
	}
	public function onEntityTeleport(EntityTeleportEvent $event){
		$player=$event->getEntity();
		if($player instanceof Player){
			if($player->getPleasureEvent()!==null){
				$player->getPleasureEvent()->Exception('一方传送 导致了性行为结束！');
			}
		}
	}
	public function onPlayerDeathEvent(PlayerDeathEvent $event){
		$player=$event->getPlayer();
		if($player->getPleasureEvent()!==null){
			$player->getPleasureEvent()->Exception('一方死亡 导致了性行为结束！');
		}
	}
}