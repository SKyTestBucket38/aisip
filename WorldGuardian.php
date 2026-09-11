<?php

namespace WorldGuardian;

use \pocketmine\event\block\BlockPlaceEvent;
use \pocketmine\event\Listener;
use \pocketmine\command\CommandExecutor;
use \pocketmine\event\block\BlockBreakEvent;
use \pocketmine\event\player\PlayerInteractEvent;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Player;
use \pocketmine\plugin\PluginBase;
use pocketmine\item\Item;
use pocketmine\utils\Config;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\level\Position;

class WorldGuardian extends PluginBase implements CommandExecutor, Listener
{
    public $db, $pos1 = array(), $pos2 = array();
    
    public $config;
    public $xgroup;
    public $sells;
    
    
    //For EconomyJob | Region Checking
	public function regionHere($x, $y, $z, $level) {
		$count  = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE (Pos1X <= $x AND $x <= Pos2X) AND (Pos1Y <= $y AND $y <= Pos2Y) AND (Pos1Z <= $z AND $z <= Pos2Z) AND Level = '" . $level . "';")->fetchArray(SQLITE3_ASSOC);
		if($count['count']) return true;
		else return false;
		
	}

	public function onBlockBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $x = round($block->getX());
        $y = round($block->getY());
        $z = round($block->getZ());
        $level = $block->getLevel()->getName();
        $username = strtolower($player->getName());
		$item = $event->getItem()->getID();
        $result = $this->db->query("SELECT * FROM AREAS WHERE (Pos1X <= $x AND $x <= Pos2X) AND (Pos1Y <= $y AND $y <= Pos2Y) AND (Pos1Z <= $z AND $z <= Pos2Z) AND Level = '" . $level . "';")->fetchArray(SQLITE3_ASSOC);
        $count  = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE (Pos1X <= $x AND $x <= Pos2X) AND (Pos1Y <= $y AND $y <= Pos2Y) AND (Pos1Z <= $z AND $z <= Pos2Z) AND Level = '" . $level . "';")->fetchArray(SQLITE3_ASSOC);
        
