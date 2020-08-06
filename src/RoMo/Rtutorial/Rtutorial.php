<?php

namespace RoMo\Rtutorial;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\level\Position;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\block\Block;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
class Rtutorial extends PluginBase implements Listener{
	public function onEnable(){
		@mkdir($this->getDataFolder());
		$this->set = new Config($this->getDataFolder(). 'setting.yml', Config::YAML);
		$this->sd = $this->set->getAll();
		$this->player = new Config($this->getDataFolder(). 'player.yml', Config::YAML);
		$this->pd = $this->player->getAll();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	public function save(){
		$this->set->setAll($this->sd);
		$this->set->save();
		$this->player->setAll($this->pd);
		$this->player->save();
	}
	public function join(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();
		if(!isset($this->pd[$name]) or $this->pd[$name] == false){
			$this->pd[$name] = false;
			$this->teleport($player);
			$this->save();
		}
	}
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool{
		if($command->getName() == "튜토리얼지정"){
			if($sender instanceof Player){
				$position = $this->getpo($sender);
				$this->sd['position'] = $position;
				$this->save();
				$sender->sendMessage("튜토리얼 지점을 설정하였습니다.");
				return true;
			}else{
				$sender->sendMessage("인게임에서만 가능합니다.");
				return true;
			}
		}
		return false;
	}
	public function getpo(Player $player){
		$x = $player->getX();
		$y = $player->getY();
		$z = $player->getZ();
		$w = $player->getLevel()->getFolderName();
		$last = "{$x}/{$y}/{$z}/{$w}";
		return $last;
	}
	public function teleport(Player $player){
		if(isset($this->sd['position'])){
			$position = explode("/", $this->sd['position']);
			$player->teleport(new Position((int) $position[0], (int) $position[1], (int) $position[2], $this->getServer()->getLevelByName($position[3])));
			$player->sendMessage("§c[§6Rtutorial§c]§f튜토리얼로 이동했습니다");
		}
	}
	public function sign(SignChangeEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$x = $block->getX();
		$y = $block->getY();
		$z = $block->getZ();
		if($event->getLine(0) == "튜토엔드"){
			if($player->isOp()){
				$event->setLine(0, "§f[§6라면서버§f]");
				$event->setLine(1, "§c튜토리얼§f을 §6끝냅니다.");
				$event->setLine(2, "§f스폰으로 이동");
				$this->sd['tutoend'] = "{$x}/{$y}/{$z}";
				$this->save();
			}
		}
	}
	public function interact(PlayerInteractEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();
		$block = $event->getBlock();
		if($block->getId() == Block::SIGN_POST or $block->getId() == Block::WALL_SIGN){
			$x = $block->getX();
			$y = $block->getY();
			$z = $block->getZ();
			$last = "{$x}/{$y}/{$z}";
			if($this->sd['tutoend'] == $last){
				$this->pd[$name] = true;
				$this->save();
				$player->kill();
			}
		}
	}
	public function command(PlayerCommandPreprocessEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();
		if(isset($this->sd['position'])){
			if($this->pd[$name] == false){
				$event->setCancelled();
				$player->sendMessage("§c[§6Rtutorial§c]§f튜토리얼을 완료하시기전엔 명령어를 입력하실 수 없습니다.");
			}
		}
	}
}