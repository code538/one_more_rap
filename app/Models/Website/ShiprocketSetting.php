<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiprocketSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'mode',
        'status',
        'base_url',
        'test_email',
        'test_password',
        'live_email',
        'live_password',
        'channel_id',
        'pickup_location',
        'company_name',
        'default_weight',
        'default_length',
        'default_breadth',
        'default_height',
        'token_cache_minutes',
    ];

    protected $casts = [
        'status' => 'boolean',
        'default_weight' => 'float',
        'default_length' => 'float',
        'default_breadth' => 'float',
        'default_height' => 'float',
        'token_cache_minutes' => 'integer',
    ];
}

