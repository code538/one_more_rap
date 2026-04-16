<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Api\BaseController;
use App\Models\Website\ShiprocketSetting;
use Illuminate\Http\Request;

class ShiprocketSettingController extends BaseController
{
    public function index()
    {
        $settings = ShiprocketSetting::latest()->get();
        return $this->success($settings, 'Shiprocket settings fetched successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'mode' => 'required|in:test,live',
            'status' => 'nullable|boolean',
            'base_url' => 'nullable|string',

            'test_email' => 'nullable|email',
            'test_password' => 'nullable|string',
            'live_email' => 'nullable|email',
            'live_password' => 'nullable|string',

            'channel_id' => 'nullable|string',
            'pickup_location' => 'nullable|string',
            'company_name' => 'nullable|string',

            'default_weight' => 'nullable|numeric|min:0.01',
            'default_length' => 'nullable|numeric|min:0.1',
            'default_breadth' => 'nullable|numeric|min:0.1',
            'default_height' => 'nullable|numeric|min:0.1',
            'token_cache_minutes' => 'nullable|integer|min:1|max:10080',
        ]);

        $setting = ShiprocketSetting::create([
            'mode' => $request->mode,
            'status' => (bool) ($request->status ?? 0),
            'base_url' => $request->base_url,

            'test_email' => $request->test_email,
            'test_password' => $request->test_password,
            'live_email' => $request->live_email,
            'live_password' => $request->live_password,

            'channel_id' => $request->channel_id,
            'pickup_location' => $request->pickup_location,
            'company_name' => $request->company_name,

            'default_weight' => $request->default_weight ?? 0.5,
            'default_length' => $request->default_length ?? 10,
            'default_breadth' => $request->default_breadth ?? 10,
            'default_height' => $request->default_height ?? 5,
            'token_cache_minutes' => $request->token_cache_minutes ?? 720,
        ]);

        // If setting is activated, disable other active rows
        if ($setting->status) {
            ShiprocketSetting::where('id', '!=', $setting->id)->where('status', 1)->update(['status' => 0]);
        }

        return $this->success($setting, 'Shiprocket setting saved successfully');
    }

    public function show($id)
    {
        $setting = ShiprocketSetting::find($id);
        if (!$setting) {
            return $this->error('Shiprocket setting not found', null, 404);
        }
        return $this->success($setting, 'Shiprocket setting fetched');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|boolean']);

        $setting = ShiprocketSetting::find($id);
        if (!$setting) {
            return $this->error('Shiprocket setting not found', null, 404);
        }

        $setting->status = (bool) $request->status;
        $setting->save();

        if ($setting->status) {
            ShiprocketSetting::where('id', '!=', $setting->id)->where('status', 1)->update(['status' => 0]);
        }

        return $this->success($setting, 'Shiprocket status updated');
    }
}

