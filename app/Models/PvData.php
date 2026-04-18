<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PvData extends Model
{
    use HasFactory;

    protected $table = 'pv_data';

    protected $fillable = [
        'voltage',
        'current',
        'power_output',
        'temperature',
        'lux',
        'voltage_change_percent',
        'current_change_percent',
        'lux_change_percent',
        'temperature_change_percent',
    ];

    protected $casts = [
        'voltage' => 'float',
        'current' => 'float',
        'power_output' => 'float',
        'temperature' => 'float',
        'lux' => 'float',
        'voltage_change_percent' => 'float',
        'current_change_percent' => 'float',
        'lux_change_percent' => 'float',
        'temperature_change_percent' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the latest PV data record
     */
    public static function getLatest()
    {
        return self::latest()->first();
    }

    /**
     * Check if the device is offline (no data received for 5 minutes)
     */
    public static function isOffline()
    {
        $latest = self::latest()->first();
        
        if (!$latest) {
            return true; // No data at all
        }

        // Check if the last update was more than 5 minutes ago
        return $latest->updated_at->diffInMinutes(now()) >= 5;
    }

    /**
     * Get the time since last update in human-readable format
     */
    public static function lastUpdateTime()
    {
        $latest = self::latest()->first();
        
        if (!$latest) {
            return 'No data';
        }

        return $latest->updated_at->diffForHumans();
    }

    /**
     * Get data within a date range for charts with 30-minute sampling
     */
    public static function getChartData($startDate = null, $endDate = null, $limit = 100)
    {
        $query = self::query()
            ->select(['id', 'voltage', 'current', 'temperature', 'lux', 'created_at']);

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        // Fetch data with limit + apply smart sampling in DB
        $allData = $query
            ->orderBy('created_at', 'asc')
            ->limit(500)
            ->get(['id', 'voltage', 'current', 'temperature', 'lux', 'created_at']);

        if ($allData->isEmpty()) {
            return collect([]);
        }

        $count = $allData->count();
        
        // Smart sampling: reduce to ~40-50 points for better chart performance
        if ($count > 50) {
            $sampleInterval = intdiv($count, 40);
            return $allData->values()->filter(function ($item, $index) use ($sampleInterval) {
                return $index % $sampleInterval === 0 || $index === $count - 1; // Always include last point
            });
        }

        return $allData;
    }
}
