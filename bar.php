<?php

namespace Efremov;

use pocketmine\Player;
use pocketmine\entity\Entity;

use pocketmine\math\Vector3;

use pocketmine\command\{Command, CommandSender};

use pocketmine\event\inventory\{InventoryPickupItemEvent};

use pocketmine\event\player\{PlayerQuitEvent, PlayerJoinEvent, PlayerChatEvent, PlayerMoveEvent};

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

class bar extends PluginBase implements Listener {

	/* TODO */
    function onEnable(){

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new \Efremov\SendTask($this), 20);

        $this->bossbar = new \Efremov\ApiBar($this, $this);

        $this->economy = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI');
    }

	function onJoin(PlayerJoinEvent $event){

		$player = $event->getPlayer(); $username = strtolower($player->getName());
		$this->bossbar->sendBossBar($player);
		$this->bossbar->setProgress($player);
	}

	function onQuit(PlayerQuitEvent $event){

		$player = $event->getPlayer(); $username = strtolower($player->getName());
	}
}
?>