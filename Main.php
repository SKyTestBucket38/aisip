<?php

namespace AutoParkur;

use pocketmine\block\Block;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerMoveEvent, PlayerExhaustEvent, PlayerQuitEvent, PlayerCommandPreprocessEvent, PlayerDropItemEvent, PlayerInteractEvent};

use pocketmine\level\Position;
use pocketmine\level\sound\{EndermanTeleportSound, PopSound};
use pocketmine\event\block\{BlockPlaceEvent, BlockBreakEvent};

use pocketmine\plugin\PluginBase;
use pocketmine\tile\Sign;
use pocketmine\utils\Config;

use pocketmine\item\Item;
use pocketmine\Player;

use pocketmine\math\Vector3;

use pocketmine\event\inventory\InventoryPickupItemEvent;

class Main extends PluginBase implements Listener {

    function onEnable(){

        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->economy = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI');
    }

    public $park = array();

    function PlayerQuitEvent(PlayerQuitEvent $event){

        $p = $event->getPlayer();

        if(isset($this->park[strtolower($p->getName())])) {
            $blockx = $this->park[strtolower($p->getName())]["x"];
            $blocky = $this->park[strtolower($p->getName())]["y"];
            $blockz = $this->park[strtolower($p->getName())]["z"];
            $blockx2 = $this->park[strtolower($p->getName())]["x2"];
            $blocky2 = $this->park[strtolower($p->getName())]["y2"];
            $blockz2 = $this->park[strtolower($p->getName())]["z2"];
			$p->getLevel()->setBlock(new Position($blockx, $blocky, $blockz), Block::get(0));
			$p->getLevel()->setBlock(new Position($blockx2, $blocky2, $blockz2), Block::get(0));
            unset($this->park[strtolower($p->getName())]);

            //$this->auth->teleportLobby($p);

            $safespawn = $p->getLevel()->getSafeSpawn(); 
            $x = $safespawn->getX();
            $y = $safespawn->getY();
            $z = $safespawn->getZ();
            $p->teleport(new Vector3($x, $y, $z));

        }
    }

    function onDisable(){

        foreach ($this->getServer()->getOnlinePlayers() as $p){

            if(isset($this->park[strtolower($p->getName())])){

                $blockx = $this->park[strtolower($p->getName())]["x"];
                $blocky = $this->park[strtolower($p->getName())]["y"];
                $blockz = $this->park[strtolower($p->getName())]["z"];

                $blockx2 = $this->park[strtolower($p->getName())]["x2"];
                $blocky2 = $this->park[strtolower($p->getName())]["y2"];
                $blockz2 = $this->park[strtolower($p->getName())]["z2"];

				$p->getLevel()->setBlock(new Position($blockx, $blocky, $blockz), Block::get(0));
				$p->getLevel()->setBlock(new Position($blockx2, $blocky2, $blockz2), Block::get(0));

                unset($this->park[strtolower($p->getName())]);

                $safespawn = $p->getLevel()->getSafeSpawn(); 
                $x = $safespawn->getX();
                $y = $safespawn->getY();
                $z = $safespawn->getZ();
                $p->teleport(new Vector3($x, $y, $z));

                $p->setFood(20);

            }
        }
    }

