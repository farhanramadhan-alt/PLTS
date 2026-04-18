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
        $query = self::query();

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $allData = $query->orderBy('created_at', 'asc')->get();

        // Sample data every 30 minutes
        if ($allData->isEmpty()) {
            return collect([]);
        }

        $sampledData = collect();
        $lastSampledTime = null;
        $intervalMinutes = 1; // Changed from 30 to 1 minute for testing

        foreach ($allData as $item) {
            if ($lastSampledTime === null) {
                // Always include the first item
                $sampledData->push($item);
                $lastSampledTime = $item->created_at;
            } else {
                // Include if at least 1 minute have passed since last sample
                if ($item->created_at->diffInMinutes($lastSampledTime) >= $intervalMinutes) {
                    $sampledData->push($item);
                    $lastSampledTime = $item->created_at;
                }
            }
        }

        return $sampledData;
    }
}
