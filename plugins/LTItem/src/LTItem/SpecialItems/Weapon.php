<?php
namespace LTItem\SpecialItems;

use LTItem\LTItem;
use LTItem\SpecialItems\Weapon\NeilBathDrinkBloodFlow;
use pocketmine\item\Item;
use pocketmine\entity\Entity;
use pocketmine\entity\Effect;
use pocketmine\Player;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use LTEntity\entity\BaseEntity;
use pocketmine\math\Vector3;
use pocketmine\Server;
use LTItem\Main;
use pocketmine\event\entity\EntityCombustByEntityEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;

class Weapon extends Item implements LTItem {
	private $plugin = null;
	private $PVPdamage = 0;
	private $PVEdamage = 0;
	private $type;
	private $conf;
	private $WeaponName;
	private $WeaponType;
	private $PVPDamgerEffects = [];
	private $PVPEntityEffects = [];
	private $PVEDamgerEffects = [];
	private $PVPFire = 0;
	private $PVEFire = 0;
	private $Injury = 0;
	private $PVPArmour = 0;
	private $PVEArmour = 0;
	private $PVPLightning;
	private $PVELightning;
	private $PVPVampire;
	private $PVEVampire;
	private $groupOfBack;
	private $knockBack;
	private $blowFly;
	private $killMessage;
	private $freeze;
	private $Boom;
	private $RealDamage;
	private $bulletType = 'snowball';
	private $iURD = false;
	private $particle = false;
	private $speed = 20;
	private $handMessage = false;
	private $SkillName = '';
	private $SkillCD = 50;
	private $SkillTime = 0;
	private $groupOfBackSize = 3;
	private $binding = '*';
	private $decomposition = false;
	private $gene = false;
	private $geneLevel = 1;
	private $Wlevel = false;



    private static $weapons = [];

    /**
     * @param string $name
     * @param array $conf
     * @param int $count
     * @param CompoundTag $nbt
     * @param bool $init
     * @return Weapon
     */
    public static function getWeapon(string $name, array $conf, int $count, CompoundTag $nbt, $init=true) : Weapon
    {
        if (isset(self::$weapons[$name])){
            return new self::$weapons[$name]($conf, $count, $nbt, $init);
        }
        return new Weapon($conf, $count, $nbt, $init);
    }


    /**
     * 初始化武器
     */
    public static function initWeapons()
    {
        self::$weapons['尼尔巴斯的饮血镰'] = NeilBathDrinkBloodFlow::class;
    }