    function parkur(PlayerMoveEvent $event){

        $p = $event->getPlayer();

        if (isset($this->park[strtolower($p->getName())])){

            $x = round($p->x - 0.5);
            $y = round($p->y) - 1;
            $z = round($p->z - 0.5);

            if ($y < $this->park[strtolower($p->getName())]["ydown"]){

                $blockx = $this->park[strtolower($p->getName())]["x"];
                $blocky = $this->park[strtolower($p->getName())]["y"];
                $blockz = $this->park[strtolower($p->getName())]["z"];

                $blockx2 = $this->park[strtolower($p->getName())]["x2"];
                $blocky2 = $this->park[strtolower($p->getName())]["y2"];
                $blockz2 = $this->park[strtolower($p->getName())]["z2"];

				$p->getLevel()->setBlock(new Position($blockx, $blocky, $blockz), Block::get(0));
				$p->getLevel()->setBlock(new Position($blockx2, $blocky2, $blockz2), Block::get(0));

                $ochk = $this->park[strtolower($p->getName())]["count"];
                $money = $this->park[strtolower($p->getName())]["money"];
               // $blocks = $this->auth->getFormat($ochk, array('блок', 'блока', 'блоков'));
                $blocks = $ochk . ' блока';
                $p->sendMessage("§b§l|§r §eТы упал, пройдено§b ". $blocks .'§e, собрано§a '. $money .'§e монет(-а-ы).');

                foreach($this->getServer()->getOnlinePlayers() as $players){

                    $p->showPlayer($players); $players->showPlayer($p);
                }

		        //$this->octopus->createCustomFloating($p, 106 + 0.5, 74 + 0.5, 508 + 0.5, '§eВ прошлый раз ты прошел '. $blocks .'.');

                unset($this->park[strtolower($p->getName())]);
                //$this->auth->teleportLobby($p); $this->octopus->spawnTEXT($p);
                $safespawn = $p->getLevel()->getSafeSpawn(); 
                $x = $safespawn->getX();
                $y = $safespawn->getY();
                $z = $safespawn->getZ();
                $p->teleport(new Vector3($x, $y, $z));

                $p->setGamemode(0);
                $p->setAllowFlight(false);
                $p->setFood(20);

                return true;
            }
            $blockx = $this->park[strtolower($p->getName())]["x"];
            $blocky = $this->park[strtolower($p->getName())]["y"];
            $blockz = $this->park[strtolower($p->getName())]["z"];
            $block = $p->getLevel()->getBlock(new Position($x, $y, $z));
            if ($block->getId() == 206) {
                $hash1 = $this->park[strtolower($p->getName())]["x2"] . " " . $this->park[strtolower($p->getName())]["y2"] . " " . $this->park[strtolower($p->getName())]["z2"];
                $hash2 = $x . " " . $y . " " . $z;
                if ($hash1 == $hash2) {
                    $p->getLevel()->addSound(new PopSound(new Position($p->x, $p->y, $p->z)), array($p));
                    if($p->isCreative() || $p->getAllowFlight()){
                        $blockx = $this->park[strtolower($p->getName())]["x"];
                        $blocky = $this->park[strtolower($p->getName())]["y"];
                        $blockz = $this->park[strtolower($p->getName())]["z"];
                        $blockx2 = $this->park[strtolower($p->getName())]["x2"];
                        $blocky2 = $this->park[strtolower($p->getName())]["y2"];
                        $blockz2 = $this->park[strtolower($p->getName())]["z2"];
						$p->getLevel()->setBlock(new Position($blockx, $blocky, $blockz), Block::get(0));
						$p->getLevel()->setBlock(new Position($blockx2, $blocky2, $blockz2), Block::get(0));
                        
                        $ochk = $this->park[strtolower($p->getName())]["count"];
                        $money = $this->park[strtolower($p->getName())]["money"];
                       // $blocks = $this->auth->getFormat($ochk, array('блок', 'блока', 'блоков'));
                        $blocks = $ochk . ' блока';
                        $p->sendMessage("§b§l|§r §eТы упал, пройдено§b ". $blocks .'§e, собрано§a '. $money .'§e монет(-а-ы).');

                        foreach($this->getServer()->getOnlinePlayers() as $players){

                            $p->showPlayer($players); $players->showPlayer($p);
                        }

                        unset($this->park[strtolower($p->getName())]);

                        $safespawn = $p->getLevel()->getSafeSpawn(); 
                        $x = $safespawn->getX();
                        $y = $safespawn->getY();
                        $z = $safespawn->getZ();
                        $p->teleport(new Vector3($x, $y, $z));
                    }

                    $p->setGamemode(0);
                    $p->setAllowFlight(false);

					$p->getLevel()->setBlock(new Position($blockx, $blocky, $blockz), Block::get(0));
                    $this->park[strtolower($p->getName())]["x"] = $x;
                    $this->park[strtolower($p->getName())]["y"] = $y;
                    $this->park[strtolower($p->getName())]["z"] = $z;
                    $this->park[strtolower($p->getName())]["ydown"] = $y - 4;
                    $rand = mt_rand(2, 3);
                    if (mt_rand(5, 6) == 5) {
                        $rand = $rand * -1;
                    }
                    $new1 = $x + $rand;
                    $rand = mt_rand(0, 1);
                    if (mt_rand(5, 6) == 5) {
                        $rand = $rand * -1;
                    }
                    $new2 = $y + $rand;
                    $rand = mt_rand(2, 3);
                    if (mt_rand(5, 6) == 5) {
                        $rand = $rand * -1;
                    }
                    $new3 = $z + $rand;
                    $this->park[strtolower($p->getName())]["x2"] = $new1;
                    $this->park[strtolower($p->getName())]["y2"] = $new2;
                    $this->park[strtolower($p->getName())]["z2"] = $new3;
                    $p->getLevel()->setBlock(new Position($new1, $new2, $new3), Block::get(206));
                    $count = $this->park[strtolower($p->getName())]["count"] + 1;
                    $this->park[strtolower($p->getName())]["count"] = $count;

                    $center = new Vector3($new1 + 0.5, $new2 + 2, $new3 + 0.5);
                    $particle = new \pocketmine\level\particle\ExplodeParticle($center);
                    //for($i=0; $i < 5; $i++) $p->getLevel()->addParticle($particle, [$p]);

                    $this->particleTick($p, $new1 + 0.5, $new2 + 1, $new3 + 0.5);

                    if(mt_rand(1, 3) == 3){

                    	$p->getLevel()->dropItem(new Vector3($new1 + 0.5, $new2 + 1, $new3 + 0.5), Item::get(371, 0, 1), new Vector3(0, 0, 0));
                    }

                    return true;
                }
            }
        } else {
            $x = round($p->x - 0.5);
            $y = round($p->y) - 1;
            $z = round($p->z - 0.5);
            // if ($x == 106 && ($y == 72 || $y == 73 || $y == 74) && $z == 508) {
            if ($x == 9547 && ($y == 67 || $y == 68 || $y == 69 || $y == 70) && $z == -9710) {
                $p->setGamemode(0);
                $p->setAllowFlight(false);
                $p->addActionBarMessage("§eТы успешно начал прохождение паркура.");

                $startx = mt_rand(11, 25);
                $starty = mt_rand(100,120);
                $startz = mt_rand(21,36);

                $this->park[strtolower($p->getName())] = array();
                $this->park[strtolower($p->getName())]["x"] = $startx;
                $this->park[strtolower($p->getName())]["y"] = $starty;
                $this->park[strtolower($p->getName())]["z"] = $startz;
                $this->park[strtolower($p->getName())]["count"] = 0;
                $this->park[strtolower($p->getName())]["money"] = 0;

                $p->teleport(new Position($startx + 0.5, $starty + 1,  $startz + 0.5));
                $p->getLevel()->setBlock(new Vector3($startx, $starty, $startz), Block::get(206));

                foreach($this->getServer()->getOnlinePlayers() as $players){

                    $p->hidePlayer($players); $players->hidePlayer($p);
                }

                $rand = mt_rand(2, 3);
                if (mt_rand(5, 6) == 5) {
                    $rand = $rand * -1;
                }
                $new1 = $startx + $rand;
                $rand = mt_rand(0, 1);
                if (mt_rand(5, 6) == 5) {
                    $rand = $rand * -1;
                }
                $new2 = $starty + $rand;
                $rand = mt_rand(2, 3);
                if (mt_rand(5, 6) == 5) {
                    $rand = $rand * -1;
                }
                $new3 = $startz + $rand;
                $this->park[strtolower($p->getName())]["x2"] = $new1;
                $this->park[strtolower($p->getName())]["y2"] = $new2;
                $this->park[strtolower($p->getName())]["z2"] = $new3;
                $p->getLevel()->setBlock(new Position($new1, $new2, $new3), Block::get(206));
                $this->park[strtolower($p->getName())]["ydown"] = $starty - 4;

                $p->setFood(20);
            }
        }
    }

