<?php

namespace FactionsPro;

use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\TextFormat;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\block\BlockPlaceEvent;


class FactionListener implements Listener {

    public $plugin;

    public function __construct(FactionMain $pg) {
        $this->plugin = $pg;
    }

    public function EntityDamageEvent(EntityDamageEvent $event){
        if($event instanceof EntityDamageByEntityEvent){
            $d = $event->getDamager();
            $p = $event->getEntity();
            if($p instanceof Player && $d instanceof Player) {
		if($this->plugin->isInFaction($p->getName()) == true) {
		        $c1 = $this->plugin->getPlayerFaction($d->getName());
		        $c2 = $this->plugin->getPlayerFaction($p->getName());
		        if ($c1 == $c2) {
		            $event->setKnockBack(0);
		            $event->setDamage(0);
		        }
		}
            }
        }
    }

    public function PlayerDeathEvent(PlayerDeathEvent $event){
        $player = $event->getPlayer();
        if($this->plugin->isInFaction($player->getName()) == true) {
            $faction = strtolower($this->plugin->getPlayerFaction($player->getName()));
            if(file_exists('clans/'.$faction)) {
                $count = file_get_contents('clans/' . $faction);
                if ($count >= 0) {
                    @file_put_contents('clans/' . $faction, $count - 1);
                }
            } else {
                @file_put_contents('clans/' . $faction, 0);
            }
        }
    }

    public function PlayerDeathEvent2(PlayerDeathEvent $event)
    {
        $player = $event->getEntity();
        if ($player instanceof Player) {
            $cause = $player->getLastDamageCause();
            if ($cause instanceof EntityDamageByEntityEvent) {
                $killer = $cause->getDamager();
                if ($killer instanceof Player) {
                    if($this->plugin->isInFaction($killer->getName()) == true) {
                        $faction = strtolower($this->plugin->getPlayerFaction($killer->getName()));
                        if(file_exists('clans/'.$faction)) {
                            $count = file_get_contents('clans/' . $faction);
                            if ($count >= 0) {
                                @file_put_contents('clans/' . $faction, $count + 1);
                            }
                        } else {
                            @file_put_contents('clans/' . $faction, 1);
                        }
                    }
                }
            }
        }
    }
}
