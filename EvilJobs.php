<?php

namespace Main;
  
use pocketmine\plugin\PluginBase;
    use pocketmine\event\Listener;
    
/* **** Events ****  */

use pocketmine\event\player\{PlayerDeathEvent, PlayerJoinEvent};
    use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockPlaceEvent;
    use pocketmine\event\block\BlockBreakEvent;

use pocketmine\event\entity\EntiyDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
    
/* **** Math **** */

use pocketmine\math\Vector3;

use pocketmine\entity\Human;

/* **** Items and e.t.c. **** */

use pocketmine\item\Item;
    use pocketmine\block\Block;
use pocketmine\inventory\Inventory;

/* **** NBT TAGS **** */

  use pocketmine\nbt\tags\NameTag;
  
  /* **** Cfg **** */

use pocketmine\utils\Config;

/* **** Commands **** */
use pocketmine\command\CommandSender;
    use pocketmine\command\Command;
  
/* **** Others **** */

use pocketmine\level\Level;
    use pocketmine\Player;
use pocketmine\Server;
    use pocketmine\permissible\Permissible;
use pocketmine\enchantment\Enchantment;
    use pocketmine\level\Position;
/* **** End Imports **** */
    
class EvilJobs extends PluginBase implements Listener {
    	
    const LEAVE = "§aТы уволился с работы!";
    const DJ = "§cТы ещё не устроился!";

    public $eco, $cfg, $miner = [], $treecutter = [], $killer = [], $rubak = [];
    
    function onEnable() {
	
	    $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->eco = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        $this->shop = $this->getServer()->getPluginManager()->getPlugin("Shop");
    }

