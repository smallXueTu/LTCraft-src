<?php
namespace Reward;

use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\sound\ExplodeSound;
use pocketmine\level\particle\HugeExplodeSeedParticle;
use pocketmine\math\Vector3;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\item\Item;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\UUID;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\scheduler\CallbackTask;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\network\protocol\AddItemEntityPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\network\protocol\SetEntityLinkPacket;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\network\protocol\InteractPacket;
use onebone\economyapi\EconomyAPI;
use pocketmine\network\protocol\PlayerListPacket;
use LTPet\Main as LTPet;
use LTItem\Main as LTItem;
use LTGrade\Main as LTGrade;
use pocketmine\level\sound\BlazeShootSound;
class Main extends PluginBase implements Listener{
	public static $eid;
	public static $pk=null;
	public static $see=null;
	public static $s=0;
	public function onEnable(){
		$this->server=$this->getServer();
		$this->server->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this,"sendDrop"]),10);
		@mkdir($this->getDataFolder()); 
		$this->Player=new Config($this->getDataFolder().'Players.yml', Config::YAML,array());
		$this->server->getPluginManager()->registerEvents($this,$this);
		self::$pk=$this->registerNPC();
		$this->rPos=new Position(743.5,13.76,23.5,$this->server->getLevelByName('zc'));
		$this->rewardItem=array(
			Item::get(320,0,32),
			Item::get(400,0,16),
			Item::get(133),
			Item::get(57),
			Item::get(393,0,12),
			Item::get(375,0,3),
			Item::get(354,0,3),
			Item::get(335,0,1),
			Item::get(366,0,12),
			Item::get(396,0,2),
			Item::get(89,0,64),
			Item::get(350,1,32),
			Item::get(344,0,12),
			Item::get(368,0,32),
			Item::get(357,0,12)
		);
		$this->jeTItem=array(
			Item::get(322,1),
			Item::get(51),
			Item::get(264),
			Item::get(264),
			Item::get(384),
			Item::get(388),
			Item::get(382),
			Item::get(399),
			Item::get(320),
			Item::get(400),
			Item::get(57),
			Item::get(133),
			Item::get(276),
			Item::get(272),
			Item::get(122)
		);
	}
	public function onQuit(PlayerQuitEvent $event){
		if($event->getPlayer()->getName()===self::$see){
			self::$see=null;
		}
		unset($event);
	}
	public function onLevelChange(EntityLevelChangeEvent $event){
		if($event->getEntity() instanceof Player){
			if($event->getTarget()->getName()=='zc'){
				$event->getEntity()->dataPacket(self::$pk);
				$event->getEntity()->dataPacket($this->skin);
				$event->getEntity()->dataPacket($this->pf);
			}else{
				$player=$event->getEntity();
				$pk=new RemoveEntityPacket();
				$pk->eid=self::$eid;
				$player->dataPacket($pk);
				$pk = new PlayerListPacket();
				$pk->type = PlayerListPacket::TYPE_REMOVE;
				$pk->entries[] = [$this->uid];
				$player->dataPacket($pk);
				$name=$player->getName();
				if($event->getOrigin()->getName()=='zc'){
					if($name==self::$see){
						self::$see=null;
					}
				}
			}
		}
		unset($event,$name,$player);
	}
	public function onInteract(DataPacketReceiveEvent $event){
		$pk=$event->getPacket();
		if($pk instanceof InteractPacket){
			if($pk->action == InteractPacket::ACTION_LEFT_CLICK){
				if($pk->target==self::$eid){
					$player=$event->getPlayer();
					if($player->isOp() or $player->getGamemode()!==0)return $player->sendTitle('§l§cOP和非生存不可以参与这个抽奖');
					$name=strtolower($player->getName());
					self::$see=$player->getName();
					$hand=$player->getItemInHand();
					if(LTItem::getInstance()->isEquals($hand, ['材料', '新春红包'])){
						if(time()<1579870800){
							$player->sendTitle('§l§c还没到时间呢', '§l§e请1月24号21点后再来开启吧~');
							return;
						}
						// if(time()>1549382399){
							// $player->sendTitle('§l§c你这个红包已经过期了！！');
							// return;
						// }
						self::$s+=8*5;
						if(self::$s>80)self::$s=80;
							$rand=mt_rand(1,100);
							if($rand>=90){
								switch(mt_rand(1,9)){
								case 1:
								case 2://鸡
								case 3:
									$player->sendTitle('§l§a运气不错,抽到了宠物鸡~');
									LTPet::getInstance()->addPet($player,'鸡','鸡'.mt_rand(1,999));
									$this->getServer()->broadcastMessage('§l§a玩家'.$player->getName().'打开红包后获得了鸡!!');
								break;
								case 4:
								case 5:
									$player->sendTitle('§l§a运气不错,抽到了羊驼~');
									LTPet::getInstance()->addPet($player,'羊驼','羊驼'.mt_rand(1,999));
									$this->getServer()->broadcastMessage('§l§a玩家'.$player->getName().'打开红包后获得了羊驼!!');
								case 7:
								case 6:
								case 8://猪
									$player->sendTitle('§l§a人气爆发,抽到了宠物猪~');
									LTPet::getInstance()->addPet($player,'猪','猪'.mt_rand(1,999));
									$this->getServer()->broadcastMessage('§l§a玩家'.$player->getName().'打开红包后获得了猪!!');
								break;
								case 9://女仆
									$player->sendTitle('§l§a人气大爆发不错,抽到了女仆！！');
									LTPet::getInstance()->addPet($player,'女仆','女仆'.mt_rand(1,999));
									$this->getServer()->broadcastMessage('§l§a玩家'.$player->getName().'打开红包后获得了女仆!!');
								break;
								}
							}elseif($rand>=70){
								$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战','鼠年神器',$player));
								$player->sendTitle('§l§a恭喜你 稀有神器','§d抽到了鼠年神器~');
								$this->getServer()->broadcastMessage('§l§a玩家'.$player->getName().'打开红包后获得了鼠年神器!!');
							}elseif($rand>=40){
								switch(mt_rand(1,10)){
								case 1:
								case 2:
									$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战','神龙之刃-中秋节',$player));
									$player->sendTitle('§l§a运气不错,抽到了神龙之刃-中秋节~');
									$this->getServer()->broadcastMessage('§l§a玩家'.$player->getName().'打开红包后获得了神龙之刃-中秋节!!');
								case 3:
									$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战','猪年神器',$player));
									$player->sendTitle('§l§a运气不错,抽到了猪年神器~');
									$this->getServer()->broadcastMessage('§l§a玩家'.$player->getName().'打开红包后获得了猪年神器!!');
								break;
								case 6:
								case 7:
									$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战','青月神剑',$player));
									$player->sendTitle('§l§a运气不错,抽到了青月神剑~');
									$this->getServer()->broadcastMessage('§l§a玩家'.$player->getName().'打开红包后获得了青月神剑!!');
								case 4:
								case 8:
									$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战','暗影金剑',$player));
									$player->sendTitle('§l§a运气不错,抽到了暗影金剑~');
									$this->getServer()->broadcastMessage('§l§a玩家'.$player->getName().'打开红包后获得了暗影金剑!!');
								break;
								case 9:
								case 10:
									if(mt_rand(0,10)>8){
										$item=LTItem::getInstance()->createWeapon('近战','神秘之剑',$player);
										$player->getInventory()->addItem($item);
										$player->sendTitle('§l§a运气大爆发','§d开到了神秘之剑~');
									}else{
										$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战','狗年神器',$player));
										$player->sendTitle('§l§a运气不错','§d开到了狗年神器~');
										$this->getServer()->broadcastMessage('§l§a玩家'.$player->getName().'打开红包后获得了狗年神器!!');
									}
								break;
								case 5:
									if(mt_rand(0,10)>8){
										$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('远程','青龙剑',$player));
										$player->sendTitle('§l§a恭喜你 稀有神器,开到了远程青龙剑~');
										$this->getServer()->broadcastMessage('§l§a玩家'.$player->getName().'打开红包后开获得稀有神器!!');
									}else{
										$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战','中秋节神龙宝刀',$player));
										$player->sendTitle('§l§a运气不错,开到了中秋节神龙宝刀~');
										$this->getServer()->broadcastMessage('§l§a玩家'.$player->getName().'打开红包后获得了中秋节神龙宝刀!!');
									}
								break;
								}
							}elseif($rand>30){
								$player->getInventory()->addItem(LTItem::getInstance()->createMaterial('奖励箱-基因'));
								$player->sendTitle('§l§a恭喜你','§d抽到了奖励箱-基因~');
								$this->getServer()->broadcastMessage('§l§a玩家'.$player->getName().'打开红包后获得了奖励箱-基因!');
							}elseif($rand>15){
								$player->getInventory()->addItem(LTItem::getInstance()->createMaterial('Bigger！Bigger！Bigger！'));
								$player->sendTitle('§l§a恭喜你','§d抽到了Bigger！Bigger！Bigger！');
								$this->getServer()->broadcastMessage('§l§a玩家'.$player->getName().'打开红包后获得了Bigger！Bigger！Bigger！!');
							}elseif($rand>8){
								$money=mt_rand(50000,1000000);
								$player->sendTitle('§l§a运气不错,开到了'.$money.'橙币');
								EconomyAPI::getInstance()->addMoney($player, $money);
							}else{
								if($player->getGrade()<300){
									$exp=mt_rand(500,4000);
									$player->addExp($exp);
									$player->sendTitle('§l§a恭喜获得'.$exp.'点经验！');
								}else{
									$money=mt_rand(50000,1000000);
									$player->sendTitle('§l§a运气不错,开到了'.$money.'橙币');
									EconomyAPI::getInstance()->addMoney($player, $money);
								}
							}
						$hand->setCount($hand->getCount()-1);
						$player->getInventory()->setItemInHand($hand);
						$player->sendMessage('§l§aLTCraft祝你新年快乐！ 小小心意请收下吧！');
						return;
					}elseif(LTItem::getInstance()->isEquals($hand, ['材料', '新春大红包'])){
						if(time()<1579870800){
							$player->sendTitle('§l§c还没到时间呢', '§l§e请1月24号21点后再来开启吧~');
							return;
						}
						self::$s+=8*5;
						if(self::$s>80)self::$s=80;
						$rand=mt_rand(1,10);
						switch($rand){
							case 1:
							case 4:
								$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战','鼠年神器',$player));
								$player->sendTitle('§l§a恭喜你 稀有神器','§d抽到了鼠年神器~');
								$this->getServer()->broadcastMessage('§l§a玩家'.$player->getName().'打开大红包后获得了鼠年神器!!');
							break;
							case 2:
							case 5:
								$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('远程','时空劫持者',$player));
								$player->sendTitle('§l§a恭喜你 稀有神器','§d抽到了时空劫持者~');
								$this->getServer()->broadcastMessage('§l§a玩家'.$player->getName().'打开大红包后获得了时空劫持者!!');
							break;
							case 3:
							case 6:
								$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战','时空之刃',$player));
								$player->sendTitle('§l§a恭喜你 稀有神器','§d抽到了时空之刃~');
								$this->getServer()->broadcastMessage('§l§a玩家'.$player->getName().'打开大红包后获得了时空之刃!!');
							break;
							case 7:
							case 8:
							case 9:
							case 10:
								// $player->getInventory()->addItem(LTItem::getInstance()->createMaterial('奖励箱-基因'));
								// $player->sendTitle('§l§a恭喜你','§d抽到了奖励箱-基因~');
								// $this->getServer()->broadcastMessage('§l§a玩家'.$player->getName().'打开红包后获得了奖励箱-基因!');
								$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战','寒冰之斧',$player));
								$player->sendTitle('§l§a恭喜你 稀有神器','§d抽到了寒冰之斧~');
								$this->getServer()->broadcastMessage('§l§a玩家'.$player->getName().'打开大红包后获得了寒冰之斧!!');
								
							break;
						}
						$hand->setCount($hand->getCount()-1);
						$player->getInventory()->setItemInHand($hand);
						$player->sendMessage('§l§aLTCraft祝你新年快乐！ 大大心意请收下吧！');
						return;
					}
					if($player->getGrade()<10)return $player->sendMessage('§a[§d每§3日§5抽§4奖§a]§c你需要大于等于10级噢~');
					if($this->Player->exists($name)){
						$status=$this->Player->get($name);
						if(isset($status[date("Y-m-d")])){
							// $player->sendMessage('§a[§d每§3日§5抽§4奖§a]§c您今天已经抽过奖了，明天再来吧~');
							// $level=$this->server->getLevelByName('zc');
							// $level->addParticle(new HugeExplodeSeedParticle($this->rPos));
							// $level->addSound(new ExplodeSound($this->rPos));
							// $player->knockBack($player,0,$player->x - 741.5,$player->z - 22.5 ,1);
							$this->Reward($player);
							self::$s+=8*5;
							if(self::$s>80)self::$s=80;
						}else{
							$this->Reward($player, true);
							$status[date("Y-m-d")]=true;
							$this->Player->set($name,$status);
							$this->Player->save();
							self::$s+=8*5;
							if(self::$s>80)self::$s=80;
						}
					}else{
						$this->Reward($player, true);
						$this->Player->set($name,array());
						$this->Player->save();
						self::$s+=8*5;
						if(self::$s>80)self::$s=80;
					}
				}
			}
		}
	}
	public function onJoin(PlayerJoinEvent $event){
		if(self::$pk===null)return;
		if($event->getPlayer()->getLevel()->getName()=='zc'){
			$event->getPlayer()->dataPacket(self::$pk);
			$event->getPlayer()->dataPacket($this->skin);
			$event->getPlayer()->dataPacket($this->pf);
			unset($event);
			return;
		}
		unset($event);
		return;
	}
	public function onMoveEvent(PlayerMoveEvent $event){
		if(self::$pk===null)return;
		$player=$event->getPlayer();
		if($player->getLevel()->getName()!=='zc')return;
		$name=$player->getName();
		if(self::$see!=null){
			if(self::$see==$name){
				if($this->rPos->distance($player)<=5){
					$this->seePlayer($player);
					return;
				}else{
					self::$see=null;
					return;
				}
			}
			return;
		}
		if($this->rPos->distance($player)<=10){
			self::$see=$name;
			$this->seePlayer($player);
			return;
		}
		return;
	}
	public function seePlayer($player){//看玩家算法
		$x=743.5-$player->getX();
		$y=12-$player->getY();
		$z=23.5-$player->getZ();
		if($x==0 and $z==0){
			$yaw = 0;
			$pitch = $y>0?-90:90;
			if($y==0)$pitch=0;
		}else{
			$yaw=asin($x/sqrt($x*$x+$z*$z))/3.14*180;
			$pitch=round(asin($y/sqrt($x*$x+$z*$z+$y*$y))/3.14*180);
		}
		if($z>0){
			$yaw=-$yaw+180;
		}
		$pk=new MovePlayerPacket();
		$pk->eid=self::$eid;
		$pk->x=743.5;
		$pk->y=13.62;
		$pk->z=23.5;
		$pk->bodyYaw=$yaw;
		$pk->pitch=$pitch;
		$pk->yaw=$yaw;
		$pk->mode=0;
		$level=$this->server->getLevelByName('zc');
		$level->addChunkPacket($pk->x >> 4, $pk->z >> 4, $pk);
		/*foreach($this->server->getOnlinePlayers() as $p){
			if($p->getLevel()->getName()=='zc'){
				$p->dataPacket($pk);
			}
		}*/
	}
	public function registerNPC(){
// $s=0;
// do
			// $this->drops[]=Entity::$entityCount++;
// while(++$s<20);
		self::$eid=Entity::$entityCount++;
		$this->uid=UUID::fromRandom();
		$pk=new AddPlayerPacket();
		$pk->uuid=$this->uid;
		$pk->username='LTCraft';
		$pk->eid=self::$eid;
		$pk->x=743.5;
		$pk->y=12;
		$pk->z=23.5;
		$pk->speedX=0;
		$pk->speedY=0;
		$pk->speedZ=0;
		$pk->yaw='';
		$pk->pitch='';
		$pk->item=Item::get(0);
		$flags = 0;
		$flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
		$flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
		$flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
		$pk->metadata = [
		 Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
		 Entity::DATA_AIR => [Entity::DATA_TYPE_SHORT, 400],
		 Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, '§l§2<<§d橙§e币§a抽§4奖§2>>'],
		 Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1],
		];
		$skinData='AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD/nGk1/4JXLf+caTX/glct/4JXLf+PYDH/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA/4JXLf+caTX/j2Ax/4JXLf+PYDH/nGk1/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP+caTX/glct/49gMf+caTX/nGk1/5xpNf8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD/j2Ax/4JXLf+caTX/nGk1/49gMf+CVy3/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA/4JXLf+caTX/nGk1/4JXLf+PYDH/nGk1/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP+caTX/glct/49gMf+caTX/glct/5xpNf8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/glct/5xpNf+PYDH/nGk1/49gMf+CVy3/AAAA/wAAAP+PYDH/nGk1/5xpNf+PYDH/glct/5xpNf8AAAD/AAAA/5xpNf+caTX/nGk1/49gMf+CVy3/nGk1/wAAAP8AAAD/nGk1/49gMf+CVy3/nGk1/49gMf+CVy3/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/wAAAP8AAAD/AAAA/49gMf+caTX/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AH19/wB9ff8AfX3/AJyc/wCmpv8Ara3/AKam/wB9ff8AfX3/AH19/wB9ff8Ara3/AK2t/wCtrf8Ara3/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAB4eHv8AAAAAAAAAAB4eHv8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAeHh7/Hh4e/x4eHv8eHh7/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAra3/AK2t/wCtrf8Ara3/JiYm/yYmJv8mJib/JiYm/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAaD4u/08vI/9oPi7/Ty8j/wCmpv8Aysr/AMrK/wCmpv8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA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';
		$skin=new PlayerListPacket();
		$skin->type = PlayerListPacket::TYPE_ADD;
		$skin->entries[] = [$this->uid, self::$eid, '§l§2<<§d橙§e币§a抽§4奖§2>>', 'Standard_Steve', base64_decode($skinData)];
		$this->skin=$skin;
		$pf=new PlayerListPacket();
		$pf->type = PlayerListPacket::TYPE_ADD;
		$pf->entries[] = [$this->uid, self::$eid, '§l§2<<§d橙§e币§a抽§4奖§2>>', 'Minecon_MineconSteveCape2012', base64_decode($skinData)];
		$this->pf=$pf;
		unset($npc,$flags,$skin);
		return $pk;
	}
	public function sendDrop(){
		if(count($this->server->getOnlinePlayers())==0 or $this->getServer()->getTicksPerSecondAverage()<18 or self::$pk===null or self::$s<8)return;
		self::$s-=8;
		$eids=[];
		$pk = new AddItemEntityPacket();
		$pk->x=743.5;
		$pk->y=13.62;
		$pk->z=23.5;
		$pk->yaw = 0;
		$pk->pitch = 0;
		$pk->roll = 0;
		for($i=0;$i<20;++$i){
			$eid = Entity::$entityCount++;;
			$eids[] = $eid;
			$pk->eid = $eid;
			$pk->item = $this->randItem();
			$pk->speedX = -0.2 + mt_rand()/mt_getrandmax()*(0.2 - -0.2);
			$pk->speedY = 0.5;
			$pk->speedZ = -0.2 + mt_rand()/mt_getrandmax()*(0.2 - -0.2);
			$level=$this->server->getLevelByName('zc');
			$level->addChunkPacket($pk->x >> 4, $pk->z >> 4, clone $pk);
			if($i%5==0)$level->addSound(new BlazeShootSound(new Vector3(741.5, 4.62, 22.5)));
		}	
		$this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"remove"],[$eids]), 75);
		unset($pk);
	}
	public function remove($eids){
		$pk=[];
		foreach($eids as $i=>$v){
			$pk[$i]=new RemoveEntityPacket();
			$pk[$i]->eid=$v;
		}
		foreach($this->server->getOnlinePlayers() as $player)
			foreach($pk as $p)
				$player->dataPacket($p);
	}
	public function randItem(){
		return $this->jeTItem[mt_rand(0,14)];
	}
	public function Reward($player, $first = false){
		$grade=$player->getGrade();
		$rand=mt_rand(1,100);
		switch(true){
			case $grade<50:
				if($first or $player->getMoney()>100000){
					if(!$first)$player->reduceMoney(100000, '抽奖');
					switch(true){
						case $rand>98:
							$player->sendTitle('§l§a运气不错,抽到了宠物鸡~');
							LTPet::getInstance()->addPet($player,'鸡','鸡'.mt_rand(1,999));
						break;
						case $rand>=90:
							$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战','暗影金剑',$player));
							$player->sendTitle('§l§a运气不错,抽到了暗影金剑~');
                        break;
						case $rand>=75:
							$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战','神秘之剑',$player));
							$player->sendTitle('§l§a运气不错,抽到了神秘之剑~');
						break;
						case $rand>=50:
							$money=mt_rand(5000,200000);
							$player->sendTitle('§l§a运气不错,抽到了'.$money.'橙币');
							EconomyAPI::getInstance()->addMoney($player, $money, '抽奖获得');
						break;
						case $rand>=30:
							$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战','欲火刃',$player));
							$player->sendTitle('§l§a运气不错,抽到了欲火刃~');
						break;
						case $rand>=15:
							$item=$this->rewardItem[mt_rand(0,13)];
							$player->getInventory()->addItem($item);
							$player->sendTitle('§l§a抽到了'.$item->getName().'×'.$item->getCount().' 请查收！');
						break;
						case $rand>=0:
							$exp=mt_rand(5,800);
							$player->addExp($exp);
							$player->sendTitle('§l§a恭喜获得'.$exp.'点经验！');
						break;
					}
				}else{
					$player->sendTitle('§l§c橙币余额不足~');
				}
			break;
			case $grade<100:
				if($first or $player->getMoney()>300000){
					if(!$first)$player->reduceMoney(300000, '抽奖');
					switch(true){
						case $rand>98:
							$player->sendTitle('§l§a运气不错,抽到了宠物狼~');
							LTPet::getInstance()->addPet($player,'狼','狼'.mt_rand(1,999));
						break;
						case $rand>=90:
							$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('远程','青龙剑',$player));
							$player->sendTitle('§l§a运气不错,抽到了远程青龙剑~');
                        break;
						case $rand>=80:
							$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战', '生化之裁',$player));
							$player->sendTitle('§l§a运气不错,抽到了生化之裁~');
						break;
						case $rand>=70:
							$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战', '屠龙剑',$player));
							$player->sendTitle('§l§a运气不错,抽到了屠龙剑~');
						break;
						case $rand>=60:
							$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战', '冰雪之刃',$player));
							$player->sendTitle('§l§a运气不错,抽到了冰雪之刃~');
						break;
						case $rand>=50:
							$money=mt_rand(10000,500000);
							$player->sendTitle('§l§a运气不错,抽到了'.$money.'橙币');
							EconomyAPI::getInstance()->addMoney($player, $money, '抽奖获得');
						break;
						case $rand>=30:
							$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战','风暴手斧',$player));
							$player->sendTitle('§l§a运气不错,抽到了风暴手斧~');
						break;
						case $rand>=15:
							$item=$this->rewardItem[mt_rand(0,13)];
							$player->getInventory()->addItem($item);
							$player->sendTitle('§l§a抽到了'.$item->getName().'×'.$item->getCount().' 请查收！');
						break;
						case $rand>=0:
							$exp=mt_rand(50,2000);
							$player->addExp($exp);
							$player->sendTitle('§l§a恭喜获得'.$exp.'点经验！');
						break;
					}
				}else{
					$player->sendTitle('§l§c橙币余额不足~');
				}
			break;
			case $grade<150:
				if($first or $player->getMoney()>500000){
					if(!$first)$player->reduceMoney(500000, '抽奖');
					switch(true){
						case $rand>98:
							$player->sendTitle('§l§a运气不错,抽到了宠物羊~');
							LTPet::getInstance()->addPet($player,'羊','羊'.mt_rand(1,999));
						break;
						case $rand>=90:
							$player->getInventory()->addItem(LTItem::getInstance()->createMaterial('碎片熔炼坛碎片'));
							$player->sendTitle('§l§a运气不错,抽到了碎片熔炼坛碎片×1~');
                        break;
						case $rand>=85:
							$player->getInventory()->addItem(LTItem::getInstance()->createMaterial('熔炼残渣'));
							$player->sendTitle('§l§a运气不错,抽到了熔炼残渣×1~');
						break;
						case $rand>=75:
							$item=LTItem::getInstance()->createMaterial('高级武器碎片');
							$item->setCount(1);
							$player->getInventory()->addItem($item);
							$player->sendTitle('§l§a运气不错,抽到了高级武器碎片×1~');
						break;
						case $rand>=50:
							$money=mt_rand(100000,800000);
							$player->sendTitle('§l§a运气不错,抽到了'.$money.'橙币');
							EconomyAPI::getInstance()->addMoney($player, $money, '抽奖获得');
						break;
						case $rand>=30:
							$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战','青干剑',$player));
							$player->sendTitle('§l§a运气不错,抽到了青干剑~');
						break;
						case $rand>=15:
							$item=$this->rewardItem[mt_rand(0,13)];
							$player->getInventory()->addItem($item);
							$player->sendTitle('§l§a抽到了'.$item->getName().'×'.$item->getCount().' 请查收！');
						break;
						case $rand>=0:
							$exp=mt_rand(100,4000);
							$player->addExp($exp);
							$player->sendTitle('§l§a恭喜获得'.$exp.'点经验！');
						break;
					}
				}else{
					$player->sendTitle('§l§c橙币余额不足~');
				}
			break;
			case $grade<200:
				if($first or $player->getMoney()>800000){
					if(!$first)$player->reduceMoney(800000, '抽奖');
					switch(true){
						case $rand>99:
							$player->sendTitle('§l§a运气不错,抽到了宠物女仆~');
							LTPet::getInstance()->addPet($player,'女仆','女仆'.mt_rand(1,999));
						break;
						case $rand>=90:
							$item=LTItem::getInstance()->createMaterial('碎片熔炼坛碎片');
							$item->setCount(3);
							$player->getInventory()->addItem($item);
							$player->sendTitle('§l§a运气不错,抽到了碎片熔炼坛碎片×3~');
                        break;
						case $rand>=85:
							$item=LTItem::getInstance()->createMaterial('熔炼残渣');
							$item->setCount(3);
							$player->getInventory()->addItem($item);
							$player->sendTitle('§l§a运气不错,抽到了熔炼残渣×3~');
						break;
						case $rand>=75:
							$item=LTItem::getInstance()->createMaterial('觉醒石碎片');
							$item->setCount(3);
							$player->getInventory()->addItem($item);
							$player->sendTitle('§l§a运气不错,抽到了觉醒石碎片×3~');
						break;
						case $rand>=50:
							$money=mt_rand(300000, 1000000);
							$player->sendTitle('§l§a运气不错,抽到了'.$money.'橙币');
							EconomyAPI::getInstance()->addMoney($player, $money, '抽奖获得');
						break;
						case $rand>=30:
							$player->getInventory()->addItem(LTItem::getInstance()->createMaterial('魔法棍'));
							$player->sendTitle('§l§a运气不错,抽到了魔法棍~');
						break;
						case $rand>=15:
							$item=$this->rewardItem[mt_rand(0,13)];
							$player->getInventory()->addItem($item);
							$player->sendTitle('§l§a抽到了'.$item->getName().'×'.$item->getCount().' 请查收！');
						break;
						case $rand>=0:
							$exp=mt_rand(500,8000);
							$player->addExp($exp);
							$player->sendTitle('§l§a恭喜获得'.$exp.'点经验！');
						break;
					}
				}else{
					$player->sendTitle('§l§c橙币余额不足~');
				}
			break;
			case $grade<250:
				if($first or $player->getMoney()>1000000){
					if(!$first)$player->reduceMoney(1000000, '抽奖');
					switch(true){
						case $rand>99:
							$player->sendTitle('§l§a运气不错,抽到了宠物女仆~');
							LTPet::getInstance()->addPet($player,'女仆','女仆'.mt_rand(1,999));
						break;
						case $rand>=90:
							$item=LTItem::getInstance()->createMaterial('碎片熔炼坛碎片');
							$item->setCount(6);
							$player->getInventory()->addItem($item);
							$player->sendTitle('§l§a运气不错,抽到了碎片熔炼坛碎片×6~');
                        break;
						case $rand>=85:
							$item=LTItem::getInstance()->createMaterial('熔炼残渣');
							$item->setCount(6);
							$player->getInventory()->addItem($item);
							$player->sendTitle('§l§a运气不错,抽到了熔炼残渣×6~');
						break;
						case $rand>=75:
							$item=LTItem::getInstance()->createMaterial('觉醒石碎片');
							$item->setCount(6);
							$player->getInventory()->addItem($item);
							$player->sendTitle('§l§a运气不错,抽到了觉醒石碎片×6~');
						break;
						case $rand>=50:
							$money=mt_rand(500000,1500000);
							$player->sendTitle('§l§a运气不错,抽到了'.$money.'橙币');
							EconomyAPI::getInstance()->addMoney($player, $money, '抽奖获得');
						break;
						case $rand>=30:
							$player->getInventory()->addItem(LTItem::getInstance()->createMaterial('魔法棍'));
							$player->sendTitle('§l§a运气不错,抽到了魔法棍~');
						break;
						case $rand>=15:
							$item=$this->rewardItem[mt_rand(0,13)];
							$player->getInventory()->addItem($item);
							$player->sendTitle('§l§a抽到了'.$item->getName().'×'.$item->getCount().' 请查收！');
						break;
						case $rand>=0:
							$exp=mt_rand(1000,10000);
							$player->addExp($exp);
							$player->sendTitle('§l§a恭喜获得'.$exp.'点经验！');
						break;
					}
				}else{
					$player->sendTitle('§l§c橙币余额不足~');
				}
			break;
			default:
				if($first or $player->getMoney()>1500000){
					if(!$first)$player->reduceMoney(1500000, '抽奖');
					switch(true){
						case $rand>99:
							$player->sendTitle('§l§a运气不错,抽到了宠物凋零~');
							LTPet::getInstance()->addPet($player,'凋零','凋零'.mt_rand(1,999));
						break;
						case $rand>=98:
							$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战','中秋节神龙宝刀',$player));
							$player->sendTitle('§l§a运气不错,抽到了中秋节神龙宝刀~');
                        break;
						case $rand>=95:
							$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战','狗年神器',$player));
							$player->sendTitle('§l§a运气不错,抽到了狗年神器~');
						break;
						case $rand>=90:
							$player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战','猪年神器',$player));
							$player->sendTitle('§l§a运气不错,抽到了猪年神器~');
						break;
						case $rand>=85:
							$player->getInventory()->addItem(LTItem::getInstance()->createMaterial('觉醒石'));
							$player->sendTitle('§l§a运气不错,抽到了觉醒石~');
						break;
						case $rand>=82:
							$player->getInventory()->addItem(LTItem::getInstance()->createMaterial('史诗武器碎片'));
							$player->sendTitle('§l§a运气不错,抽到了史诗武器碎片×1~');
						break;
						case $rand>=75:
							$player->getInventory()->addItem(LTItem::getInstance()->createMaterial('武器解绑水晶'));
							$player->sendTitle('§l§a运气不错,抽到了武器解绑水晶~');
						break;
						case $rand>=70:
							$player->getInventory()->addItem(LTItem::getInstance()->createMaterial('盔甲解绑水晶'));
							$player->sendTitle('§l§a运气不错,抽到了盔甲解绑水晶~');
						break;
						case $rand>=50:
							$money=mt_rand(800000,2000000);
							$player->sendTitle('§l§a运气不错,抽到了'.$money.'橙币');
							EconomyAPI::getInstance()->addMoney($player, $money, '抽奖获得');
						break;
						case $rand>=40:
							$player->getInventory()->addItem(LTItem::getInstance()->createMaterial('皮肤碎片'));
							$player->sendTitle('§l§a运气不错,抽到了皮肤碎片~');
						break;
						case $rand>=30:
							$player->getInventory()->addItem(LTItem::getInstance()->createMaterial('SS级宠物碎片'));
							$player->sendTitle('§l§a运气不错,抽到了SS级武器碎片~');
						break;
						case $rand>=15:
							$item=$this->rewardItem[mt_rand(0,13)];
							$player->getInventory()->addItem($item);
							$player->sendTitle('§l§a抽到了'.$item->getName().'×'.$item->getCount().' 请查收！');
						break;
						case $rand>=0:
							$exp=mt_rand(3000,20000);
							$player->addExp($exp);
							$player->sendTitle('§l§a恭喜获得'.$exp.'点经验！');
						break;
					}
				}else{
					$player->sendTitle('§l§c橙币余额不足~');
				}
			break;
		}
	}
}