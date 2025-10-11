<?php

namespace App\Interfaces;

interface FloorRepositoryInterface
{
    public function index($user);
    public function store(array $data);
    public function update(array $data, string $id);
    public function destroy(string $id);
}