    function particleTick($player, $x, $y, $z){

        for($i=0;$i<=70;$i++){

            $distance = 0.1 + lcg_value();
            $yaw = M_PI / 180 + (-0.5 + lcg_value()) * 90;

            $pos = new Vector3($x + $distance * cos($yaw), $y + lcg_value() * 1.65 + 0.5, $z + $distance * sin($yaw));

            $particle = new \pocketmine\level\particle\DustParticle($pos, 138, 43, 226);
            $player->getLevel()->addParticle($particle, [$player]);
        }
    }

    function pickupShards(InventoryPickupItemEvent $event){

		$player = $event->getInventory()->getHolder();

		if($player instanceof Player && $event->getItem()->getItem()->getId() == 371 && isset($this->park[strtolower($player->getName())]["money"])){

			$money = $this->park[strtolower($player->getName())]["money"] + 1;
            $this->park[strtolower($player->getName())]["money"] = $money;

			$player->sendPopup("§e+1 монета");
			$this->economy->addMoney($player, 1);

            $event->getItem()->kill(); $event->setCancelled();

            $player->getLevel()->addSound((new \pocketmine\level\sound\ExpPickupSound($player)), [$player]);
		}
    }

    function onPlace(BlockPlaceEvent $event){

        $player = $event->getPlayer();

        if(isset($this->park[strtolower($player->getName())])) $event->setCancelled();
	}

	function onFood(PlayerExhaustEvent $event){

		$player = $event->getPlayer();

        if(isset($this->park[strtolower($player->getName())])) $event->setCancelled();
	}

    function onInteract(PlayerInteractEvent $event){

        $player = $event->getPlayer();

        if(isset($this->park[strtolower($player->getName())])) $event->setCancelled();
	}

	function onBreak(BlockBreakEvent $event){
        
        $player = $event->getPlayer();

        if(isset($this->park[strtolower($player->getName())])) $event->setCancelled();
	}

	function onDrop(PlayerDropItemEvent $event){

		$player = $event->getPlayer();

        if(isset($this->park[strtolower($player->getName())])) $event->setCancelled();
	}

    function EntityDamageEvent(EntityDamageEvent $event){

        if($event->getCause() == EntityDamageEvent::CAUSE_FALL){

            if(isset($this->park[strtolower($event->getEntity()->getName())])) $event->setCancelled();
        }
    }

    function onProcess(PlayerCommandPreprocessEvent $event){

        $player = $event->getPlayer(); $username = strtolower($player->getName());
        $message = $event->getMessage(); $time = time();

        if($message[0] === '/'){

            if(isset($this->park[$username])) $event->setCancelled();
        }
    }
}
?>