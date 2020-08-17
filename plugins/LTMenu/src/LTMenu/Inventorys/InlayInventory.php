<?php
namespace LTMenu\Inventorys;

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
	public $funCount=3;
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
							if(!($i instanceof Material))continue;
							switch($i->getLTName()){
								case '基因精髓-法师':
									if(in_array($item->getWlevel(), ['终极', '史诗', '定制', '仙器']) and ($item->getGene()===false or $item->getGene()==='法师') and $item->getGeneLevel()<3){
										$item=$item->setGene('法师');
										$count=1;
									}
								break;
								case '基因精髓-刺客':
									if(in_array($item->getWlevel(), ['终极', '史诗', '定制', '仙器']) and ($item->getGene()===false or $item->getGene()==='刺客') and $item->getGeneLevel()<3){
										$item=$item->setGene('刺客');
										$count=1;
									}
								break;
								case '基因精髓-牧师':
									if(in_array($item->getWlevel(), ['终极', '史诗', '定制', '仙器']) and ($item->getGene()===false or $item->getGene()==='牧师') and $item->getGeneLevel()<3){
										$item=$item->setGene('牧师');
										$count=1;
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
									if($item->getDType()==='pvp'){//武器类型等于pvp的话..
										$effects=$item->getPVPEntityEffects();
										if(isset($effects[20]) and $effects[20][3]<100){
											$aeffects=Weapon::explodeEffect($nbt['attribute'][15]);
											$zy=$effects[20]->getAmplifier();//原药水等级
											$ay=$aeffects[20]??0;
											if(isset($aeffects[20]))
												$aeffects[20]+=$i->getCount();
											else 
												$aeffects[20]=$i->getCount();
											if($zy+$aeffects[20]>100){
												$aeffects[20]=100-$zy;
											}
											$str='';
											foreach($aeffects as $key=>$v){
												$str+=$v[0].':'.$v[1].'&';
											}
											$str=substr($str,0,strlen($str)-1);
											$count=$aeffects[20]-$ay;
											$nbt['attribute'][15]=new StringTag('', $str);
											$item->setNamedTag($nbt);
											$item->initW($item->getConfig());
										}
									}
								break;
								case '中毒魔切':
									if($item->getDType()==='pvp'){
										$effects=$item->getPVPEntityEffects();
										if(isset($effects[19]) and $effects[19][3]<100){
											$aeffects=Weapon::explodeEffect($nbt['attribute'][15]);
											$zy=$effects[19]->getAmplifier();//原药水等级
											$ay=$aeffects[19]??0;
											if(isset($aeffects[19]))
												$aeffects[19]+=$i->getCount();
											else 
												$aeffects[19]=$i->getCount();
											if($zy+$aeffects[19]>100){
												$aeffects[19]=100-$zy;
											}
											$str='';
											foreach($aeffects as $key=>$v)$str+=$v[0].':'.$v[1].'&';
											$str=substr($str,0,strlen($str)-1);
											$count=$aeffects[19]-$ay;
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
									$item->setNamedTag($nbt);
								break;
								case '血之晶':
									if($item->getAttribute('附加血量最大值')==false)continue 2;
									$count=$nbt['armor'][4];
									$nbt['armor'][4]=new StringTag('',$nbt['armor'][4]+1*$i->getCount());
									if($nbt['armor'][4]>$item->getAttribute('附加血量最大值'))$nbt['armor'][4]=new StringTag('',$item->getAttribute('附加血量最大值'));
									$count=$nbt['armor'][4]-$count;
									$item->initW($item->getConfig());
								break;
								case '减控水晶':
									if($item->getAttribute('附加控制减少最大值')==false)continue 2;
									$count=$nbt['armor'][7];
									$nbt['armor'][7]=new StringTag('',$nbt['armor'][7]+1*$i->getCount());
									if($nbt['armor'][7]>$item->getAttribute('附加控制减少最大值'))$nbt['armor'][7]=new StringTag('',$item->getAttribute('附加控制减少最大值'));
									$count=$nbt['armor'][7]-$count;
									$item->initW($item->getConfig());
								break;
								case '黑色金刚石':
									if($item->getAttribute('附加护甲最大值')==false)continue 2;
									$count=$nbt['armor'][3];
									$nbt['armor'][3]=new StringTag('',$nbt['armor'][3]+1*$i->getCount());
									if($nbt['armor'][3]>$item->getAttribute('附加护甲最大值'))$nbt['armor'][3]=new StringTag('',$item->getAttribute('附加护甲最大值'));
									$count=$nbt['armor'][3]-$count;
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
								case '盔甲精髓':
									if($nbt['attribute'][7]>=$item->getMaxX())continue 2;
									for($c=1;$c<=$i->getCount() and $nbt['attribute'][7]<$item->getMaxX();$c++){
										if(!mt_rand(0,9))$nbt['attribute'][7]=new StringTag('',$nbt['attribute'][7]+1);
										$count++;
									}
									// $name=$item->getCustomName();
									// $level=str_repeat('§a★',$nbt['attribute'][7]).str_repeat('§f★',$item->getMaxX()-$nbt['attribute'][7]);
									// $item->setCustomName(preg_replace('#(§a★)+(§f★)+#', $level, $name));
								break;
							}
						}
					break;
					default:
						$open->error();
					break;
				}
			}
		}
	}
}