<?php

namespace App\Interfaces;

interface PropertyRepositoryInterface
{
    public function index();
    public function show($user, $id);
    public function store(array $data);
    public function update(array $data, $id);
    public function delete($id);
    public function forUser($user);
    public function createWithRelations(string $landlordId, array $data);
}
