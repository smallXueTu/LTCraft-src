<?php
namespace LTItem;

use LTItem\Mana\Mana;
use LTItem\Mana\ManaRing;
use LTItem\SpecialItems\Armor\ReduceMana;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;

use LTEntity\entity\BaseEntity;

use LTItem\SpecialItems\BaseOrnaments;

class Buff{
	private $player;
	private $effects = [];
	private $damageEffects = [];
	private $damagerEffects = [];
	private $Ornaments = [];
	private $thorns = 0;
	private $armor = 0;
	private $PVEDamage = 0;
	private $PVPDamage = 0;
	private $groupOfBack = 0;
	private $PVPMedical = 0;
	private $PVEMedical = 0;
	private $miss = 0;
	private $armorV = 0;
	private $lucky = 0;
	private $tough = 0;
	private $PVPArmour = 0;
	private $PVEArmour = 0;
	private $RealDamage = 0;
	private $controlReduce = 0;
	private $enable = false;
	private $info = [];
	public function __construct(Player $player, $buffInfo='Default'){
		$this->player=$player;
		if($buffInfo!=='Default'){
		    $this->enable = $buffInfo['启用']??true;
		    if ($this->enable){
                $this->info=$buffInfo;
                $this->initBuff();
            }
		}
	}
	public function initBuff(){
		if(!$this->enable)return;
		try{
			$buffInfo = $this->info;
			if(count($buffInfo)<=0)return;
			if($buffInfo['effect']!=='')foreach(explode('@', $buffInfo['effect']) as $eff){
				$effInfo = explode(':', $eff);
				if($effInfo[0]==5)continue;
				$this->effects[$effInfo[0]] = Effect::getEffect($effInfo[0])->setDuration(61*20)->setAmplifier($effInfo[1]);
			}
			if($buffInfo['被动瞬间effect']!=='')foreach(explode('@', $buffInfo['被动瞬间effect']) as $eff){
				$effInfo = explode(':', $eff);
				$this->damageEffects[$effInfo[0]] = Effect::getEffect($effInfo[0])->setDuration($effInfo[2]*20)->setAmplifier($effInfo[1]);
			}
			if($buffInfo['对方effect']!=='')foreach(explode('@', $buffInfo['对方effect']) as $eff){
				$effInfo = explode(':', $eff);
				$this->damagerEffects[$effInfo[0]] = Effect::getEffect($effInfo[0])->setDuration($effInfo[2]*20)->setAmplifier($effInfo[1]);
			}
			$this->thorns = (int)$buffInfo['反伤'];
			$this->armor = (int)$buffInfo['减伤'];
			$this->lucky = (int)($buffInfo['幸运']??0)+$this->player->isVIP()*10;
			$this->miss = (int)$buffInfo['闪避'];
			$this->tough = (int)$buffInfo['坚韧'];
			$this->RealDamage = (int)($buffInfo['真实伤害']??0);
			$this->controlReduce = (int)$buffInfo['控制减少'];
		}catch(\Throwable $e){
			Server::getInstance()->getLogger()->warning($this->player->getName().'Buff配置文件出错 在'.$e->getLine());
		}
	}

    /**
     * @return bool
     */
    public function getEnable()
    {
        return $this->enable;
    }
	public function addControlReduce($v){
		$this->controlReduce += $v;
	}
	public function delControlReduce($v){
		$this->controlReduce -= $v;
	}
	public function getGroupOfBack(){
		return $this->groupOfBack;
	}
	public function addPVPMedical($v){
		$this->PVPMedical+=$v;
	}
	public function delPVPMedical($v){
		$this->PVPMedical-=$v;
	}
	public function getPVPMedical(){
		return $this->PVPMedical;
	}
	public function addGroupOfBack($v){
		$this->groupOfBack+=$v;
	}
	public function delPVEMedical($v){
		$this->PVEMedical-=$v;
	}
	public function addPVEMedical($v){
		$this->PVEMedical+=$v;
	}
	public function getPVEMedical(){
		return $this->PVEMedical;
	}
	public function getPVPDamage(){
		return $this->PVPDamage;
	}
	public function addPVPDamage($v){
		$this->PVPDamage+=$v;
	}
	public function delPVPDamage($v){
		$this->PVPDamage-=$v;
	}
	public function delPVEDamage($v){
		$this->PVEDamage-=$v;
	}
	public function addPVEDamage($v){
		$this->PVEDamage+=$v;
	}
	public function getPVEDamage(){
		return $this->PVEDamage;
	}
	public function delGroupOfBack($v){
		$this->groupOfBack-=$v;
	}
	public function getControlReduce(){
		return $this->controlReduce;
	}
	public function getTough(){
		return $this->tough;
	}
	public function getArmorV(){
		return $this->armorV;
	}
	public function addArmorV($v){
		$this->armorV+=$v;
	}
	public function delArmorV($v){
		$this->armorV-=$v;
	}
	public function addTough($v){
		$this->tough+=$v;
	}
	public function delTough($v){
		$this->tough-=$v;
	}
	public function getLucky(){
		return $this->lucky;
	}
	public function addLucky($v){
		$this->lucky+=$v;
	}
	public function delRealDamage($v){
		$this->RealDamage-=$v;
	}
	public function addRealDamage($v){
		$this->RealDamage+=$v;
	}
	public function getRealDamage(){
		$this->RealDamage;
	}
	public function delPVPArmour($v){
		$this->PVPArmour-=$v;
	}
	public function addPVPArmour($v){
		$this->PVPArmour+=$v;
	}
	public function getPVPArmour(){
		$this->PVPArmour;
	}
	public function delPVEArmour($v){
		$this->PVEArmour-=$v;
	}
	public function addPVEArmour($v){
		$this->PVEArmour+=$v;
	}
	public function getPVEArmour(){
		$this->PVEArmour;
	}
	public function delLucky($v){
		$this->lucky-=$v;
	}
	public function getDamagerEffects(){
		return$this->damagerEffects;
	}
	public function getDamageEffects(){
		return $this->damageEffects;
	}
	public function getEffects(){
		return$this->effects;
	}
	public function addToEntityEffect(Entity $entity, Player $damager){
		foreach($this->damagerEffects as $effect)$damager->addEffect(clone $effect);
		foreach($this->damageEffects as $effect)$entity->addEffect(clone $effect);
	}

