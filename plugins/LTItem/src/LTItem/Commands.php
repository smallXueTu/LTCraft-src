<?php
namespace LTItem;
use LTItem\Mana\Mana;
use pocketmine\Player;
use pocketmine\entity\Effect;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\item\Item;
use LTItem\SpecialItems\Weapon;
use LTItem\SpecialItems\Armor;
use LTItem\SpecialItems\Material;
use LTItem\SpecialItems\BaseOrnaments;
//use pocketmine\block\Ari;
class Commands
{
	public $cd = [];
	private $plugin;
	private static $instance;
	public static function getInstance(){
		return self::$instance;
	}
	public function __construct(Main $plugin, $server)
	{
		self::$instance=$this;
		$this->plugin = $plugin;
		$this->server = $server;
	}
	public function getWeaponCallback($name, $weaponData){
		$player=$this->server->getPlayerExact($name);
		if(!$player)return false;
		if(!isset(Cooling::$query[$name])){
			Cooling::$query[$name]=time();
			$count=0;
			foreach($weaponData as $data){
				if(in_array($data[0], ['近战', '远程', '通用', '盔甲', '材料', '饰品', '宠物', '魔法'])){
					$count++;
				}elseif($data[0]=='留言'){
					$player->sendMessage($data[1], true);
					$this->server->dataBase->pushService('1'.chr(2)."update wed.items set status=1 where id='{$data[3]}'");
				}elseif($data[0]=='命令'){
					$player->getServer()->dispatchCommand($player->getServer()->consoleSender, $data[1]);
					$this->server->dataBase->pushService('1'.chr(2)."update wed.items set status=1 where id='{$data[3]}'");
				}
			}
			if($count>0){
				$player->sendMessage('§e您有§d'.$count.'§e个物件未领取，你可以输入§d/tw get§e来领取这些物件~', true);
			}
			return;
		}
		if(count($weaponData)<=0)return $player->sendMessage('§a无结果！');
		foreach($weaponData as $data){
			$contents=$player->getInventory()->getContents();
			switch($data[0]){
				case '近战':
				case '远程':
				case '通用':
					$weapon=$this->plugin->createWeapon($data[0], $data[1], $player);
					if($weapon instanceof Weapon){
						while($data[2]-- >0){
							if(!$player->getInventory()->canAddItem($weapon)){
								$player->getInventory()->setContents($contents);
								$player->sendMessage('§c无法将'.$data[0].':'.$data[1].'×'.$data[2].'发送到你背包 请检查背包空间！');
								return;
							}else $player->getInventory()->addItem($weapon);
						}
						$player->sendMessage('§a已将'.$data[0].':'.$data[1].'×'.$data[2].'发送到你背包！');
					}else{
						$player->sendMessage('§a'.$data[0].':'.$data[1].'仿佛不存在 请联系服主！');
					}
				break;
				case '盔甲':
					$armor=$this->plugin->createArmor($data[1], $player);
					if($armor instanceof Armor){
						while($data[2]-- >0){
							if(!$player->getInventory()->canAddItem($armor)){
								$player->getInventory()->setContents($contents);
								$player->sendMessage('§c无法将'.$data[0].':'.$data[1].'×'.$data[2].'发送到你背包 请检查背包空间！');
								return;
							}else $player->getInventory()->addItem($armor);
						}
						$player->sendMessage('§a已将'.$data[0].':'.$data[1].'×'.$data[2].'发送到你背包！');
					}else{
						$player->sendMessage('§a'.$data[0].':'.$data[1].'仿佛不存在 请联系服主！');
					}
				break;
				case '魔法':
					$armor=$this->plugin->createMana($data[1], $player);
					if($armor instanceof Mana){
						while($data[2]-- >0){
							if(!$player->getInventory()->canAddItem($armor)){
								$player->getInventory()->setContents($contents);
								$player->sendMessage('§c无法将'.$data[0].':'.$data[1].'×'.$data[2].'发送到你背包 请检查背包空间！');
								return;
							}else $player->getInventory()->addItem($armor);
						}
						$player->sendMessage('§a已将'.$data[0].':'.$data[1].'×'.$data[2].'发送到你背包！');
					}else{
						$player->sendMessage('§a'.$data[0].':'.$data[1].'仿佛不存在 请联系服主！');
					}
				break;
				case '材料':
					$Material=$this->plugin->createMaterial($data[1]);
					if($Material instanceof Material){
						while($data[2]-- >0){
							if(!$player->getInventory()->canAddItem($Material)){
								$player->getInventory()->setContents($contents);
								$player->sendMessage('§c无法将'.$data[0].':'.$data[1].'×'.$data[2].'发送到你背包 请检查背包空间！');
								return;
							}else $player->getInventory()->addItem($Material);
						}
						$player->sendMessage('§a已将'.$data[0].':'.$data[1].'×'.$data[2].'发送到你背包！');
					}else{
						$player->sendMessage('§a'.$data[0].':'.$data[1].'仿佛不存在 请联系服主！');
					}
				break;
				case '饰品':
					$Ornaments=$this->plugin->createOrnaments($data[1]);
					if($Ornaments instanceof BaseOrnaments){
						while($data[2]-- >0){
							if(!$player->getInventory()->canAddItem($Ornaments)){
								$player->getInventory()->setContents($contents);
								$player->sendMessage('§c无法将'.$data[0].':'.$data[1].'×'.$data[2].'发送到你背包 请检查背包空间！');
								return;
							}else $player->getInventory()->addItem($Ornaments);
						}
						$player->sendMessage('§a已将'.$data[0].':'.$data[1].'×'.$data[2].'发送到你背包！');
					}else{
						$player->sendMessage('§a'.$data[0].':'.$data[1].'仿佛不存在 请联系服主！');
					}
				break;
				case '宠物':
					\LTPet\Main::getInstance()->addPet($player, $data[1], $data[1].mt_rand(10, 99));
					$player->sendMessage('§a宠物 ('.$data[1].')已赠送与你！');
				break;
				case '留言':
					$player->sendMessage($data[1], true);
				break;
				case '命令':
					$player->getServer()->dispatchCommand($player->getServer()->consoleSender, $data[1]);
				break;
				default:
					if(is_numeric($data[0])){
						$item = Item::get($data[0], $data[1], $data[2]);
						if(!$player->getInventory()->canAddItem($item)){
							$player->getInventory()->setContents($contents);
							$player->sendMessage('§c无法将'.$item->getItemString().'×'.$data[2].'发送到你背包 请检查背包空间！');
							return;
						}else $player->getInventory()->addItem($item);
						$player->sendMessage('§a已将'.$item->getItemString().'×'.$data[2].'发送到你背包！');
					}
					$player->sendMessage('§c类型'.$data[0].'不存在 请联系服主！');
				break;
			}
			$this->server->dataBase->pushService('1'.chr(2)."update wed.items set status=1 where id='{$data[3]}'");
		}
	}
	public function onCommand(CommandSender $sender, Command $command, $label, array $args)
	{
		if(!isset($args[0]))return true;
		switch($args[0]) {
		    /*
			case '祝福点':
				$sender->sendMessage('§e你的祝福点:'.$this->plugin->R->get(strtolower($sender->getName()), 0));
			return;
			case '增加祝福点':
				if(!isset($args[2]))return $sender->sendMessage('§c/tw 增加祝福点 ID 数量');
				$this->plugin->R->set(strtolower($args[1]), $this->plugin->R->get(strtolower($args[1]), 0)+$args[2]);
				$sender->sendMessage('§a为'.$args[1].'增加了'.$args[2].'祝福点~');
			return;
		    */
			case '爆炸':
				$all = $this->plugin->config->get('爆炸', []);
				switch($args[1]) {
				case '关闭':
					$all[strtolower($sender->getName())] = false;
					$sender->sendMessage('§c关闭爆炸效果');
					break;
				case '打开':
					$all[strtolower($sender->getName())] = true;
					$sender->sendMessage('§a打开爆炸效果');
					break;
				default:
					return $sender->sendMessage('§c/tw 爆炸 [关闭 或 打开]');
				}
				$this->plugin->config->set('爆炸', $all);
				$this->plugin->config->save();
			return;
			case  '远程':
				$all = $this->plugin->config->get('远程', []);
				switch($args[1]) {
				case '关闭':
					$all[strtolower($sender->getName())] = false;
					$sender->sendMessage('§c关闭远程');
					break;
				case '打开':
					$all[strtolower($sender->getName())] = true;
					$sender->sendMessage('§a打开远程');
					break;
				default:
					return $sender->sendMessage('§c/tw 远程 [关闭 或 打开]');
				}
				$this->plugin->config->set('远程', $all);
				$this->plugin->config->save();
			return;
			case 'get':
				if(!isset(Cooling::$query[strtolower($sender->getName())]) or Cooling::$query[strtolower($sender->getName())]<time()){
					$sender->sendMessage('§e正在查询');
					$name=strtolower($sender->getName());
					$this->server->dataBase->pushService('0'.chr(7).chr(strlen($name)).$name."SELECT * FROM wed.items WHERE username='{$name}'");
                    Cooling::$query[strtolower($sender->getName())]=time()+3;
				}else $sender->sendMessage('§a请等待！');
			return;
			case 'buff':
				if(isset(\LTItem\Main::getInstance()->Buff[strtolower($sender->getName())])){
                    /** @var Config $conf */
					$conf=\LTItem\Main::getInstance()->Buff[strtolower($sender->getName())];
					if(!isset($args[1]))return $sender->sendMessage('§c/tw buff [关闭 或 打开]');
					switch($args[1]) {
					case '关闭':
						$conf->set('启用', false);
						$sender->sendMessage('§c关闭Buff');
						$sender->setBuff(new Buff($sender, $conf->getAll()));
						break;
					case '打开':
						$conf->set('启用', true);
						$sender->sendMessage('§a打开Buff');
						$sender->setBuff(new Buff($sender, $conf->getAll()));
						break;
					default:
						return $sender->sendMessage('§c/tw Buff [关闭 或 打开]');
					}
					$conf->save();
				}else $sender->sendMessage('§a你还没Buff效果 快来http://www.ltcraft.cn/shop定制吧！');
			return;
			case 'info':
				$hand=$sender->getItemInHand();
				switch(true){
					case $hand instanceof Weapon:
						$sender->sendMessage('§c============武器属性============');
						$sender->sendMessage(('§e武器名:'.$hand->getLTName()));
						if($hand->getDType()!=false)$sender->sendMessage(('§e武器类型:'.$hand->getDType()));
						if($hand->getAwakening()>0)$sender->sendMessage(('§e觉醒:'.$hand->getAwakeningF()));
						if($hand->getParticle())$sender->sendMessage('§e攻击造成血粒子！');
						if($hand->getBinding()!='*')$sender->sendMessage(('§e绑定:'.$hand->getBinding()));
						if($hand->getSkillName()!==''){
							$sender->sendMessage(('§a技能:'.$hand->getSkillName()));
							$sender->sendMessage(('§a技能强化等级:'.$hand->SkillCTime()));
						}
						if($hand->getDecomposition() instanceof Item){
							$item=$hand->getDecomposition();
							$sender->sendMessage(('§e分解可获得:材料 '.$item->getLTName()));
						}
						if($hand->getWeaponType()!=='近战'){
							$sender->sendMessage(('§e武器射速:'.($hand->getSpeed()*0.05).'s/次'));
							switch($hand->getBulletType()){
								case 'arrow':
									$bullet='箭';
								break;
								case 'EnderPearl':
									$bullet='末影球';
								break;
								case 'snowball':
									$bullet='雪球';
								break;
							}
							$sender->sendMessage(('§e子弹类型:'.$bullet??'错误！'));
							if($hand->getBoom()!=false){
								$sender->sendMessage(('§e子弹击中可以爆炸 造成'.$hand->getBoom().'伤害！'));
								if($hand->iURD())$sender->sendMessage('§e爆炸造成无条件真实伤害！');
							}
						}
						if($hand->getMaxX()>0){
							$sender->sendMessage(('§e星级:'.str_repeat('§a★',$hand->getX()).str_repeat('§f★',$hand->getMaxX()-$hand->getX())));
							$sender->sendMessage(('§e等级:'.$hand->getLevel().'/'.$hand->getMaxLevel()));
							$sender->sendMessage(('§e经验:'.$hand->getExp().'/'. Weapon::getUpExp($hand->getLevel())));
						}
						if($hand->getWlevel()!=false)$sender->sendMessage(('§e武器称号:'.$hand->getWlevel()));
						if($hand->getDType()==false or $hand->getDType()=='pve'){
							$sender->sendMessage('§dPVE属性:');
							$sender->sendMessage(('§d攻击力:'.$hand->getPVEdamage()));
							if($hand->getPVEvampire()>0)$sender->sendMessage(('§d吸血:'.($hand->getPVEvampire()*100) .'%'));
							if($hand->getGroupsOfBack()>0){
								$sender->sendMessage(('§d群回量:'.$hand->getGroupsOfBack()));
								$sender->sendMessage(('§d群回范围:'.$hand->getGroupOfBackSize()));
							}
							if($hand->getPVEFire()>0)$sender->sendMessage(('§d燃烧:'.$hand->getPVEFire()));
							if($hand->getPVEArmour()>0)$sender->sendMessage(('§d穿甲:'.$hand->getPVEArmour().'%'));
							if(count($hand->getPVEDamgerEffects())>0){
								$effects='';
								foreach($hand->getPVEDamgerEffects() as $effectInfo){
									$effects.=$effectInfo[0]->getName().' LV:'.$effectInfo[0]->getAmplifier().' 持续:'.round($effectInfo[0]->getDuration()/20,2).'s '.$effectInfo[1].'% ';
								}
								$sender->sendMessage(('§d药水:'.$effects));
							}
							if($hand->getPVELightning()!=false)$sender->sendMessage(('§d雷击伤害:'.$hand->getPVELightning()[0]. ' '.$hand->getPVELightning()[1].'%'));
						}
						if($hand->getDType()==false or $hand->getDType()=='pvp'){
							$sender->sendMessage('§aPVP属性:');
							$sender->sendMessage(('§d攻击力:'.$hand->getPVPdamage()));
							if($hand->getRealDamage()>0)$sender->sendMessage(('§a真实伤害:'.$hand->getRealDamage()));
							if($hand->getPVPvampire()>0)$sender->sendMessage(('§a吸血:'.($hand->getPVPvampire()*100).'%'));
							if($hand->getPVPFire()>0)$sender->sendMessage(('§a燃烧:'.$hand->getPVPFire()));
							if($hand->getPVPArmour()>0)$sender->sendMessage(('§d穿甲:'.$hand->getPVPArmour().'%'));
							if(count($hand->getPVPDamgerEffects())>0){
								$effects='';
								foreach($hand->getPVPDamgerEffects() as $effectInfo){
									$effects.=$effectInfo[0]->getName().' LV:'.$effectInfo[0]->getAmplifier().' 持续:'.round($effectInfo[0]->getDuration()/20,2).'s '.$effectInfo[1].'% ';
								}
								$sender->sendMessage(('§a自身药水:'.$effects));
							}
							if(count($hand->getPVPEntityEffects())>0){
								$effects='';
								foreach($hand->getPVPEntityEffects() as $effectInfo){
									$effects.=$effectInfo[0]->getName().' LV:'.$effectInfo[0]->getAmplifier().' 持续:'.round($effectInfo[0]->getDuration()/20,2).'s '.$effectInfo[1].'% ';
								}
								$sender->sendMessage(('§a对方药水:'.$effects));
							}
							if($hand->getBlowFly()>0)$sender->sendMessage(('§a击飞:'.$hand->getBlowFly()));
							if($hand->getKnockBack()>0)$sender->sendMessage(('§a击退:'.$hand->getKnockBack()));
							if($hand->getGene()!==false){
								$sender->sendMessage(('§a职业基因:'.$hand->getGene()));
								$sender->sendMessage(('§a基因等级:'.$hand->getGeneLevel()));
								switch($hand->getGene()){
									case '刺客':
										$sender->sendMessage("§d基因介绍:附加15%×基因等级 攻击力");
									break;
									case '法师':
										$sender->sendMessage("§d基因介绍:对玩家造成基于等级的重伤\n§e攻击怪物群回附加§c基于武器自身群回量/(4-基因等级)§e的群回");
									break;
									case '牧师':
										$sender->sendMessage("§d基因介绍:对玩家造成基于等级的真实伤害/n§e对怪物造成等级基因等级秒的破甲 护甲减半");
									break;
								}
							}
							if($hand->getFreeze()!=false)$sender->sendMessage(('§a冰冻:'.$hand->getFreeze()[0].'s '.$hand->getFreeze()[1].'%'));
							if($hand->getPVELightning()!=false)$sender->sendMessage(('§a雷击伤害:'.$hand->getPVELightning()[0]. ' '.$hand->getPVELightning()[1].'%'));
						}
						if($hand->getInfo()!=false)$sender->sendMessage(('§e武器介绍:'.$hand->getInfo()));
					break;
					case $hand instanceof Armor:
						$sender->sendMessage('§c============盔甲属性============');
						$sender->sendMessage(('§e盔甲名:'.$hand->getLTName()));
						if($hand->getArmorV()>0)$sender->sendMessage(('§e护甲:'.$hand->getArmorV()));
						if($hand->getThorns()>0)$sender->sendMessage(('§e反伤:'.$hand->getThorns().'%'));
						if($hand->getMiss()>0)$sender->sendMessage(('§e闪避:'.$hand->getMiss().'%'));
						if($hand->getLucky()>0)$sender->sendMessage(('§e幸运增加:'.$hand->getLucky().'%'));
						if($hand->getTough()>0)$sender->sendMessage(('§e坚韧:'.$hand->getTough().'% 减少击退'));
						if($hand->getSpeed()>1)$sender->sendMessage(('§e速度加成:'.$hand->getSpeed().'%'));
						if($hand->getHealth()>0)$sender->sendMessage(('§e生命值加成:'.$hand->getHealth()));
						if($hand->getControlReduce()>0)$sender->sendMessage(('§e控制减少:'.$hand->getControlReduce().'%'));
						if($hand->isResistanceFire())$sender->sendMessage(('§e附带抗燃烧'));
						if($hand->getBinding()!='*')$sender->sendMessage(('§e绑定:'.$hand->getBinding()));
						if($hand->getEffects()!=''){
							$effects='';
							foreach(explode('@', $hand->getEffects()) as $effect){
								$effectInfo=explode(':', $effect);
								$effects.=Effect::getEffect($effectInfo[0])->getName().' LV:'.$effectInfo[1].' ';
							}
							$sender->sendMessage(('§e药水:'.$effects));
						}
						if($hand->getInfo()!=false)$sender->sendMessage(('§e盔甲介绍:'.$hand->getInfo()));
					break;
					case $hand instanceof BaseOrnaments:
						$sender->sendMessage('§c============饰品属性============');
						$sender->sendMessage(('§e=饰品名:'.$hand->getLTName()));
						if($hand->getArmorV()>0)$sender->sendMessage(('§e增加护甲:'.$hand->getArmorV()));
						// if($hand->getThorns()>0)$sender->sendMessage(('§e增加反伤:'.$hand->getThorns().'%'));
						if($hand->getMiss()>0)$sender->sendMessage(('§e增加闪避:'.$hand->getMiss().'%'));
						if($hand->getLucky()>0)$sender->sendMessage(('§e增加幸运值:'.$hand->getLucky().'%'));
						if($hand->getTough()>0)$sender->sendMessage(('§e增加坚韧:'.$hand->getTough().'% 减少击退'));
						if($hand->getPVEDamage()>0)$sender->sendMessage(('§e增加PVE攻击力:'.$hand->getPVEDamage()));
						if($hand->getPVPDamage()>0)$sender->sendMessage(('§e增加PVP攻击力:'.$hand->getPVPDamage()));
						if($hand->getRealDamage()>0)$sender->sendMessage(('§e增加真实伤害:'.$hand->getRealDamage()));
						if($hand->getPVPArmour()>0)$sender->sendMessage(('§e增加PVP穿甲:'.$hand->getPVPArmour()));
						if($hand->getPVEArmour()>0)$sender->sendMessage(('§e增加PVE穿甲:'.$hand->getPVEArmour()));
						if($hand->getPVPMedical()>0)$sender->sendMessage(('§e攻击PVP医疗加成:'.$hand->getPVPMedical()));
						if($hand->getPVEMedical()>0)$sender->sendMessage(('§e攻击PVE医疗加成:'.$hand->getPVEMedical()));
						if($hand->getGroupOfBack()>0)$sender->sendMessage(('§e攻击群回加成:'.$hand->getGroupOfBack()));
						if($hand->getControlReduce()>0)$sender->sendMessage(('§e控制减少:'.$hand->getControlReduce().'%'));
					break;
					default:
						$sender->sendMessage('§c============自身属性============');
						$buff=$sender->getBuff();
						if($buff->getArmor()>0)$sender->sendMessage(('§e减伤:'.$buff->getArmor().'%'));
						$sender->sendMessage('§e基础PVP攻击力:'.($buff->getPVPDamage()+1));
						$sender->sendMessage('§e基础PVE攻击力:'.($buff->getPVEDamage()+1));
						$sender->sendMessage('§e攻击群回加成:'.($buff->getGroupOfBack()+1));
						if($buff->getPVPMedical()>0)$sender->sendMessage(('§e基础PVP医疗:'.$buff->getPVPMedical()));
						if($buff->getPVEMedical()>0)$sender->sendMessage(('§e基础PVE医疗:'.$buff->getPVEMedical()));
						if($buff->getPVPArmour()>0)$sender->sendMessage(('§e基础PVP穿甲:'.$buff->getPVPArmour() .'%'));
						if($buff->getPVEArmour()>0)$sender->sendMessage(('§e基础PVE穿甲:'.$buff->getPVEArmour() .'%'));
						if($buff->getLucky()>0)$sender->sendMessage(('§e增加幸运值:'.$buff->getLucky().'%'));
						if($buff->getThorns()>0)$sender->sendMessage(('§e反伤:'.$buff->getThorns().'%'));
						if($buff->getMiss()>0)$sender->sendMessage(('§e闪避:'.$buff->getMiss().'%'));
						if($buff->getTough()>0)$sender->sendMessage(('§e坚韧:'.$buff->getTough().'% 减少击退'));
						if($buff->getTough()>0)$sender->sendMessage(('§e坚韧:'.$buff->getTough().'% 减少击退'));
						if($sender->getGeNeAwakening()>0){
							$level=['', 'I', 'II', 'III'];
							switch($sender->getRole()){
								case '刺客':
									$sender->sendMessage(("§d职业觉醒被动技能:技能名字§c[隐身]§d觉醒等级[".$level[$sender->getGeNeAwakening()]."]\n攻击时获得隐身和速度2 持续[".$sender->getGeNeAwakening()*2 ."]秒\n冷却时间§3[".(300-$sender->getGeNeAwakening()*50)."]秒"));
								break;
								case '战士':
									$sender->sendMessage(("§d职业觉醒被动技能:技能名字§c[爆灭]§d觉醒等级[".$level[$sender->getGeNeAwakening()]."]\n在受到玩家攻击时身体爆炸击退附近玩家\n冷却时间§3[".(300-$sender->getGeNeAwakening()*50)."]秒"));
								break;
								case '法师':
									$sender->sendMessage(("§d职业觉醒被动技能:技能名字§c[暗灭]§d觉醒等级[".$level[$sender->getGeNeAwakening()]."]\n在受到玩家攻击时反弹附近玩家药水[失明 夜市 范围] 时间[".$sender->getGeNeAwakening()."]秒\n冷却时间§3[".(300-$sender->getGeNeAwakening()*50)."]秒"));
								break;
								case '牧师':
									$sender->sendMessage(("§d职业觉醒被动技能:技能名字§c[医疗]§d等级[".$level[$sender->getGeNeAwakening()]."]\n攻击时医疗自身50%已损失生命值\n冷却时间§3[".(300-$sender->getGeNeAwakening()*50)."]秒"));
								break;
							}
						}
						if(count($buff->getDamagerEffects())>0){
							$effects='';
							foreach($buff->getDamagerEffects() as $effectInfo){
								$effects.=$effectInfo->getName().' LV:'.$effectInfo->getAmplifier().' 持续:'.round($effectInfo->getDuration()/20,2).'s ';
							}
							$sender->sendMessage(('§a受伤攻击者药水:'.$effects));
						}
						if(count($buff->getDamageEffects())>0){
							$effects='';
							foreach($buff->getDamageEffects() as $effectInfo){
								$effects.=$effectInfo->getName().' LV:'.$effectInfo->getAmplifier().' 持续:'.round($effectInfo->getDuration()/20,2).'s ';
							}
							$sender->sendMessage(('§a受伤自身瞬间效果:'.$effects));
						}
						if(count($buff->getEffects())>0){
							$effects='';
							foreach($buff->getEffects() as $effectInfo){
								$effects.=$effectInfo->getName().' LV:'.$effectInfo->getAmplifier().' ';
							}
							$sender->sendMessage(('§a实时药水:'.$effects));
						}
					break;
				}
			return;
		}
//		if($sender->getName() !== 'Angel_XX' AND $sender instanceof Player)return;
		if(!isset($args[0]))return $sender->sendMessage('§c用法:/tw [add reload give]');
		switch($args[0]) {
		case 'add':
			if(count($args) < 4)return $sender->sendMessage('§c用法:/tw add 类型 名字 武器ID');
			switch($args[1]) {
			case '近战':
				$attribute = [
				 '武器名' => $args[2],
				 '武器ID' => $args[3],
				 '粒子' => false,
				 'PVE' => [
					 '攻击力' => 0,
					 '吸血' => 0,
					 '群回' => 0,
					 '燃烧' => 0,
					 '药水(Damager)' => false,
					 '雷击' => false,
					 '穿甲' => false,
				 ],
				 'PVP' => [
					 '攻击力' => 0,
					 '真实伤害' => 0,
					 '穿甲' => 0,
					 '吸血' => 0,
					 '燃烧' => 0,
					 '药水(Damager)' => false,
					 '药水(Entity)' => false,
					 '击飞' => 0,
					 '击退' => 0,
					 '重伤' => 0,
					 '雷击' => 0,
					 '冰冻' => false,
					 '击杀提示' => false,
				 ],
				 '手持提示' => false,
				 '最大星级' => false,
				 '等级' => false,
				 '全员可用' => false,
				 '分解可获得' => false,
				 '可以叠加' => false,
				 '介绍' => false,
				 '类型' => false
			 ];
				$this->plugin->JZ[$args[2]] = new Config($this->plugin->getDataFolder().'Items/近战/'.$args[2].'.yml', Config::YAML, $attribute);
				break;
			case '远程':
				$attribute = [
					 '武器名' => $args[2],
					 '武器ID' => $args[3],
					 '粒子' => false,
					 '粒子' => false,
					 '射速' => 20,
					 '子弹模型' => 'snowball',
					 'PVP' => [
						 '攻击力' => 0,
						 '真实伤害' => 0,
						 '穿甲' => 0,
						 '吸血' => 0,
						 '燃烧' => 0,
						 '药水(Damager)' => false,
						 '药水(Entity)' => false,
						 '击飞' => 0,
						 '击退' => 0,
						 '雷击' => false,
						 '重伤' => 0,
						 '冰冻' => false,
						 '击杀提示' => false
					 ],
					 '手持提示' => false,
					 '全员可用' => false,
					 '类型' => false,
					 '分解可获得' => false,
					 '最大星级' => false,
					 '等级' => false,
					 '爆炸' => false,
					 '可以叠加' => false,
					 '介绍' => false,
					 '无条件真实伤害' => false
				 ];
				$this->plugin->YC[$args[2]] = new Config($this->plugin->getDataFolder().'Items/远程/'.$args[2].'.yml', Config::YAML, $attribute);
				break;
			case '通用':
				$attribute = [
					 '武器名' => $args[2],
					 '武器ID' => $args[3],
					 '粒子' => false,
					 '射速' => 20,
					 '子弹模型' => 'snowball',
					 'PVE' => [
						'攻击力' => 0,
						'吸血' => 0,
						'群回' => 0,
						'燃烧' => 0,
						'药水(Damager)' => false,
						'雷击' => false,
						'穿甲' => false,
					 ],
					'PVP' => [
						'攻击力' => 0,
						'真实伤害' => 0,
						'吸血' => 0,
						'穿甲' => 0,
						'燃烧' => 0,
						'药水(Damager)' => false,
						'药水(Entity)' => false,
						'击飞' => 0,
						'击退' => 0,
						'重伤' => 0,
						'雷击' => false,
						'冰冻' => false,
						'击杀提示' => false
					],
					'手持提示' => false,
					'全员可用' => false,
					 '爆炸' => false,
					'类型' => false,
					'最大星级' => false,
					'等级' => false,
					'可以叠加' => false,
					'介绍' => false,
					'分解可获得' => false,
					'无条件真实伤害' => false
				 ];
				$this->plugin->TY[$args[2]] = new Config($this->plugin->getDataFolder().'Items/通用/'.$args[2].'.yml', Config::YAML, $attribute);
				break;
			case '材料':
				$attribute = [
					 '材料名字' => $args[2],
					 '材料ID' => $args[3],
					 '手持提示' => false,
					 '使用提示' => false,
					 '叠加最大' => false,
				 ];
				$this->plugin->CL[$args[2]] = new Config($this->plugin->getDataFolder().'Items/材料/'.$args[2].'.yml', Config::YAML, $attribute);
				break;
			case '魔法':
				$attribute = [
					 '名字' => $args[2],
					 'ID' => $args[3],
					 '手持提示' => false,
					 '叠加最大' => false,
					 '全员可用' => false,
					 '最大Mana' => 0,
				 ];
				$this->plugin->MANA[$args[2]] = new Config($this->plugin->getDataFolder().'Items/魔法/'.$args[2].'.yml', Config::YAML, $attribute);
				break;
			case '盔甲':
				$buff=[
					'ID'=>$args[3],
					'名字'=>$args[2],
					'护甲'=>0,
					'反伤'=>0,
					'effect'=>'',
					'闪避'=>0,
					'坚韧'=>0,
					'手持提示'=>false,
					'全员可用'=>false,
					'抗燃烧'=>false,
					'速度'=>0,
					'控制减少'=>0,
					'介绍' => false,
					'颜色' => false,
					'最大星级' => false,
					'等级' => false,
					'幸运' => 0,
					'附加血量'=>0
				];
				$this->plugin->KJ[$args[2]]=new Config($this->plugin->getDataFolder().'Items/盔甲/'.$args[2].'.yml',Config::YAML,$buff);
				break;
				case '饰品':
					$Ornaments=[
						'ID'=>$args[3],
						'名字'=>$args[2],
						'护甲'=>0,
						'闪避'=>0,
						'附PVE攻击力'=>0,
						'附PVP攻击力'=>0,
						'PVP攻击医疗'=>0,
						'PVE攻击医疗'=>0,
						'附PVP穿甲'=>0,
						'附PVE穿甲'=>0,
						'附真实伤害'=>0,
						'群回'=>0,
						'坚韧'=>0,
						'幸运'=>0,
						'手持提示'=>false,
						'控制减少'=>0,
						'介绍' => false,
					];
					$this->plugin->SQ[$args[2]]=new Config($this->plugin->getDataFolder().'Items/饰品/'.$args[2].'.yml',Config::YAML,$Ornaments);
				break;
			default:
				return $sender->sendMessage('§c不存在的武器类型！！');
			}
			$sender->sendMessage('§a成功添加类型：§e'.$args[1].'§aID:'.$args[3].'名字：'.$args[2].'！！');
			break;
		case 'addbuff':
			if(count($args) < 2)return $sender->sendMessage('§c用法:/tw addbuff 玩家名字');
			$buff = [
				'启用' => true,
				'减伤' => 0,
				'幸运' => 0,
				'反伤' => 0,
				'effect' => '',
				'闪避' => 0,
				'被动瞬间effect' => '',
				'对方effect' => '',
				 '控制减少'=>0,
				 '坚韧'=>0,
			];
			$this->plugin->Buff[strtolower($args[1])] = new Config($this->plugin->getDataFolder().'Items/Buff/'.strtolower($args[1]).'.yml', Config::YAML, $buff);
			$sender->sendMessage('§c成功为目标玩家添加buff效果');
			break;
		case 'give':
			if(count($args) < 4)return $sender->sendMessage('§c用法:/tw give 玩家名字 类型 名字 数量');
			$player = $this->server->getPlayer($args[1]);
			if(!$player)return $sender->sendMessage('§c玩家不在线！');
			if(!$player->getInventory()->isNoFull()) {
				$sender->sendMessage('§a玩家无法接收物品，背包已满！');
				$player->sendMessage('§a无法收到物品请检查背包占用');
				return;
			}
			if($args[2] == '材料') {
				$item = $this->plugin->createMaterial($args[3]);
				if($item->getId()==0)return $sender->sendMessage('§a请检查这个材料是否存在？');
				if(isset($args[4]))
					for($i = 1; $i <= $args[4]; $i++)$player->getInventory()->addItem($item);
				else $player->getInventory()->addItem($item);
				$sender->sendMessage('§a成功赐予玩家'.$player->getName().'材料:'.$args[3]);
				$player->sendMessage('§a收到材料'.$args[3]);
				return;
			}elseif($args[2] == '饰品') {
				$item = $this->plugin->createOrnaments($args[3]);
				if($item->getId()==0)return $sender->sendMessage('§a请检查这个饰品是否存在？');
				if(isset($args[4]))
					for($i = 1; $i <= $args[4]; $i++)$player->getInventory()->addItem($item);
				else $player->getInventory()->addItem($item);
				$sender->sendMessage('§a成功赐予玩家'.$player->getName().'饰品:'.$args[3]);
				$player->sendMessage('§a收到饰品'.$args[3]);
				return;
			}elseif($args[2] == '魔法') {
				$item = $this->plugin->createMana($args[3], $player);
				if($item->getId()==0)return $sender->sendMessage('§a请检查这个魔法物品是否存在？');
				if(isset($args[4]))
					for($i = 1; $i <= $args[4]; $i++)$player->getInventory()->addItem($item);
				else $player->getInventory()->addItem($item);
				$sender->sendMessage('§a成功赐予玩家'.$player->getName().'魔法:'.$args[3]);
				$player->sendMessage('§a收到魔法物品'.$args[3]);
				return;
			}elseif($args[2]=='TI'){//这个是挖方块不掉落直接进背包的物品
				$item=Main::createSendToInvItem($args[3]);
				if(isset($args[4]))
					for($i=1;$i<=$args[4];$i++)$player->getInventory()->addItem($item);
				else 
					$player->getInventory()->addItem($item);
				$sender->sendMessage('§a成功赐予玩家'.$player->getName());
				$player->sendMessage('§a收到工具');
				return;
			}elseif($args[2]=='SELL'){//挖方块自动出售
				$item=Main::createAutoSellItem($args[3]);
				if(isset($args[4]))
					for($i=1;$i<=$args[4];$i++)$player->getInventory()->addItem($item);
				else 
					$player->getInventory()->addItem($item);
				$sender->sendMessage('§a成功赐予玩家'.$player->getName());
				$player->sendMessage('§a收到工具');
				return;
			}elseif($args[2]=='无耐久'){
				$item=Main::createUnbreakableItem($args[3]);
				if(isset($args[4]))
					for($i=1;$i<=$args[4];$i++)$player->getInventory()->addItem($item);
				else 
					$player->getInventory()->addItem($item);
				$sender->sendMessage('§a成功赐予玩家'.$player->getName());
				$player->sendMessage('§a收到无耐久物品');
				return;
			}elseif($args[2]=='盔甲') {
				if(!$this->plugin->ThereAre($args[2],$args[3]))return $sender->sendMessage('§c这个名字的盔甲不存在！！');
				$item=$this->plugin->createArmor($args[3],strtolower($player->getName()));
				if($item->getId()==0)return $sender->sendMessage('§a哎呀 出错了！');
				if(isset($args[4]))
					for($i=1; $i<=$args[4]; $i++)$player->getInventory()->addItem($item);
				else $player->getInventory()->addItem($item);
				$sender->sendMessage('§a成功赐予玩家'.$player->getName().$args[2].':'.$args[3]);
				$player->sendMessage('§a收到盔甲'.$args[3]);
				return;
			}
			if($args[2] != '近战' and $args[2] != '通用' and $args[2] != '远程')return $sender->sendMessage('§c这个类型不存在！！');
			if(!$this->plugin->ThereAre($args[2], $args[3]))return $sender->sendMessage('§c这个名字的武器不存在！！');
			$item = $this->plugin->createWeapon($args[2], $args[3], strtolower($player->getName()));
			if($item->getId()==0)return $sender->sendMessage('§a哎呀 出错了！');
			if(isset($args[4]))
				for($i = 1; $i <= $args[4]; $i++)$player->getInventory()->addItem($item);
			else $player->getInventory()->addItem($item);
			$sender->sendMessage('§a成功赐予玩家'.$player->getName().$args[2].':'.$args[3]);
			$player->sendMessage('§a收到'.$args[2].'武器'.$args[3]);
			break;
		case 'reload':
			unset($this->plugin->JZ, $this->plugin->YC, $this->plugin->TY, $this->plugin->Buff, $this->plugin->KJ, $this->plugin->SQ, $this->plugin->MANA);
			$this->plugin->getAllItemss();
			$sender->sendMessage('§a重载完成！');
			break;
		}
	}
}