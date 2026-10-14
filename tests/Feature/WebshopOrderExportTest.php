<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->products = collect([
        Product::factory()->create(['name' => '=1+1', 'product_type' => 'product']),
        Product::factory()->create(['name' => 'Dagje uit', 'product_type' => 'activity']),
    ]);
    $this->orders = collect();
    foreach ($this->products as $index => $product) {
        $customer = User::factory()->create(['phone' => '0612345678']);
        $payment = Payment::create([
            'mollieId' => 'tr_export_' . $index, 'status' => PaymentStatus::paid,
            'description' => 'Bestelling #' . $index, 'price' => 31,
        ]);
        $order = Order::create([
            'user_id' => $customer->id, 'payment_id' => $payment->id, 'is_guest_checkout' => (bool) $index,
        ]);
        $order->products()->attach($product, ['quantity' => 2, 'unit_price' => 15.5]);
        $this->orders->push($order);
    }
    $payment = Payment::create([
        'mollieId' => 'tr_membership', 'status' => PaymentStatus::paid,
        'description' => 'Contributie', 'price' => 99,
    ]);
    Order::create(['user_id' => $this->admin->id, 'payment_id' => $payment->id]);
});

it('filters the order overview and provides a download using the same filter', function () {
    $this->actingAs($this->admin)->get(route('admin.orders', [
        'type' => 'activity', 'product_id' => $this->products[1]->id,
    ]))->assertOk()
        ->assertViewHas('guestOrders', fn ($orders) => $orders->count() === 1)
        ->assertViewHas('memberOrders', fn ($orders) => $orders->isEmpty())
        ->assertSee('Download Excel')
        ->assertSee(route('admin.orders.download', ['type' => 'activity', 'product_id' => $this->products[1]->id]));
});

it('downloads actual Excel rows with historical prices, contact details and safe text cells', function (?string $type, int $expectedRows) {
    $response = $this->actingAs($this->admin)->get(route('admin.orders.download', array_filter(['type' => $type])))
        ->assertOk()->assertDownload();
    $path = tempnam(sys_get_temp_dir(), 'shop_export_test_');
    $spreadsheet = null;
    try {
        file_put_contents($path, $response->streamedContent());
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        expect($sheet->getHighestDataRow())->toBe($expectedRows + 1)
            ->and($sheet->getCell('H1')->getValue())->toBe('Item')
            ->and($sheet->getCell('K2')->getValue())->toEqual(15.5)
            ->and($sheet->getCell('L2')->getValue())->toEqual(31)
            ->and($sheet->getCell('F2')->getValue())->toBe('0612345678')
            ->and($sheet->getCell('F2')->getDataType())->toBe(DataType::TYPE_STRING);
        if ($type === 'product') {
            expect($sheet->getCell('H2')->getValue())->toBe('=1+1')
                ->and($sheet->getCell('H2')->getDataType())->toBe(DataType::TYPE_STRING);
        }
    } finally {
        $spreadsheet?->disconnectWorksheets();
        unlink($path);
    }
})->with([[null, 2], ['activity', 1], ['product', 1]]);

it('downloads only the selected product lines of mixed orders and retains the full order total', function () {
    $this->orders[0]->products()->attach($this->products[1], ['quantity' => 1, 'unit_price' => 12]);
    $this->orders[0]->payment->update(['price' => 43]);
    $response = $this->actingAs($this->admin)->get(route('admin.orders.download', [
        'product_id' => $this->products[0]->id,
    ]))->assertOk();
    $path = tempnam(sys_get_temp_dir(), 'shop_export_test_');
    $spreadsheet = null;
    try {
        file_put_contents($path, $response->streamedContent());
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        expect($sheet->getHighestDataRow())->toBe(2)
            ->and($sheet->getCell('H2')->getValue())->toBe('=1+1')
            ->and($sheet->getCell('L2')->getValue())->toEqual(31)
            ->and($sheet->getCell('M2')->getValue())->toEqual(43);
    } finally {
        $spreadsheet?->disconnectWorksheets();
        unlink($path);
    }
});

it('rejects invalid filters and prevents non-admin downloads', function () {
    $this->actingAs($this->admin)->get(route('admin.orders.download', ['type' => 'invalid']))
        ->assertSessionHasErrors('type');
    $this->get(route('admin.orders.download', ['product_id' => 99999]))
        ->assertSessionHasErrors('product_id');
    $this->actingAs(User::factory()->create())->get(route('admin.orders.download'))
        ->assertRedirect(route('home'));
});

it('creates a valid empty workbook for filters without matching orders', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.orders.download', [
        'type' => 'activity', 'product_id' => $this->products[0]->id,
    ]))->assertOk()->assertDownload();
    $path = tempnam(sys_get_temp_dir(), 'shop_export_test_');
    $spreadsheet = null;
    try {
        file_put_contents($path, $response->streamedContent());
        $spreadsheet = IOFactory::load($path);
        expect($spreadsheet->getActiveSheet()->getHighestDataRow())->toBe(1);
    } finally {
        $spreadsheet?->disconnectWorksheets();
        unlink($path);
    }
});
