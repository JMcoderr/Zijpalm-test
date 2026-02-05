<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJoinRequest;
use App\Mail\BoardNewMembers;
use App\Mail\NewMember;
use App\Models\Content;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\UserType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Mollie\Laravel\Facades\Mollie;

class InformationController extends Controller
{
    public function about()
    {
        // Get the content for the about page
        $banner = Content::where('name', 'info-banner')->first();
        $about = Content::where('name', 'info-over')->first();
        $specialLeave = Content::where('name', 'info-bijzonder-verlof')->first();
        $privacy = Content::where('name', '=', 'privacy')->first();
        $rules = Content::where('name', '=', 'huishoudelijk-reglement')->first();
        $bylaws = Content::where('name', '=', 'statuten')->first();

        // Get the board members
        $boardMembers = Content::where('type', 'bestuurslid')->get()->sortByDesc('text');

        // Sort the board members by order
        // foreach ($boardMembers as $member) {
        //     $member->order = match ($member->text) {
        //         'Voorzitter' => 'order-1',
        //         'Penningmeester' => 'order-2',
        //         'Secretaris' => 'order-3',
        //         default => 'order-4',
        //     };
        // }

        return view('information.about', compact(
            'banner',
            'about',
            'specialLeave',
            'boardMembers',
            'privacy',
            'rules',
            'bylaws',
        ));
    }

    public function charity()
    {
        $banner = Content::where('name', 'lief-en-leed-banner')->first();
        $info = Content::where('name', 'lief-en-leed-info')->first();
        $contributions = Content::where('name', 'lief-en-leed-bijdragen')->first();
        $participants = Content::where('name', 'lief-en-leed-deelnemers')->first();
        $contact = Content::where('name', 'lief-en-leed-contact')->first();

        return view('information.charity', compact(
            'banner',
            'info',
            'contributions',
            'participants',
            'contact',
        ));
    }

    public function contact()
    {
        return view('information.contact');
    }

    public function join()
    {
        $banner = Content::where('name', 'lid-worden-banner')->first();
        $info = Content::where('name', 'lid-worden-info')->first();

        return view('information.join', compact('banner', 'info'));
    }

    public function joinForm()
    {
        // Get the content for the join form
        $content = Content::where('name', 'lid-worden-aanmelden')->first();
        $privacy = Content::where('name', 'privacy')->first();
        $terms = Content::where('name', 'huishoudelijk-reglement')->first();
        $monthlyPrice = Product::where('name', 'Contributie')->first()->price;
        $monthlyPrice = floatval($monthlyPrice);

        return view('information.joinForm', compact('content', 'privacy', 'terms', 'monthlyPrice'));
    }

    public function processJoinForm(StoreJoinRequest $request)
    {
        // Get the monthly price from the database
        $contribution = Product::where('name', 'Contributie')->first();

        // Check if the user is a member or a stagiair
        if(isset($request->endDate) && $request->type == UserType::Stagiair->value) {
            // Parse the end date from the request to a Carbon date
            $endDate = Carbon::parse($request->endDate)->startOfDay();

            // Calculate the months until the end date
            $months = (int)now()->diffInMonths($endDate);
            // Round the months to the nearest integer
//            $months = (int)round($months);
        } else {
            $endDate = null;
            // Calculate the remaining months of the year
            $months = 12 - date('n');
        }

        // Calculate the price for the amount of months
        $price = $months * $contribution->price;
        // Format the price to 2 decimal places
        $price = (string)number_format($price, 2, '.');

        // Create the user
        $user = User::create([
            'firstName' => $request->firstname,
            'lastName' => $request->lastname,
            'email' => $request->email,
            'phone' => $request->phone,
            'type' => $request->type,
            'contribution' => $price,
            'accepted_terms_at' => now(),
            'accepted_privacy_at' => now(),
            'deleted_at' => $endDate,
        ]);



        if ($price > 0) {
            // Create the order
            $order = Order::createWithProducts(
                [
                    [
                        'productId' => $contribution->id,
                        'quantity' => $months,
                    ]
                ],
                // Voorbeeld contributie
                // Contributie voornaam achternaam 2025
                'Contributie ' . $user->firstName . ' ' . $user->lastName . ' ' . now()->format('Y'),
                $user,
                // The user is a new member, because they just signed up
                true
            );
            // Get the payment from Mollie
            $mollie = Mollie::api()->payments->get($order->payment->mollieId);

            // Redirect to the payment page
            return redirect($mollie->_links->checkout->href, 303);
        } else {
            // Send an email to the board with the new member
            // Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(new BoardNewMembers(User::find(1), new Collection([$user])));
            Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(new NewMember($user));
            // Send an email to the board with the new member
            Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(new BoardNewMembers(User::find(1), new Collection([$user])));

            return redirect()->route('home')->with('success', 'Uw bent succesvol geregistreerd.');
        }
    }
}