        $find = $x.":".$y.":".$z.":".$level;
        if(isset($this->sells[$find])) {
            $region = $this->sells[$find]["region"];
                if($result !== false && $username != $result['Owner'] && !$player->isOp()) {
                    $player->sendMessage("§7| §3Приват §7| §fВы §cне владелец §fэтого региона§7!");
				    $event->setCancelled(true);
				    return;
                }

            $this->db->query("UPDATE AREAS SET Sell = '0' WHERE Region = '$region'");
            $this->sells[$find] = null;
            unset($this->sells[$find]);
            $player->sendMessage("§7| §3Приват §7| §fРегион§d {$region} §aуспешно снят с продажи§7.");
        }

        
		if ($item == 271) {
            $this->pos1[$username] = array($x, $y, $z, $level);
            $player->sendMessage("§7| §3Приват §7| §aПервая §fточка установлена на §7({$x}, {$y}, {$z}).");
            
            if (isset($this->pos1[$username]) && isset($this->pos2[$username]) && $this->pos1[$username][3] == $this->pos2[$username][3]) {
                $pos1 = $this->pos1[$username];
                $pos2 = $this->pos2[$username];
                $min[0] = min($pos1[0], $pos2[0]);
                $max[0] = max($pos1[0], $pos2[0]);
                $min[1] = min($pos1[1], $pos2[1]);
                $max[1] = max($pos1[1], $pos2[1]);
                $min[2] = min($pos1[2], $pos2[2]);
                $max[2] = max($pos1[2], $pos2[2]);
                $count  = $this->countBlocks($min[0], $min[1], $min[2], $max[0], $max[1], $max[2]);
                $player->sendMessage("§7| §3Приват §7| §fВыбрано§d {$count} §fблоков§7.");
            }
            $event->setCancelled(true);
        } else {
            $member = $this->db->query("SELECT COUNT(*) as count FROM MEMBERS WHERE Region = '" . $result['Region'] . "' AND Name = '$username'")->fetchArray(SQLITE3_ASSOC);
            $flag = $this->db->query("SELECT COUNT(*) as count FROM FLAGS WHERE Region = '" . $result['Region'] . "' AND Flag = 'build' AND Value = 'allow'")->fetchArray(SQLITE3_ASSOC);
            $chest_access_flag = $this->db->query("SELECT COUNT(*) as count FROM FLAGS WHERE Region = '" . $result['Region'] . "' AND Flag = 'chest-access' AND Value = 'allow'")->fetchArray(SQLITE3_ASSOC);
            
            if ($result !== false && $username != $result['Owner'] && !$player->isOp() && !$member['count'] && !$flag['count']) {
                $player->sendMessage("§7| §3Приват §7| §fВы §cне можете §fломать блоки на этой территории§7.");
                $event->setCancelled(true);
            }
        }
    }
    
    
    public function onEntityDamageByEntity(EntityDamageEvent $event)
    {
        $entity = $event->getEntity();
        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();
            $leveld = $damager->getLevel()->getName();
            $xd = round($damager->getX());
            $yd = round($damager->getY());
            $zd = round($damager->getZ());
            $resultd_check = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE (Pos1X <= $xd AND $xd <= Pos2X) AND (Pos1Y <= $yd AND $yd <= Pos2Y) AND (Pos1Z <= $zd AND $zd <= Pos2Z) AND Level = '" . $leveld . "';")->fetchArray(SQLITE3_ASSOC);
            $resultd = $this->db->query("SELECT * FROM AREAS WHERE (Pos1X <= $xd AND $xd <= Pos2X) AND (Pos1Y <= $yd AND $yd <= Pos2Y) AND (Pos1Z <= $zd AND $zd <= Pos2Z) AND Level = '" . $leveld . "';")->fetchArray(SQLITE3_ASSOC);
            $pvpd_flag = $this->db->query("SELECT * FROM FLAGS WHERE Region = '" . $resultd['Region'] . "' AND Flag = 'pvp'")->fetchArray(SQLITE3_ASSOC);
            $pvpd_flag_check = $this->db->query("SELECT COUNT(*) as count FROM FLAGS WHERE Region = '" . $resultd['Region'] . "' AND Flag = 'pvp'")->fetchArray(SQLITE3_ASSOC);
            $levele = $entity->getLevel()->getName();
            $xe = round($entity->getX());
            $ye = round($entity->getY());
            $ze = round($entity->getZ());
            $resulte_check = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE (Pos1X <= $xe AND $xe <= Pos2X) AND (Pos1Y <= $ye AND $ye <= Pos2Y) AND (Pos1Z <= $ze AND $ze <= Pos2Z) AND Level = '" . $levele . "';")->fetchArray(SQLITE3_ASSOC);
            $resulte = $this->db->query("SELECT * FROM AREAS WHERE (Pos1X <= $xe AND $xe <= Pos2X) AND (Pos1Y <= $ye AND $ye <= Pos2Y) AND (Pos1Z <= $ze AND $ze <= Pos2Z) AND Level = '" . $levele . "';")->fetchArray(SQLITE3_ASSOC);
            $pvpe_flag = $this->db->query("SELECT * FROM FLAGS WHERE Region = '" . $resulte['Region'] . "' AND Flag = 'pvp'")->fetchArray(SQLITE3_ASSOC);
            $pvpe_flag_check = $this->db->query("SELECT COUNT(*) as count FROM FLAGS WHERE Region = '" . $resulte['Region'] . "' AND Flag = 'pvp'")->fetchArray(SQLITE3_ASSOC);
            
            if ($entity instanceof Player && $damager instanceof Player) {
                if (($resultd_check['count'] && $pvpd_flag_check['count']) || ($resulte_check['count'] && $pvpe_flag_check['count'])) {
                    if ($pvpd_flag['Value'] == "deny" && $pvpe_flag['Value'] != "deny") {
                        $event->setCancelled(true);
                        $damager->sendMessage("§7| §3Приват §7| §fВы находитесь на территории с §cотключенным §fPvP§7.");
                    }
                    if ($pvpd_flag['Value'] == "deny" && $pvpe_flag['Value'] == "deny") {
                        $event->setCancelled(true);
                        $damager->sendMessage("§7| §3Приват §7| §fВы находитесь на территории с §cотключенным §fPvP§7.");
                    }
                    if ($pvpd_flag['Value'] != "deny" && $pvpe_flag['Value'] == "deny") {
                        $event->setCancelled(true);
                        $damager->sendMessage("§7| §3Приват §7| §fИгрок находится на территории с §cотключенным §fPvP§7.");
                    }
                }
            }
        }
    }
    
    
    public function onEntityDamage(EntityDamageEvent $event)
    {
        $entity = $event->getEntity();
        if ($event instanceof EntityDamageEvent) {
            if ($entity instanceof Player) {
                $x = round($entity->getX());
                $y = round($entity->getY());
                $z = round($entity->getZ());
                $level  = $entity->getLevel()->getName();
                $result = $this->db->query("SELECT * FROM AREAS WHERE (Pos1X <= $x AND $x <= Pos2X) AND (Pos1Y <= $y AND $y <= Pos2Y) AND (Pos1Z <= $z AND $z <= Pos2Z) AND Level = '" . $level . "';")->fetchArray(SQLITE3_ASSOC);
                $count = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE (Pos1X <= $x AND $x <= Pos2X) AND (Pos1Y <= $y AND $y <= Pos2Y) AND (Pos1Z <= $z AND $z <= Pos2Z) AND Level = '" . $level . "';")->fetchArray(SQLITE3_ASSOC);
                $flag = $this->db->query("SELECT COUNT(*) as count FROM FLAGS WHERE Region = '" . $result['Region'] . "' AND Flag = 'invincible' AND Value = 'allow'")->fetchArray(SQLITE3_ASSOC);
                if ($count['count'] && $flag['count']) {
                    $event->setCancelled(true);
                }
            }
        }
    }
    
    
    public function onPlayerChat(PlayerChatEvent $event)
    {
        $player = $event->getPlayer();
        $x = round($player->getX());
        $y = round($player->getY());
        $z = round($player->getZ());
        $level = $player->getLevel()->getName();
        $username = strtolower($player->getName());
        $result = $this->db->query("SELECT * FROM AREAS WHERE (Pos1X <= $x AND $x <= Pos2X) AND (Pos1Y <= $y AND $y <= Pos2Y) AND (Pos1Z <= $z AND $z <= Pos2Z) AND Level = '" . $level . "';")->fetchArray(SQLITE3_ASSOC);
        $count = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE (Pos1X <= $x AND $x <= Pos2X) AND (Pos1Y <= $y AND $y <= Pos2Y) AND (Pos1Z <= $z AND $z <= Pos2Z) AND Level = '" . $level . "';")->fetchArray(SQLITE3_ASSOC);
        $flag = $this->db->query("SELECT COUNT(*) as count FROM FLAGS WHERE Region = '" . $result['Region'] . "' AND Flag = 'send-chat' AND Value = 'deny'")->fetchArray(SQLITE3_ASSOC);
        if ($count['count'] && $flag['count'] && !$player->isOp()) {
            $player->sendMessage("§7| §3Приват §7| §fВы §cне можете§f использовать чат на этой территории§7.");
            $event->setCancelled(true);
        }
    }
    
    
    public function onPlayerDropItem(PlayerDropItemEvent $event)
    {
        $player = $event->getPlayer();
        $x = round($player->getX());
        $y = round($player->getY());
        $z = round($player->getZ());
        $level = $player->getLevel()->getName();
        $username = strtolower($player->getName());
        $result = $this->db->query("SELECT * FROM AREAS WHERE (Pos1X <= $x AND $x <= Pos2X) AND (Pos1Y <= $y AND $y <= Pos2Y) AND (Pos1Z <= $z AND $z <= Pos2Z) AND Level = '" . $level . "';")->fetchArray(SQLITE3_ASSOC);
        $flag = $this->db->query("SELECT COUNT(*) as count FROM FLAGS WHERE Region = '" . $result['Region'] . "' AND Flag = 'item-drop' AND Value = 'deny'")->fetchArray(SQLITE3_ASSOC);
        if ($flag['count']) {
            $player->sendMessage("§7| §3Приват §7| §fВы §cне можете §fвыбрасывать вещи на этой территории§7.");
            $event->setCancelled(true);
        }
    }
    
    
    public function onBlockPlace(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $x = round($block->getX());
        $y = round($block->getY());
        $z = round($block->getZ());
        $level = $block->getLevel()->getName();
        $username = strtolower($player->getName());
        $result = $this->db->query("SELECT * FROM AREAS WHERE (Pos1X <= $x AND $x <= Pos2X) AND (Pos1Y <= $y AND $y <= Pos2Y) AND (Pos1Z <= $z AND $z <= Pos2Z) AND Level = '" . $level . "';")->fetchArray(SQLITE3_ASSOC);
        $member = $this->db->query("SELECT COUNT(*) as count FROM MEMBERS WHERE Region = '" . $result['Region'] . "' AND Name = '$username'")->fetchArray(SQLITE3_ASSOC);
        $flag = $this->db->query("SELECT COUNT(*) as count FROM FLAGS WHERE Region = '" . $result['Region'] . "' AND Flag = 'build' AND Value = 'allow'")->fetchArray(SQLITE3_ASSOC);
        if ($result !== false and $username != $result['Owner'] and !$player->isOp() and !$member['count'] and !$flag['count']) {
            $player->sendMessage("§7| §3Приват §7| §fВы §cне можете §fставить блоки на этой территории§7.");
            $event->setCancelled(true);
        }
    }
    
    
    public function onInteract(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $bid = $block->getId();
        $x = round($block->getX());
        $y = round($block->getY());
        $z = round($block->getZ());
        $level = $block->getLevel()->getName();
        $username = strtolower($player->getName());
        $money = $this->economyapi->myMoney($username);
        $item = $event->getItem()->getId();
        $result = $this->db->query("SELECT * FROM AREAS WHERE (Pos1X <= $x AND $x <= Pos2X) AND (Pos1Y <= $y AND $y <= Pos2Y) AND (Pos1Z <= $z AND $z <= Pos2Z) AND Level = '" . $level . "';")->fetchArray(SQLITE3_ASSOC);
        $count  = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE (Pos1X <= $x AND $x <= Pos2X) AND (Pos1Y <= $y AND $y <= Pos2Y) AND (Pos1Z <= $z AND $z <= Pos2Z) AND Level = '" . $level . "';")->fetchArray(SQLITE3_ASSOC);
        
        
        $find = $x.":".$y.":".$z.":".$level;
        if(isset($this->sells[$find])) {
            $region = $this->sells[$find]["region"];
            $xgroup = $this->xgroup->getAll();
            $user_group = $this->getPlayerGroup($username, array('levelName' => null));
		    
            if(isset($xgroup[$user_group]) && is_array($xgroup[$user_group])) $group = $user_group;
		    else $group = $this->config->get("default_group");
            
            if($result == false) {
                $player->sendMessage("§7| §3Приват §7| §fЭтот регион §cбольше не продается§7.");
                $event->setCancelled(true);
				return;
            }
            if($username == $result['Owner']){
                $player->sendMessage("§7| §3Приват §7| §fВы §cне можете покупать §fрегион у самого себя§7.");
                return;
            }
            if(!$count['count']) {
                $player->sendMessage("§7| §3Приват §7| §fЭтот регион больше §cне продается§7.");
                return;
            }
            if(($this->sells[$find]["blocks"] > $xgroup[$group]['max_region_count_blocks']) && !$player->isOp()) {
                $player->sendMessage("§7| §3Приват §7| §fВ данном привате §eзапривачено большое количество блоков §7({$this->sells[$find]["blocks"]}), §eчем доступно у вас §7({$xgroup[$group]['max_region_count_blocks']})");
                $event->setCancelled(true);
                return;
            }
            if($money < $this->sells[$find]["price"]) {
                $player->sendMessage("§7| §3Приват §7| §fУ вас §cне хватает денег §fна покупку данной территории§7. §eОтправляйтесь на работу§7, §fбездельник §7;)");
                $event->setCancelled(true);
                return;
            }
			$rg_count = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE Owner = '$username'")->fetchArray();
			if($rg_count['count'] >= $xgroup[$group]["max_regions_num"] and !$player->isOp()) {
                $player->sendMessage("§7| §3Приват §7| §fВы §cне можете создать §fболее§d {$xgroup[$group]['max_regions_num']} §fрегионов§7.\n");
                $player->sendMessage("§r§7| §3Приват §7| §fУ вас §aуже есть§d {$rg_count['count']} §fрегионов§7.\n§r§7| §3Приват §7| §cБольше §fдля данной привилегии §cне доступно§7.\n§r§7| §3Приват §7| §eПовысьте свою привилегию §fили §eудалите один из имеющихся приватов§7.");
                $event->setCancelled(true);
                return;
            }
            $this->economyapi->payMoney($result['Owner'], $this->sells[$find]["price"]);
			$this->db->exec("DELETE FROM MEMBERS WHERE Region = '$region'; DELETE FROM FLAGS WHERE Region = '$region'");
			$this->db->query("UPDATE AREAS SET Sell = '0' WHERE Region = '$region'");
            $this->db->query("UPDATE AREAS SET Owner = '$username' WHERE Region = '$region'");
            $this->economyapi->remMoney($username, $this->sells[$find]["price"]);
            $this->sells[$find] = null;
            unset($this->sells[$find]);
            $player->sendMessage("§7| §3Приват §7| §fВы §aуспешно приобрели §fрегион§d {$region}§7. §eНе забудьте сломать §fтабличку§7!");
        }
        
        
        if ($item == 271) {
            $this->pos2[$username] = array($x, $y, $z, $level);
            $player->sendMessage("§7| §3Приват §7| §aВторая §fточка установлена на §7({$x}, {$y}, {$z}).");
            if (isset($this->pos1[$username]) && isset($this->pos2[$username]) && $this->pos1[$username][3] == $this->pos2[$username][3]) {
                $pos1 = $this->pos1[$username];
                $pos2 = $this->pos2[$username];
                $min[0] = min($pos1[0], $pos2[0]);
                $max[0] = max($pos1[0], $pos2[0]);
                $min[1] = min($pos1[1], $pos2[1]);
                $max[1] = max($pos1[1], $pos2[1]);
                $min[2] = min($pos1[2], $pos2[2]);
                $max[2] = max($pos1[2], $pos2[2]);
                $count = $this->countBlocks($min[0], $min[1], $min[2], $max[0], $max[1], $max[2]);
                $player->sendMessage("§7| §3Приват §7| §fВыбрано§d {$count} §fблоков§7.");
            }
            $event->setCancelled(true);
        }
        
        if ($bid == 54) {
            if ($count['count']) {
                $member = $this->db->query("SELECT COUNT(*) as count FROM MEMBERS WHERE Name = '$username' AND Region = '" . $result['Region'] . "'")->fetchArray(SQLITE3_ASSOC);
                $flag   = $this->db->query("SELECT COUNT(*) as count FROM FLAGS WHERE Flag = 'chest-access' AND Region = '" . $result['Region'] . "' AND Value = 'allow'")->fetchArray(SQLITE3_ASSOC);
                if (!$member['count'] && !$flag['count'] && $username != $result['Owner']) {
                    if (!$player->isOp()) {
                        $player->sendMessage("§7| §3Приват §7| §fВы §cне можете §fоткрывать сундуки на этой территории§7.");
                        $event->setCancelled(true);
                    }
                }
            }
        }
        
        if ($item == 290 || $item == 291 || $item == 292 || $item == 293 || $item == 294) {
            if ($count['count']) {
                $member = $this->db->query("SELECT COUNT(*) as count FROM MEMBERS WHERE Name = '$username' AND Region = '" . $result['Region'] . "'")->fetchArray(SQLITE3_ASSOC);
                if (!$member['count'] && $username != $result['Owner']) {
                    if (!$player->isOp()) {
                        $player->sendMessage("§7| §3Приват §7| §fВы §cне можете §fокучивать землю на этой территории§7.");
                        $event->setCancelled(true);
                    }
                }
            }
        }
        
        if ($bid == 64 || $bid == 71 || $bid == 324 || $bid == 330) {
            $result = $this->db->query("SELECT * FROM AREAS WHERE (Pos1X <= $x AND $x <= Pos2X) AND (Pos1Y <= $y AND $y <= Pos2Y) AND (Pos1Z <= $z AND $z <= Pos2Z) AND Level = '" . $level . "';")->fetchArray(SQLITE3_ASSOC);
            $count = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE (Pos1X <= $x AND $x <= Pos2X) AND (Pos1Y <= $y AND $y <= Pos2Y) AND (Pos1Z <= $z AND $z <= Pos2Z) AND Level = '" . $level . "';")->fetchArray(SQLITE3_ASSOC);
            if ($count['count']) {
                $member = $this->db->query("SELECT COUNT(*) as count FROM MEMBERS WHERE Name = '$username' AND Region = '" . $result['Region'] . "'")->fetchArray(SQLITE3_ASSOC);
                $flag = $this->db->query("SELECT COUNT(*) as count FROM FLAGS WHERE Flag = 'use' AND Region = '" . $result['Region'] . "' AND Value = 'allow'")->fetchArray(SQLITE3_ASSOC);
                if (!$member['count'] && !$flag['count'] && $username != $result['Owner']) {
                    if (!$player->isOp()) {
                        $player->sendMessage("§7| §3Приват §7| §fВы §cне можете §fоткрывать двери на этой территории§7.");
                        $event->setCancelled(true);
                    }
                }
            }
        }
        
        if ($bid == 61 || $bid == 62) {
            if ($count['count']) {
                $member = $this->db->query("SELECT COUNT(*) as count FROM MEMBERS WHERE Name = '$username' AND Region = '" . $result['Region'] . "'")->fetchArray(SQLITE3_ASSOC);
                $flag = $this->db->query("SELECT COUNT(*) as count FROM FLAGS WHERE Flag = 'use' AND Region = '" . $result['Region'] . "' AND Value = 'allow'")->fetchArray(SQLITE3_ASSOC);
                if (!$member['count'] && !$flag['count'] && $username != $result['Owner']) {
                    if (!$player->isOp()) {
                        $player->sendMessage("§7| §3Приват §7| §fВы §cне можете §fпользоваться печкой на этой территории§7.");
                        $event->setCancelled(true);
                    }
                }
            }
        }
        
        if ($item == 280) {
            if ($count['count']) {
                $count_blocks = $this->countBlocks($result['Pos1X'], $result['Pos1Y'], $result['Pos1Z'], $result['Pos2X'], $result['Pos2Y'], $result['Pos2Z']);
                $flag = $this->db->query("SELECT COUNT(*) as count FROM FLAGS WHERE Region = '" . $result['Region'] . "' AND Flag = 'info' AND Value = 'deny'")->fetchArray(SQLITE3_ASSOC);
                if (!$flag['count'] || $username == $result['Owner'] || $player->isOp()) {
                    $player->sendMessage("§8===== §eИнформация о регионе §f{$result['Region']} §8=====\n§r§aВладелец §7- §3{$result['Owner']}\n§r§aКоличество блоков §7- §d{$count_blocks}\n§r§bПервая точка§7: §6{$result['Pos1X']} {$result['Pos1Y']} {$result['Pos1Z']}\n§r§bВторая точка§7: §6{$result['Pos2X']} {$result['Pos2Y']} {$result['Pos2Z']}");
                    if($result['Sell'] == 0) $player->sendMessage("§aСтатус§7: §cНе продается.");
                    else $player->sendMessage("§aСтатус§7:§e Продается");
                } else {
                    $player->sendMessage("§7| §3Приват §7| §fИнформация об этом регионе §cскрыта§7.");
                }
            } elseif(!$count['count']) {
                $player->sendMessage("§7| §3Приват §7| §fПриватов §cне обнаружено§7.");
            }
        }
    }
    
    
	public function getSells(){
		return $this->sells;
	}
    
    public function onEnable()
    {
        @mkdir($this->getDataFolder());
        $dbf = fopen($this->getDataFolder() . "regions.sqlite3", 'a');
        fwrite($dbf, "");
        fclose($dbf);
        $this->saveDefaultConfig();
        $this->economyapi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        
		$this->sells = (new Config($this->getDataFolder(). "sells.yml", Config::YAML))->getAll();
        if (file_exists($this->getDataFolder() . "config.yml")) {
            $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        } else {
            $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, array(
                'enable_permission_plugin_support' => true,
                'permission_plugin' => 'PurePerms',
                'default_group' => 'Игрок',
                'use_world_parameter' => false
            ));
        }
        
        
		if (file_exists($this->getDataFolder() . "xgroups.yml")) {
            $this->xgroup = new Config($this->getDataFolder() . "xgroups.yml", Config::YAML);
        } else {
            $this->xgroup = new Config($this->getDataFolder() . "xgroups.yml", Config::YAML, array(
                "Игрок" => array(
                    'max_regions_num' => 2,
                    'max_region_count_blocks' => 10000
                ),
                'Флай' => array(
                    'max_regions_num' => 5,
                    'max_region_count_blocks' => 15000
                ),
                'Вип' => array(
                    'max_regions_num' => 5,
                    'max_region_count_blocks' => 15000
                ),
                'Премиум' => array(
                    'max_regions_num' => 5,
                    'max_region_count_blocks' => 20000
                ),
                'Креатив' => array(
                    'max_regions_num' => 5,
                    'max_region_count_blocks' => 20000
                ),
                'Модер' => array(
                    'max_regions_num' => 5,
                    'max_region_count_blocks' => 25000
                ),
                'Админ' => array(
                    'max_regions_num' => 5,
                    'max_region_count_blocks' => 25000
                ),
                'Глава' => array(
                    'max_regions_num' => 5,
                    'max_region_count_blocks' => 30000
                ),
                'Бог' => array(
                    'max_regions_num' => 5,
                    'max_region_count_blocks' => 30000
                ),
                'Создатель' => array(
                    'max_regions_num' => 5,
                    'max_region_count_blocks' => 50000
                )
            ));
        }
        $this->loadDB();
		//$this->sells->save();
        $this->config->save();
        $this->xgroup->save();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    
    
    public function getPlayerGroup($username, $params)
    {
        $pureperms = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
        $pgroup    = $pureperms->getUserDataMgr()->getGroup($this->getServer()->getOfflinePlayer($username), $params['levelName'])->getName();
        return $pgroup;
    }
    
    
    
    public function onTouch(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $x = round($block->getX());
        $y = round($block->getY());
        $z = round($block->getZ());
        $level = $block->getLevel()->getName();
        $username = strtolower($player->getName());
        $item = $event->getItem();
        $id = $item->getId();
        
        if ($id == 351 && $item->getDamage() == 15) {
            $result = $this->db->query("SELECT * FROM AREAS WHERE (Pos1X <= $x AND $x <= Pos2X) AND (Pos1Y <= $y AND $y <= Pos2Y) AND (Pos1Z <= $z AND $z <= Pos2Z) AND Level = '" . $level . "';")->fetchArray(SQLITE3_ASSOC);
            $count  = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE (Pos1X <= $x AND $x <= Pos2X) AND (Pos1Y <= $y AND $y <= Pos2Y) AND (Pos1Z <= $z AND $z <= Pos2Z) AND Level = '" . $level . "';")->fetchArray(SQLITE3_ASSOC);
            if ($count['count']) {
                $member = $this->db->query("SELECT COUNT(*) as count FROM MEMBERS WHERE Name = '$username' AND Region = '" . $result['Region'] . "'")->fetchArray(SQLITE3_ASSOC);
                $flag   = $this->db->query("SELECT COUNT(*) as count FROM FLAGS WHERE Flag = 'bone-meal' AND Region = '" . $result['Region'] . "' AND Value = 'allow'")->fetchArray(SQLITE3_ASSOC);
                if (!$member['count'] && !$flag['count'] && $username != $result['Owner']) {
                    if (!$player->isOp()) {
                        $player->sendMessage("§7| §3Приват §7| §fВы §cне можете §fиспользовать костную муку на этой территории§7.");
                        $event->setCancelled(true);
                    }
                }
            }
        }
        
        if ($id == 325) {
            $result = $this->db->query("SELECT * FROM AREAS WHERE (Pos1X <= $x AND $x <= Pos2X) AND (Pos1Y <= $y AND $y <= Pos2Y) AND (Pos1Z <= $z AND $z <= Pos2Z) AND Level = '" . $level . "';")->fetchArray(SQLITE3_ASSOC);
            $count  = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE (Pos1X <= $x AND $x <= Pos2X) AND (Pos1Y <= $y AND $y <= Pos2Y) AND (Pos1Z <= $z AND $z <= Pos2Z) AND Level = '" . $level . "';")->fetchArray(SQLITE3_ASSOC);
            if ($count['count']) {
                $member = $this->db->query("SELECT COUNT(*) as count FROM MEMBERS WHERE Name = '$username' AND Region = '" . $result['Region'] . "'")->fetchArray(SQLITE3_ASSOC);
                $flag   = $this->db->query("SELECT COUNT(*) as count FROM FLAGS WHERE Flag = 'bucket' AND Region = '" . $result['Region'] . "' AND Value = 'allow'")->fetchArray(SQLITE3_ASSOC);
                if (!$member['count'] && !$flag['count'] && $username != $result['Owner']) {
                    if (!$player->isOp()) {
                        $player->sendMessage("§7| §3Приват §7| §fВы §cне можете §fиспользовать ведро на этой территории§7.");
                        $event->setCancelled(true);
                    }
                }
            }
        }
        
        if ($id == 259) {
            $result = $this->db->query("SELECT * FROM AREAS WHERE (Pos1X <= $x AND $x <= Pos2X) AND (Pos1Y <= $y AND $y <= Pos2Y) AND (Pos1Z <= $z AND $z <= Pos2Z) AND Level = '" . $level . "';")->fetchArray(SQLITE3_ASSOC);
            $count  = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE (Pos1X <= $x AND $x <= Pos2X) AND (Pos1Y <= $y AND $y <= Pos2Y) AND (Pos1Z <= $z AND $z <= Pos2Z) AND Level = '" . $level . "';")->fetchArray(SQLITE3_ASSOC);
            if ($count['count']) {
                $member = $this->db->query("SELECT COUNT(*) as count FROM MEMBERS WHERE Name = '$username' AND Region = '" . $result['Region'] . "'")->fetchArray(SQLITE3_ASSOC);
                $flag   = $this->db->query("SELECT COUNT(*) as count FROM FLAGS WHERE Flag = 'lighter' AND Region = '" . $result['Region'] . "' AND Value = 'allow'")->fetchArray(SQLITE3_ASSOC);
                if (!$member['count'] && !$flag['count'] && $username != $result['Owner']) {
                    if (!$player->isOp()) {
                        $player->sendMessage("§7| §3Приват §7| §fВы §cне можете §fиспользовать огниво на этой территории§7.");
                        $event->setCancelled(true);
                    }
                }
            }
        }
    }
    
    
    
    public function countBlocks($x1, $y1, $z1, $x2, $y2, $z2)
    {
        $count = abs(($x2 - $x1 + 1) * ($y2 - $y1 + 1) * ($z2 - $z1 + 1));
        return $count;
    }
    
    
    public function loadDB()
    {
        @mkdir($this->getDataFolder());
        $this->db = new \SQLite3($this->getDataFolder(). "regions.sqlite3");
        $this->db->exec("CREATE TABLE IF NOT EXISTS AREAS(Region TEXT,Owner TEXT NOT NULL,Pos1X INTEGER NOT NULL,Pos1Y INTEGER NOT NULL,Pos1Z INTEGER NOT NULL,Pos2X INTEGER NOT NULL,Pos2Y INTEGER NOT NULL,Pos2Z INTEGER NOT NULL,Level TEXT NOT NULL,Sell INTEGER NOT NULL);CREATE TABLE IF NOT EXISTS MEMBERS(Name TEXT NOT NULL,Region TEXT NOT NULL);CREATE TABLE IF NOT EXISTS FLAGS(Region TEXT NOT NULL,Flag TEXT NOT NULL,Value TEXT NOT NULL);");
    }
    
    
    public function onDisable()
    {
        $this->db->close();
		$config = (new Config($this->getDataFolder()."sells.yml", Config::YAML));
		$config->setAll($this->sells);
		$config->save();
    }
	
	
	
	
	
	
	
	private function member($player, $username){
		$result = $this->db->query("SELECT * FROM MEMBERS WHERE Name = '$username'");
		$result_check = $this->db->query("SELECT COUNT(*) as count FROM MEMBERS WHERE Name = '$username'")->fetchArray(SQLITE3_ASSOC);
		if($result_check['count']){
			$player->sendMessage("§7| §3Приват §7| §fВы §eдобавлены §fв следующий§7(§fе§7)§f регион§7(§fы§7) :");
			while($list = $result->fetchArray(SQLITE3_ASSOC)){
				$player->sendMessage("§7> §b{$list['Region']}");
			}
		} else {
			$player->sendMessage("§7| §3Приват §7| §fВас никто §cне добавлял §fв свой регион§7.");
		}
	}
	
	
	private function addmember($player, $username, $region, $member){
		if(!$player->isOp()){
			$result = $this->db->query("SELECT * FROM AREAS WHERE Region = '$region' AND Owner = '$username'")->fetchArray(SQLITE3_ASSOC);
			$count = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE Region = '$region' AND Owner = '$username'")->fetchArray(SQLITE3_ASSOC);
		} else {
			$result = $this->db->query("SELECT * FROM AREAS WHERE Region = '$region'")->fetchArray(SQLITE3_ASSOC);
			$count = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE Region = '$region'")->fetchArray(SQLITE3_ASSOC);
		}
		
		if($count['count']){
			$check = $this->db->query("SELECT COUNT(*) as count FROM MEMBERS WHERE Region = '$region' AND Name = '$member'")->fetchArray(SQLITE3_ASSOC);
			if(! $check['count']){
				$this->db->query("INSERT INTO MEMBERS (Region, Name) VALUES ('$region','$member')");
				$player->sendMessage("§7| §3Приват §7| §e{$member} §fбыл §aдобавлен §fв Ваш регион§7.");
			} else $player->sendMessage("§7| §3Приват §7| §e{$member} §cуже добавлен §fв Ваш регион§7.");
		} else $player->sendMessage("§7| §3Приват §7| §fРегиона§e {$region} §cне существует§7.");
	}
	
	
	private function removemember($player, $username, $region, $member){
		if(! $player->isOp()){
			$result = $this->db->query("SELECT * FROM AREAS WHERE Region = '$region' AND Owner = '$username'")->fetchArray(SQLITE3_ASSOC);
			$count = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE Region = '$region' AND Owner = '$username'")->fetchArray(SQLITE3_ASSOC);
		}else{
			$result = $this->db->query("SELECT * FROM AREAS WHERE Region = '$region'")->fetchArray(SQLITE3_ASSOC);
			$count = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE Region = '$region'")->fetchArray(SQLITE3_ASSOC);
		}
		
        if($count['count']){
			$check = $this->db->query("SELECT COUNT(*) as count FROM MEMBERS WHERE Region = '$region' AND Name = '$member'")->fetchArray(SQLITE3_ASSOC);
			if($check['count']){
				$this->db->query("DELETE FROM MEMBERS WHERE Region = '$region' AND Name = '$member'");
				$player->sendMessage("§7| §3Приват §7| §e{$member} §cбыл исключён §fиз Вашего региона§7.");
			} else $player->sendMessage("§7| §3Приват §7| §e{$member} §cне прописан §fв Вашем регионе§7.");
        } else $player->sendMessage("§7| §3Приват §7| §fРегион§e {$region} §cне существует§7.");
	}
	
	
	private function flag($player, $username, $region, $flag, $value){
		if(! $player->isOp()) {
			$count = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE Owner = '$username' AND Region = '$region'")->fetchArray(SQLITE3_ASSOC);
		} else $count = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE Region = '$region'")->fetchArray(SQLITE3_ASSOC);
			
		if($count['count']){
			if($flag == "pvp" || $flag == "build" || $flag == "chest-access" || $flag == "use" || $flag == "info" || $flag == "bone-meal" || $flag == "bucket" || $flag == "lighter" || $flag == "send-chat" || $flag == "item-drop" || ($flag == "invincible" && $player->isOp())){
				if($value == "allow" || $value == "deny"){
					$check_flag = $this->db->query("SELECT COUNT(*) as count FROM FLAGS WHERE Region = '$region' AND Flag = '$flag'")->fetchArray(SQLITE3_ASSOC);
					if($check_flag['count']) $this->db->query("UPDATE FLAGS SET Value = '$value' WHERE Region = '$region' AND Flag = '$flag'");
                    else $this->db->query("INSERT INTO FLAGS (Region, Flag, Value) VALUES ('$region', '$flag', '$value')");
				    $player->sendMessage("§7| §3Приват §7| §eУстановлено значение §7'{$value}' §fдля флага§7 '{$flag}'");
						
				} else $player->sendMessage("§7| §3Приват §7| §eЗначение может быть только §7'allow' (разрешить) §eили §7'deny' (запретить).");
            }else{
				$player->sendMessage("§7| §3Приват §7| §eСуществующие флаги§7: §dpvp§7,§d build§7, §dchest-access§7, §duse§7, §dinfo§7,§d bone-meal§7, §dbucket§7, §dlighter§7,§d send-chat§7, §ditem-drop");
				if($player->isOp()) $player->sendMessage("§eФлаги для администраторов§7: §dinvincible");
				if(($flag == "invincible") && ! $player->isOp()) $player->sendMessage("§7| §3Приват §7| §fВы §cне можете устанавливать §fэтот флаг§7.");
			}
		} else $player->sendMessage("§7| §3Приват §7| §fРегион§e {$region} §cне существует§7.");
	}
	
	
	private function leaveregion($player, $username, $region){
		$check = $this->db->query("SELECT COUNT(*) as count FROM MEMBERS WHERE Region = '$region' AND Name = '$username'")->fetchArray(SQLITE3_ASSOC);
		if($check['count']) {
			$this->db->query("DELETE FROM MEMBERS WHERE Name = '$username' AND Region = '$region'");
			$player->sendMessage("§7| §3Приват §7| §fВы §eпокинули §fрегион§d {$region}");
		} else $player->sendMessage("§7| §3Приват §7| §fВы §cне прописаны §fв регионе§d {$region}");
	}
	
	
	private function claim($player, $username, $region){
		$level = $player->getLevel()->getName();
		$xgroup = $this->xgroup->getAll();
        $user_group = $this->getPlayerGroup($player->getName(), array('levelName' => null));
		
		if(isset($xgroup[$user_group]) && is_array($xgroup[$user_group])) {
			$group = $user_group;
		} else {
			$group = $this->config->get("default_group");
		}
		
		if(preg_match("/^[a-zA-Z0-9_]+$/", $region)){
			$check = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE Region = '$region'")->fetchArray(SQLITE3_ASSOC);
			if(! $check['count']){
				if(! isset($this->pos1[$username]) || ! isset($this->pos2[$username])) {
					$player->sendMessage("§7| §3Приват §7| §cВыделите область региона §7!");
				return true;
			    }
				
				if($this->pos1[$username][3] !== $this->pos2[$username][3]) {
					$player->sendMessage("§7| §3Приват §7| §cВыбранные точки в разных мирах §7!");
				return true;
				}
				$pos1 = $this->pos1[$username];
				$pos2 = $this->pos2[$username];
				$min[0] = min($pos1[0], $pos2[0]);
				$max[0] = max($pos1[0], $pos2[0]);
				$min[1] = min($pos1[1], $pos2[1]);
				$max[1] = max($pos1[1], $pos2[1]);
				$min[2] = min($pos1[2], $pos2[2]);
				$max[2] = max($pos1[2], $pos2[2]);
				$count = $this->countBlocks($min[0], $min[1], $min[2], $max[0], $max[1], $max[2]);
				$result = $this->db->query("SELECT * FROM AREAS WHERE Pos2X >= $min[0] AND Pos1X <= $max[0] AND Pos2Y >= $min[1] AND Pos1Y <= $max[1] AND Pos2Z >= $min[2] AND Pos1Z <= $max[2] AND Level = '".$pos1[3]."';")->fetchArray(SQLITE3_ASSOC);
				if($result !== false && ! $player->isOp()) {
					$player->sendMessage("§7| §3Приват §7| §fЭтот регион §eпересекает §fграницу региона§d {$result['Region']}");
				return true;
				
				}elseif(($count > $xgroup[$group]['max_region_count_blocks']) && ! $player->isOp()) {
					$player->sendMessage("§7| §3Приват §7| §cМаксимальное допустимое количество блоков региона§d {$xgroup[$group]['max_region_count_blocks']}\n§r§7| §3Приват §7| §fВы §aвыделили §d{$count}");
				return true;
				}
				
				$level = $pos1[3];
				$rg_count = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE Owner = '$username'")->fetchArray();
				if($rg_count['count'] < $xgroup[$group]["max_regions_num"] || $player->isOp()) {
					$this->db->exec("INSERT INTO AREAS (Owner, Pos1X, Pos1Y, Pos1Z, Pos2X, Pos2Y, Pos2Z, Level, Region, Sell) VALUES ('$username', $min[0], $min[1], $min[2], $max[0], $max[1], $max[2], '$level', '$region', '0')");
					unset($this->pos1[$username]);
					unset($this->pos2[$username]);
					$player->sendMessage("§7| §3Приват §7| §fНовый регион §aуспешно создан §fи назван как§b {$region}");

				}else{
					$player->sendMessage("§7| §3Приват §7| §fВы §cне можете создать §fболее§d {$xgroup[$group]['max_regions_num']} §fрегионов§7.\n§r§7| §3Приват §7| §fВы §aуже создали§d {$rg_count['count']} §fрегионов§7.");
			    }
			}else{
				$player->sendMessage("§7| §3Приват §7| §fРегион с названием§a {$region} §eуже существует §7!");
			}
		}else{
			$player->sendMessage("§7| §3Приват §7| §cНекорректное название региона §7!\n§r§7| §3Приват §7| §eДопускаются только §dбуквы латинского алфавита§7, §dцифры §eи §dнижнее подчёркивание§7.");
		}
	}
	
	
	private function unclaim($player, $username, $region){
		if($player->isOp()) {
			$rg_count = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE Region = '$region'")->fetchArray();
		} else {
			$rg_count = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE Owner = '$username' AND Region = '$region'")->fetchArray();
		}
		
		if($rg_count['count']) {
			$this->db->exec("DELETE FROM AREAS WHERE Region = '$region'; DELETE FROM MEMBERS WHERE Region = '$region'; DELETE FROM FLAGS WHERE Region = '$region'");
			$player->sendMessage("§7| §3Приват §7| §fВы §aудалили §fрегион§d {$region}");
			} else $player->sendMessage("§7| §3Приват §7| §fРегион§e {$region} §cне существует§7.");
	}
	
	
	private function rglist($player, $username, $who) {
		if($player->isOp()) $list_sql = "SELECT * FROM AREAS WHERE Owner = '$who'";
		else $list_sql = "SELECT * FROM AREAS WHERE Owner = '$username'";
		
		$query = $this->db->query($list_sql);
		$player->sendMessage("§7| §3Приват §7| §eРегионы§7:");
		while($row = $query->fetchArray()) {
			$player->sendMessage("§7> §b". $row['Region']);
		}
	}
	
	
	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
	    foreach ($args as $arg){
	        if (preg_match('/\'/', $arg) == 1){
	            break;
	            $sender->sendMessage("Попытка взлома");
	            return false;
            }
        }

		$username = strtolower($sender->getName());
        $c = $cmd->getName();
		$player = $this->getServer()->getPlayer($username);

        if($c == "rg" or $c == "region") {
            if(isset($args[0])) {
                switch($args[0]) {
					
					case "city":
					if(isset($args[1])){
						switch($args[1]) {
						case "middle_ages":
						$sender->sendMessage("§e✎ Вы телепортировались в город §cСредневековъя");
						$sender->teleport(new Position(-444, 66, 294));
						if($args[1] !== "middle_ages"){
							$sender->sendMessage("§c✎ §eТакого города не существует\n§c✎ §eПишите§7: §7/§brg city назв.города\n§e✎ §aДоступные города §7: §cmiddle_ages§7, §cmodern");
						}
						break;
					case "modern":
						$sender->sendMessage("§e✎ Вы телепортировались в город §cМодерн");
						$sender->teleport(new Position(-156, 66, 68));
						if($args[1] !== "modern"){
							$sender->sendMessage("§c✎ §eТакого города не существует\n§c✎ §eПишите§7: §7/§brg city назв.города\n§e✎ §aДоступные города §7: §cmiddle_ages§7, §cmodern");
						}
						break;
						}
					}else{
						$sender->sendMessage("§c✎ §eПишите§7: §7/§brg city назв.города\n§e✎ §aДоступные города §7: §cmiddle_ages§7, §cmodern");
					}
					break;
                        
			        case 'addmember':
                    if(isset($args[1]) and isset($args[2])) {
                        $region = strtolower($args[1]);
                        $member = strtolower($args[2]);
                        $this->addmember($player, $username, $region, $member);
                    } else $sender->sendMessage("§e✎ Использование§7: §3/rg addmember <регион> <игрок>");
			        break;
                        
			        case 'removemember':
                    if(isset($args[1]) and isset($args[2])) {
                        $region = strtolower($args[1]);
                        $member = strtolower($args[2]);
                        $this->removemember($player, $username, $region, $member);
                    } else $sender->sendMessage("§e✎ Использование§7: §3/rg removemember <регион> <игрок>");
			        break;
                        
			        case 'flag':
                    if(isset($args[1]) and isset($args[2]) and isset($args[3])) {
                        $region = strtolower($args[1]);
                        $flag = strtolower($args[2]);
                        $value = strtolower($args[3]);
                        $this->flag($player, $username, $region, $flag, $value);
                    } else $sender->sendMessage("§e✎ Использование§7: §3/rg flag <регион> <флаг> <значение>");
			        break;
                                       
			        case 'leave':
			        if(isset($args[1])) {
                        $region = strtolower($args[1]);
                        $this->leaveregion($player, $username, $region);
                    } else $sender->sendMessage("§7| §3Приват §7| §cВыберите регион§7, §eиз которого хотите уйти §7! - §3/rg leave <регион>");
			        break;
                                       
			        case 'wand':
			        $id = Item::get(271, 0, 1);
			        $player->getInventory()->addItem($id);
			        $player->sendMessage("§7| §3Приват §7| §eДолгий тап (сломать блок): первая точка. Быстрый тап: вторая точка.");
			        break;
                        
			        case 'create':
                    case "claim":
			        if(isset($args[1])) {
                        $region = strtolower($args[1]);
						$region=str_replace('"',"",$region);
						$region=str_replace("'","",$region);
						$region=str_replace("or","",$region);
						$region=str_replace("like","",$region);
						$region=str_replace("where","",$region);
						$region=str_replace("update","",$region);
						$region=str_replace("remove","",$region);
						$region=str_replace("limit","",$region);
                        $this->claim($player, $username, $region);
                    } else $sender->sendMessage("§e✎ Использование§7: §3/rg create <название>");
			        break;
                        
			        case "remove":
                    case "delete":
                    case "unclaim":
			        if(isset($args[1])) {
                        $region = strtolower($args[1]);
						$region=str_replace('"',"",$region);
						$region=str_replace("'","",$region);
						$region=str_replace("or","",$region);
						$region=str_replace("like","",$region);
						$region=str_replace("where","",$region);
						$region=str_replace("update","",$region);
						$region=str_replace("remove","",$region);
						$region=str_replace("limit","",$region);
                        $this->unclaim($player, $username, $region);
                    } else $sender->sendMessage("§e✎ Использование§7: §3/rg remove <регион>");
			        break;
                        
                    case "pos2":
				    $x = round($player->getX());
				    $y = round($player->getY());
				    $z = round($player->getZ());
				    $level = $player->getLevel()->getName();
				    $this->pos2[$username] = array($x,$y,$z,$level);
				    $player->sendMessage("§7| §3Приват §7| §aВторая §fточка установлена на §7({$x}, {$y}, {$z}).");
				    if(isset($this->pos1[$username]) && isset($this->pos2[$username]) && $this->pos1[$username][3] == $this->pos2[$username][3]){
					    $pos1 = $this->pos1[$username];
					    $pos2 = $this->pos2[$username];
					    $min[0] = min($pos1[0], $pos2[0]);
					    $max[0] = max($pos1[0], $pos2[0]);
					    $min[1] = min($pos1[1], $pos2[1]);
					    $max[1] = max($pos1[1], $pos2[1]);
					    $min[2] = min($pos1[2], $pos2[2]);
					    $max[2] = max($pos1[2], $pos2[2]);
					    $count = $this->countBlocks($min[0], $min[1], $min[2], $max[0], $max[1], $max[2]);
					    $player->sendMessage("§7| §3Приват §7| §fВыбрано§d {$count} §fблоков§7.");
				    }
                    break;
                        
                    case "pos1":
				    $x = round($player->getX());
				    $y = round($player->getY());
				    $z = round($player->getZ());
				    $level = $player->getLevel()->getName();
				    $this->pos1[$username] = array($x,$y,$z,$level);
				    $player->sendMessage("§7| §3Приват §7| §aПервая §fточка установлена на §7({$x}, {$y}, {$z}).");
				    if(isset($this->pos1[$username]) && isset($this->pos2[$username]) && $this->pos1[$username][3] == $this->pos2[$username][3]){
					    $pos1 = $this->pos1[$username];
					    $pos2 = $this->pos2[$username];
					    $min[0] = min($pos1[0], $pos2[0]);
					    $max[0] = max($pos1[0], $pos2[0]);
					    $min[1] = min($pos1[1], $pos2[1]);
					    $max[1] = max($pos1[1], $pos2[1]);
					    $min[2] = min($pos1[2], $pos2[2]);
					    $max[2] = max($pos1[2], $pos2[2]);
					    $count = $this->countBlocks($min[0], $min[1], $min[2], $max[0], $max[1], $max[2]);
					    $player->sendMessage("§7| §3Приват §7| §fВыбрано§d {$count} §fблоков§7.");
				    }
                    break;
                        
                    case "info":
			        if(isset($args[1])) {
                        $subcommand = strtolower($args[1]);
                        $result = $this->db->query("SELECT * FROM AREAS WHERE Region = '$subcommand'")->fetchArray(SQLITE3_ASSOC);
			            $count = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE Region = '$subcommand'")->fetchArray(SQLITE3_ASSOC);
                        
                        if($count['count']){
				            $count_blocks = $this->countBlocks($result['Pos1X'], $result['Pos1Y'], $result['Pos1Z'], $result['Pos2X'], $result['Pos2Y'], $result['Pos2Z']);
				            $flag = $this->db->query("SELECT COUNT(*) as count FROM FLAGS WHERE Region = '$subcommand' AND Flag = 'info' AND Value = 'deny'")->fetchArray(SQLITE3_ASSOC);
				            
                            if(! $flag['count'] || $username == $result['Owner'] || $player->isOp()){
					           $player->sendMessage("§8===== §eИнформация о регионе §f{$result['Region']} §8=====\n§r§aВладелец §7- §3{$result['Owner']}\n§r§aКоличество блоков §7- §d{$count_blocks}\n§r§bПервая точка§7: §6{$result['Pos1X']} {$result['Pos1Y']} {$result['Pos1Z']}\n§r§bВторая точка§7: §6{$result['Pos2X']} {$result['Pos2Y']} {$result['Pos2Z']}");
                                if($result['Sell'] == 0) $sender->sendMessage("§aСтатус§7: §cНе продается.");
                                else $sender->sendMessage("§aСтатус§7:§e Продается");
				            }else{
					           $player->sendMessage("§7| §3Приват §7| §fИнформация об этом регионе §cскрыта§7.");
				            }
			             } else $player->sendMessage("§7| §3Приват §7| §fРегиона§d {$subcommand} §eне существует§7.");
                    } else $sender->sendMessage("§e✎ Использование§7: §3/rg info <регион>");
                    break;
                        
                    case "list":
				    if(isset($args[1])) $this->rglist($player, $username, $args[1]);
                    else $sender->sendMessage("§e✎ Использование§7: §3/rg list <ник игрока>");
                    break;
                        
                    case "flags":
                    if(isset($args[1])) {
                        $region = strtolower($args[1]);
                        $count = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE Region = '$region'")->fetchArray(SQLITE3_ASSOC);
			            if($count['count']){
				            $flags = $this->db->query("SELECT Flag,Value FROM FLAGS WHERE Region = '$region'");
				            $count_flags = $this->db->query("SELECT COUNT(*) as count FROM FLAGS WHERE Region = '$region'")->fetchArray(SQLITE3_ASSOC);
				            $player->sendMessage("§8==== §eФлаги региона§d {$region} §8====");
				            if($count_flags['count'] > 0){
					            while($flags_list = $flags->fetchArray()){
						            $player->sendMessage("§5{$flags_list['Flag']}: §9{$flags_list['Value']}");
					            }
				            } else $player->sendMessage("§7| §3Приват §7| §cНет §fустановленных флагов§7.");
			            } else $player->sendMessage("§7| §3Приват §7| §fРегиона§e {$region} §cне существует§7.");
                    } else $sender->sendMessage("§e✎ Использование§7: §3/rg flags <регион>");
                    break;
                        
                    case "help":
                    $player->sendMessage("§8(§aПриват§8)§f Помощь по привату.");
                    $player->sendMessage("§8 * §b/rg info §7- §fУзнать информацию о регионе, в котором вы находитесь.");
                    $player->sendMessage("§8 * §b/rg info <регион> §7- §fУзнать информацию о указанном регионе.");
                    $player->sendMessage("§8 * §b/rg list §7- Посмотреть список своих регионов.");
                    $player->sendMessage("§8 * §b/rg <регион> members §7- §fПосмотреть список тех, кто добавлен в регион.");
                    $player->sendMessage("§8 * §b/rg addmember <регион> <никнейм> §7- §fДобавить игрока в регион.");
                    $player->sendMessage("§8 * §b/rg removemember <регион> <никнейм> §7- §fИсключить игрока из региона.");
                    $player->sendMessage("§8 * §b/rg leaveregion <регион> §7- §fВыйти из региона.");
                    $player->sendMessage("§8 * §b/rg member §7- §fПосмотреть список регионов, в которые вы добавлены.");
                    $player->sendMessage("§8 * §b/rg flag <регион> <флаг> <allow/deny> §7- §fУстановить флаг для региона.");
                    $player->sendMessage("§8 * §b/rg pos1 §fи §b/rg pos2 §7- §fУстановить точки начала и конца нового региона (можно и деревянным топором).");
                    $player->sendMessage("§8 * §b/rg create <регион> §7- §fСоздать новый регион.");
                    $player->sendMessage("§8 * §b/rg remove <регион> §7- §fУдалить регион.");
                    break;
                    
                }
            } else $sender->sendMessage("§e✎ Использование§7: §3/rg help");
        }
    }
    
    
    public function onSignChange(SignChangeEvent $event) {
        $player = $event->getPlayer();
        $name = strtolower($player->getName());
        $block = $event->getBlock();
        $level = $block->getLevel()->getName();
        $x = (int) $block->getX();
        $y = (int) $block->getY();
        $z = (int) $block->getZ();
        
        if($event->getLine(0) == "Продаю") {
            if(!is_numeric($event->getLine(1))) {
                $player->sendMessage("§7| §3Приват §7| §fВторая строчка §cдолжна состоять из цифр§7. §fТам §eуказывается цена§7.");
                $event->setCancelled();
                return;
            }
            $result = $this->db->query("SELECT * FROM AREAS WHERE (Pos1X <= $x AND $x <= Pos2X) AND (Pos1Y <= $y AND $y <= Pos2Y) AND (Pos1Z <= $z AND $z <= Pos2Z) AND Level = '" . $level . "';")->fetchArray(SQLITE3_ASSOC);
            $count  = $this->db->query("SELECT COUNT(*) as count FROM AREAS WHERE (Pos1X <= $x AND $x <= Pos2X) AND (Pos1Y <= $y AND $y <= Pos2Y) AND (Pos1Z <= $z AND $z <= Pos2Z) AND Level = '" . $level . "';")->fetchArray(SQLITE3_ASSOC);
            if ($count['count']) {
                if($result['Owner'] != $name) {
                    $player->sendMessage("§7| §3Приват §7| §fВы §cне владелец §fданного региона§7!");
                    $event->setCancelled();
                    return;
                }
                
                if($result['Sell'] == 1) {
                    $player->sendMessage("§7| §3Приват §7| §fДанный регион §cуже продаётся§7.");
                    $event->setCancelled();
                    return;
                }
                $count_blocks = $this->countBlocks($result['Pos1X'], $result['Pos1Y'], $result['Pos1Z'], $result['Pos2X'], $result['Pos2Y'], $result['Pos2Z']);
                $region = $result['Region'];
                $this->db->query("UPDATE AREAS SET Sell = '1' WHERE Region = '$region'");
                $price = (int) $event->getLine(1);
                $this->sells[$x.":".$y.":".$z.":".$level] = array(
				"x" => $x,
				"y" => $y,
				"z" => $z,
				"level" => $level,
				"price" => $price,
                "region" => $region,
                "blocks" => $count_blocks
			    );
                $event->setLine(0, "§7[§bПродаётся§7]");
                $event->setLine(1, "§l§d{$price} §7$");
                $event->setLine(2, "§d{$count_blocks} §9блоков");
                $event->setLine(3, "§eПродавец§7:§3 {$name}");
                $player->sendMessage("§7| §3Приват §7| §fВы §aуспешно начали продавать §fсвой регион§7.");
            } else {
                $player->sendMessage("§7| §3Приват §7| §fТаблица §cдолжна быть §fна территории продаваемого региона§7.");
                $event->setCancelled();
            }
            
            
        }
    }
}