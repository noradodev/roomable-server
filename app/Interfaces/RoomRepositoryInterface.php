<?php

namespace App\Interfaces;

interface RoomRepositoryInterface
{
    public function index($user, ?string $floorId = null);
    public function store(array $data);
    public function update(array $data, string $id);
    public function destroy(string $id);
   public function assignRoom(string $roomId, string $tenantId, string $landlordId);
}
