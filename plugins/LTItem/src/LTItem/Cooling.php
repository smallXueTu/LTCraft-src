<?php


namespace LTItem;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;

/**
 * 简易冷却类
 * TODO：添加判断冷却方法来简化代码。
 * Class Cooling
 * @package LTItem
 */
class Cooling implements Listener
{
    /**
     * @var array Skill冷却时间
     */
    public static array $weapon = [];
    /**
     * @var array 使用材料冷却时间
     */
    public static array $material = [];
    /**
     * @var array 查询冷却时间
     */
    public static array $query = [];
    /**
     * @var array 发射冷却时间
     */
    public static array $launch = [];

    public static function onPlayerQuit(Player $player){
        $player = $player->getName();
        unset(
            self::$query[strtolower($player)],
            self::$weapon[$player],
            self::$material[$player],
            self::$launch[$player],
        );
    }
    public static function onPlayerJoin(Player $player){
        $player = $player->getName();
        self::$weapon[$player] = [];
        self::$material[$player] = [];
        self::$launch[$player] = 0;
    }
}