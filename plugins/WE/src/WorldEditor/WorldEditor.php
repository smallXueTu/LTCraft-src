<?php
namespace WorldEditor;

use pocketmine\Player;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;

class WorldEditor extends PluginBase implements Listener
{
    private $Positions = [];
    private $clipboards = [];

	public function onDisable(){
		// \LTCraft\Main::getInstance()->r->save(false);
	}
    public function onEnable()
    {
        @mkdir($this->getDataFolder());
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
		// \LTCraft\Main::getInstance()->r->save(false);
		// foreach(\LTItem\Main::getInstance()->TY as $conf){
			// $conf->save(false);
		// }
    }
    public function onJoinEvent(PlayerJoinEvent $event)
    {
        $this->Positions[$event->getPlayer()->getName()] = [];
    }
    public function onQuitEvent(PlayerQuitEvent $event)
    {
        unset($this->Positions[$event->getPlayer()->getName()]);
    }
    public function onCommand(CommandSender $sender, Command $command, $label, array $params)
    {
        if(!($sender instanceof Player))$sender->sendMessage('§c请在游戏里使用创世魔杖');
//        if($sender->getName() !== 'Angel_XX' and $sender->getName() !== 'Angel_XX')return $sender->sendMessage('§c你无权使用创世神');
        switch($command) {
        case '粘贴':
            $this->W_paste($this->clipboards[$sender->getName()], $sender->asPosition()->toInteger(), $sender);
            break;
        case '复制':
            $count = $this->countBlocks($sender->getName(), $startX, $startY, $startZ);
            $blocks = $this->W_copy($sender);
            if(count($blocks) > 0) {
                $offset = array($startX - $sender->getX() - 0.5, $startY - $sender->getY(), $startZ - $sender->getZ() - 0.5);
                $this->clipboards[$sender->getName()] = array($offset, $blocks);
            }
            break;
        case '剪切':
            $count = $this->countBlocks($sender->getName(), $startX, $startY, $startZ);
            $blocks = $this->W_cut($sender);
            if(count($blocks) > 0) {
                $offset = array($startX - $sender->getX() - 0.5, $startY - $sender->getY(), $startZ - $sender->getZ() - 0.5);
                $this->clipboards[$sender->getName()] = array($offset, $blocks);
            }
            break;
        case '清除':
            unset($this->Positions[$sender->getName()]);
            $sender->sendMessage('§e当前选中的方块编辑区域 起点终点坐标已被清除');
            break;
        case '坐标1':
            $count = $this->setPosition1($sender->getName(), ($pos = $sender->asPosition()->toInteger()));
            $sender->sendMessage('§e起点坐标为 ('.$pos->x.', '.$pos->y.', '.$pos->z.')方块数为'.$count);
            break;
        case '坐标2':
            $count = $this->setPosition2($sender->getName(), ($pos = $sender->asPosition()->toInteger()));
            $sender->sendMessage('§e起点终标为 ('.$pos->x.', '.$pos->y.', '.$pos->z.')方块数为'.$count);
            break;
        case '空心':
            $filled = false;
        case '实心':
            if(!isset($filled)) {
                $filled = true;
            }
            if(!isset($params[1]) or $params[1] == '') 
				return $sender->sendMessage('§c用法: /实心 <方块ID> <球形方块区域的半径r>');
            $radius = abs(floatval($params[1]));

            $items = Item::fromString($params[0], true);
            if($items) {
                foreach($items as $item) {
                    if($item->getID() > 0xff) {
						return $sender->sendMessage('§c方块出错,请使用正确的方块ID');
                    }
                }
                $this->W_sphere($sender->asPosition()->toInteger(), $items, $radius, $radius, $radius, $filled, $sender);
            } else 
				return $sender->sendMessage('§c方块出错,请使用正确的方块ID');
            break;
        case '体积':
            if(!isset($filled)) {
                $filled = true;
            }
            if(!isset($params[1]) or $params[1] == '') {
				return $sender->sendMessage('§c用法: /体积 <方块ID> <球形方块区域的半径r>');
            }
            $radius = abs(floatval($params[1]));

            $items = Item::fromString($params[0], true);
            if($items) {
                foreach($items as $item) {
                    if($item->getID() > 0xff) {
						return $sender->sendMessage('§c方块出错,请使用正确的方块ID');
                    }
                }
                $this->W_cube($sender->asPosition()->toInteger(), $items, $radius, $radius, $radius, $filled, $player);
            } else
				return $sender->sendMessage('§c方块出错,请使用正确的方块ID');
            break;
        case '置换':
            if(empty($params))
                return $sender->sendMessage('用法: /置换 <方块ID>');
            $items = Item::fromString($params[0], true);
            if($items) {
                foreach($items as $item)
                    if($item->getID() > 0xff)
                        return $sender->sendMessage('§c方块出错,请使用正确的方块ID');
                $this->W_set($items,$sender);
            } else
                return $sender->sendMessage('§c方块出错,请使用正确的方块ID');
            break;
        case '替换':
            if(count($params) != 2) {
                $sender->sendMessage('用法: /替换 <需要被替换的某种方块ID> <替换为特定的方块ID>');
                return true;
            }

            $count = $this->countBlocks($sender->getName());
            $item1 = Item::fromString($params[0]);
            if($item1->getID() > 0xff)return $sender->sendMessage('§c需要被替换的某种方块的ID出错');
            $items2 = Item::fromString($params[1], true);
            if($items2) {
                foreach($items2 as $item)
                    if($item->getID() > 0xff)
                        return $sender->sendMessage('§c需要被替换为特定方块的ID出错');
                $this->W_replace($item1, $items2, $sender);
            } else
                return $sender->sendMessage('§c方块出错,请使用正确的方块ID');
            break;
        }
    }

