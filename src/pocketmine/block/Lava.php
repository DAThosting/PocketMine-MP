<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityCombustByBlockEvent;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;

class Lava extends Liquid{

	protected $id = self::FLOWING_LAVA;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getLightLevel() : int{
		return 15;
	}

	public function getName() : string{
		return "Lava";
	}

	public function tickRate() : int{
		return 30;
	}

	public function getLiquidLevelDecreasePerBlock() : int{
		return 2; //TODO: this is 1 in the nether
	}

	protected function checkForHarden(){
		$colliding = false;
		for($side = 1; $side <= 5 and !$colliding; ++$side){ //don't check downwards side
			$colliding = $this->getSide($side) instanceof Water;
		}

		if($colliding){
			if($this->getDamage() === 0){
				$this->level->setBlock($this, BlockFactory::get(Block::OBSIDIAN), true, true);
			}elseif($this->getDamage() <= 4){
				$this->level->setBlock($this, BlockFactory::get(Block::COBBLESTONE), true, true);
			}
		}
	}

	protected function flowIntoBlock(Block $block, int $newFlowDecay) : void{
		if($block instanceof Water){
			$this->level->setBlock($block, BlockFactory::get(Block::STONE), true, true);
		}else{
			parent::flowIntoBlock($block, $newFlowDecay);
		}
	}

	public function onEntityCollide(Entity $entity) : void{
		$entity->fallDistance *= 0.5;

		$ev = new EntityDamageByBlockEvent($this, $entity, EntityDamageEvent::CAUSE_LAVA, 4);
		$entity->attack($ev);

		$ev = new EntityCombustByBlockEvent($this, $entity, 15);
		Server::getInstance()->getPluginManager()->callEvent($ev);
		if(!$ev->isCancelled()){
			$entity->setOnFire($ev->getDuration());
		}

		$entity->resetFallDistance();
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		$ret = $this->getLevel()->setBlock($this, $this, true, false);
		$this->getLevel()->scheduleDelayedBlockUpdate($this, $this->tickRate());

		return $ret;
	}

}