  public function Break(BlockBreakEvent $e) {
      $p = $e->getPlayer();
      $b = $e->getBlock();
$x = round($b->getX());
        $y = round($b->getY());
        $z = round($b->getZ());
        $level = $b->getLevel()->getName();
     if(isset($this->miner[strtolower($p->getName())])) {
      	        $ids = [1, 2, 3, 12, 13];
              $ids2 = [14, 15, 16, 56, 73, 129];
        	if(!$p->isCreative() && in_array($b->getId(), $ids)) {
             if(empty($this->wg->regionHere($x, $y, $z, $level))) {
        $this->eco->addMoney($p, 1);
            $p->sendPopup("§e+ 1 монет");
                }
            } elseif(!$p->isCreative() && in_array($b->getId(), $ids2)) {
          	 if(empty($this->wg->regionHere($x, $y, $z, $level))) {
            	$this->eco->addMoney($p, 5);
            $p->sendPopup("§e+ 5 монет");
               }
            }
        }
          
       if(isset($this->gardener[strtolower($p->getName())])) {
     
if(!$p->isCreative() && $b->getId() == "18") {
   if(empty($this->wg->regionHere($x, $y, $z, $level))) {
     $this->eco->addMoney($p, 4);
          $p->sendTip("§e+ 4 монет");
     }
   }
       }
     if(isset($this->treecutter[strtolower($p->getName())])) {
        if(!$p->isCreative() && $b->getId() == "17") {
        	 if(empty($this->wg->regionHere($x, $y, $z, $level))) {
        	  $this->eco->addMoney($p, 3);
        $p->sendTip("§e+ 3 монет");
      }
   } 
      }
      }
  public function Place(BlockPlaceEvent $e) {
  	
  foreach($this->getServer()->getOnlinePlayers() as $p) {
  $b = $e->getBlock();
$x = round($b->getX());
        $y = round($b->getY());
        $z = round($b->getZ());
        $level = $b->getLevel()->getName();
    
    if(!$p->isCreative()) {
if(isset($this->builder[strtolower($p->getName())])) {
	  $id = [1, 2, 3, 4, 5, 24, 35, 41, 42, 43, 44, 45, 97, 98, 99, 100];
if(!$p->isCreative() && in_array($b->getId(), $id)) {
	 if(empty($this->wg->regionHere($x, $y, $z, $level))) {
	  $this->eco->addMoney($p, 2);
	$p->sendTip("§e+ 2 монет");
	}
}
  }
    }
    }
    }
	public function onDeath(PlayerDeathEvent $e) {
				
	$entity = $e->getEntity();
  
	$cause = $entity->getLastDamageCause();
	if($cause instanceof EntityDamageByEntityEvent) {
			
		$p = $cause->getDamager();

    if(isset($this->killer[strtolower($p->getName())])) {
			
		if($p instanceof Player) {
			
			  if(!$p->isCreative() && $entity instanceof Player) {
				$this->eco->addMoney($p, 150);
			$p->sendTip("§e+ 150 монет");
			}
	    }
	  }
  }
}
		
		
 public function onCommand(CommandSender $p, Command $cmd, $label, array $args) {
 	switch($cmd->getName()) {
 	
case "job":
if(!isset($args[0])) {
  $p->sendMessage("§eСписок команд: §b/job info");
  }
  if(isset($args[0])) {
  	if($args[0] == "info") {
  $p->sendMessage(" §b/job list §7- §eСписок работ");
  $p->sendMessage(" §b/job help §7- §eПодробнее о работах");
  }
  	if($args[0] == "list") {
  	    $p->sendMessage("§7- §b/miner §7– §eУсроиться шахтером\n§7- §b/killer §7– §eУстроиться убийцей\n§7- §b/treecutter §7– §eУстроиться Дровосеком\n§7- §b/rubak §7– §eУстроиться Рыбаком\n§7- §b/job leave §7– §eУволиться с работы");
  }
      if($args[0] == "help") {
   $p->sendMessage("§6Miner §7(Шахтер) §f– §eЛомайте руды, землю, булыжник, песок и получайте за это деньги!");
   $p->sendMessage("§6TreeCutter §7(Дровосек) §f– §eИногда на сервере идет дождь или снег, и чтобы согреться , стоит растопить печку. Рубите дерево и зарабатывайте деньги!");
   $p->sendMessage("§6Killer §7(Убийца) §f– §eМногие считают PvP грифферством, но это не так. Быть убийцей - продуктивно. Убивай игроков, так еще и получай деньги!");
   $p->sendMessage("§6Rubak §7(Рыбак) §f– §eСтать одним из топовых рабаков на сервере и покори этот майнкрафт мир!");
  }
  if($args[0] == "leave") {
$nick = strtolower($p->getName());
if(isset($this->miner[$nick]) || isset($this->treecutter[$nick]) || isset($this->rubak[$nick]) || isset($this->killer[$nick])) {
	unset($this->miner[$nick], $this->rubak[$nick], $this->treecutter[$nick], $this->killer[$nick]);
	$p->sendMessage(self::LEAVE);
  } else {
  	$p->sendMessage(self::DJ);
  }
    }
       }
    break;

  case "miner":

if(isset($this->killer[strtolower($p->getName())]) || isset($this->treecutter[strtolower($p->getName())])) {
 $p->sendMessage("§cСначала уволься с другой работы!");
}

elseif(isset($this->miner[strtolower($p->getName())])) {
	  $p->sendMessage("§cТы уже работаешь Шахтером!");
	} else {
		$this->miner[strtolower($p->getName())] = true;
		  $p->sendMessage("§aТы устроился Шахтером!");
		}
		break;
		
		case "killer":
		
if(isset($this->miner[strtolower($p->getName())]) || isset($this->treecutter[strtolower($p->getName())])) {
	 $p->sendMessage("§cСначала уволься с другой работы!");
}

elseif(isset($this->killer[strtolower($p->getName())])) {
	$p->sendMessage("§cТы уже работаешь Киллером!");
	} else {
		$this->killer[strtolower($p->getName())] = true;
		  $p->sendMessage("§aТы устроился Киллером!");
		}
		break;
 
     case "treecutter":
    
  if(isset($this->killer[strtolower($p->getName())]) || isset($this->miner[strtolower($p->getName())])) {
	 $p->sendMessage("§cСначала уволься с другой работы!");
	}
elseif(isset($this->treecutter[strtolower($p->getName())])) {
	$p->sendMessage("§cТы уже работаешь Дровосеком!");
	} else {
		$this->treecutter[strtolower($p->getName())] = true;
		  $p->sendMessage("§aТы устроился Дровосеком!");
		}
		break;
    case "rubak":
    
  if(isset($this->killer[strtolower($p->getName())]) || isset($this->miner[strtolower($p->getName())]) || isset($this->treecutter[strtolower($p->getName())])) {
   $p->sendMessage("§cСначала уволься с другой работы!");
  }
elseif(isset($this->rubak[strtolower($p->getName())])) {
  $p->sendMessage("§cТы уже работаешь Рыбаком!");
  } else {
    $this->rubak[strtolower($p->getName())] = true;
      $p->sendMessage("§aТы устроился Рыбаком!");
    }
    break;
		
		}
    }
  }
   
		
?>
            