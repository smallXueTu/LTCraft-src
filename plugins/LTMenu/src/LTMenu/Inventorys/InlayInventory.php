<?php
namespace LTMenu\Inventorys;

use LTItem\Mana\Mana;
use LTItem\SpecialItems\BaseOrnaments;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\ItemFrame;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use LTMenu\Open;
use LTItem\Main as LTItem;
use LTItem\SpecialItems\Weapon;
use LTItem\SpecialItems\Armor;
use LTItem\SpecialItems\Material;

class InlayInventory extends OperationInventory{
    public int $funCount = 3;

    /**
     * @param $event DataPacketReceiveEvent
     * @param $open Open
     * @return void
     */
	public function event($event, $open){
		$packet=$event->getPacket();
		if($this->getItem($packet->slot)->getId() == 0)return;
		if(!$this->getOwner()->getInventory()->isNoFull())return $open->invError();
		if($packet->slot==24 or $this->getOwner()->getGamemode()!==0 or $open->isDisable()){
			return $this->setCancelled($event);
		}
		if($packet->slot==25 or $packet->slot==26 or $packet->slot==24){
			$this->setCancelled($event);
			if($open->getLastClick()!==$packet->slot)return $open->setLastClick($packet->slot);
			$open->setLastClick(null);
			if($packet->slot==25){
				$open->closeMultiLevel();
			}elseif($packet->slot==26){//处理镶嵌操作
				$item=$this->getItem(0);
				$nbt=$item->getNamedTag();
				if(!($nbt instanceof CompoundTag))$nbt=new CompoundTag("", []);
				switch(true){
					case $item instanceof Tool:
						$index=1;
						while($index<=23){
							$i=$this->getItem($index++);
							$count=0;
							if($i->getId()===0)continue;
							if(!($i instanceof Material))continue;
							switch($i->getLTName()){
								case '永恒之晶':
									if(isset($nbt->Unbreakable))continue 2;
									$nbt->Unbreakable=new ByteTag('Unbreakable',1);
									$item->setNamedTag($nbt);
									$count=1;
								break;
								case '耐久水晶':
									$item->setDamage(0);
									$count=1;
								break;
							}
							$i->setCount($i->getCount()-$count);
							if($i->getCount()<=0)$i=Item::get(0);
							$this->setItem($index-1,$i);
						}
						$this->setItem(0,$item);
					break;
					case $item instanceof Weapon:
						$index=1;
						while($index<=23){
							$i=$this->getItem($index++);
							$count=0;
							if($i->getId()===0)continue;
							if(!($i instanceof Material) and !($i instanceof Mana))continue;
							switch($i->getLTName()){
								case '基因精髓-法师':
									if(in_array($item->getWlevel(), ['终极', '史诗', '定制', '仙器', '神话']) and ($item->getGene()===false or $item->getGene()==='法师') and $item->getGeneLevel()<3){
										$item=$item->setGene('法师');
										$count=1;
									}
								break;
								case '基因精髓-刺客':
									if(in_array($item->getWlevel(), ['终极', '史诗', '定制', '仙器', '神话']) and ($item->getGene()===false or $item->getGene()==='刺客') and $item->getGeneLevel()<3){
										$item=$item->setGene('刺客');
										$count=1;
									}
								break;
								case '基因精髓-牧师':
									if(in_array($item->getWlevel(), ['终极', '史诗', '定制', '仙器', '神话']) and ($item->getGene()===false or $item->getGene()==='牧师') and $item->getGeneLevel()<3){
										$item=$item->setGene('牧师');
										$count=1;
									}
								break;
								case '§e耀魂宝珠':
								    if ($item instanceof Weapon\DrawingKnife){
								        /** @var $item Weapon\DrawingKnife */
                                        $item->addForging($i->getCount());
                                        $count = $i->getCount();
                                        $item->initW();
                                    }
								break;
								case '樱花的誓约技能石':
                                    if ($item instanceof Weapon){
                                        /** @var $item Weapon */
                                        $item->setSkillName('樱花的誓约');
                                        $count = 1;
                                    }
                                break;
                                case '储魔升级':
                                    if ($item instanceof Armor\ManaArmor and $item->getStorageUpgrade() < Armor\ManaArmor::STORAGE_UPGRADE_MAX){
                                        /** @var Armor\ManaArmor $item */
                                        $yLevel = $item->getStorageUpgrade();
                                        $item->setStorageUpgrade($item->getStorageUpgrade() + $i->getCount());
                                        $count = $i->getCount() - ($item->getStorageUpgrade() - $yLevel);
                                    }
                                break;
                                case '注魔升级':
                                    if ($item instanceof Armor\ManaArmor and $item->getNoteMagicUpgrade() < Armor\ManaArmor::NOTE_MAGIC_UPGRADE_MAX){
                                        /** @var Armor\ManaArmor $item */
                                        $yLevel = $item->getNoteMagicUpgrade();
                                        $item->setNoteMagicUpgrade($item->getNoteMagicUpgrade() + $i->getCount());
                                        $count = $i->getCount() - ($item->getNoteMagicUpgrade() - $yLevel);
                                    }
                                break;
                                case '耗魔升级':
                                    if ($item instanceof Armor\ReduceMana and $item->getReduceMana() < $item->getMaxReduce()){
                                        /** @var Armor\ReduceMana $item */
                                        $yLevel = $item->getReduce();
                                        $item->setReduceMana($item->getReduceMana() + $i->getCount());
                                        $count = $i->getCount() - ($item->getReduceMana() - $yLevel);
                                    }
                                break;
								case '时空撕裂技能石':
								    if ($item instanceof Weapon){
								        /** @var $item Weapon */
                                        $item->setSkillName('时空撕裂');
                                        $count = 1;
                                    }
								break;
								case '耀魂碎片':
								    if ($item instanceof Weapon\DrawingKnife and $item->getDurable() < 0 and $item->canUse($event->getPlayer())){
								        /** @var $item Weapon\DrawingKnife */
                                        if ($item->getDurable() == -1)
                                            $item->setDurable(Weapon\DrawingKnife::MAX_DURABLE);
                                        else
                                            $item->setDurable($item->getDurable() + 1);
                                        $event->getPlayer()->setGrade($event->getPlayer()->getGrade() - 2);
                                        $event->getPlayer()->recalculateHealth();
                                        $count = 1;
                                        $item->initW();
                                    }
								break;
								case '卡拉森的意志':
								case '加斯的意志':
								case '亚瑟的意志':
								case '图拉的意志':
								    if ($item instanceof Weapon\Trident and $item->getWillCount() <= 1){
								        /** @var $item Weapon\Trident */
                                        $item->addWill($i->getLTName());
                                        $count = 1;
                                        $item->initW();
                                    }
								break;
								case '灵魂圣布':
								    if ($i instanceof Mana){
								        if ($i->getMana()>=$i->getMaxMana()){
                                            if($item instanceof Weapon or $item instanceof Armor){
                                                if(!$item->canUse($event->getPlayer()) and !$event->getPlayer()->isOp()){
                                                    $open->error('仅支持武器和盔甲。');
                                                }else{
                                                    if($item->getWlevel()=='定制'){
                                                        $open->error('定制不能更换绑定。');
                                                    }else{
                                                        $count = 1;
                                                        $item=$item->setBinding($i->getOwner());
                                                    }
                                                }
                                            }else{
                                                $open->error('仅支持武器和盔甲。');
                                            }
                                        }else{
                                            $open->error('灵魂圣布未激活。');
                                        }
                                    }
								break;
								case '初级嗜血之书':
									if($item->getAttribute('附加吸血最大值')==false)continue 2;
									for($c=1;$c<$i->getCount() and $nbt['attribute'][4]<$item->getAttribute('附加吸血最大值');$c++){
										if(mt_rand(0,9)<6)$nbt['attribute'][4]=new StringTag('',$nbt['attribute'][4]+0.002);
										$count++;
									}
									$item->setNamedTag($nbt);
									$item->initW($item->getConfig());//重新初始化武器
								break;
								case '中级嗜血之书':
									if($item->getAttribute('附加吸血最大值')==false)continue 2;
									for($c=1;$c<$i->getCount() and $nbt['attribute'][4]<$item->getAttribute('附加吸血最大值');$c++){
										if(mt_rand(0,9)<4)$nbt['attribute'][4]=new StringTag('',$nbt['attribute'][4]+0.005);
										$count++;
									}
									$item->setNamedTag($nbt);
									$item->initW($item->getConfig());
								break;
								case '高级嗜血之书':
									if($item->getAttribute('附加吸血最大值')==false)continue 2;
									for($c=1;$c<$i->getCount() and $nbt['attribute'][4]<$item->getAttribute('附加吸血最大值');$c++){
										if(mt_rand(0,9)<2)$nbt['attribute'][4]=new StringTag('',$nbt['attribute'][4]+0.01);
										$count++;
									}
									$item->setNamedTag($nbt);
									$item->initW($item->getConfig());
								break;
								case 'PVE锋利之书':
									if($item->getDType()!='pve' or $item->getAttribute('附加攻击力最大值')==false)continue 2;
									$count=$nbt['attribute'][3];
									$nbt['attribute'][3]=new StringTag('',$nbt['attribute'][3]+1*$i->getCount());
									if($nbt['attribute'][3]>$item->getAttribute('附加攻击力最大值'))$nbt['attribute'][3]=new StringTag('',$item->getAttribute('附加攻击力最大值'));
									$count=$nbt['attribute'][3]-$count;
									$item->setNamedTag($nbt);
									$item->initW($item->getConfig());
								break;
								case 'PVP锋利之书':
									if($item->getDType()!='pvp' or $item->getAttribute('附加攻击力最大值')==false)continue 2;
									for($c=1;$c<$i->getCount() and $nbt['attribute'][3]<$item->getAttribute('附加攻击力最大值');$c++){
										if(mt_rand(0,9)<2)$nbt['attribute'][3]=new StringTag('',$nbt['attribute'][3]+1);
										$count++;
									}
									$item->setNamedTag($nbt);
									$item->initW($item->getConfig());
								break;
								case '技能石':
									if($item->getSkillName()=='' or $item->getAttribute('技能最大等级', true)==false)continue 2;
									$count=$nbt['attribute'][9];
									$nbt['attribute'][9]=new StringTag('',$nbt['attribute'][9]+1*$i->getCount());
									if($nbt['attribute'][9]>$item->getAttribute('技能最大等级', true))$nbt['attribute'][9]=new StringTag('',$item->getAttribute('技能最大等级', true));
									$count=$nbt['attribute'][9]-$count;
									$item->setNamedTag($nbt);
									$item->initW($item->getConfig());
								break;
								case '医疗水晶':
									if($item->getAttribute('附加群回最大值')==false)continue 2;
									for($c=1;$c<$i->getCount() and $nbt['attribute'][12]<$item->getAttribute('附加群回最大值');$c++){
										if(mt_rand(0,9)<2)$nbt['attribute'][12]=new StringTag('',$nbt['attribute'][12]+1);
										$count++;
									}
									$item->setNamedTag($nbt);
									$item->initW($item->getConfig());
								break;
								case '时空之石':
									if($item->getLTName()=='时空之刃'){
										for($c=1;$c<$i->getCount() and $nbt['attribute'][10]<18;$c++){
											$nbt['attribute'][10]=new StringTag('',$nbt['attribute'][10]+1);
											$count++;
										}
										$item->setNamedTag($nbt);
										$item->initW($item->getConfig());
									}
								break;
								case '黑色尖刃':
									if($item->getAttribute('附加真实伤害最大值')==false)continue 2;
									for($c=1;$c<$i->getCount() and $nbt['attribute'][8]<$item->getAttribute('附加真实伤害最大值');$c++){
										if(mt_rand(0,9)<2)$nbt['attribute'][8]=new StringTag('',$nbt['attribute'][8]+1);
										$count++;
									}
									$item->setNamedTag($nbt);
									$item->initW($item->getConfig());
								break;
								case '凋零魔切':
									if($item->getDType()==='pvp'){//武器类型等于pvp的话.
                                        $effects=$item->getPVPEntityEffects();
                                        if(isset($effects[20]) and $effects[20][1]<100){
                                            $effects=$item->getOriginalPVPEntityEffects();
                                            $aEffects=Weapon::explodeEffect($nbt['attribute'][15]);//array[ID] = 概率
                                            $trigger = $effects[20][1];//原概率
                                            $aTrigger = $aEffects[20]??0;//附加的概率
                                            if(isset($aEffects[19]))
                                                $aEffects[20]+=$i->getCount();
                                            else
                                                $aEffects[20]=$i->getCount();
                                            if($trigger+$aEffects[19]>100){
                                                $aEffects[20]=100-$trigger;
                                            }
                                            $str='';
                                            foreach($aEffects as $id=>$t){//ID => 概率
                                                $str.=$id.':'.$t.'&';
                                            }
                                            $str=substr($str,0,strlen($str)-1);
                                            $count=$aEffects[20]-$aTrigger;//之前附加的等级-刚附加的等级
                                            $nbt['attribute'][15]=new StringTag('', $str);
                                            $item->setNamedTag($nbt);
                                            $item->initW($item->getConfig());
                                        }
									}
								break;
								case '中毒魔切':
									if($item->getDType()==='pvp'){
										$effects=$item->getPVPEntityEffects();
										if(isset($effects[19]) and $effects[19][1]<100){
                                            $effects=$item->getOriginalPVPEntityEffects();
											$aEffects=Weapon::explodeEffect($nbt['attribute'][15]);//array[ID] = 概率
											$trigger = $effects[19][1];//原概率 例子：为1
											$aTrigger = $aEffects[19]??0;//附加的概率 例子：为50
											if(isset($aEffects[19]))
                                                $aEffects[19]+=$i->getCount();//例子：50+64=114
											else
                                                $aEffects[19]=$i->getCount();
											if($trigger+$aEffects[19]>100){//例子：1+114 肯定大于100
                                                $aEffects[19]=100-$trigger;//例子：100-1 = 99
											}
											$str='';
											foreach($aEffects as $id=>$t){//ID => 概率
											    $str.=$id.':'.$t.'&';
                                            }
											$str=substr($str,0,strlen($str)-1);
											$count=$aEffects[19]-$aTrigger;//之前附加的等级-刚附加的等级 例子：99-50 = 49
											$nbt['attribute'][15]=new StringTag('', $str);
											$item->setNamedTag($nbt);
											$item->initW($item->getConfig());
										}
									}
								break;
								case '最后的轻语':
									if($item->getAttribute('附加穿甲最大值')==false)continue 2;
									$count=$nbt['attribute'][17];
									$nbt['attribute'][17]=new StringTag('',$nbt['attribute'][17]+1*$i->getCount());
									if($nbt['attribute'][17]>$item->getAttribute('附加穿甲最大值'))$nbt['attribute'][17]=new StringTag('',$item->getAttribute('附加穿甲最大值'));
									$count=$nbt['attribute'][17]-$count;
									$item->setNamedTag($nbt);
									$item->initW($item->getConfig());
								break;
								case '雷之石':
									if($nbt['attribute'][5]!=0)continue 2;
									$nbt['attribute'][5]=new StringTag('',1);
									$count=1;
									$item->setNamedTag($nbt);
									$item->initW($item->getConfig());
								break;
								case '群回范围附加石':
									if($nbt['attribute'][16]>=7)continue 2;
                                    $count=$nbt['attribute'][16];
                                    $nbt['attribute'][16]=new StringTag('',$nbt['attribute'][16]+1*$i->getCount());
                                    if($nbt['attribute'][16]>10)$nbt['attribute'][16]=new StringTag('',7);
                                    $count=$nbt['attribute'][16]-$count;
                                    $item->setNamedTag($nbt);
                                    $item->initW($item->getConfig());
								break;
								case '高级武器经验水晶':
									$exp=6;
								case '中级武器经验水晶':
									if(!isset($exp))$exp=4;
								case '初级武器经验水晶':
									if(!isset($exp))$exp=2;
									if($item->getMaxX()!==false and $item->getLevel()<$item->getMaxX()*10){
										$redundantExp=$item->addExp($exp*$i->getCount());
										if($redundantExp>$exp){//剩余经验
											$count=(int)$redundantExp/$exp;
											$count=$i->getCount()-$count;
										}else $count=$i->getCount();
									}
								break;
								case '武器精髓':
									if($nbt['attribute'][7]>=$item->getMaxX())continue 2;
									for($c=1;$c<=$i->getCount() and $nbt['attribute'][7]<$item->getMaxX();$c++){
										if(!mt_rand(0,9))$nbt['attribute'][7]=new StringTag('',$nbt['attribute'][7]+1);
										$count++;
									}
									$item->setNamedTag($nbt);
								break;
								default:
									continue 2;
							}
							$i->setCount($i->getCount()-$count);
							if($i->getCount()<=0)$i=Item::get(0);
							$this->setItem($index-1,$i);
						}
						$this->setItem(0, $item);
					break;
					case $item instanceof Armor:
						$index=1;
						while($index<=23){
							$i=$this->getItem($index++);
							$count=0;
							if($i->getId()===0)continue;
							if(!($i instanceof Material))continue;
							switch($i->getLTName()){
								case '盔甲精髓':
									if($nbt['attribute'][11]>=$item->getMaxX())continue 2;
									for($c=1;$c<=$i->getCount() and $nbt['attribute'][11]<$item->getMaxX();$c++){
										if(!mt_rand(0,9))$nbt['attribute'][11]=new StringTag('',$nbt['attribute'][11]+1);
										$count++;
									}
                                    // $name=$item->getCustomName();
                                    // $level=str_repeat('§a★',$nbt['attribute'][7]).str_repeat('§f★',$item->getMaxX()-$nbt['attribute'][7]);
                                    // $item->setCustomName(preg_replace('#(§a★)+(§f★)+#', $level, $name));
									$item->setNamedTag($nbt);
								break;
								case '血之晶':
									if($item->getAttribute('附加血量最大值')==false)continue 2;
									$count=$nbt['armor'][4];
									$nbt['armor'][4]=new StringTag('',$nbt['armor'][4]+1*$i->getCount());
									if($nbt['armor'][4]>$item->getAttribute('附加血量最大值'))$nbt['armor'][4]=new StringTag('',$item->getAttribute('附加血量最大值'));
									$count=$nbt['armor'][4]-$count;
                                    $item->setNamedTag($nbt);
									$item->initW($item->getConfig());
								break;
								case '减控水晶':
									if($item->getAttribute('附加控制减少最大值')==false)continue 2;
									$count=$nbt['armor'][7];
									$nbt['armor'][7]=new StringTag('',$nbt['armor'][7]+1*$i->getCount());
									if($nbt['armor'][7]>$item->getAttribute('附加控制减少最大值'))$nbt['armor'][7]=new StringTag('',$item->getAttribute('附加控制减少最大值'));
									$count=$nbt['armor'][7]-$count;
                                    $item->setNamedTag($nbt);
									$item->initW($item->getConfig());
								break;
                                case '幻影药水':
                                    if (!$item->isPhantom()){
                                        $count = 1;
                                        $item->setPhantom(true);
                                    }
                                break;
								case '黑色金刚石':
									if($item->getAttribute('附加护甲最大值')==false)continue 2;
									$count=$nbt['armor'][3];
									$nbt['armor'][3]=new StringTag('',$nbt['armor'][3]+1*$i->getCount());
									if($nbt['armor'][3]>$item->getAttribute('附加护甲最大值'))$nbt['armor'][3]=new StringTag('',$item->getAttribute('附加护甲最大值'));
									$count=$nbt['armor'][3]-$count;
                                    $item->setNamedTag($nbt);
									$item->initW($item->getConfig());
								break;
								case '高级盔甲经验水晶':
									$exp=6;
								case '中级盔甲经验水晶':
									if(!isset($exp))$exp=4;
								case '初级盔甲经验水晶':
									if(!isset($exp))$exp=2;
									if($item->getMaxX()!==false and $item->getLevel()<$item->getMaxX()*10){
										$redundantExp=$item->addExp($exp*$i->getCount());
										if($redundantExp>$exp){//剩余经验
											$count=(int)$redundantExp/$exp;
											$count=$i->getCount()-$count;
										}else $count=$i->getCount();
									}
								break;
							}
                            $i->setCount($i->getCount()-$count);
                            if($i->getCount()<=0)$i=Item::get(0);
                            $this->setItem($index-1,$i);
						}
                        $this->setItem(0, $item);
					break;
					default:
						$open->error();
					break;
				}
			}
		}
	}
}