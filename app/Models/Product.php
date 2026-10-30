<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\PaymentStatus;
use App\ApplicationStatus;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'product_type',
        'description',
        'price',
        'guest_price',
        'stock',
        'max_participants',
        'imagePath',
        'pickupLocation',
        'hidden',
        'allow_guest_checkout',
        'registration_starts_at',
        'registration_ends_at',
        'activity_starts_at',
        'activity_ends_at',
        'activity_id',
        'normal_registration',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'guest_price' => 'decimal:2',
        'hidden' => 'boolean',
        'normal_registration' => 'boolean',
        'allow_guest_checkout' => 'boolean',
        'registration_starts_at' => 'date',
        'registration_ends_at' => 'date',
        'activity_starts_at' => 'datetime',
        'activity_ends_at' => 'datetime',
    ];

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_products')->withPivot('quantity', 'unit_price');
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function usesNormalRegistration(): bool
    {
        return $this->isActivityProduct() && $this->activity_id && $this->normal_registration;
    }

    public function requiresActivityForm(): bool
    {
        return $this->activity_id && ($this->activity->questions()->exists()
            || $this->activity->free_organizer_count > 0
            || $this->activity->personal_confirmation_enabled);
    }

    public function registerOrder(Order $order): ?Application
    {
        if (!$this->isActivityProduct() || !$this->activity_id) {
            return null;
        }

        $existing = Application::where('shop_order_id', $order->id)->first();
        if ($existing) {
            return $existing;
        }

        $order->loadMissing('products', 'payment', 'user');
        if ($order->products->count() !== 1 || !$order->payment || $order->payment->application_id) {
            throw new \RuntimeException('Een bestaande bestelling bevat meerdere artikelen of heeft geen betaling. Controleer deze eerst bij Bestellingen voordat je het aanmeldproces wisselt.');
        }

        $item = $order->products->sole();
        $application = Application::create([
            'activity_id' => $this->activity_id,
            'shop_order_id' => $order->id,
            'shop_unit_price' => $item->pivot->unit_price ?? ((float) $order->payment->price / max(1, (int) $item->pivot->quantity)),
            'user_id' => $order->user_id,
            'email' => $order->user?->email ?? '',
            'phone' => $order->user?->phone ?? '',
            'participants' => (int) $item->pivot->quantity,
            'comment' => 'Shopbestelling #' . $order->id . ((int) $item->pivot->quantity > 1 ? ' (namen extra deelnemers niet opgegeven)' : ''),
            'status' => match ($order->payment->status) {
                PaymentStatus::paid => ApplicationStatus::Active,
                PaymentStatus::open, PaymentStatus::pending, PaymentStatus::authorized => ApplicationStatus::Pending,
                default => ApplicationStatus::Cancelled,
            },
        ]);
        $order->payment->application()->associate($application);
        $application->created_at = $order->payment->created_at;
        $application->save();
        $order->payment->save();

        return $application;
    }

    public function syncActivityDetails(): void
    {
        if (!$this->activity_id) {
            return;
        }

        $this->activity->update([
            'title' => $this->name,
            'description' => $this->descriptionForEditor(),
            'imagePath' => $this->imagePath,
            'location' => $this->pickupLocation,
            'price' => $this->price,
            'maxParticipants' => $this->max_participants,
            'start' => $this->activity_starts_at,
            'end' => ($this->activity_ends_at ?? $this->activity_starts_at)?->copy()->endOfDay(),
            'registrationStart' => $this->registration_starts_at ?? $this->activity->registrationStart,
            'registrationEnd' => $this->registration_ends_at ?? $this->activity_starts_at,
            'type' => in_array($this->activity->type, [\App\ActivityType::OneDay, \App\ActivityType::MultiDay], true)
                ? ($this->activity_starts_at?->isSameDay($this->activity_ends_at ?? $this->activity_starts_at)
                    ? \App\ActivityType::OneDay : \App\ActivityType::MultiDay)
                : $this->activity->type,
        ]);
    }

    public function isActivityProduct(): bool
    {
        return $this->product_type === 'activity';
    }

    public function displayGuestPrice(): float
    {
        return (float) ($this->guest_price ?? $this->price);
    }

    public function descriptionForEditor(): string
    {
        $data = $this->editorDescriptionData();
        if ($data !== null) {
            return json_encode($data);
        }

        return json_encode([
            'time' => now()->getTimestampMs(),
            'version' => '2.31.4',
            'blocks' => [['type' => 'paragraph', 'data' => ['text' => nl2br(e($this->decodedDescription()))]]],
        ]);
    }

    public function descriptionHtml(): string
    {
        $description = $this->activity?->description ?? $this->description;
        $data = $this->editorDescriptionData($description);
        if ($data === null) {
            return '<p>' . nl2br(e($this->decodedDescription($description))) . '</p>';
        }

        try {
            $data += ['time' => now()->getTimestampMs(), 'version' => '2.31.4'];
            $activity = new Activity(['description' => json_encode($data)]);
            $html = $activity->descriptionHTML;
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('Cache.SerializerPath', storage_path('framework/cache'));

            return (new \HTMLPurifier($config))->purify($html);
        } catch (\Throwable $exception) {
            report($exception);

            return '<p>' . nl2br(e($this->decodedDescription($description))) . '</p>';
        }
    }

    private function editorDescriptionData(?string $description = null): ?array
    {
        $raw = (string) ($description ?? $this->description);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $data = json_decode($raw, true);
            if (is_array($data) && isset($data['blocks'])) {
                return $data;
            }

            $decoded = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded === $raw) {
                break;
            }
            $raw = $decoded;
        }

        return null;
    }

    private function decodedDescription(?string $description = null): string
    {
        $description = (string) ($description ?? $this->description);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $decoded = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded === $description) {
                break;
            }
            $description = $decoded;
        }

        return $description;
    }

    public function scopeVisibleTo(Builder $query, bool $admin): Builder
    {
        return $admin ? $query : $query->where('hidden', false);
    }

    public function scopeForDigest(Builder $query): Builder
    {
        return $query->visibleTo(false)
            ->where(function (Builder $query) {
                $query->whereNull('activity_ends_at')->orWhere('activity_ends_at', '>=', now());
            })
            ->where(function (Builder $query) {
                $query->whereNotNull('activity_ends_at')
                    ->orWhereNull('activity_starts_at')
                    ->orWhereDate('activity_starts_at', '>=', today());
            })
            ->whereDoesntHave('activity', fn (Builder $query) => $query
                ->whereIn('type', [\App\ActivityType::Cancelled, \App\ActivityType::Archived]));
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where(function (Builder $query) use ($type) {
            $query->where('product_type', $type);

            if ($type === 'product') {
                $query->orWhereNull('product_type');
            }
        });
    }

    public function availableQuantity(): ?int
    {
        if ($this->isActivityProduct() && $this->activity_id) {
            return $this->activity?->participants->capacity;
        }

        $stock = $this->stock === null ? null : (int) $this->stock;

        if (!$this->isActivityProduct() || !$this->max_participants) {
            return $stock;
        }

        $reserved = (int) $this->orders()->whereHas('payment', fn (Builder $query) => $query
            ->whereIn('status', [PaymentStatus::open, PaymentStatus::pending, PaymentStatus::authorized, PaymentStatus::paid]))
            ->sum('order_products.quantity');
        $capacity = max(0, (int) $this->max_participants - $reserved);

        return $stock === null ? $capacity : min($stock, $capacity);
    }
}
