<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Models\Content;
use App\Models\User;
use App\UserNotifications;
use App\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user = null)
    {
        // If no user is provided, use the authenticated user
        if ($user === null) {
            $user = auth()->user();
        }

        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        // Users can update their own notifications, admins can do this for other users too.
        if (auth()->user()->is($user) || auth()->user()->isAdmin()) {
            // Reset the notifications
            $user->notifications = 0;

            // Get the checked notifications
            $submittedNotifications = $request->input('notifications', []);

            // Loop through all notification flags and set only the submitted ones.
            foreach (UserNotifications::cases() as $case) {
                if (array_key_exists($case->name, $submittedNotifications)) {
                    $user->setNotification($case);
                }
            }

            // New activity announcements are mandatory for all users.
            $user->setNotification(UserNotifications::NEW_ACTIVITY);
        }

        $user->firstName = $request->input('firstName', $user->firstName);
        $user->lastName = $request->input('lastName', $user->lastName);
        $phone = $request->input('phone', $user->phone);
        $user->phone = $phone === '' ? null : $phone;
        $user->email = $request->input('email', $user->email);

        // Only admins can change role/admin state, except for protected system users.
        if (auth()->user()->isAdmin() && !$user->isType(UserType::System)) {
            // If the is_admin checkbox is checked, set the user as admin
            $user->is_admin = $request->has('is_admin');

            // If the type is set in the request and is a valid type, set the user type
            if ($request->has('type') && in_array($request->input('type'), UserType::toArray())) {
                $user->type = UserType::from($request->input('type'));
            }
        }

        // Save the changed fields
        $user->save();

        return redirect()->back()->with(['success' => 'Gebruikersprofiel is succesvol geüpdatet']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }

    /**
     * Show the form for canceling the subscription.
     */
    public function cancelSubscription(User $user = null)
    {
        // If no user is provided, use the authenticated user
        if ($user === null) {
            $user = auth()->user();
        }

        // Choose different page text for employees versus normal members.
        if ($user->isType(UserType::Medewerker)) {
            $content = Content::where('name', 'afmelden-zijpalm-medewerker')->first();
        } else {
            $content = Content::where('name', 'afmelden-zijpalm')->first();
        }

        // Build the right cancel route depending on who is doing the action.
        if (auth()->user()->is($user)) {
            $route = route('settings.processCancel');
        } else {
            $route = route('user.processCancel', $user);
        }

        return view('users.cancel', compact('user', 'content', 'route'));
    }

    /**
     * Process the subscription cancellation.
     */
    public function processCancelSubscription(Request $request, User $user = null)
    {
        // If no user is provided, use the authenticated user
        if ($user === null) {
            $user = auth()->user();
        }

        // Employees and system users must use HRM for cancellation.
        if ($user->isType(UserType::Medewerker) || $user->isType(UserType::System)) {
            return redirect()->back()->withErrors('Je kan je account niet afmelden op deze manier. Meld je af via MijnHRM');
        }

        // Soft-delete the user by setting deleted_at.
        $user->deleted_at = now();
        $user->save();

        // If users cancel themselves, log them out immediately.
        if(auth()->user()->is($user)) {
            // Log the user out
            auth()->logout();
        }
        // Redirect to the home page with a success message
        return redirect()->route('home')->with('success', 'Het account is succesvol afgemeld');
    }

    /**
     * Send a password reset link to the selected user (admin only).
     */
    public function sendPasswordResetLink(User $user)
    {
        $admin = auth()->user();

        if (!$admin || !$admin->isAdmin()) {
            abort(403);
        }

        if (empty($user->email)) {
            Log::warning('[UserController] Password reset link not sent: user has no email', [
                'admin_id' => $admin->id,
                'target_user_id' => $user->id,
            ]);

            return redirect()->back()->withErrors('Deze gebruiker heeft geen e-mailadres.');
        }

        $status = Password::sendResetLink([
            'email' => $user->email,
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            Log::info('[UserController] Password reset link sent by admin', [
                'admin_id' => $admin->id,
                'target_user_id' => $user->id,
                'target_user_email' => $user->email,
            ]);

            return redirect()->back()->with('success', 'Resetmail is verstuurd naar de gebruiker.');
        }

        Log::warning('[UserController] Password reset link failed', [
            'admin_id' => $admin->id,
            'target_user_id' => $user->id,
            'target_user_email' => $user->email,
            'status' => $status,
        ]);

        return redirect()->back()->withErrors(__('Het versturen van de resetmail is mislukt. Probeer het opnieuw.'));
    }
}
