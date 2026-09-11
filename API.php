<?php

namespace D200;

use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\level\Level;
use pocketmine\utils\Config;
use pocketmine\Inventory;
use pocketmine\item\Item;
use pocketmine\entity\Effect;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\scheduler\CallbackTask;

Class API extends PluginBase implements Listener{

      public $eco;
      public $hack;
      public $pp;
      public $report;

public function onEnable(){
   $this->hack = [];
   $this->report = [];
   $this->gods = array();
   $this->vanish = array();
   $this->getServer()->getPluginManager()->registerEvents($this, $this);
   $this->eco = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
   $this->pp = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
      $this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask(array($this, "money")), 20 * 60 * 5);
}
public function money(){
     foreach($this->getServer()->getOnlinePlayers() as $p){
   $this->eco->addMoney($p, 100);
     }
       }
public function NoCreativeDamage(EntityDamageEvent $event){
        if($event instanceof EntityDamageByEntityEvent){
            $d = $event->getDamager();
            if($d instanceof Player){
                if($d->isCreative()){
                    $d->sendMessage("§cНельзя §fдраться в §bкреативе!");
                    $event->setCancelled(true);
                }
            }
        }
    }
public function ShowJoin(PlayerJoinEvent $e){
   $e->setJoinMessage(null);
}
public function ShowDeath(PlayerDeathEvent $e){
   $e->setDeathMessage(null);
}
public function ShowQuit(PlayerQuitEvent $e){
   $e->setQuitMessage(null);
}
public function onCommand(CommandSender $sender, Command $command, $label, array $args){
  switch($command->getName()){
   
   case "heal":
   $sender->setHealth(20);
   $sender->setFood(20);
   $sender->sendMessage("§eВы успешно исцелили себя!");
   break;
   case "clear":
   $sender->getInventory()->clearAll();
   $sender->sendMessage("§eВы успешно очистили свой инвентарь!");
   break;
  
   case "v":
			if(isset($this->vanish[$sender->getName()])){ 	
   $sender->removeAllEffects();
			$sender->sendMessage("§eВы отключили режим неведимости"); 		
    unset($this->vanish[$sender->getName()]); 			}else{ 				
   $sender->addEffect(Effect::getEffect(14)->setVisible(false)->setAmplifier(10)->setDuration(1928000));
   $this->vanish[$sender->getName()] = true; $sender->sendMessage("§eВы включили неведимость"); 		
	} 
   break;
   case "report":
          if(!isset($args[0]) && !isset($args[1])){
   $sender->sendMessage("§cИспользуй: §a/report <пользователь> <причина>");
}
          if(isset($args[0]) && isset($args[1])){
          if(!isset($this->report[$sender->getName()])){
   $this->report[$sender->getName()] = 1;
   $name = $sender->getName();
   $sender->sendMessage("§eТы отправлял жалобу на §c". $args[0] .'§e.');

	   foreach ($this->getServer()->getOnlinePlayers() as $player) {
			if($player->hasPermission("accept.report")) {

				$player->sendMessage("§eИгрок§f {$sender->getNameTag()} §eпожаловался на §c". $args[0] ."§e. Причина:§b ". $args[1] ."");
			}
		}

     }else{
   $sender->sendMessage("§cТы уже отправлял жалобу!");
}
}
   break;
   case "dupe":
			   if($sender->getGamemode() !== 0){
				   $sender->sendMessage("§eВы не можете дюпать в режиме §bКреатива!");
			     }else{
				  $inv = $sender->getInventory();
				  $i = $inv->getItemInHand();
				  $invid = $i->getId();
					$sender->sendMessage("§eВы успешно дюпнули предмет в руке");
                    $i->setCount(64);
}
			     break;
          case "sleep":
          if($sender instanceof Player){
   $sender->sleepOn(new Vector3($sender->getX(), $sender->getY()+1, $sender->getZ()));
				  $sender->sendMessage("§eВы успешно легли поспать на грязный пол");
     }
     else{
   $sender->sendMessage("§cЭту команду можно использовать только в игре!");
   }
				    break;
       case "cc":
          if($sender instanceof Player){
   $name = $sender->getName();
   $this->getServer()->broadcastMessage("\n \n \n \n \n \n \n \n \n \n \n \n \n \n \n \n \n \n \n \n \n \n \n \n \n \n \n \n \n \n \n \n \n \n \n \n ");
	$this->getServer()->broadcastMessage("§eИгрок §7$name §eочистил чат!");
     }
     else{
   $sender->sendMessage("§cЭту команду можно использовать только в игре!");
   }
     break;
   case "spawn":
   $safespawn = $sender->getLevel()->getSafeSpawn(); 
   $x = $safespawn->getX();
   $y = $safespawn->getY();
   $z = $safespawn->getZ();
   $sender->teleport(new Vector3($x, $y, $z));
   $sender->sendMessage("§aТелепортация...");
   break;
   case "top":
   $sender->teleport(new Vector3($sender->getX(), 128, $sender->getZ()));
   $sender->sendMessage("§eТелепортация...");
   break;
}
}

public function onDisable(){}
}
?>