    public function setPosition1($username, Position $position)
    {
        $this->Positions[$username][0] = $position;
        $count = $this->countBlocks($username);
        return $count;
    }

    public function setPosition2($username, Position $position)
    {
        $this->Positions[$username][1] = $position;
        $count = $this->countBlocks($username);
        return $count;
    }

    private function countBlocks($username, &$startX = null, &$startY = null, &$startZ = null): int{
        $selection = $this->Positions[$username];
        if(!isset($selection[0]) or !isset($selection[1]))
            return 0;
        $startX = min($selection[0]->x, $selection[1]->x);
        $endX = max($selection[0]->x, $selection[1]->x);
        $startY = min($selection[0]->y, $selection[1]->y);
        $endY = max($selection[0]->y, $selection[1]->y);
        $startZ = min($selection[0]->z, $selection[1]->z);
        $endZ = max($selection[0]->z, $selection[1]->z);
        return ($endX - $startX + 1) * ($endY - $startY + 1) * ($endZ - $startZ + 1);
    }

    private function W_paste($clipboard, Position $pos, $player)
    {
        if(count($clipboard) !== 2) 
			return $player->sendMessage('§c请先复制或剪切要粘贴的方块区域');
        $clipboard[0][0] += $pos->x - 0.5;
        $clipboard[0][1] += $pos->y;
        $clipboard[0][2] += $pos->z - 0.5;
        $offset = array_map('round', $clipboard[0]);
        $count = 0;
        foreach($clipboard[1] as $x => $i) {
            foreach($i as $y => $j) {
                foreach($j as $z => $block) {
                    $b = Block::get(ord($block[0]), ord($block[1]));
                    if(0 == $pos->getLevel()->setBlock(new Vector3($x + $offset[0], $y + $offset[1], $z + $offset[2]), $b, false)) {
                        $count++;
                    }
                    unset($b);
                }
            }
        }
		return $player->sendMessage('§c总共有 $count 个方块被成功粘贴.');
    }

