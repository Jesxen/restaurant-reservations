<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * A client may submit a review only if they have at least one completed
     * reservation. (The fine-grained "one per reservation" rule lives in
     * StoreReviewRequest.) Staff/admin are operators, not reviewers.
     */
    public function create(User $user): bool
    {
        return $user->reservas()->where('estado', 'completada')->exists();
    }

    /** Only staff/admin may moderate (approve/unapprove). */
    public function moderate(User $user, Review $review): bool
    {
        return $user->isStaff();
    }

    /** Only staff/admin may delete a review. */
    public function delete(User $user, Review $review): bool
    {
        return $user->isStaff();
    }
}
