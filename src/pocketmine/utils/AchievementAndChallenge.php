<?php


namespace pocketmine\utils;


use LTItem\LTItem;
use pocketmine\item\Item;

class AchievementAndChallenge
{
    public static $ItemProgress = [
        '17' => ['achievement', '获得木头'],
        '162' => ['achievement', '获得木头'],
        '58' => ['achievement', '这是？工作台！'],
        '270' => ['achievement', '采矿时间到！'],
        '61' => ['achievement', '“热”门话题', '获得熔炉'],
        '265' => ['achievement', '来硬的！', '获得铁锭'],
        '290' => ['achievement', '耕种时间到！'],
        '291' => ['achievement', '耕种时间到！'],
        '292' => ['achievement', '耕种时间到！'],
        '293' => ['achievement', '耕种时间到！'],
        '294' => ['achievement', '耕种时间到！'],
        '297' => ['achievement', '烤面包'],
        '354' => ['achievement', '蛋糕是个谎言'],
        '274' => ['achievement', '获得升级', '升级你的稿子'],
        '267' => ['achievement', '出击时间到！'],
        '268' => ['achievement', '出击时间到！'],
        '272' => ['achievement', '出击时间到！'],
        '276' => ['achievement', '出击时间到！'],
        '283' => ['achievement', '出击时间到！'],
        '261' => ['achievement', '狙击手的对决'],
        '264' => ['achievement', '钻石！'],
        '373' => ['achievement', '本地的酿造厂','酿造一瓶药水'],
        '47' => ['achievement', '图书管理员'],
        '138' => ['achievement', '带信标回家'],
        '325:10' => ['achievement', '热气腾腾的'],
        '49' => ['achievement', '冰桶挑战'],
        '412' => ['achievement', '兔子的季节', '烹饪一个兔子肉。'],
        '444' => ['achievement', '天空极为极限'],
        '材料:泰拉钢锭' => ['achievement', '黑暗中的舞者', '制作泰拉凝聚板并凝聚一个泰拉钢锭'],
        '魔法:泰拉钢刃' => ['achievement', '絶境パラノイア', '获得泰拉钢刃'],
        '魔法:天翼族之眼' => ['challenge', '死亡天使', '获得战利品:天翼族之眼'],
        '魔法:天翼族之冠' => ['challenge', '征服天空', '获得战利品:天翼族之冠'],
        '魔法:禁忌之果' => ['challenge', '放心食用', '获得专利品:禁忌之果'],
        '魔法:托尔之戒' => ['challenge', '一步分层', '获得战利品:托尔之戒'],
        '魔法:奥丁之戒' => ['challenge', '回忆补时', '获得战利品:奥丁之戒'],
        '魔法:王者之剑' => ['challenge', '一个假的,假的精神', '获得战利品:王者之剑'],
        '魔法:魔力石板' => ['achievement', 'Mana时代', '制作一个魔力石板来装Mana'],
    ];
    public static function getTypeAndNameForItem(Item $item){
        if ($item instanceof LTItem){
            if(isset(self::$ItemProgress[$item->getTypeName().':'.$item->getLTName()])){
                return self::$ItemProgress[$item->getTypeName().':'.$item->getLTName()];
            }
            return null;
        }else{
            if(isset(self::$ItemProgress[$item->getId()])){
                return self::$ItemProgress[$item->getId()];
            }elseif(isset(self::$ItemProgress[$item->getId().':'.$item->getDamage()])){
                return self::$ItemProgress[$item->getId().':'.$item->getDamage()];
			}
            return null;
        }
    }
}