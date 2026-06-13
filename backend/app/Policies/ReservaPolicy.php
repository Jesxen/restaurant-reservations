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

    /**
     * A client may edit their own reservation while it is still in an editable
     * state (pendiente/confirmada); admins may edit any editable reservation.
     */
    public function update(User $user, Reserva $reserva): bool
    {
        return ($user->isAdmin() || $reserva->user_id === $user->id)
            && in_array($reserva->estado, Reserva::ESTADOS_ACTIVOS, true);
    }
}
