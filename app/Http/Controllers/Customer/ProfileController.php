<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\MediaPathService;
use App\Services\MediaReferenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use App\Services\BandaraCreditService;

class ProfileController extends Controller
{
    public function __construct(
        protected MediaPathService $media,
        protected MediaReferenceService $mediaReferences,
    ) {
    }

    public function edit(Request $request)
    {
        return view('account.profile', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $this->throwIfPhpUploadFailed('avatar');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => [
                'required',
                'email',
                'max:191',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['required', 'string', 'max:50'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [], [
            'avatar' => 'profile photo',
        ]);

        $emailChanged = strcasecmp((string) $data['email'], (string) $user->email) !== 0;
        if ($emailChanged) {
            $data['email_verified_at'] = null;
        }

        $newAvatarPath = null;
        $oldAvatarPath = $user->avatar_path ?? null;

        if ($request->hasFile('avatar')) {
            $newAvatarPath = $this->media->storePublic(
                $request->file('avatar'),
                $this->media->avatarDirectory($user),
                'avatar'
            );
            $data['avatar_path'] = $newAvatarPath;
        }

        unset($data['avatar']);

        try {
            $user->update($data);
        } catch (\Throwable $e) {
            if ($newAvatarPath) {
                $this->media->deleteFromDisks($newAvatarPath, [$this->media->publicDisk()]);
            }

            throw $e;
        }

        if ($newAvatarPath && $oldAvatarPath) {
            $this->mediaReferences->deletePublicFileIfUnreferenced($oldAvatarPath);
        }

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();

            return back()->with('status', 'Profile updated. Please verify your new email address.');
        }

        return back()->with('status', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Current password is incorrect.'])
                ->with('password_section', true);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        $request->session()->regenerate();

        return back()->with([
            'status' => 'Password updated successfully.',
            'password_section' => true,
        ]);
    }

    protected function throwIfPhpUploadFailed(string $field): void
    {
        if (! isset($_FILES[$field])) {
            return;
        }

        $error = (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_OK);

        if (in_array($error, [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE], true)) {
            return;
        }

        throw ValidationException::withMessages([
            $field => $this->phpUploadErrorMessage($error),
        ]);
    }

    protected function phpUploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE =>
                'The profile photo exceeds PHP upload_max_filesize.',
            UPLOAD_ERR_FORM_SIZE =>
                'The profile photo exceeds the form upload limit.',
            UPLOAD_ERR_PARTIAL =>
                'The profile photo was only partially uploaded. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR =>
                'PHP is missing a temporary upload folder.',
            UPLOAD_ERR_CANT_WRITE =>
                'PHP could not write the uploaded profile photo to the temporary folder.',
            UPLOAD_ERR_EXTENSION =>
                'A PHP extension stopped the profile photo upload.',
            default =>
                'The profile photo could not be uploaded.',
        };
    }

    public function newsletter(\Illuminate\Http\Request $request)
    {
        return view('customer.account.newsletter', [
            'user' => $request->user(),
        ]);
    }

    public function rewards(Request $request, BandaraCreditService $bandaraCreditService)
    {
        if (($request->user()?->customer_type ?? 'b2c') === 'b2b') {
            return redirect()->route('dashboard.customer');
        }

        $snapshot = $bandaraCreditService->snapshotForUser($request->user()->id);

        $perAmount = (int) config('bandara_credit.earning.per_amount_spent', 100);
        $creditAmount = (int) config('bandara_credit.earning.credit_amount', 1);
        $redeemEnabled = (bool) config('bandara_credit.redeem_enabled', false);

        return view('customer.account.rewards', [
            ...$snapshot,
            'earnRateLabel' => "Earn {$creditAmount} Bandara Credit for every ₹{$perAmount} of eligible order value.",
            'redeemRuleLabel' => $redeemEnabled
                ? 'Redeem available Bandara Credit on eligible checkout orders.'
                : 'Redemption is not enabled yet. Your credits will continue accumulating safely.',
            'expiryLabel' => null,
        ]);
    }

    public function rewardTerms(Request $request, BandaraCreditService $bandaraCreditService)
    {
        if (($request->user()?->customer_type ?? 'b2c') === 'b2b') {
            return redirect()->route('dashboard.customer');
        }

        return view('customer.account.reward-terms', [
            'programEnabled' => (bool) config('bandara_credit.enabled'),
            'eligibleUser' => $bandaraCreditService->isEligibleUserForBandaraCredit($request->user()),
            'tiers' => $bandaraCreditService->tierDefinitions(),
            'redeemEnabled' => (bool) config('bandara_credit.redeem_enabled', false),
            'minimumRedeemPoints' => (int) config('bandara_credit.redemption.minimum_points', 1),
            'maxOrderPercent' => (float) config('bandara_credit.redemption.max_order_percent', config('bandara_credit.redemption.max_order_percentage', 20)),
            'minimumPayableAmount' => (float) config('bandara_credit.redemption.minimum_payable_amount', 1),
            'tierValidity' => (string) config('bandara_credit.tiers.validity', 'qualifying_year_plus_next_year'),
        ]);
    }
}