    /**
     * 给玩家附加药水效果
     */
	public function runEffect(){
		foreach($this->effects as $effect){
			$this->player->addEffect(clone $effect);
		}
	}
	public function addEffect($effectInfo){
		foreach(explode('@', $effectInfo) as $eff){
			$effInfo = explode(':', $eff);
			if(isset($this->effects[$effInfo[0]]))
				$this->effects[$effInfo[0]] = Effect::getEffect($effInfo[0])->setDuration(61*20)->setAmplifier($effInfo[1]+$this->effects[$effInfo[0]]->getAmplifier());
			else 
				$this->effects[$effInfo[0]] = Effect::getEffect($effInfo[0])->setDuration(61*20)->setAmplifier($effInfo[1]);
		}
	}
	public function delEffect($effectInfo){
		foreach(explode('@', $effectInfo) as $eff){
			$effInfo = explode(':', $eff);
			if(isset($this->effects[$effInfo[0]])){
				$level=$this->effects[$effInfo[0]]->getAmplifier()-$effInfo[1];
				if($level<=0){
					unset($this->effects[$effInfo[0]]);
					continue;
				}
				$this->effects[$effInfo[0]] = Effect::getEffect($effInfo[0])->setDuration(61*20)->setAmplifier($level);
			}
		}
	}
	public function getThorns(){
		return $this->thorns;
	}
	public function addThorns($v){
		$this->thorns+=$v;
	}
	public function delThorns($v){
		$this->thorns-=$v;
	}
	public function getArmor(){
		return $this->armor;
	}
	public function getVampire(Entity $entity){
		$damager=$this->player;
		if($entity instanceof Player){
			return $this->PVPMedical;
		}elseif($entity instanceof BaseEntity){
			return $this->PVEMedical;
		}else return 0;
	}
	public function getDamage(Entity $entity){
		if($entity instanceof Player)
			return $this->PVPDamage;
		elseif($entity instanceof BaseEntity)
			return $this->PVEDamage;
		else return 0;
	}
	public function getArmour(Entity $entity){
		if($entity instanceof Player)
			return $this->PVPArmour;
		elseif($entity instanceof BaseEntity)
			return $this->PVEArmour;
		else return 0;
		
	}
	public function getMedical(Entity $entity){
		if($entity instanceof Player)
			return $this->PVPMedical;
		elseif($entity instanceof BaseEntity)
			return $this->PVEMedical;
		else return 0;
	}

    /**
     * 获取身上魔力值
     * @return int
     */
	public function getMana(){
	    if ($this->player->getOrnamentsInventory()->onUse)return 0;//玩家正在操作饰品
        $mana = 0;
        foreach ($this->player->getOrnamentsInventory()->getContents() as $item){
            /** @var ManaRing $item */
            if ($item instanceof Mana){
                $mana += $item->getMana();
            }
        }
        return $mana;
    }

    /**
     * 消耗魔力
     * @param int $mana
     * @param bool $reduce
     * @return bool
     */
	public function consumptionMana(int $mana, bool $reduce = true): bool{
	    if ($reduce){
	        $fun = function (Player $player): float{
                $f = 0.0;
                foreach ($player->getInventory()->getArmorContents() as $item){
                    if ($item instanceof ReduceMana){
                        $f += $item->getReduce();
                    }
                }
                return $f;
            };
	        $mana *= (1 - $fun($this->player));
        }
	    if ($this->getMana()<$mana)return false;
        foreach ($this->player->getOrnamentsInventory()->getContents() as $index => $item){
            /** @var ManaRing $item */
            if ($item instanceof Mana){
                if ($item->consumptionMana($mana)){
                    $this->player->getOrnamentsInventory()->setItem($index, $item);
                    return true;
                }else{
                    if($item->getMana() <= 0){
                        continue;
                    }
                    $mana -= $item->getMana();
                    $item->consumptionMana($item->getMana());
                    $this->player->getOrnamentsInventory()->setItem($index, $item);
                }
            }
        }
        return false;
    }

