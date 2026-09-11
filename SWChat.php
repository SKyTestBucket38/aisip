<?php

namespace Cust0mPhase;

class SWChat extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener{

 public $pp, $db, $color, $message = [], $chat = [], $teleported = [], $chatStatus = [];

 public static $instance;

 public function onEnable(){
  $this->getServer()->getPluginManager()->registerEvents($this,$this);
  self::$instance = $this;
  $this->color = [];
  $this->message = [];
  $this->chat = [];
  $this->teleported = [];
  $this->chatStatus = [];

        $this->pp = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
        $this->lvl = $this->getServer()->getPluginManager()->getPlugin("lvl");

}

 public function onCommand(\pocketmine\command\CommandSender $s,\pocketmine\command\Command $cmd, $label,array $args){
 	if(!$s instanceof \pocketmine\Player){
 		$s->sendMessage("§cЭту команду можно использовать только в игре!");
 		return false;
 	}
 	$subcmds = ["on","off"];
 	if(!isset($args[0]) or !in_array(strtolower($args[0]),$subcmds)){
 		$s->sendMessage("§eИспользование: §a/chat §7(§aon§7/§aoff§7)");
 		return false;
 	}
 	$sName = strtolower($s->getName());
 	switch(strtolower($args[0])){
 		case "on":
 			if(!isset($this->chatStatus[$sName]) or $this->chatStatus[$sName]){
 				$s->sendMessage("§cУ вас уже включен чат!");
 				return false;
 			}
 			$this->chatStatus[$sName] = true;
 			$s->sendMessage("§eВы успешно §aвключили §eчат!");
 			return true;
 		break;
 		case "off":
 			if(isset($this->chatStatus[$sName]) and !$this->chatStatus[$sName]){
 				$s->sendMessage("§cУ вас уже выключен чат!");
 				return false;
 			}
 			$this->chatStatus[$sName] = false;
 			$s->sendMessage("§eВы успешно §cвыключили §eчат!");
 			return true;
 		break;
 	}
 	return true;
 }

 public static function getInstance(){
   return self::$instance;
 }

  function joinEvent(\pocketmine\event\player\PlayerJoinEvent $e){
    $p = $e->getPlayer();

    // ｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚ
    // ＡＢＣＤＥＦＧＨＩＪＫＬＭＮＯＰＱＲＳＴＵＶＷＸＹＺａｂｃ

    $lvl = "§7(§e". $this->lvl->getL($p, 'lvl') ."§7)§r ";

  switch($this->pp->getUserDataMgr()->getGroup($p)->getName()){

  case "Player": $p->setNameTag($lvl."§7Ｐｌａｙｅｒ§f ".$p->getName()."§r"); $p->setDisplayName("§7".$p->getName()."§r"); $e->setJoinMessage(null); break;

  case "Fly": $p->setNameTag($lvl."§3§lＦｌｙ §r§f".$p->getName()."§r"); $p->setDisplayName("§a§lＦｌｙ §r§f".$p->getName()."§r");
          $e->setJoinMessage("§3§lＦｌｙ §r§f".$p->getName()." §r§eприсоединился(-ась) к игре."); break;

  case "Creativ": $p->setNameTag($lvl."§6§lＣｒｅａｔｉｖ §r§f".$p->getName()."§r"); $p->setDisplayName("§6§lＣｒｅａｔｉｖ §r§f".$p->getName()."§r");
          $e->setJoinMessage("§6§lＣｒｅａｔｉｖ §r§f".$p->getName()." §r§eприсоединился(-ась) к игре."); break;

  case "Creativ+": $p->setNameTag($lvl."§6§lＣｒｅａｔｉｖ§c+ §r§f".$p->getName()."§r"); $p->setDisplayName("§6§lＣｒｅａｔｉｖ§c+ §r§f".$p->getName()."§r");
          $e->setJoinMessage("§6§lＣｒｅａｔｉｖ§c+ §r§f".$p->getName()." §r§eприсоединился(-ась) к игре."); break;

  case "Vip": $p->setNameTag($lvl."§6§lＶＩＰ §r§f".$p->getName()."§r"); $p->setDisplayName("§6§lＶＩＰ §r§f".$p->getName()."§r"); break;
  }
}

    function quitEvent(\pocketmine\event\player\PlayerQuitEvent $e){
	    $p = $e->getPlayer();

	   $e->setQuitMessage(null);
    }
 

 public function chatEvent(\pocketmine\event\player\PlayerChatEvent $e){
    $p = $e->getPlayer();
    $message = \pocketmine\utils\TextFormat::clean($e->getMessage());
    $pNname = strtolower($p->getName());
    if(isset($this->chatStatus[$pNname]) and !$this->chatStatus[$pNname]){
    	$p->sendMessage("§cВы не можете писать сообщения в чат пока он выключен!");
    	$e->setCancelled();
    	return false;
    }
   
    if(!$p->hasPermission("chatfilter.admin")){
    	if(preg_match("/[0-9]{1,4}( |\.| \. | \.|)[0-9]{1,4}( |\.| \. | \.|)[0-9]{1,4}( |\.| \. | \.|)[0-9]{1,4}( |\.| \. | \.|)/", $message)){
			$e->setCancelled();
			$p->sendMessage("§cПиар запрещен!");
			return false;
		}
	}
	if(isset($this->message[$p->getName()]) and $this->message[$p->getName()] == strtolower($message)){
		$e->setCancelled();
		$p->sendMessage("§cПожалуйста, не флудите в чат!");
		return false;
	}

		$this->message[$p->getName()] = $message;
    
    //$lvl = "§7(§e". $this->lvl->getL($p, 'lvl') ."§7)§r ";
    $msg = "§r§7{$p->getNameTag()}§r§7: {$message}§r";

	foreach($this->getServer()->getOnlinePlayers() as $player){
		if((!isset($this->chatStatus[strtolower($player->getName())]) or $this->chatStatus[strtolower($player->getName())]))
			$player->sendMessage($msg);
    }
    $e->setCancelled();
    return true;
    //$e->setFormat($msg);	
  }
}
      
  