    public static function upGrade(Weapon $weapon, $level, $init=true){
        switch($weapon->getLTName()){
            case '尼尔巴斯的饮血镰':
            case '万人斩-冥界的咆哮':
                $nbt=$weapon->getNamedTag();
                $nbt['attribute'][3] = new StringTag('',$level*20);
                $nbt['attribute'][12] = new StringTag('',(int)($level/2));
                $weapon->setNamedTag($nbt);
                break;
            case '幽灵手弩':
                $nbt=$weapon->getNamedTag();
                $nbt['attribute'][3] = new StringTag('',$level*20);
                $nbt['attribute'][12] = new StringTag('',(int)($level/3));
                $weapon->setNamedTag($nbt);
                break;
            case '死亡之舞':
                $nbt=$weapon->getNamedTag();
                $nbt['attribute'][3] = new StringTag('',$level*5);
                $nbt['attribute'][12] = new StringTag('',(int)($level/2));
                $weapon->setNamedTag($nbt);
                break;
            case '死亡黑狼图腾':
                $nbt=$weapon->getNamedTag();
                $nbt['attribute'][3] = new StringTag('',$level*20);
                $nbt['attribute'][12] = new StringTag('',(int)($level/4));
                $weapon->setNamedTag($nbt);
                break;
            case '流星锤':
                $nbt=$weapon->getNamedTag();
                $nbt['attribute'][3] = new StringTag('',$level*5);
                $nbt['attribute'][12] = new StringTag('',(int)($level/3));
                $weapon->setNamedTag($nbt);
                break;
            case '诸神狱焰剑':
            case '流光星陨刃':
                $nbt=$weapon->getNamedTag();
                $nbt['attribute'][3] = new StringTag('',$level*10);
                $nbt['attribute'][12] = new StringTag('',(int)$level/2);
                $weapon->setNamedTag($nbt);
                break;
            case '烈火金箍棒':
                $nbt=$weapon->getNamedTag();
                $nbt['attribute'][3] = new StringTag('',$level*16);
                $nbt['attribute'][12] = new StringTag('',(int)$level/2);
                $weapon->setNamedTag($nbt);
                break;
            case '盘古斧':
                $nbt=$weapon->getNamedTag();
                $nbt['attribute'][3] = new StringTag('',$level*20);
                $nbt['attribute'][12] = new StringTag('',(int)$level/2);
                $weapon->setNamedTag($nbt);
                break;
            case '村正妖刀':
                $nbt=$weapon->getNamedTag();
                $nbt['attribute'][3] = new StringTag('',$level*4);
                $nbt['attribute'][12] = new StringTag('',(int)$level/2);
                $weapon->setNamedTag($nbt);
                break;
            case '圣龙之牙':
                $nbt=$weapon->getNamedTag();
                $nbt['attribute'][3] = new StringTag('',$level*1);
                $nbt['attribute'][5] = new StringTag('',$level*2);
                $nbt['attribute'][19] = new StringTag('', (int)($level/10));
                $weapon->setNamedTag($nbt);
                break;
        }
        if($init)$weapon->initW($weapon->getConfig());
    }
    /**
     * Weapon constructor.
     * @param array $conf
     * @param int $count
     * @param CompoundTag $nbt
     * @param bool $init
     */
	public function __construct(array $conf, int $count, CompoundTag $nbt, $init=true){
		$this->plugin = Main::getInstance();
		$idInfo=explode(':',$conf['武器ID']);
		parent::__construct($idInfo[0], $idInfo[1]??0, $count);
		$this->setCompoundTag($nbt);
		$this->setCustomName($conf['武器名']);
		$this->WeaponName=$nbt['attribute'][2];
		$this->WeaponType=$nbt['attribute'][1];
		$this->binding=strtolower($nbt['attribute'][0]);
		$this->conf=$conf;
		if($init)$this->initW($conf);
	}
	public function getTypeName(){
		return $this->WeaponType;
	}

