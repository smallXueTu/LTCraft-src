<?php
namespace LTPet\Commands;

use LTPet\Pet;
use LTPet\Main;
use pocketmine\Player;

class Admin
{
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        unset($plugin);
    }
    public function run($args, $player)
    {
//        if($player->getName() !== 'Angel_XX' AND $player instanceof Player)return;
        if(count($args) <= 0)return $player->sendMessage('§l§aLTPet>>§c用法:/pet 管理 [删除 删除全部 回收 回收全部 列表 赐予 重载皮肤]');
        switch($args[0]) {
        case 'givepet':
            if(count($args) < 3)return $player->sendMessage('§l§aLTPet>>§c用法/pet 管理 赐予 玩家ID 宠物类型 名字');
            if(!isset(Pet::$Pets[$args[2]]))return $player->sendMessage('§l§aLTPet>>§c没有这个类型的宠物');
            $p = $this->plugin->server->getPlayer($args[1]);
            if(!$p)return $player->sendMessage('§l§aLTPet>>§c目标不在线');
			$this->plugin->addPet($p, $args[2], $args[3]??$args[2].mt_rand(1,100));
            $player->sendMessage('§l§a[LTPet>>§a成功赐予玩家宠物。');
		break;
        case 'killpet':
            if(count($args) < 3)return $player->sendMessage('§l§a[LT宠物系统]§a用法/pet 管理 killpet 玩家名字 宠物名字');
            $p = $this->plugin->server->getPlayer($args[1]);
            if(!$p)return $player->sendMessage('§l§a[LT宠物系统]§c玩家不在线！');
			$name=Main::getCleanName($args[2]);
            if($this->plugin->comes[$p->getName()]->getPet($name)===null)return $player->sendMessage('§l§a[LT宠物系统]§c玩家没召唤这个宠物。');
			$this->plugin->comes[$p->getName()]->getPet($name)->close();
		break;
        case 'KillAllPet':
            if(count($args) < 2)return $player->sendMessage('§l§a[LT宠物系统]§a用法/pet 管理 KillAllPet 玩家名字');
            $p = $this->plugin->server->getPlayer($args[1]);
            if(!$p)return $player->sendMessage('§l§a[LT宠物系统]§c玩家不在线！');
            $this->killAll($p);
            $p->sendMessage('§lLTPet>>§c管理员回收了你的全部宠物！');
            return $player->sendMessage('§l§a[LT宠物系统]§a成功回收'.$args[1].'的全部宠物');
		break;
        case 'del':
            if(count($args) < 3)return $player->sendMessage('§l§a[LT宠物系统]§a用法/pet 管理 del 玩家名字 宠物名字');
            $p = $this->plugin->server->getPlayer($args[1]);
            if(!$p)return $player->sendMessage('§l§a[LT宠物系统]§c玩家不在线！');
			$name=Main::getCleanName($args[2]);
            if($this->plugin->comes[$p->getName()]->getPet($name)!==null){
				$this->plugin->comes[$p->getName()]->getPet($name)->close();
			}
			$p->removePet($name);
            $player->sendMessage('§l§a[LT宠物系统]§c成功删除玩家'.$args[1].'的宠物'.$args[2]);
            return $p->sendMessage('§l§aLTPet>>§a管理员删除了你的'.$args[2].'宠物');
		break;
        case 'delall':
            if(count($args) < 2)return $player->sendMessage('§l§aLTPet>>§a用法/pet 管理 delall 玩家名字');
            $p = $this->plugin->server->getPlayer($args[1]);
            if(!$p)return $player->sendMessage('§l§a[LT宠物系统]§c玩家不在线！');
            $this->killAll($p);
            $p->setAllPet([]);
            $player->sendMessage('§l§a[LT宠物系统]§c删除成功！');
            return $p->sendMessage('§l§a[LT宠物系统]§c管理员删除了你的全部宠物！');
		break;
        case 'list':
            if(count($args) < 2)return $player->sendMessage('§l§a[LT宠物系统]§a用法/pet 管理 list 玩家名字');
            $p = $this->plugin->server->getPlayer($args[1]);
            if(!$p)return $player->sendMessage('§l§a[LT宠物系统]§c玩家不在线！');
            if(count($p->gePets()) <= 0)return $player->sendMessage('§l§a[LT宠物系统]§c该玩家还没有宠物！');
            foreach($p->gePets() as $name => $mess) {
                $player->sendMessage('§d名字:'.$name."属性: \n§3★饥饿度:".$mess['hunger']."❤爱心度:".$mess['love']);
            }
		break;
        case 'reloadSkin':
			$this->plugin->skins=[];
			$this->plugin->loadSkins();
			$player->sendMessage('重载完成~');
		break;
        default:
            $player->sendMessage('§l§a[LT宠物系统]§c用法:/pet 管理 [del delall killpet KillAllPet list givepet reloadSkin] 值');
            break;
        }
    }
    public function killAll($player)
    {
        foreach($this->plugin->comes[$player->getName()]->getPets() as $pet)
            $pet->close();
    }
}