    /**
     * @param $name
     * @param $type string 类型
     * @return bool|int
     */
	public function checkOrnamentsInstall($name, string  $type = '魔法') {
        return $this->player->getOrnamentsInventory()->containsLTItem([$type, $name])!==false;
    }

    /**
     * 获取一个饰品的格子
     * @param $name
     * @param $type string 类型
     * @return bool|int
     */
	public function getOrnamentsInstallIndex($name, string  $type = '魔法') {
        return $this->player->getOrnamentsInventory()->containsLTItem([$type, $name]);
    }
	public function getMiss(){
		return $this->miss;
	}
	public function addMiss($v){
		$this->miss+=$v;
	}
	public function delMiss($v){
		$this->miss-=$v;
	}
	public function setEmpty(){
		$this->effects = [];
		$this->damageEffects = [];
		$this->damagerEffects = [];
		$this->Ornaments = [];
		$this->thorns = 0;
		$this->armor = 0;
		$this->PVPDamage = 0;
		$this->PVEDamage = 0;
		$this->groupOfBack = 0;
		$this->armorV = 0;
		$this->PVPMedical = 0;
		$this->PVEMedical = 0;
		$this->miss = 0;
		$this->lucky = 0;
		$this->tough = 0;
		$this->PVEArmour = 0;
		$this->PVPArmour = 0;
		$this->RealDamage = 0;
		$this->controlReduce = 0;
	}
	public function updateBuff(){
		$this->player->delArmorV($this->getArmorV());
		$this->setEmpty();
		$this->initBuff($this->info);
		foreach($this->player->getInventory()->getArmorContents() as $index => $item){
			if($item->isArmor() and $item instanceof Armor and $item->canUse($this->player)){
				$this->addThorns($item->getThorns());
				$this->addMiss($item->getMiss());
				$this->addLucky($item->getLucky());
				$this->addTough($item->getTough());
				$this->addControlReduce($item->getControlReduce());
				if($item->getEffects()!==''){
					foreach(explode('@',$item->getEffects()) as $effect) {
						$eff=explode(':',$effect);
						$this->player->addEffect(Effect::getEffect($eff[0])->setAmplifier($eff[1])->setDuration(60*20));
					}
					$this->addEffect($item->getEffects());
				}
			}
		}
		$Ornaments=[];
		$fly = false;
		foreach($this->player->getOrnamentsInventory()->getItems() as $index=>$item){
			if($index>5)break;
            if ($item->getLTName()=='天翼族之冠')
                $fly = true;
			if($item instanceof BaseOrnaments and !in_array($item->getLTName(), $Ornaments)){
				$Ornaments[] = $item->getLTName();
				if($item->getControlReduce()>0){
					$this->addControlReduce($item->getControlReduce());
					if($this->getControlReduce()>100){
						$this->controlReduce=100;
					}
				}
				if($item->getPVPDamage()>0){
					$this->addPVPDamage($item->getPVPDamage());
				}
				if($item->getPVEDamage()>0){
					$this->addPVEDamage($item->getPVEDamage());
				}
				if($item->getPVPMedical()>0){
					$this->addPVPMedical($item->getPVPMedical());
				}
				if($item->getPVEMedical()>0){
					$this->addPVEMedical($item->getPVEMedical());
				}
				if($item->getGroupOfBack()>0){
					$this->addGroupOfBack($item->getGroupOfBack());
				}
				if($item->getArmorV()>0){
					$this->addArmorV($item->getArmorV());
				}
				if($item->getMiss()>0){
					$this->addMiss($item->getMiss());
				}
				if($item->getRealDamage()>0){
					$this->addRealDamage($item->getRealDamage());
				}
				if($item->getPVPArmour()>0){
					$this->addPVPArmour($item->getPVPArmour());
				}
				if($item->getPVEArmour()>0){
					$this->addPVEArmour($item->getPVEArmour());
				}
				if($item->getLucky()>0){
					$this->addLucky($item->getLucky());
				}
				if($item->getTough()>0){
					$this->addTough($item->getTough());
					if($this->getTough()>100){
						$this->tough=100;
					}
				}
			}
		}
		$this->player->addArmorV($this->getArmorV());
        $this->player->canFly = $fly or ($this->player->isVIP()!==false or $this->player->getFlyTime()>time());
        $this->player->setAllowFlight($this->player->canFly);
	}
	public function miss(){
		if(mt_rand(1,100)<=$this->miss)return true;
		return false;
	}
}