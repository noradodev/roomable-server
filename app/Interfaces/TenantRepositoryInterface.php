<?php

namespace App\Interfaces;

interface TenantRepositoryInterface
{
    public function index($user);
    public function store(array $data);
    public function update(array $data, string $id);
    public function assignRoom(string $tenantId, string $roomId, array $meta = []);
    public function delete(string $id);
}