    /**
     * 初始化武器
     * @param null $conf
     */
	public function initW($conf = null){
		try{
			if($conf==null)$conf=$this->conf;
			$this->Wlevel=$conf['等级'];
			$this->type=$conf['类型'];
			if($this->WeaponType!=='远程'){
				$this->PVEdamage=$conf['PVE']['攻击力'];
				$this->PVEArmour=$conf['PVE']['穿甲']??0;
				if($conf['PVE']['药水(Damager)']!==false){
				    foreach(explode('@', $conf['PVE']['药水(Damager)']) as $eff){
                        $effInfo=explode(':', $eff);
                        $this->PVEDamgerEffects[$effInfo[0]]=[Effect::getEffect($effInfo[0])->setDuration($effInfo[2]*20)->setAmplifier($effInfo[1]), $effInfo[3]];
                    }
                }
				$this->PVELightning =  $conf['PVE']['雷击']==false?false:explode(':', $conf['PVE']['雷击']);
				$this->PVEFire = $conf['PVE']['燃烧'];
				$this->groupOfBack = $conf['PVE']['群回'];
				$this->PVEVampire = $conf['PVE']['吸血'];
			}else 	$this->type='pvp';
			$this->PVPdamage=$conf['PVP']['攻击力'];
			$this->PVPArmour=$conf['PVP']['穿甲']??0;
			if(isset($conf['技能CD']))$this->SkillCD=$conf['技能CD'];
			if($conf['PVP']['药水(Entity)']!==false){
			    foreach(explode('@', $conf['PVP']['药水(Entity)']) as $eff) {
                    $effInfo = explode(':', $eff);
                    $this->PVPEntityEffects[$effInfo[0]] = [Effect::getEffect($effInfo[0])->setDuration($effInfo[2] * 20)->setAmplifier($effInfo[1]), $effInfo[3]];
                }
			}
			if($conf['PVP']['药水(Damager)']!==false){
			    foreach(explode('@', $conf['PVP']['药水(Damager)']) as $eff){
                    $effInfo=explode(':', $eff);
                    $this->PVPDamgerEffects[$effInfo[0]]=[Effect::getEffect($effInfo[0])->setDuration($effInfo[2]*20)->setAmplifier($effInfo[1]), $effInfo[3]];
                }
            }
			$this->PVPLightning = $conf['PVP']['雷击']==false?false:explode(':', $conf['PVP']['雷击']);
			$this->PVPVampire = $conf['PVP']['吸血'];
			$this->PVPFire = $conf['PVP']['燃烧'];
			if($conf['分解可获得']!=false){
				$ItemInfo=explode(':',$conf['分解可获得']);
				switch($ItemInfo[0]){
					case '近战':
					case '远程':
					case '通用':
						$this->decomposition=$this->plugin->createWeapon($ItemInfo[0], $ItemInfo[1]);
					break;
					case '盔甲':
						$this->decomposition=$this->plugin->createArmor($ItemInfo[1]);
					break;
					case '材料':
						$this->decomposition=$this->plugin->createMaterial($ItemInfo[1]);
					break;
				}
				if($this->decomposition instanceof Item)$this->decomposition->setCount($ItemInfo[2]);
			}
			$this->RealDamage = $conf['PVP']['真实伤害'];
			$this->handMessage = $conf['手持提示'];
			$this->Injury = $conf['PVP']['重伤']??0;
			$this->blowFly = $conf['PVP']['击飞'];
			$this->SkillName = $conf['技能']??'';
			$this->knockBack = $conf['PVP']['击退'];
			$this->freeze = $conf['PVP']['冰冻']==false?false:explode(':', $conf['PVP']['冰冻']);
			$this->killMessage = $conf['PVP']['击杀提示'];
			if($this->getWeaponType()!=='近战')$this->Boom = $conf['爆炸'];
			if($this->getWeaponType()!=='近战')$this->iURD = $conf['无条件真实伤害'];
			if($this->getWeaponType()!=='近战')$this->bulletType = $conf['子弹模型'];
			if($this->getWeaponType()!=='近战')$this->speed = $conf['射速'];
			$this->particle = $conf['粒子'];
			$nbt=$this->getNamedTag();
			if(!isset($nbt['attribute'][24])){
                /**
                 * 初始化NBT加成
                 */
				$nbt['attribute'][3]=new StringTag('',$nbt['attribute'][3]??0);//3 附加攻击力
				$nbt['attribute'][4]=new StringTag('',$nbt['attribute'][4]??0.0);//4 附加吸血
				$nbt['attribute'][5]=new StringTag('',$nbt['attribute'][5]??0);//5 附加雷击几率
				$nbt['attribute'][6]=new StringTag('',$nbt['attribute'][6]??0);//6 经验
				$nbt['attribute'][7]=new StringTag('',$nbt['attribute'][7]??1);//7 武器星级
				$nbt['attribute'][8]=new StringTag('',$nbt['attribute'][8]??0);//8 附加真实伤害
				$nbt['attribute'][9]=new StringTag('',$nbt['attribute'][9]??1);//9 技能持续时间
				$nbt['attribute'][10]=new StringTag('',$nbt['attribute'][10]??0);//10 技能冷却缩短
				$nbt['attribute'][11]=new StringTag('',$nbt['attribute'][11]??0);//11 技能效果附加
				$nbt['attribute'][12]=new StringTag('',$nbt['attribute'][12]??0);//12 群回量
				$nbt['attribute'][13]=new StringTag('',$nbt['attribute'][13]??1);//13 武器等级
				$nbt['attribute'][14]=new StringTag('',$nbt['attribute'][14]??'');//14 自身药水附加
				$nbt['attribute'][15]=new StringTag('',$nbt['attribute'][15]??'');//15 对方药水附加
				$nbt['attribute'][16]=new StringTag('',$nbt['attribute'][16]??0);//16 群回范围附加
				$nbt['attribute'][17]=new StringTag('',$nbt['attribute'][17]??0);//17 穿甲百分比
				$nbt['attribute'][18]=new StringTag('',$nbt['attribute'][18]??0);//18 击飞
				$nbt['attribute'][19]=new StringTag('',$nbt['attribute'][19]??0);//19 击退
				$nbt['attribute'][20]=new StringTag('',$nbt['attribute'][20]??'');//20 武器技能名
				$nbt['attribute'][21]=new StringTag('',$nbt['attribute'][21]??0);//21 觉醒层数
				$nbt['attribute'][22]=new StringTag('',$nbt['attribute'][22]??'');//22 武器基因
				$nbt['attribute'][23]=new StringTag('',$nbt['attribute'][23]??1);//23 基因等级
				$nbt['attribute'][24]=new StringTag('',$nbt['attribute'][24]??1);//24 基因等级
				$this->setNamedTag($nbt);
				self::upGrade($this, $this->getLevel(), false);
			}
			if($nbt['attribute'][20]!==''){
				$this->SkillName = $nbt['attribute'][20];
			}
			if($nbt['attribute'][22]!==''){
				$this->gene = $nbt['attribute'][22];
				$this->geneLevel = $nbt['attribute'][23];
			}
			if($this->type==='pve'){
				$this->PVEdamage+=$nbt['attribute'][3];
				$this->PVEVampire+=$nbt['attribute'][4];
				$this->groupOfBack+=$nbt['attribute'][12];
				$this->PVEArmour+=$nbt['attribute'][17];
				$this->addPVEDamgerEffects($nbt['attribute'][14]);
				if($nbt['attribute'][5]>0){
					if($this->PVELightning==false)$this->PVELightning=[1, 0];
					$this->PVELightning[1]+=$nbt['attribute'][5];
				}
			}elseif($this->type==='pvp'){
				$this->PVPdamage+=$nbt['attribute'][3];
				$this->PVPVampire+=$nbt['attribute'][4];
				$this->PVPArmour+=$nbt['attribute'][17];
				$this->blowFly+=$nbt['attribute'][18];
				$this->knockBack+=$nbt['attribute'][19];
				if($nbt['attribute'][5]>0){
					if($this->PVPLightning==false)$this->PVPLightning=[1, 0];
					$this->PVPLightning[1]+=$nbt['attribute'][5];
				}
				$this->addPVPEntityEffects($nbt['attribute'][15]);
				$this->addPVPDamgerEffects($nbt['attribute'][14]);
				$this->RealDamage+=$nbt['attribute'][8];
            }
            $this->groupOfBackSize+=$nbt['attribute'][16];
			$this->SkillCD-=$nbt['attribute'][10];
			if($this->getAwakening()>1){
				if($this->type==='pve'){
					$this->PVEdamage+=$this->PVEdamage*($this->getAwakening()*0.2);
				}elseif($this->type==='pvp'){
					$this->PVPdamage+=$this->PVPdamage*($this->getAwakening()*0.2);
				}
			}
		}catch(\Throwable $e){
			Server::getInstance()->getLogger()->warning($this->getWeaponType().':'.$this->getLTName().'配置文件出错 '.$e->getMessage().' 在'.$e->getFile().'第'.$e->getLine().'行');
		}
	}
	public function getAwakening(){
		$nbt=$this->getNamedTag();
		return $nbt['attribute'][21]??0;
	}
	public function setBinding($name){
		$nbt=$this->getNamedTag();
		$nbt['attribute'][0]=new StringTag('',$name);
		$this->binding = strtolower($name);
		$this->setNamedTag($nbt);
		return $this;
	}
	public function setGene($name){
		if($this->gene!==false){
			$nbt=$this->getNamedTag();
			$nbt['attribute'][23]=new StringTag('', ++$this->geneLevel);
			$this->setNamedTag($nbt);
			return $this;
		}else{
			$nbt=$this->getNamedTag();
			$nbt['attribute'][22]=new StringTag('',$name);
			$this->gene = $name;
			$this->setNamedTag($nbt);
			return $this;
		}
	}
	public function getGene(){
		return $this->gene;
	}
	public function getGeneLevel(){
		return $this->geneLevel;
	}
	public function addAwakening(){
		$nbt=$this->getNamedTag();
		$nbt['attribute'][21]=new StringTag('',$nbt['attribute'][21]+1);
		$this->setNamedTag($nbt);
		return $this;
	}
	public function getGroupOfBackSize(){
		return $this->groupOfBackSize;
	}
	public function getConfig(){
		return $this->conf;
	}
	public function getWlevel(){
		return $this->Wlevel;
	}
	public function getSkillName(){
		return $this->SkillName;
	}
	public function getSkillTime(){
		return $this->SkillTime;
	}
	public function setSkillTime($time){//冷却时间
		$this->SkillTime = $time;
	}
	public function Skill($base = 0){//TODO Fix
		$this->SkillTime = time() + $this->getSkillCD()+$base;
	}
	public function getSkillCD(){
		return $this->SkillCD;
	}
	public function SkillCTime(){
		$nbt=$this->getNamedTag();
		return $nbt['attribute'][9];
	}
	public function SkillXTime(){
		$nbt=$this->getNamedTag();
		return $nbt['attribute'][11];
	}
	public function getExp(){
		return $this->getNamedTag()['attribute'][6];
	}
	public function setExp($exp){
		$nbt=$this->getNamedTag();
		$nbt['attribute'][6] = new StringTag('',$exp);
		$this->setNamedTag($nbt);
	}
	public function getLevel(){
		return $this->getNamedTag()['attribute'][13];
	}
	public function setLevel($level){
		if($level>$this->getLevel())self::upGrade($this, $level);
		$nbt=$this->getNamedTag();
		$nbt['attribute'][13] = new StringTag('',$level);
		$this->setNamedTag($nbt);
	}
	public function getMaxLevel(){
		return $this->getNamedTag()['attribute'][7]*10;
	}
	public function getX(){
		return $this->getNamedTag()['attribute'][7];
	}
	public function getMaxX(){
		return $this->conf['最大星级'];
	}
	
