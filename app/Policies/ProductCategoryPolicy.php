<?php

namespace App\Policies;

use App\Models\ProductCategory;
use App\Models\User;

/** Konten katalog & company profile hanya dikelola Marketing (PBI 24). */
class ProductCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isMarketing();
    }

    public function create(User $user): bool
    {
        return $user->isMarketing();
    }

    public function update(User $user, ProductCategory $model): bool
    {
        return $user->isMarketing();
    }

    public function delete(User $user, ProductCategory $model): bool
    {
        return $user->isMarketing();
    }
}