    private function W_copy($player)
    {
        $selection = $this->Positions[$player->getName()];
        if(!isset($selection[0]) or !isset($selection[1])){
			$player->sendMessage('§c请先选中要复制的方块区域');
			return 0;
		}
        if($selection[0]->getLevel() !== $selection[1]->getLevel()) {
			$player->sendMessage('§c世界不相同！！');
			return 0;
		}
        $level=$selection[0]->getLevel();
        $blocks = array();
        $startX = min($selection[0][0], $selection[1][0]);
        $endX = max($selection[0][0], $selection[1][0]);
        $startY = min($selection[0][1], $selection[1][1]);
        $endY = max($selection[0][1], $selection[1][1]);
        $startZ = min($selection[0][2], $selection[1][2]);
        $endZ = max($selection[0][2], $selection[1][2]);
        $count = $this->countBlocks($player->getName());
        for($x = $startX; $x <= $endX; ++$x) {
            $blocks[$x - $startX] = array();
            for($y = $startY; $y <= $endY; ++$y) {
                $blocks[$x - $startX][$y - $startY] = array();
                for($z = $startZ; $z <= $endZ; ++$z) {
                    $b = $level->getBlock(new Vector3($x, $y, $z));
                    $blocks[$x - $startX][$y - $startY][$z - $startZ] = chr($b->getID()).chr($b->getDamage());
                    unset($b);
                }
            }
        }
		$player->sendMessage('§c总共有'.$count.'个方块被成功复制');
        return $blocks;
    }

    private function W_cut($player)
    {
        $selection = $this->Positions[$player->getName()];
        if(!isset($selection[0]) or !isset($selection[1])){
			$player->sendMessage('§c请先选中要剪贴的方块区域');
			return 0;
		}
        $totalCount = $this->countBlocks($player->getName());
        if($totalCount > 512)
            $send = false;
        else
            $send = true;
        $level = $selection[0]->getLevel();
        $blocks = array();
        $startX = min($selection[0]->x, $selection[1]->x);
        $endX = max($selection[0]->x, $selection[1]->x);
        $startY = min($selection[0]->y, $selection[1]->y);
        $endY = max($selection[0]->y, $selection[1]->y);
        $startZ = min($selection[0]->z, $selection[1]->z);
        $endZ = max($selection[0]->z, $selection[1]->z);
        $count = $this->countBlocks($player->getName());
        $air = new Air();
        for($x = $startX; $x <= $endX; ++$x) {
            $blocks[$x - $startX] = array();
            for($y = $startY; $y <= $endY; ++$y) {
                $blocks[$x - $startX][$y - $startY] = array();
                for($z = $startZ; $z <= $endZ; ++$z) {
                    $b = $level->getBlock(new Vector3($x, $y, $z));
                    $blocks[$x - $startX][$y - $startY][$z - $startZ] = chr($b->getID()).chr($b->getDamage());
                    $level->setBlock(new Vector3($x, $y, $z), $air, false, $send);
                    unset($b);
                }
            }
        }
        if($send === false) {
            $forceSend = function($X, $Y, $Z) {
                $this->changedCount[$X.':'.$Y.':'.$Z] = 4096;
            };
            $forceSend->bindTo($level, $level);
            for($X = $startX >> 4; $X <= ($endX >> 4); ++$X) {
                for($Y = $startY >> 4; $Y <= ($endY >> 4); ++$Y) {
                    for($Z = $startZ >> 4; $Z <= ($endZ >> 4); ++$Z) {
                        $forceSend($X, $Y, $Z);
                    }
                }
            }
        }
		$player->sendMessage('§a总共有'.$count.'个方块被成功剪切.');
		return $blocks;
    }

    private function W_set($blocks, $player)
    {
        $selection = $this->Positions[$player->getName()];
        if(!isset($selection[0]) or !isset($selection[1]))
			return $player->sendMessage('§c请先选中要置换的方块区域');
        if($selection[0]->getLevel() !== $selection[1]->getLevel()) 
			return $player->sendMessage('§c世界不相同！！');
        $totalCount = $this->countBlocks($player->getName());
        $level = $selection[0]->getLevel();
        $bcnt = count($blocks) - 1;
        if($bcnt < 0)return $player->sendMessage('§c方块出错');
        $startX = min($selection[0]->x, $selection[1]->x);
        $endX = max($selection[0]->x, $selection[1]->x);
        $startY = min($selection[0]->y, $selection[1]->y);
        $endY = max($selection[0]->y, $selection[1]->y);
        $startZ = min($selection[0]->z, $selection[1]->z);
        $endZ = max($selection[0]->z, $selection[1]->z);
        $count = 0;
        for($x = $startX; $x <= $endX; ++$x) {
            for($y = $startY; $y <= $endY; ++$y) {
                for($z = $startZ; $z <= $endZ; ++$z) {
                    $a = $level->getBlock(new Vector3($x, $y, $z));
                    $b = $blocks[mt_rand(0, $bcnt)];
                    if($a->getID() != 0) {
                        if($level->setBlock(new Vector3($x, $y, $z), $b->getBlock(), false, false)) {
                            $count++;
                        }
                    }
                }
            }
        }
		return $player->sendMessage('§a总共有'.$count.'个方块被成功置换');
    }

