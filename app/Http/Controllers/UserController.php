<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Models\Content;
use App\Models\User;
use App\UserNotifications;
use App\UserType;
use Illuminate\Http\Request;

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
        // A user can only set their own notifications
        if (auth()->user()->is($user)) {
            // Reset the notifications
            $user->notifications = 0;

            // Get the checked notifications
            $submittedNotifications = $request->input('notifications', []);

            // Itterate over all the notifications and if it is checked in the frontend, set it
            foreach (UserNotifications::cases() as $case) {
                if (array_key_exists($case->name, $submittedNotifications)) {
                    $user->setNotification($case);
                }
            }
        }

        $user->firstName = $request->input('firstName', $user->firstName);
        $user->lastName = $request->input('lastName', $user->lastName);
        $user->phone = $request->input('phone', $user->phone);
        $user->email = $request->input('email', $user->email);

        // Actions only an admin can do
        // If the user is a system user, we don't want to change is_admin or type
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

        if ($user->isType(UserType::Medewerker)) {
            $content = Content::where('name', 'afmelden-zijpalm-medewerker')->first();
        } else {
            $content = Content::where('name', 'afmelden-zijpalm')->first();
        }

        // Determine the route for processing the cancellation
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

        if ($user->isType(UserType::Medewerker) || $user->isType(UserType::System)) {
            return redirect()->back()->withErrors('Je kan je account niet afmelden op deze manier. Meld je af via MijnHRM');
        }

        // Sets the user as deleted
        // This is a soft delete, so the user will not be removed from the database
        // but will be marked as deleted
        $user->deleted_at = now();
        $user->save();

        // If the authenticated user is the same as the user being deleted
        // we log them out to prevent any further actions
        // If an admin is deleting a user, they will not be logged out
        if(auth()->user()->is($user)) {
            // Log the user out
            auth()->logout();
        }
        // Redirect to the home page with a success message
        return redirect()->route('home')->with('success', 'Het account is succesvol afgemeld');
    }
}
