<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\Activity;
use App\ActivityType;
use App\Models\User;
use App\Mail\OrderPaid;
use App\Mail\BoardNewOrder;
use App\PaymentStatus;
use App\UserType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Mollie\Laravel\Facades\Mollie;
use Throwable;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::query()
            ->visibleTo((bool) auth()->user()?->isAdmin())
            ->ofType('product')
            ->orderBy('name')
            ->get();

        return view('shop.index', [
            'products' => $products,
        ]);
    }

    /**
     * Show activity-focused page with activity products only.
     */
    public function activities()
    {
        $products = Product::query()
            ->visibleTo((bool) auth()->user()?->isAdmin())
            ->ofType('activity')
            ->orderBy('name')
            ->get();

        return view('shop.activities', [
            'products' => $products,
        ]);
    }

    /**
     * Show towel-focused page with quick order links.
     */
    public function towels()
    {
        return redirect()->route('product.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $defaultType = request()->query('type');

        if (!in_array($defaultType, ['product', 'activity'], true)) {
            $defaultType = 'product';
        }

        $defaultName = request()->query('name');
        if (!is_string($defaultName) || trim($defaultName) === '') {
            $defaultName = null;
        }

        return view('shop.create', [
            'defaultType' => $defaultType,
            'defaultName' => $defaultName,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $product = Product::create($this->productData($request));

        if (($product->product_type ?? 'product') === 'activity') {
            return redirect()->route('product.activities')->with('success', 'Activiteit succesvol aangemaakt.');
        }

        return redirect()->route('product.index')->with('success', 'Product succesvol aangemaakt.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        if ($product->hidden && !auth()->user()?->isAdmin()) {
            abort(404);
        }

        if ($product->usesNormalRegistration()) {
            return redirect()->route('activity.show', $product->activity_id);
        }

        return view('shop.read', [
            'product' => $product,
            'memberPrice' => (float) $product->price,
            'guestPrice' => $product->displayGuestPrice(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        return view('shop.update', [
            'product' => $product,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $this->productData($request);
        if ($product->activity_id && ($data['product_type'] !== 'activity'
            || (float) ($data['guest_price'] ?? $data['price']) !== (float) $data['price'])) {
            return back()->withErrors(['product_type' => 'Een gekoppelde activiteit blijft een activiteit en gebruikt dezelfde prijs voor leden en introducees.'])->withInput();
        }

        if ($product->activity_id && (empty($data['activity_starts_at']) || empty($data['pickupLocation']))) {
            return back()->withErrors(['activity_starts_at' => 'Een gekoppelde activiteit houdt een startdatum en locatie nodig.'])->withInput();
        }

        DB::transaction(function () use ($product, $data) {
            $product->update($data);
            $product->syncActivityDetails();
        });

        return redirect()->route('product.show', $product)->with('success', 'Product succesvol bijgewerkt.');
    }

    private function productData(ProductRequest $request): array
    {
        $validated = $request->validated();
        $validated['description'] = decodeEditorData($validated['description']);

        if ($request->hasFile('imagePath')) {
            $validated['imagePath'] = $request->file('imagePath')->store('products', 'public');
        }

        $validated['allow_guest_checkout'] = (bool) ($validated['allow_guest_checkout'] ?? false);
        $validated['hidden'] = (bool) ($validated['hidden'] ?? false);
        if ($validated['product_type'] === 'product') {
            $validated = array_merge($validated, array_fill_keys([
                'max_participants', 'registration_starts_at', 'registration_ends_at',
                'activity_starts_at', 'activity_ends_at',
            ], null));
        }

        return $validated;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        if ($product->orders()->exists() || $product->activity?->applications()->exists()) {
            $product->update(['hidden' => true]);

            return redirect()->route('product.index')->with(
                'error',
                'Dit item is gekoppeld aan bestaande bestellingen en is daarom verborgen in plaats van verwijderd.'
            );
        }

        DB::transaction(function () use ($product) {
            $product->activity?->delete();
            $product->delete();
        });

        return redirect()->route('product.index')->with('success', 'Product verwijderd.');
    }

    public function copy(Product $product)
    {
        $copy = $product->replicate(['activity_id', 'normal_registration']);
        $copy->name = $product->name . ' (kopie)';
        $copy->hidden = true;
        $copy->save();

        return redirect()->route('product.edit', $copy)->with('success', 'Product gekopieerd.');
    }

    public function convertToActivity(Product $product)
    {
        if (!$product->isActivityProduct()) {
            abort(404);
        }

        try {
            $result = DB::transaction(function () use ($product) {
            $product = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            if ($product->activity_id) {
                $product->update(['normal_registration' => true]);

                return $product->activity;
            }
            if ($product->hidden) {
                return 'Publiceer de activiteit voordat je deze omzet.';
            }
            if (!$product->activity_starts_at || !$product->pickupLocation) {
                return 'Vul eerst een startdatum en locatie in bij Bewerken.';
            }
            if ($product->displayGuestPrice() !== (float) $product->price) {
                return 'Het normale activiteitenproces gebruikt dezelfde prijs voor leden en introducees. Maak de prijzen eerst gelijk als je wilt omzetten.';
            }
            $capacity = $product->availableQuantity();
            $reserved = (int) $product->orders()->whereHas('payment', fn ($query) => $query->whereIn('status', [
                PaymentStatus::open, PaymentStatus::pending, PaymentStatus::authorized, PaymentStatus::paid,
            ]))->sum('order_products.quantity');
            $totalCapacity = $capacity === null ? null : $capacity + $reserved;
            if ($totalCapacity !== null && $totalCapacity <= 0) {
                return 'Deze activiteit heeft geen beschikbare plaatsen. Pas eerst de voorraad of het deelnemersmaximum aan.';
            }

            $start = $product->activity_starts_at;
            $end = ($product->activity_ends_at ?? $start)->copy()->endOfDay();
            $activity = Activity::create([
                'title' => $product->name,
                'description' => $product->descriptionForEditor(),
                'imagePath' => $product->imagePath,
                'location' => $product->pickupLocation,
                'organizer' => auth()->user()->name,
                'price' => $product->price,
                'maxParticipants' => $totalCapacity,
                'maxGuests' => 1,
                'start' => $start,
                'end' => $end,
                'registrationStart' => $product->registration_starts_at ?? now()->startOfDay(),
                'registrationEnd' => $product->registration_ends_at ?? $start,
                'cancellationEnd' => $product->registration_ends_at ?? $start,
                'type' => $start->isSameDay($end) ? ActivityType::OneDay : ActivityType::MultiDay,
            ]);
            $product->update([
                'activity_id' => $activity->id,
                'normal_registration' => true,
                'max_participants' => $totalCapacity,
                'stock' => null,
            ]);
            foreach ($product->orders()->with('products', 'payment', 'user')->get() as $order) {
                $product->registerOrder($order);
            }

            return $activity;
            });
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return $result instanceof Activity
            ? redirect()->route('activity.show', $result)->with('success', 'Het normale aanmeldproces is ingeschakeld. Je kunt altijd terug naar het nieuwe aanmeldproces.')
            : back()->with('error', $result);
    }

    public function useShopRegistration(Product $product)
    {
        abort_unless($product->isActivityProduct() && $product->activity_id, 404);
        DB::transaction(function () use ($product) {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $product->update(['normal_registration' => false]);
        });

        return redirect()->route('product.show', $product)
            ->with('success', 'Het nieuwe aanmeldproces is ingeschakeld. Deelnemers en betalingen zijn behouden.');
    }

    public function purchase(Request $request, Product $product)
    {
        if ($product->hidden) {
            abort(404);
        }

        if ($product->usesNormalRegistration()) {
            return redirect()->route('activity.show', $product->activity_id);
        }

        if ($product->requiresActivityForm()) {
            return redirect()->route('product.show', $product)
                ->with('error', 'Vul het volledige aanmeldformulier in voor deze activiteit.');
        }

        if ($product->isActivityProduct()) {
            if ($product->registration_starts_at && now()->startOfDay()->lt($product->registration_starts_at)) {
                return back()->with('error', 'De aanmeldperiode voor deze activiteit is nog niet begonnen.');
            }
            if ($product->registration_ends_at && now()->startOfDay()->gt($product->registration_ends_at)) {
                return back()->with('error', 'De inschrijfdeadline voor deze activiteit is verstreken.');
            }
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:50',
            'firstName' => 'nullable|string|max:255',
            'lastName' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
        ]);

        $quantity = (int) $validated['quantity'];

        $isGuestCheckout = !auth()->check();

        if ($isGuestCheckout) {
            if (!$product->allow_guest_checkout) {
                return back()->with('error', 'Alleen leden kunnen dit item bestellen. Log eerst in.');
            }
            $request->validate([
                'firstName' => 'required|string|max:255',
                'lastName' => 'required|string|max:255',
                'email' => 'required|email|max:255',
            ]);

            $customer = User::query()->firstOrCreate(
                ['email' => mb_strtolower(trim((string) $validated['email']))],
                [
                    'firstName' => $validated['firstName'],
                    'lastName' => $validated['lastName'],
                    'phone' => $validated['phone'] ?? null,
                    'type' => UserType::Inhuur,
                    'password' => null,
                ]
            );

        } else {
            $customer = auth()->user();
        }

        try {
            $payment = DB::transaction(function () use ($product, $quantity, $customer, $isGuestCheckout) {
                // Lock the product row so stock/capacity checks are safe under concurrent purchases.
                $lockedProduct = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
                if ($lockedProduct->hidden || ($isGuestCheckout && !$lockedProduct->allow_guest_checkout)) {
                    throw new \RuntimeException('Dit item kan niet meer worden besteld.');
                }
                if ($lockedProduct->usesNormalRegistration() || $lockedProduct->requiresActivityForm()) {
                    throw new \RuntimeException('Het aanmeldproces is gewijzigd. Open de activiteit opnieuw om je aan te melden.');
                }
                if ($lockedProduct->activity_id) {
                    $activity = Activity::whereKey($lockedProduct->activity_id)->lockForUpdate()->firstOrFail();
                    $lockedProduct->setRelation('activity', $activity);
                    if ($activity->isCancelled()) {
                        throw new \RuntimeException('Deze activiteit is geannuleerd.');
                    }
                    if ($activity->applications()->where('user_id', $customer->id)->where('status', '!=', \App\ApplicationStatus::Cancelled)->exists()) {
                        throw new \RuntimeException('Je staat al op de deelnemerslijst voor deze activiteit.');
                    }
                }
                $unitPrice = $isGuestCheckout ? $lockedProduct->displayGuestPrice() : (float) $lockedProduct->price;
                $totalPrice = $unitPrice * $quantity;
                if ($totalPrice < 0) {
                    throw new \RuntimeException('Het totaalbedrag mag niet negatief zijn.');
                }

                $available = $lockedProduct->availableQuantity();
                if ($available !== null && $quantity > $available) {
                    throw new \RuntimeException($lockedProduct->isActivityProduct()
                        ? 'Er zijn niet genoeg plekken meer beschikbaar voor deze activiteit.'
                        : 'Niet genoeg voorraad beschikbaar.');
                }

                $order = Order::create([
                    'user_id' => $customer->id,
                    'is_guest_checkout' => $isGuestCheckout,
                ]);

                $order->products()->attach($lockedProduct->id, [
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ]);

                $payment = $totalPrice == 0 ? Payment::create([
                    'mollieId' => 'free-' . $order->id,
                    'description' => "Bestelling #{$order->id} - {$lockedProduct->name}",
                    'status' => PaymentStatus::paid,
                    'price' => 0,
                ]) : Payment::generatePayment(
                    $totalPrice,
                    "Bestelling #{$order->id} - {$lockedProduct->name}",
                    null,
                    [
                        'orderId' => $order->id,
                        'newMember' => false,
                    ]
                );

                $order->payment_id = $payment->id;
                $order->save();
                $lockedProduct->registerOrder($order);

                if ($lockedProduct->stock !== null) {
                    $lockedProduct->decrement('stock', $quantity);
                }

                return $payment;
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('[ProductController] Onverwachte fout bij aanmaken betaling', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Betalen is nu tijdelijk niet beschikbaar. Probeer het over een paar minuten opnieuw.');
        }

        if ((float) $payment->price === 0.0) {
            $payment->paidAt = now();
            $payment->save();
            try {
                $order = $payment->orders;
                Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(new OrderPaid($order));
                Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))
                    ->send(new BoardNewOrder($customer, $order));
            } catch (Throwable $e) {
                report($e);
            }

            return redirect()->route('product.show', $product)->with('success', $product->isActivityProduct()
                ? 'Je aanmelding is bevestigd.' : 'Je bestelling is bevestigd.');
        }

        return redirect(Mollie::api()->payments->get($payment->mollieId)->_links->checkout->href, 303);
    }
}
