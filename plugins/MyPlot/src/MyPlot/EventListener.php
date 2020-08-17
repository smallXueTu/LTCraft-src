<?php
namespace MyPlot;

use pocketmine\block\Sapling;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\level\LevelUnloadEvent;
use pocketmine\utils\Config;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\ItemFrameDropItemEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\utils\TextFormat;
use pocketmine\block\Lava;
use pocketmine\block\Water;


class EventListener implements Listener
{
    /** @var MyPlot */
    private $plugin;

    public function __construct(MyPlot $plugin){
        $this->plugin = $plugin;
    }

    public function onLevelLoad(LevelLoadEvent $event) {
        if ($event->getLevel()->getProvider()->getGenerator() === "myplot") {
            $settings = $event->getLevel()->getProvider()->getGeneratorOptions();
            if (isset($settings["preset"]) === false or $settings["preset"] === "") {
                return;
            }
            $settings = json_decode($settings["preset"], true);
            if ($settings === false) {
                return;
            }
            $levelName = $event->getLevel()->getName();
            $filePath = $this->plugin->getDataFolder() . "worlds/" . $levelName . ".yml";
            $config = $this->plugin->getConfig();
            $default = [
                "RestrictEntityMovement" => $config->getNested("DefaultWorld.RestrictEntityMovement"),
                "ClaimPrice" => $config->getNested("DefaultWorld.ClaimPrice"),
                "ClearPrice" => $config->getNested("DefaultWorld.ClearPrice"),
                "DisposePrice" => $config->getNested("DefaultWorld.DisposePrice"),
                "ResetPrice" => $config->getNested("DefaultWorld.ResetPrice"),
            ];
            $config = new Config($filePath, Config::YAML, $default);
            foreach (array_keys($default) as $key) {
                $settings[$key] = $config->get($key);
            }
            $this->plugin->addLevelSettings($levelName, new PlotLevelSettings($levelName, $settings));
        }
    }
	public function onDamageByEntityEvent(EntityDamageEvent $event){
		if($event instanceof EntityDamageByEntityEvent and $event->getEntity() instanceof \pocketmine\entity\Painting and $event->getDamager() instanceof Player){
			if($event->getEntity()->getLevel()->getName()==='zc' and !$event->getDamager()->isOp()){
				$event->setCancelled(true);
				return;
			}
			$plot = $this->plugin->getPlotByPosition($event->getEntity());
			if ($plot !== null and $event->getDamager() instanceof Player) {
				$username = strtolower($event->getDamager()->getName());
				if ($plot->owner !== $username and !$plot->isHelper($username) and !$event->getDamager()->hasPermission("myplot.admin.build.plot")) {
					$event->setCancelled(true);
					return;
				}
			}
		}
	}
    public function onLevelUnload(LevelUnloadEvent $event) {
        $levelName = $event->getLevel()->getName();
        $this->plugin->unloadLevelSettings($levelName);
    }

    public function onBlockPlace(BlockPlaceEvent $event) {
        $this->onEventOnBlock($event);
    }

    public function onBlockBreak(BlockBreakEvent $event) {
        $this->onEventOnBlock($event);
    }

    public function onItemFrameDropItem(ItemFrameDropItemEvent $event) {
        $this->onEventOnBlock($event);
    }
    
    public function onPlayerInteract(PlayerInteractEvent $event) {
        $this->onEventOnBlock($event);
    }
	
    /**
     * @param BlockPlaceEvent|BlockBreakEvent|PlayerInteractEvent $event
     */
    private function onEventOnBlock($event) {
        $levelName = $event->getBlock()->getLevel()->getName();
        if (!$this->plugin->isLevelLoaded($levelName)) {
            return;
        }
        $plot = $this->plugin->getPlotByPosition($event->getBlock());
        if ($plot !== null) {
            $username = strtolower($event->getPlayer()->getName());
            if ($plot->owner == $username or $plot->isHelper($username) or $event->getPlayer()->hasPermission("myplot.admin.build.plot")) {
                if (!($event instanceof PlayerInteractEvent and $event->getBlock() instanceof Sapling))
                    return;
                $block = $event->getBlock();
                $maxLengthLeaves = (($block->getDamage() & 0x07) == Sapling::SPRUCE) ? 3 : 2;
                $beginPos = $this->plugin->getPlotPosition($plot);
                $endPos = clone $beginPos;
                $beginPos->x += $maxLengthLeaves;
                $beginPos->z += $maxLengthLeaves;
                $plotSize = $this->plugin->getLevelSettings($levelName)->plotSize;
                $endPos->x += $plotSize - $maxLengthLeaves;
                $endPos->z += $plotSize - $maxLengthLeaves;

                if ($block->x >= $beginPos->x and $block->z >= $beginPos->z and $block->x < $endPos->x and $block->z < $endPos->z) {
                    return;
                }
            }
        } elseif ($this->plugin->canUseRoad($event->getBlock(), $event->getPlayer())) {
            return;
        }
        $event->setCancelled(true);
    }

    public function onPlayerMove(PlayerMoveEvent $event) {
        if (!$this->plugin->getConfig()->get("ShowPlotPopup", true))
            return;

        $levelName = $event->getPlayer()->getLevel()->getName();
        if (!$this->plugin->isLevelLoaded($levelName))
            return;

        $plot = $this->plugin->getPlotByPosition($event->getTo());
        if ($plot !== null and $plot !== $this->plugin->getPlotByPosition($event->getFrom())) {
            if ($plot->owner != "") {
				$plotName = TextFormat::GREEN . $plot;
				 $event->getPlayer()->sendTitle('§d你来到了领地:'.$plotName,'§a这块领地领主是:'.$plot->ownerName);
            }else{
                $event->getPlayer()->sendTitle('§d这块领地没有领主','§a如果你钱够的话可以输/p claim买下来。');
            }
        }
    }
}
