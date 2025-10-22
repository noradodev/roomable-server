<?php

namespace App\Http\Controllers\API;

use App\Events\FooEvent;
use App\Events\MessageSent;
use App\Events\PaymentCreated;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponser;
use App\Models\LandlordPaymentMethod;
use App\Models\LandlordPaymentType;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(Request $request): ApiResponser
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->profile()->create([
                'phone' => null,
                'address' => null,
                'profile_image' => null
            ]);

            $user->assignRole('landlord');

            $this->createPaymentMethods($user);

            return $user;
        });
        $token = $user->createToken('auth_token')->accessToken;

        return ApiResponser::ok([
            'user' => $user->load('profile'),
            'token' => $token,
        ]);;
    }
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return ApiResponser::error('Invalid credentials', 401);
        }

        $token = $user->createToken('auth_token')->accessToken;

        return ApiResponser::ok([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->first(),
                'profile' => $user->profile,
            ],
            'token' => $token,
        ]);
    }
    public function updateProfile(Request $request): ApiResponser
{
    $user = $request->user();

    $validated = $request->validate([
        'name'           => 'required|string|max:255',
        'phone'          => 'nullable|string|max:20',
        'address'        => 'nullable|string|max:255',
        'profile_image'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    ]);

    DB::transaction(function () use ($validated, $user, $request) {

        if (isset($validated['name'])) {
            $user->update(['name' => $validated['name']]);
        }

        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');

            if ($user->profile->profile_image) {
                Storage::disk('public')->delete($user->profile->profile_image);
            }

            $path = $file->store('profile_images', 'public');
            $validated['profile_image'] = $path;
        }

        $user->profile()->update([
            'phone'         => $validated['phone'] ?? $user->profile->phone,
            'address'       => $validated['address'] ?? $user->profile->address,
            'profile_image' => $validated['profile_image'] ?? $user->profile->profile_image,
        ]);
    }); 

    return ApiResponser::ok([
        'user' => $user->load('profile')
    ]);
}

    public function me(Request $request)
    {

        $user = $request->user();

        $user->load('profile');
        $roles = $user->getRoleNames()->first();

        return ApiResponser::ok([
            'curr_user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $roles,
                'profile' => $user->profile,
            ],
        ]);
    }

    public function sendMessage(Request $request)
    {
        $payment = Payment::where('id', 'a02158d5-b36b-4b46-9ad7-9be6af04011b')->first();
        // dd($payment);
        $message = $request->input('message');
        // broadcast(new MessageSent($message)); 
        broadcast(new PaymentCreated($payment));
        return response()->json(['status' => 'Message Sent!']);
    }
    private function createPaymentMethods(User $user): void
    {
        $methodTypes = LandlordPaymentType::where('is_active', true)->get();

        if ($methodTypes->isEmpty()) {
            Log::info("No active LandlordPaymentType found; skipping payment method creation for user {$user->id}.");
            return;
        }

        DB::transaction(function () use ($methodTypes, $user) {
            foreach ($methodTypes as $type) {
                LandlordPaymentMethod::firstOrCreate(
                    [
                        'landlord_id' => $user->id,
                        'payment_type_id' => $type->id,
                    ],
                    [
                        'is_enabled' => (bool) $type->is_required,
                        'is_active' => true,
                    ]
                );
            }
        });

        Log::info('Created/ensured ' . $methodTypes->count() . ' payment methods for user ' . $user->id);
    }
}