	public function addExp($exp){
		$nbt=$this->getNamedTag();
		$yexp=$nbt['attribute'][6];
		if($this->getLevel()>=$this->getX()*10)return $exp;	
		if($yexp+$exp>=self::getUpExp($this->getLevel())){
			$redundantExp=$exp-(self::getUpExp($this->getLevel())-$yexp);
			$this->setLevel($this->getLevel()+1);
			$nextLvel=$this->getLevel();
			while($redundantExp>=self::getUpExp($nextLvel)){
				if($nextLvel>=$this->getX()*10){
					$this->setExp(0);
					return $redundantExp;
				}
				$redundantExp-=self::getUpExp($nextLvel++);
				$this->setLevel($this->getLevel()+1);
			}
			$this->setExp($redundantExp);
		}else $this->setExp($this->getExp()+$exp);
		$this->setNamedTag($nbt);
		return 0;
	}
	public function getPVEDamgerEffects(){
		return $this->PVEDamgerEffects;
	}
	public function getOriginalPVPEntityEffects(){
	    $arr = [];
        if($this->getConfig()['PVP']['药水(Entity)']!==false) {
            foreach (explode('@', $this->getConfig()['PVP']['药水(Entity)']) as $eff) {
                $effInfo = explode(':', $eff);
                $arr[$effInfo[0]] = [Effect::getEffect($effInfo[0])->setDuration($effInfo[2] * 20)->setAmplifier($effInfo[1]), $effInfo[3]];
            }
        }
        return $arr;
	}
	public function getOriginalPVEDamgerEffects(){
	    $arr = [];
        if($this->getConfig()['PVE']['药水(Damager)']!==false) {
            foreach (explode('@', $this->getConfig()['PVE']['药水(Damager)']) as $eff) {
                $effInfo = explode(':', $eff);
                $arr[$effInfo[0]] = [Effect::getEffect($effInfo[0])->setDuration($effInfo[2] * 20)->setAmplifier($effInfo[1]), $effInfo[3]];
            }
        }
        return $arr;
	}
	public function getOriginalPVPDamgerEffects(){
	    $arr = [];
        if($this->getConfig()['PVP']['药水(Damager)']!==false) {
            foreach (explode('@', $this->getConfig()['PVP']['药水(Damager)']) as $eff) {
                $effInfo = explode(':', $eff);
                $arr[$effInfo[0]] = [Effect::getEffect($effInfo[0])->setDuration($effInfo[2] * 20)->setAmplifier($effInfo[1]), $effInfo[3]];
            }
        }
        return $arr;
	}
	public function addPVEDamgerEffects($effectInfo){
        if(strlen(trim($effectInfo))<=0)return;
		foreach(explode('@', $effectInfo) as $eff){
            if(strlen(trim($eff))<=0)continue;
			$effInfo = explode(':', $eff);
			if(isset($this->PVEDamgerEffects[$effInfo[0]])){
				$this->PVEDamgerEffects[$effInfo[0]][1] += $effInfo[1];
			}elseif($effInfo[2]>0 and $effInfo[1]>0){
				$this->PVEDamgerEffects[$effInfo[0]] = 1;
			}
		}
	}
	public function getPVPEntityEffects(){
		return $this->PVPEntityEffects;
	}
	public function addPVPEntityEffects($effectInfo){
        if(strlen(trim($effectInfo))<=0)return;
		foreach(explode('@', $effectInfo) as $eff){
            if(strlen(trim($eff))<=0)continue;
			$effInfo = explode(':', $eff);//0ID  1概率
			if(isset($this->PVPEntityEffects[$effInfo[0]])){//如果这个武器不带这个药水效果就忽略
				$this->PVPEntityEffects[$effInfo[0]][1] += $effInfo[1]??0;//原来的概率+附加的概率
			}
		}
	}
	public function getPVPDamgerEffects(){
		return $this->PVPDamgerEffects;
	}
	public function addPVPDamgerEffects($effectInfo){
		if(strlen(trim($effectInfo))<=0)return;
		foreach(explode('@', $effectInfo) as $eff){
            if(strlen(trim($eff))<=0)continue;
			$effInfo = explode(':', $eff);//0ID  1概率
			if(isset($this->PVPDamgerEffects[$effInfo[0]])){//如果这个武器不带这个药水效果就忽略
				$this->PVPDamgerEffects[$effInfo[0]][1] = $effInfo[1]??0;//原来的概率+附加的概率
			}
		}
	}
	public function vampire(Player $damager, Entity $entity, $damage, $PAdd = 0, $addVampire = 0, $addGroupOfBack = 0){
		/*
		$PAdd 应该是附加百分比
		*/
		if($damage>$entity->getHealth()){
			$damage=$entity->getHealth();
		}
		if($entity instanceof Player){
			$finally=$damage*($this->PVPVampire+$PAdd)+$addVampire;
		}elseif($entity instanceof BaseEntity){
			if($entity->enConfig['团队']){
				$this->groupOfBack($entity, $PAdd, $addGroupOfBack);
				return;
			}
			$finally=$damage*($this->PVEVampire+$PAdd)+$addVampire;
		}else{
			return;
		}
		if($damager->getHealth() < $damager->getMaxHealth() and $finally>0){
			$damager->heal($finally,new EntityRegainHealthEvent($damager, $finally, EntityRegainHealthEvent::CAUSE_MAGIC));
		}
	}
	public function addEffect(Entity $entity, Player $damager){
		if($entity instanceof BaseEntity){
			foreach($this->PVEDamgerEffects as $info){
				if(mt_rand(1,100)<=$info[1]){
					$damager->addEffect(clone $info[0]);
				}
			}
		}elseif($entity instanceof Player){
			foreach($this->PVPEntityEffects as $info){
				if(mt_rand(1,100)<=$info[1]){
					$entity->addEffect(clone $info[0]);
				}
			}
			foreach($this->PVPDamgerEffects as $info){
				if(mt_rand(1,100)<=$info[1]){
					$damager->addEffect(clone $info[0]);
				}
			}
		}
	}
	public function getDecomposition(){
		return $this->decomposition;
	}
	public function getAwakeningF(){
		switch($this->getAwakening()){
			case 0:
				return '';
			break;
			case 1:
				return 'I';
			break;
			case 2:
				return 'II';
			break;
			case 3:
				return 'III';
			break;
			case 4:
				return 'IV';
			break;
			case 5:
				return 'V';
			break;
			case 6:
				return 'VI';
			break;
			case 7:
				return 'VIII';
			break;
			case 8:
				return 'IIX';
			break;
			case 9:
				return 'IX';
			break;
			case 10:
				return 'X';
			break;
			default:
			return $this->getAwakening();
		}
	}
	public function getHandMessage(Player $player){
		if($this->canUse($player, false)){
			if($player->getGTo()<6 and !in_array($this->getWlevel(), ['入门', '普通', '中级', '中级+', '高级', '定制', '仙器', '传说', '稀有'])){
				return '你目前不能驾驭这把武器！';
			}
			if($player->getMaxDamage()<$this->PVEdamage){
				$player->setMaxDamage($this->PVEdamage);
			}
			if(!$player->getAStatusIsDone($this->getWlevel())){
			    $player->addAStatus($this->getWlevel());
            }
			return strtr($this->handMessage .($this->getAwakening()>1?'觉醒:'.$this->getAwakeningF():''),['@ed'=>$this->getPVEdamage(),'%'=>'%%%%','@ex'=>$this->getPVEvampire()*100,'@pd'=>$this->getPVPdamage(),'@px'=>$this->getPVPvampire()*100,'@eq'=>$this->getGroupsOfBack()]);
		}else return '你不是这个武器的拥有者！';
	}
	public function getParticle(){
		return $this->particle;
	}
	public function getSpeed(){
		return $this->speed;
	}
	public function getBulletType(){
		return $this->bulletType;
	}
	public function getBoom(){
		return $this->Boom;
	}
	public function getInjury(){
		return $this->Injury;
	}
	public function iURD(){
		return $this->iURD;
	}
	public function getRealDamage(){
		return $this->RealDamage;
	}
	public function groupOfBack(Entity $entity, $PAdd=0, $add=0){
		if($this->groupOfBack<=0)return;
		foreach($entity->level->getPlayers() as $player){
			if($player->distance($entity)<$this->getGroupOfBackSize()){
				$x=$this->groupOfBack+($this->groupOfBack*$PAdd)+$add;
				$player->heal($x ,new EntityRegainHealthEvent($player, $x, EntityRegainHealthEvent::CAUSE_MAGIC));
			}
		}
	}
	public function fire(Entity $entity, Player $Damager){
		if($entity instanceof BaseEntity){
			if($this->PVEFire<=0)return;
				$entity->setOnFire($this->PVEFire);
		}elseif($entity instanceof Player){
			if($this->PVPFire<=0)return;
			$ev = new EntityCombustByEntityEvent($Damager, $entity, $this->PVPFire);
			Server::getInstance()->getPluginManager()->callEvent($ev);
			if(!$ev->isCancelled())
				$entity->setOnFire($ev->getDuration());
		}
	}
	public function getPVEFire(){
		return $this->PVEFire;
	}
	public function getPVPFire(){
		return $this->PVPFire;
	}
	public function getBlowFly(){
		return $this->blowFly;
	}
	public function getKnockBack(){
		return $this->knockBack;
	}
	public function knockBackAndblowFly(Player $damager, Entity $entity, $e){
		if($this->blowFly<=0 and $this->knockBack<=0)return;
		$entity->knockBack = true;
		$e->setKnockBack(0);
		$v3=new Vector3(0, 0.4, 0);
		if($this->blowFly>0)
			$v3=new Vector3(0, $this->blowFly*0.1, 0);
		if($this->knockBack>0){
			$x = $entity->x - $damager->x;
			$z = $entity->z - $damager->z;
			$f = sqrt($x * $x + $z * $z);
			if($f > 0){
				$f = 1 / $f;
				// $motion = new Vector3($entity->motionX, $entity->motionY, $entity->motionZ);
				$motion = new Vector3(0, 0, 0);
				// $motion->x /= 2;
				// $motion->z /= 2;
				$motion->x = $x * $f * $this->knockBack*0.4;
				$motion->z = $z * $f * $this->knockBack*0.4;
				$v3=$v3->add($motion);
			}
		}
		if($entity->getBuff()->getTough()>0){
			$entity->setMotion($v3->multiply((100-$entity->getBuff()->getTough())/100));
		}else $entity->setMotion($v3);
	}
	public function getKillMessage(Player $damager, Entity $entity){
		if($this->killMessage==false)return false;
		return str_replace('{damager}', $damager->getName(), str_replace('{entity}', $entity->getName(), $this->killMessage));
	}
	public function Lightning(Player $damager, Entity $entity){
		if($entity instanceof Player){
			if($this->PVPLightning!==false and mt_rand(1, 100)<=$this->PVPLightning[1]){
				$entity->getLevel()->spawnLightning($entity,$this->PVPLightning[0],$damager);
			}
		}elseif($entity instanceof BaseEntity){
			if($this->PVELightning!==false and mt_rand(1, 100)<=$this->PVELightning[1]){
				$entity->getLevel()->spawnLightning($entity,$this->PVELightning[0],$damager);
			}
		}
	}
	public function getPVELightning(){
		return $this->PVELightning;
	}
	public function getPVPLightning(){
		return $this->PVPLightning;
	}
	public function freeze(Player $damager, Player $entity){
		if($this->freeze===false)return;
		if(mt_rand(1, 100) <= $this->freeze[1]) {
			$damager->addTitle('§e触发§d冰冻!');
			$entity->setFreeze($this->freeze[0]);
			$entity->sendTitle('§d你被'.$damager->getName().'冰冻了！');
		}
	}
	public function getFreeze(){
		return $this->freeze;
	}
	public function canUse(Player $player, $a = true){
		if($a and $player->getGTo()<6 and !in_array($this->getWlevel(), ['入门', '普通', '中级', '中级', '高级', '定制', '仙器', '传说'])){
			return false;
		}
		if($this->binding==='*' or $this->binding===strtolower($player->getName()))return true;
		return false;
	}
	public function getBinding(){
		return $this->binding;
	}
	public function getPVEvampire(){
		return $this->PVEVampire;
	}
	public function getPVPvampire(){
		return $this->PVPVampire;
	}
	public function getGroupsOfBack(){
		return $this->groupOfBack;
	}
	public function getPVEdamage(){
		return $this->PVEdamage;
	}
	public function getPVPdamage(){
		return $this->PVPdamage;
	}
	public function getWeaponType(){
		return $this->WeaponType;
	}
	public function getAttribute($attribute, $external=false){
		if($external){
			return $this->conf[$attribute]??false;
		}else{
			if($this->getDType()=='pvp')
				return $this->conf['PVP'][$attribute]??false;
			elseif($this->getDType()=='pve')
				return $this->conf['PVE'][$attribute]??false;
			else return $this->conf[$attribute]??false;
		}
	}
	public function getArmour(Entity $entity){
		if($entity instanceof Player)
			return $this->PVPArmour;
		elseif($entity instanceof BaseEntity)
			return $this->PVEArmour;
		else return 0;
		
	}
	public function getPVPArmour(){
		return $this->PVPArmour;
	}
	public function getPVEArmour(){
		return $this->PVEArmour;
	}
	public function getLTName(){
		return $this->WeaponName;
	}
	public function isEquals($arr, $player){
		if($this->canUse($player) and $this->WeaponType==$arr[0] and $this->WeaponName==$arr[1])
			return true;
		return false;
	}
	public function getModifyAttackDamage(Entity $entity){
		if($entity instanceof Player){
			return $this->getPVPdamage();
		}elseif($entity instanceof BaseEntity){
			return $this->getPVEdamage();
		}else{
			return $this->getPVEdamage()/10;
		}
	}
	public function updateNameTag(){

    }
	public function getDType(){
		return $this->type;
	}
    public function getInfo() {
		return $this->conf['介绍'];
	}
	public static function getUpExp($level){
		return $level*2;
	}
	public function getMaxStackSize() : int{
		return $this->conf['可以叠加']==false?1:(int) $this->conf['可以叠加'];
	}
	public static function explodeEffect(String $string){
        if(strlen(trim($string))<=0)return [];
		$effects=[];
		foreach(explode('&', $string) as $effect){
		    if (strlen(trim($effect)<=0))continue;
			$tmp=explode(':', $effect);//ID:概率
			$effects[$tmp[0]]=$tmp[1];
		}
		return $effects;
	}
}