<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});

Broadcast::channel('Landlord.{landlordId}', function ($user, $landlordId) {
    Log::info('Broadcast auth', [
        'user_id' => $user->id, 
        'landlordId_param' => $landlordId,
        'user_type' => gettype($user->id),
        'landlordId_type' => gettype($landlordId)
    ]);
     return (string) $user->id === (string) $landlordId;
});