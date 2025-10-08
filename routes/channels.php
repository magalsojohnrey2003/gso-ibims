<?php

use Illuminate\Support\Facades\Broadcast;



Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('admin', function ($user) {
    // allow only admins to join the admin channel
    return isset($user->role) && $user->role === 'admin';
});

Broadcast::channel('user.{id}', function ($user, $id) {
    // allow the owner or admins
    return (int)$user->id === (int)$id || (isset($user->role) && $user->role === 'admin');
});