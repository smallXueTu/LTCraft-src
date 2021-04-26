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
     * @var array 饰品冷却时间
     */
    public static array $ornaments = [];
    /**
     * @var array 使用材料冷却时间
     */
    public static array $material = [];
    /**
     * @var array Mana盔甲护盾
     */
    public static array $manaArmorShield = [];
    /**
     * @var array 查询冷却时间
     */
    public static array $query = [];
    /**
     * @var array 发射冷却时间
     */
    public static array $launch = [];
    /**
     * @var array 图拉的意志
     */
    public static array $willOfTula = [];

    public static function onPlayerQuit(Player $player){
        $player = $player->getName();
        unset(
            self::$query[strtolower($player)],
            self::$weapon[$player],
            self::$material[$player],
            self::$launch[$player],
            self::$ornaments[$player],
            self::$willOfTula[$player],
            self::$manaArmorShield[$player],
        );
    }
    public static function onPlayerJoin(Player $player){
        $player = $player->getName();
        self::$weapon[$player] = [];
        self::$material[$player] = [];
        self::$ornaments[$player] = [];
        self::$launch[$player] = 0;
        self::$willOfTula[$player] = 0;
        self::$manaArmorShield[$player] = 0;
    }
}