<?php

/*
 *
 *  ____  _             _         _____
 * | __ )| |_   _  __ _(_)_ __   |_   _|__  __ _ _ __ ___
 * |  _ \| | | | |/ _` | | '_ \    | |/ _ \/ _` | '_ ` _ \
 * | |_) | | |_| | (_| | | | | |   | |  __/ (_| | | | | | |
 * |____/|_|\__,_|\__, |_|_| |_|   |_|\___|\__,_|_| |_| |_|
 *                |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  Blugin team
 * @link    https://github.com/Blugin
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace blugin\lib\entity;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\SkinData;
use pocketmine\player\Player;
use pocketmine\uuid\UUID;

/**
 * This trait override most methods in the {@link EntityBase} abstract class.
 */
trait FakePlayerTrait{
    /** @return string */
    public static function getNetworkTypeId() : string{
        return EntityIds::PLAYER;
    }

    /** @var UUID */
    protected $uuid;

    /** @var SkinData */
    protected $skinData;

    /**
     * @override Override for to spawn as a player
     *
     * @param Player $player
     */
    protected function sendSpawnPacket(Player $player) : void{
        $pk = new AddPlayerPacket();
        $pk->uuid = $this->uuid;
        $pk->username = "";
        $pk->entityRuntimeId = $this->id;
        $pk->position = $this->getSpawnPosition($this->location);
        $pk->pitch = $this->location->pitch;
        $pk->yaw = $this->location->yaw;
        $pk->item = ItemStack::null();
        $pk->metadata = $this->getSyncedNetworkData(false);

        $this->server->broadcastPackets([$player], [
            PlayerListPacket::add([PlayerListEntry::createAdditionEntry($this->uuid, $this->id, "", $this->skinData)]),
            $pk,
            PlayerListPacket::remove([PlayerListEntry::createRemovalEntry($this->uuid)])
        ]);
    }

    /**
     * @override Override for to moving via MovePlayerPacket
     *
     * @param bool $teleport
     */
    public function broadcastMovement(bool $teleport = true) : void{
        $pk = new MovePlayerPacket();
        $pk->entityRuntimeId = $this->id;
        $pk->position = $this->getOffsetPosition($this->location);
        $pk->pitch = $this->location->pitch;
        $pk->headYaw = $this->location->yaw;
        $pk->yaw = $this->location->yaw;
        $pk->mode = $teleport ? MovePlayerPacket::MODE_TELEPORT : MovePlayerPacket::MODE_NORMAL;
        $this->getWorld()->broadcastPacketToViewers($this->location, $pk);
    }

    /**
     * Sets the human's skin.
     *
     * @param SkinData $skinData
     */
    public function setSkin(SkinData $skinData) : void{
        $this->skinData = $skinData;
    }

    /**
     * Sends the human's skin to the specified list of players.
     *
     * @param Player[]|null $targets
     */
    public function sendSkin(?array $targets = null) : void{
        $this->server->broadcastPackets($targets ?? $this->hasSpawned, [PlayerSkinPacket::create($this->uuid, $this->skinData)]);
    }

    /** @return float */
    public function getBaseOffset() : float{
        return 1.62;
    }

    /**
     * @override 오프셋위치를 수정하기 위해 오버라이드
     *
     * @param Vector3 $vector3
     *
     * @return Vector3
     */
    public function getOffsetPosition(Vector3 $vector3) : Vector3{
        return $vector3->add(0, $this->getBaseOffset(), 0);
    }

    /**
     * @param Vector3 $vector3
     *
     * @return Vector3
     */
    public function getSpawnPosition(Vector3 $vector3) : Vector3{
        return $this->getOffsetPosition($vector3)->subtract(0, $this->getBaseOffset(), 0);
    }
}
