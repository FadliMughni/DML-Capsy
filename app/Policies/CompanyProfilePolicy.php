<?php

namespace App\Policies;

use App\Models\CompanyProfile;
use App\Models\User;

/** Konten katalog & company profile hanya dikelola Marketing (PBI 24). */
class CompanyProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isMarketing();
    }

    public function create(User $user): bool
    {
        return $user->isMarketing();
    }

    public function update(User $user, CompanyProfile $model): bool
    {
        return $user->isMarketing();
    }

    public function delete(User $user, CompanyProfile $model): bool
    {
        return $user->isMarketing();
    }
}
