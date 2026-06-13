<?php

namespace App\Policies;

use App\Models\Reserva;
use App\Models\User;

class ReservaPolicy
{
    public function view(User $user, Reserva $reserva): bool
    {
        return $user->isAdmin() || $reserva->user_id === $user->id;
    }

    public function cancel(User $user, Reserva $reserva): bool
    {
        return ($user->isAdmin() || $reserva->user_id === $user->id) && $reserva->cancelable();
    }
}