    private function W_replace(Item $block1, $blocks2, $player)
    {
        $selection = $this->Positions[$player->getName()];
        if(!isset($selection[0]) or !isset($selection[1]))
			return $player->sendMessage('§c请先选中要置换的方块区域');
        if($selection[0]->getLevel() !== $selection[1]->getLevel()) 
            return $player->sendMessage('§c世界不相同！！');
        $totalCount = $this->countBlocks($player->getName());
        $level = $selection[0]->getLevel();
        $id1 = $block1->getID();
        $meta1 = $block1->getDamage();

        $bcnt2 = count($blocks2) - 1;
        if($bcnt2 < 0) 
			return $player->sendMessage('§c方块出错.\n');
        $startX = min($selection[0]->x, $selection[1]->x);
        $endX = max($selection[0]->x, $selection[1]->y);
        $startY = min($selection[0]->y, $selection[1]->y);
        $endY = max($selection[0]->y, $selection[1]->y);
        $startZ = min($selection[0]->z, $selection[1]->z);
        $endZ = max($selection[0]->z, $selection[1]->z);
        $count = 0;
        for($x = $startX; $x <= $endX; ++$x) {
            for($y = $startY; $y <= $endY; ++$y) {
                for($z = $startZ; $z <= $endZ; ++$z) {
                    $b = $level->getBlock(new Vector3($x, $y, $z));
                    if($b->getID() === $id1) {
                        if($level->setBlock($b, $blocks2[mt_rand(0, $bcnt2)]->getBlock(), false, false)) {
                            $count++;
                        }
                    }
                    unset($b);
                }
            }
        }
		return $player->sendMessage('§a总共有'.$count.'个方块被成功替换');
    }

    public static function lengthSq($x, $y, $z)
    {
        return ($x * $x) + ($y * $y) + ($z * $z);
    }

