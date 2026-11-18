<?php

namespace App\Http\Controllers\Backoffice\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\UpdateCompanyProfileRequest;
use App\Models\CompanyProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyProfileController extends Controller
{
    public function edit(): View
    {
        $profile = CompanyProfile::current();
        $this->authorize('update', $profile);

        return view('backoffice.marketing.company-profile.edit', compact('profile'));
    }

    public function update(UpdateCompanyProfileRequest $request): RedirectResponse
    {
        $profile = CompanyProfile::current();
        $profile->fill($request->validated())->save();

        return redirect()->route('backoffice.marketing.company-profile.edit')
            ->with('success', 'Profil perusahaan berhasil diperbarui dan langsung tampil di website publik.');
    }
}
