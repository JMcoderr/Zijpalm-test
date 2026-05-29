<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Http\Controllers;

use App\Models\MailSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MailSettingController extends Controller
{
    /**
     * Store or update mail settings for a named form/modal.
     * English comment: This endpoint accepts `name`, `batch_size` and `delay` and upserts a record.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'batch_size' => 'nullable|integer|min:1',
            'delay' => 'nullable|integer|min:0',
        ]);

        try {
            MailSetting::updateOrCreate(
                ['name' => $data['name']],
                ['batch_size' => $data['batch_size'] ?? null, 'delay' => $data['delay'] ?? null]
            );

            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            Log::error('[MailSettingController] store failed', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error'], 500);
        }
    }
}