    private function W_sphere(Position $pos, $blocks, $radiusX, $radiusY, $radiusZ, $filled = true, $player)
    {
        $count = 0;

        $radiusX += 0.5;
        $radiusY += 0.5;
        $radiusZ += 0.5;

        $invRadiusX = 1 / $radiusX;
        $invRadiusY = 1 / $radiusY;
        $invRadiusZ = 1 / $radiusZ;

        $ceilRadiusX = (int) ceil($radiusX);
        $ceilRadiusY = (int) ceil($radiusY);
        $ceilRadiusZ = (int) ceil($radiusZ);

        $bcnt = count($blocks) - 1;

        $nextXn = 0;
        $breakX = false;
        for($x = 0; $x <= $ceilRadiusX and $breakX === false; ++$x) {
            $xn = $nextXn;
            $nextXn = ($x + 1) * $invRadiusX;
            $nextYn = 0;
            $breakY = false;
            for($y = 0; $y <= $ceilRadiusY and $breakY === false; ++$y) {
                $yn = $nextYn;
                $nextYn = ($y + 1) * $invRadiusY;
                $nextZn = 0;
                $breakZ = false;
                for($z = 0; $z <= $ceilRadiusZ; ++$z) {
                    $zn = $nextZn;
                    $nextZn = ($z + 1) * $invRadiusZ;
                    $distanceSq = WorldEditor::lengthSq($xn, $yn, $zn);
                    if($distanceSq > 1) {
                        if($z === 0) {
                            if($y === 0) {
                                $breakX = true;
                                $breakY = true;
                                break;
                            }
                            $breakY = true;
                            break;
                        }
                        break;
                    }

                    if($filled === false) {
                        if(WorldEditor::lengthSq($nextXn, $yn, $zn) <= 1 and WorldEditor::lengthSq($xn, $nextYn, $zn) <= 1 and WorldEditor::lengthSq($xn, $yn, $nextZn) <= 1) {
                            continue;
                        }
                    }

                    $count += ($pos->getLevel()->setBlock($pos->add($x, $y, $z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false) == 0 ? 1 : 0);
                    $count += ($pos->getLevel()->setBlock($pos->add(-$x, $y, $z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false) == 0 ? 1 : 0);
                    $count += ($pos->getLevel()->setBlock($pos->add($x, -$y, $z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false) == 0 ? 1 : 0);
                    $count += ($pos->getLevel()->setBlock($pos->add($x, $y, -$z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false) == 0 ? 1 : 0);
                    $count += ($pos->getLevel()->setBlock($pos->add(-$x, -$y, $z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false) == 0 ? 1 : 0);
                    $count += ($pos->getLevel()->setBlock($pos->add($x, -$y, -$z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false) == 0 ? 1 : 0);
                    $count += ($pos->getLevel()->setBlock($pos->add(-$x, $y, -$z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false) == 0 ? 1 : 0);
                    $count += ($pos->getLevel()->setBlock($pos->add(-$x, -$y, -$z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false) == 0 ? 1 : 0);

                }
            }
        }

		return $player->sendMessage('§a'.$count.'个方块的球形方块区域成功生成');
    }

    private function W_cube(Position $pos, $blocks, $radiusX, $radiusY, $radiusZ, $filled = true, $player)
    {
        $count = 0;

        $radiusX += 0.5;
        $radiusY += 0.5;
        $radiusZ += 0.5;

        $invRadiusX = 1 / $radiusX;
        $invRadiusY = 1 / $radiusY;
        $invRadiusZ = 1 / $radiusZ;

        $ceilRadiusX = (int) ceil($radiusX);
        $ceilRadiusY = (int) ceil($radiusY);
        $ceilRadiusZ = (int) ceil($radiusZ);

        $bcnt = count($blocks) - 1;

        $nextXn = 0;
        $breakX = false;
        for($x = 0; $x <= $ceilRadiusX and $breakX === false; ++$x) {
            $xn = $nextXn;
            $nextXn = ($x + 1) * $invRadiusX;
            $nextYn = 0;
            $breakY = false;
            for($y = 0; $y <= $ceilRadiusY and $breakY === false; ++$y) {
                $yn = $nextYn;
                $nextYn = ($y + 1) * $invRadiusY;
                $nextZn = 0;
                $breakZ = false;
                for($z = 0; $z <= $ceilRadiusZ; ++$z) {
                    $zn = $nextZn;
                    $nextZn = ($z + 1) * $invRadiusZ;
                    $distanceSq = WorldEditor::lengthSq($xn, $yn, $zn);
                    if($distanceSq > 1) {
                        if($z === 0) {
                            if($y === 0) {
                                $breakX = true;
                                $breakY = true;
                                break;
                            }
                            $breakY = true;
                            break;
                        }
                        break;
                    }

                    if($filled === false) {
                        if(WorldEditor::lengthSq($nextXn, $yn, $zn) <= 1 and WorldEditor::lengthSq($xn, $nextYn, $zn) <= 1 and WorldEditor::lengthSq($xn, $yn, $nextZn) <= 1) {
                            continue;
                        }
                    }

                    $count += ($pos->getLevel()->setBlock($pos->add($x, $y, $z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false) == 0 ? 1 : 0);
                    $count += ($pos->getLevel()->setBlock($pos->add(-$x, $y, $z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false) == 0 ? 1 : 0);
                    $count += ($pos->getLevel()->setBlock($pos->add($x, -$y, $z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false) == 0 ? 1 : 0);
                    $count += ($pos->getLevel()->setBlock($pos->add($x, $y, -$z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false) == 0 ? 1 : 0);
                    $count += ($pos->getLevel()->setBlock($pos->add(-$x, -$y, $z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false) == 0 ? 1 : 0);
                    $count += ($pos->getLevel()->setBlock($pos->add($x, -$y, -$z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false) == 0 ? 1 : 0);
                    $count += ($pos->getLevel()->setBlock($pos->add(-$x, $y, -$z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false) == 0 ? 1 : 0);
                    $count += ($pos->getLevel()->setBlock($pos->add(-$x, -$y, -$z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false) == 0 ? 1 : 0);

                }
            }
        }

		return $player->sendMessage('§a'.$count.'个方块的多维体积方块区域成功生成');
    }
}