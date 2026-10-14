<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\User;
use App\UserType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $product->update($this->productData($request));

        return redirect()->route('product.show', $product)->with('success', 'Product succesvol bijgewerkt.');
    }

    private function productData(ProductRequest $request): array
    {
        $validated = $request->validated();

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
        if ($product->orders()->exists()) {
            $product->update(['hidden' => true]);

            return redirect()->route('product.index')->with(
                'error',
                'Dit item is gekoppeld aan bestaande bestellingen en is daarom verborgen in plaats van verwijderd.'
            );
        }

        $product->delete();

        return redirect()->route('product.index')->with('success', 'Product verwijderd.');
    }

    public function copy(Product $product)
    {
        $copy = $product->replicate();
        $copy->name = $product->name . ' (kopie)';
        $copy->hidden = true;
        $copy->save();

        return redirect()->route('product.edit', $copy)->with('success', 'Product gekopieerd.');
    }

    public function purchase(Request $request, Product $product)
    {
        if ($product->hidden) {
            abort(404);
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
                $unitPrice = $isGuestCheckout ? $lockedProduct->displayGuestPrice() : (float) $lockedProduct->price;
                $totalPrice = $unitPrice * $quantity;
                if ($totalPrice <= 0) {
                    throw new \RuntimeException('Het totaalbedrag moet groter zijn dan 0.');
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

                $payment = Payment::generatePayment(
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

        return redirect(Mollie::api()->payments->get($payment->mollieId)->_links->checkout->href, 303);
    }
}
