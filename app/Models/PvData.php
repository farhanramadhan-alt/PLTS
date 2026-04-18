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
        $baseQuery = 'SELECT id, voltage, current, temperature, lux, created_at FROM pv_data';
        
        if ($startDate && $endDate) {
            $results = \DB::select(
                $baseQuery . ' WHERE created_at BETWEEN ? AND ? ORDER BY created_at ASC LIMIT 300',
                [$startDate, $endDate]
            );
        } else {
            $results = \DB::select($baseQuery . ' ORDER BY created_at ASC LIMIT 300');
        }
        
        if (empty($results)) {
            return collect([]);
        }

        $count = count($results);
        
        // Aggressive sampling: target 25-30 points
        if ($count > 30) {
            $sampleInterval = max(1, intdiv($count, 25));
            $sampled = [];
            foreach ($results as $index => $item) {
                if ($index % $sampleInterval === 0 || $index === $count - 1) {
                    $sampled[] = $item;
                }
            }
            return collect($sampled)->map(fn($row) => (object)$row);
        }

        return collect($results)->map(fn($row) => (object)$row);
    }
}
