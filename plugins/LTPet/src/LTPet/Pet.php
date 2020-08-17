<?php
namespace LTPet;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ByteTag;
use LTPet\Pets\WalkingPets\{
	LTRabbit,LTChicken,LTPig,LTSheep,LTWolf,LTVillager,LTOcelot,LTHorse,LTSilverfish,LTSlime,LTCreeper,LTNPC,LTLlama,LTSpider,LTSkeleton
};
use LTPet\Pets\FlyingPets\{
	LTWitherBoss,LTEnderDragon
};
use LTPet\Pets\MountPet;
class Pet{
	public static $Pets=[];
	public static $All=[];
	public static function getCount($pet){//获取需要物品
		switch($pet){
		case '鸡':
			return ['D级宠物碎片',10];
		case '狼':
		case '兔子':
		case '蠢虫':
			return ['C级宠物碎片',10];
		case '豹猫':
		case '苦力怕':
		case '史莱姆':
		case '村民':
			return ['B级宠物碎片',30];
		case '猪':
		case '羊':
		case '马':
		case '凋零':
			return ['A级宠物碎片',50];
		case '女仆':
			return ['S级宠物碎片',50];
		case '末影龙':
			return ['SS级宠物碎片',50];
		default:
			return false;
		}
	}
	public static function getSkinCount($skinName){//获取需要物品
		switch($skinName){
			case '朝夕女孩':
				return ['皮肤碎片', 2];
			break;
			case '清凉夏季女孩':
				return ['皮肤碎片', 3];
			break;
			case '暴露小女孩':
				return ['皮肤碎片', 4];
			break;
			case '黑酷少女':
				return ['皮肤碎片', 5];
			break;
			case '黑猫少女':
				return ['皮肤碎片', 6];
			break;
			case '灰发少女':
				return ['皮肤碎片', 6];
			break;
			case '清凉薄荷少女':
				return ['皮肤碎片', 10];
			break;
			case '糖果女孩':
				return ['皮肤碎片', 15];
			break;
			case '潮流女孩':
				return ['皮肤碎片', 20];
			break;
			case '甜蜜玉兔':
				return ['中秋节皮肤碎片', 6];
			break;
			case '猫女':
				return ['皮肤碎片', 30];
			break;
			case '背带小女孩':
				return ['皮肤碎片', 7];
			break;
			case '春晖女郎':
				return ['春晖碎片', 15];
			break;
		default:
			return '§d活动获得！';
		}
	}
	public static function init(){
		self::$Pets=[
			'鸡'=>'LTChicken',
			'猪'=>'LTPig',
			'羊'=>'LTSheep',
			'狼'=>'LTWolf',
			'村民'=>'LTVillager',
			'豹猫'=>'LTOcelot',
			'凋零'=>'LTWitherBoss',
			'兔子'=>'LTRabbit',
			'末影龙'=>'LTEnderDragon',
			'马'=>'LTHorse',
			'蠢虫'=>'LTSilverfish',
			'史莱姆'=>'LTSlime',
			'苦力怕'=>'LTCreeper',
			'羊驼'=>'LTLlama',
			'蜘蛛'=>'LTSpider',
			'骷髅'=>'LTSkeleton',
			'女仆'=>'LTNPC'
		];
		self::$All=[
			LTChicken::class,
			LTPig::class,
			LTSheep::class,
			LTWolf::class,
			LTVillager::class,
			LTOcelot::class,
			LTWitherBoss::class,
			LTRabbit::class,
			LTEnderDragon::class,
			LTHorse::class,
			LTSilverfish::class,
			LTSlime::class,
			LTCreeper::class,
			LTLlama::class,
			LTSpider::class,
			LTSkeleton::class,
			LTNPC::class
		];
		foreach(self::$All as $class){
			Entity::registerEntity($class);
		}
	}
	public static function Come($player,$info){
		if(!isset(self::$Pets[$info['type']]))
			return false;
		else
			$type=self::$Pets[$info['type']];
		$level=$player->getLevel();
		$nbt = new CompoundTag;
		$nbt->Pos = new ListTag("Pos",[
		   new DoubleTag("", $player->getX()),
		   new DoubleTag("", $player->getY() + 0.5),
		   new DoubleTag("", $player->getZ())
		]);
		$nbt->Rotation = new ListTag("Rotation",[
			new FloatTag("", 0),
			new FloatTag("", 0)
		]);
		$nbt->Speed = new DoubleTag("Speed", 1.8);
		$pet = Entity::createEntity($type, $level, $nbt, $player, $info);
		$pet->getAtt()->updateNameTag();
		$pet->spawnToAll();
		if($pet instanceof MountPet)$pet->setRideObject(new Ride($pet));
		Main::getInstance()->comes[$player->getName()]->addPet(Main::getCleanName($info['name']), $pet);
		return true;
	}
}