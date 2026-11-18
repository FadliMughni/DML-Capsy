<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/** Konten katalog & company profile hanya dikelola Marketing (PBI 24). */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isMarketing();
    }

    public function create(User $user): bool
    {
        return $user->isMarketing();
    }

    public function update(User $user, Product $model): bool
    {
        return $user->isMarketing();
    }

    public function delete(User $user, Product $model): bool
    {
        return $user->isMarketing();
    }
}
