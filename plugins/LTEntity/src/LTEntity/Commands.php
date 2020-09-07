<?php

namespace LTEntity;

use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandExecutor;
use pocketmine\nbt\tag\StringTag;
use LTEntity\DataList;
use LTEntity\entity\BaseEntity;
use LTItem\Main as LTMain;
use LTItem\LTItem;

class Commands extends PluginBase implements CommandExecutor{
    /** @var Main $plugin */
    private Main $plugin;
	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if(!isset($args[0])) return $sender->sendMessage("用法 /ma help");
//		if($sender->getName()!=='Angel_XX' AND $sender instanceof Player)return $sender->sendMessage("§c你没这个权限！");
		switch($args[0]){
		case 'add':
		    /** @var Player $sender */
			if(!isset($args[2])) return $sender->sendMessage("用法 /ma add <刷怪点名字> <类型>");
			if(!isset(DataList::$ModName[$args[2]])) return $sender->sendMessage("§c不支持此类型的生物,查看列表请输入 §e/ma type");
			$level = $sender->level;
			if($level->getName() !== $level->getFolderName()){
				$provider = $level->getProvider();
				$provider->getLevelData()->LevelName = new StringTag("LevelName", $level->getFolderName());
				$provider->saveLevelData();
			}
			if(!isset($this->plugin->enConfig[$args[1]])){
				$this->plugin->EnConfig[$args[1]] = [
					"刷怪点" => $args[1],
					"类型" => $args[2],
					"名字" => $args[2],
					"血量" => 20,
					"攻击" => 3,
					"团队" => false,
					"橙币" => false,
					"击杀药水" => [],
					"药水" => [],
					'参与橙币'=>0,
					'参与经验'=>0,
					'经验'=>0,
					"燃烧" => 0,
					"速度" => 1.8,
					"显示" => true,
					"抛射物允许反弹" => false,
					"死亡信息" => false,
					"怪物模式" => 1,
					"护甲" => 0,
					"数量" => 1,
					"刷怪时间" => 10,
					"死亡执行命令" => null,
					"掉落" => [],
					"参与击杀掉落" => [],
					"边界范围半径"=>12,
					"手持ID"=>false,
					"头盔ID"=>false,
					"胸甲ID"=>false,
					"护膝ID"=>false,
					"鞋子ID"=>false,
					"x" => $sender->getFloorX() + 0.5,
					"y" => $sender->getFloorY(),
					"z" => $sender->getFloorZ() + 0.5,
					"世界"=>$sender->getLevel()->getFolderName(),
					"悬浮介绍"=>false,
				];
				if($args[2] == "npc"){
					$this->plugin->enConfig[$args[1]]["皮肤"] = "默认";
					$this->plugin->enConfig[$args[1]]["皮肤ID"] = "Standard_Custom";
					$this->plugin->enConfig[$args[1]]["披风"] = false;
				}
				$sender->sendMessage("§b刷怪点§e{$args[1]}§b创建成功.");
			}else
			return $sender->sendMessage("§c刷怪点§e{$args[1]}§c已经存在,请勿设置两个相同名字的刷怪点.");
		break;
		case 'test':
			foreach($this->plugin->EnConfig as $vid => $data) {
				foreach($data['掉落']??[] as $dropInfo){
					$dropItem = explode(':', $dropInfo);
					if($dropItem[0]=='材料')
						$item = LTMain::getInstance()->createMaterial($dropItem[1]);
					elseif(in_array($dropItem[0], ['近战', '远程', '通用']))
						$item = LTMain::getInstance()->createWeapon($dropItem[0], $dropItem[1]);
					elseif($dropItem[0]=='盔甲')
						$item = LTMain::getInstance()->createArmor($dropItem[1]);
					if(!($item instanceof LTItem)){
						echo $vid.'掉落'.$dropInfo.'不存在！'.PHP_EOL;
					}
				}
				foreach($data['参与击杀掉落']??[] as $dropInfo){
					$dropItem = explode(':', $dropInfo);
					if($dropItem[0]=='材料')
						$item = LTMain::getInstance()->createMaterial($dropItem[1]);
					elseif(in_array($dropItem[0], ['近战', '远程', '通用']))
						$item = LTMain::getInstance()->createWeapon($dropItem[0], $dropItem[1]);
					elseif($dropItem[0]=='盔甲')
						$item = LTMain::getInstance()->createArmor($dropItem[1]);
					if(!($item instanceof LTItem)){
						echo $vid.'参与击杀掉落'.$dropInfo.'不存在！'.PHP_EOL;
					}
				}
			}
		break;
		case 'del':
			if(!isset($args[1])) return $sender->sendMessage("§c请指定需要被删除的刷怪点名字.");
			if(!isset($this->plugin->enConfig[$args[1]]))return $sender->sendMessage("§c刷怪点§e{$args[1]}§c不存在.");
			$data = $this->plugin->enConfig[$args[1]];
				if(isset($this->plugin->spawnTmp[$data["刷怪点"]]["标识"])){
				$level=$sender->server->getLevelByName($data['世界']);
				if($level instanceof Level)$level->removeFloatingText($this->plugin->spawnTmp[$data["刷怪点"]]["悬浮字"]);
				unset($this->plugin->spawnTmp[$data["刷怪点"]],$this->plugin->enConfig[$args[1]]);
			}
			$sender->sendMessage("§b刷怪点§e{$args[1]}§b已成功删除.");
		break;
		case 'set':
			if(!isset($args[3]))return $sender->sendMessage("用法 /ma help");
			if(!isset($this->plugin->enConfig[$args[1]])) return $sender->sendMessage("§c刷怪点§e{$args[1]}§c不存在.");
			if(in_array($args[2],["血量","攻击","燃烧","数量","速度","边界范围半径"]) and !is_numeric($args[3])) return $sender->sendMessage("§6{$args[2]}必须是数字.");
			if($args[2] == "类型" and !isset(DataList::$ModName[$args[3]])) return $sender->sendMessage("§c请使用生物的中文名字,比如: 僵尸 苦力怕");
			switch($args[2]){
			case "类型":
			case "名字":
				$this->plugin->enConfig[$b[1]][$b[2]]=$args[3];
				$sender->sendMessage("§6设置刷怪点§e{$b[1]}§6的怪物§e{$b[2]}§6为: §e".implode(" ", $args)."");
			break;
			case "死亡执行命令":
				$b=$args;
				array_shift($args);
				array_shift($args);
				array_shift($args);
				$this->plugin->enConfig[$b[1]][$b[2]]=implode(" ", $args);
				$sender->sendMessage("§6设置刷怪点§e{$b[1]}§6的怪物§e{$b[2]}§6为: §e".implode(" ", $args)."");
			break;
			case "血量":
			case "攻击":
			case "燃烧":
			case "数量":
			case "速度":
			case "刷怪时间":
			case "边界范围半径":
				$this->plugin->enConfig[$args[1]][$args[2]] = (int)$args[3];
				$sender->sendMessage("§6设置刷怪点§e{$args[1]}§6的怪物§e{$args[2]}§6为: §e{$args[3]}");
			break;
			case '显示':
				$v=false;
				if($args[3]=='显示'){
					$v=true;
					$d='显示';
				}else
					$d='不显示';
				$this->plugin->enConfig[$args[1]][$args[2]] =$v;
				$sender->sendMessage('§6设置显示格式为'.$d);
			break;
			case "添加掉落":
				$this->plugin->enConfig[$args[1]]["掉落"][] = $args[3];
				$sender->sendMessage("§6添加刷怪点§e{$args[1]}§6生物的死亡掉落: §e{$args[3]}");
			break;
			case "添加药水":
				$this->plugin->enConfig[$args[1]]["药水"][] = $args[3];
				$sender->sendMessage("§6添加刷怪点§e{$args[1]}§6生物的药水效果: §e{$args[3]}");
			break;
			default:
				return $sender->sendMessage("用法 /ma help");
			}
			$level=$sender->server->getLevelByName($this->plugin->enConfig[$args[1]]['世界']);
			if($level instanceof Level){
				foreach($level->getEntities() as $entity){
					if($entity instanceof BaseEntity and $entity->enConfig['刷怪点']===$args[1])$entity->close();
				}
			}
		break;
		case 'spawn':
			if(isset($args[1])){
				if(isset($this->plugin->spawnTmp[$args[1]])){
					$this->plugin->spawnTmp[$args[1]]['剩余时间']=0;
					return $sender->sendMessage("§6刷出怪物成功！");
				}else return $sender->sendMessage("§6不存在这个刷怪点！");
			}else return $sender->sendMessage("§6用法/ma spawn 刷怪点");
		case 'setSkin':
			if(!isset($args[2])) return $sender->sendMessage("用法 /ma help");
			if(!isset($this->plugin->enConfig[$args[1]])) return $sender->sendMessage("§c刷怪点§e{$args[1]}§c不存在.");
			if($this->plugin->enConfig[$args[1]]["类型"] != "npc") return $sender->sendMessage("§c刷怪点§e{$args[1]}§c不是NPC的刷怪点.");
			if(!isset($this->plugin->skinId[$args[3]])) return $sender->sendMessage("提示: 不存在皮肤 {$args[3]}.");
			if(!file_exists($this->plugin->getDataFolder() . '/skins/cache/' . $this->plugin->skinId[$args[3]]))return $sender->sendMessage("提示: 不存在皮肤 {$args[3]} 的文件内容.");
			$this->plugin->enConfig[$args[1]]["皮肤"] = $args[2];
			$sender->sendMessage("§a成功设置刷怪点§e{$args[1]}§a的NPC皮肤为§e{$args[2]}§a.");
		break;
		case 'saveSkin':
			$player = $this->plugin->getServer()->getPlayer($args[1]);
			if(!$player instanceof Player) return $sender->sendMessage("§c玩家§6{$args[1]}c不存在.");
			$name = isset($args[2])? $args[2]: $player->getName();
			$this->plugin->skinId[$name] = [$player->getSkinId(),base64_encode($player->getSkinData())];
			$this->plugin->skinConfig->setAll($this->plugin->skinId);
			$this->plugin->skinConfig->save();
			return $sender->sendMessage("§a成功存储玩家§6{$args[1]}§a的皮肤,储存皮肤名字: §e{$name}.");
		break;
		case 'help':
			$helpList = [
				"§6〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓 §aLTEntity HELP§6 〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓 ",
				"§b➣ /ma add <刷怪点名字> <类型> <怪物大小>",
				"§a➣ /ma del <刷怪点名字>",
				"§e➣ /ma set <刷怪点名字> <添加掉落> <物品ID: 特殊值: 数量: 几率: 附魔ID: 附魔等级: 自定义名字>",
				"§c➣ /ma type <查看怪物列表>",
				"§6➣ /ma set <刷怪点名字> <类型/名字/血量/攻击/燃烧/数量/速度/边界范围半径/刷怪时间/死亡执行命令> <项目值>",
				"§5➣ /ma setSkin <刷怪点名字> <已存储皮肤名字>",
				"§a➣ /ma saveSkin <玩家名字> <存储皮肤名字>",
				"§6〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓"
			];
			foreach($helpList as $help) $sender->sendMessage($help);
		break;
		case 'type':
				$typelistings = ["§d〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓 §aLTEntity TYPE§d 〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓",
				"§a➣ 鸡, 牛, 羊, 猪, 狼, 村民, 哞菇, 鱿鱼, 兔子, 蝙蝠, 豹猫, ",
				"§b➣ 骡子, 骷髅马, 僵尸马, 僵尸村民, 僵尸, 苦力怕, 骷髅, 蜘蛛",
				"§c➣ 僵尸猪人, 史莱姆, 末影人, 蠢虫, 洞穴蜘蛛, 恶魂",
				"§e➣ 岩浆怪, 烈焰人, 女巫, 流浪者, 剥皮者, 凋零骷髅",
				"§5➣ 雪傀儡, 铁傀儡, 凋零, 守卫者, 老守卫者, 北极熊, 末影螨",
				"§a➣ 潜匿之贝, 末影龙, npc, 驴, 马, 唤魔者",
				"§d〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓〓"
			];
			foreach($typelistings as $type)
				$sender->sendMessage($type);
		break;
		case 'reload':
			$this->plugin->reloadConfig();
			return $sender->sendMessage("§a重载完成");
		break;
		default:
			return $sender->sendMessage("用法 /ma help");
		}
			$this->plugin->RPGSpawn->setAll($this->plugin->EnConfig);
			$this->plugin->RPGSpawn->save();
	}
}
