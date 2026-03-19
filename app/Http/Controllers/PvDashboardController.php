<?php

namespace App\Http\Controllers;

use App\Models\PvData;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PvDashboardController extends Controller
{
    /**
     * Display the PV dashboard
     */
    public function index()
    {
        $latest = PvData::getLatest();
        $isOffline = PvData::isOffline();
        $lastUpdateTime = PvData::lastUpdateTime();

        return view('dashboard.pv', [
            'latest' => $latest,
            'isOffline' => $isOffline,
            'lastUpdateTime' => $lastUpdateTime,
        ]);
    }

    /**
     * Get chart data for AJAX requests
     */
    public function getChartData(Request $request)
    {
        $period = $request->query('period', '1H'); // 1H, 24H, 7D
        $parameter = $request->query('parameter', 'lux');

        $data = $this->getDataByPeriod($period);

        $labels = $data->map(fn ($item) => $item->created_at->format('H:i'));
        $values = match ($parameter) {
            'voltage' => $data->pluck('voltage'),
            'current' => $data->pluck('current'),
            'temperature' => $data->pluck('temperature'),
            'lux' => $data->pluck('lux'),
            'power' => $data->map(fn ($item) => $item->voltage * $item->current),
            default => $data->pluck('lux'),
        };

        return response()->json([
            'labels' => $labels->values(),
            'data' => $values->values(),
            'success' => true,
        ]);
    }

    /**
     * Get power output chart data
     */
    public function getPowerOutputData(Request $request)
    {
        $period = $request->query('period', '1H');

        $data = $this->getDataByPeriod($period);

        $labels = $data->map(fn ($item) => $item->created_at->format('H:i'));
        $voltageData = $data->pluck('voltage');
        $currentData = $data->pluck('current');

        return response()->json([
            'labels' => $labels->values(),
            'voltage' => $voltageData->values(),
            'current' => $currentData->values(),
            'success' => true,
        ]);
    }

    /**
     * Get environment data
     */
    public function getEnvironmentData(Request $request)
    {
        $period = $request->query('period', '24H');

        $data = $this->getDataByPeriod($period);

        $labels = $data->map(fn ($item) => $item->created_at->format('H:i'));
        $tempData = $data->pluck('temperature');
        $luxData = $data->pluck('lux');

        return response()->json([
            'labels' => $labels->values(),
            'temperature' => $tempData->values(),
            'lux' => $luxData->values(),
            'success' => true,
        ]);
    }

    /**
     * Store new PV data (for API/Sensor integration)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'voltage' => 'required|numeric',
            'current' => 'required|numeric',
            'temperature' => 'required|numeric',
            'lux' => 'required|numeric',
            'timestamp' => 'nullable|date_format:Y-m-d H:i:s',
        ]);

        $pvData = $this->persistData($validated);

        return response()->json([
            'success' => true,
            'data' => $pvData,
            'message' => 'Data berhasil disimpan',
        ], 201);
    }

    /**
     * Store data from ESP sensor with API key protection.
     */
    public function ingest(Request $request)
    {
        $validated = $request->validate([
            'api_key' => 'required|string',
            'voltage' => 'required|numeric',
            'current' => 'required|numeric',
            'temperature' => 'nullable|numeric',
            'suhu' => 'nullable|numeric',
            'lux' => 'required|numeric',
            'timestamp' => 'nullable|date_format:Y-m-d H:i:s',
        ]);

        $expectedApiKey = config('services.pv_sensor.api_key');
        if (empty($expectedApiKey) || $validated['api_key'] !== $expectedApiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key tidak valid',
            ], 401);
        }

        $temperature = $validated['temperature'] ?? $validated['suhu'] ?? null;
        if ($temperature === null) {
            return response()->json([
                'success' => false,
                'message' => 'Field temperature atau suhu wajib diisi',
            ], 422);
        }

        $payload = [
            'voltage' => $validated['voltage'],
            'current' => $validated['current'],
            'temperature' => $temperature,
            'lux' => $validated['lux'],
            'timestamp' => $validated['timestamp'] ?? null,
        ];

        $pvData = $this->persistData($payload);

        return response()->json([
            'success' => true,
            'data' => $pvData,
            'message' => 'Data sensor berhasil diterima',
        ], 201);
    }

    private function persistData(array $payload): PvData
    {
        $latest = PvData::latest()->first();

        $recordedAt = now();
        if (!empty($payload['timestamp'])) {
            $recordedAt = Carbon::createFromFormat('Y-m-d H:i:s', $payload['timestamp'], 'Asia/Jakarta');
        }

        $data = [
            'voltage' => $payload['voltage'],
            'current' => $payload['current'],
            'temperature' => $payload['temperature'],
            'lux' => $payload['lux'],
            'power_output' => $payload['voltage'] * $payload['current'],
            'voltage_change_percent' => 0,
            'current_change_percent' => 0,
            'lux_change_percent' => 0,
            'temperature_change_percent' => 0,
            'created_at' => $recordedAt,
            'updated_at' => $recordedAt,
        ];

        if ($latest) {
            $data['voltage_change_percent'] = $this->calculateChangePercent($data['voltage'], (float) $latest->voltage);
            $data['current_change_percent'] = $this->calculateChangePercent($data['current'], (float) $latest->current);
            $data['lux_change_percent'] = $this->calculateChangePercent($data['lux'], (float) $latest->lux);
            $data['temperature_change_percent'] = $this->calculateChangePercent($data['temperature'], (float) $latest->temperature);
        }

        return PvData::create($data);
    }

    private function getDataByPeriod(string $period)
    {
        return match ($period) {
            '1H' => PvData::getChartData(
                now()->subHour(),
                now(),
                60
            ),
            '24H' => PvData::getChartData(
                now()->subDay(),
                now(),
                288
            ),
            '7D' => PvData::getChartData(
                now()->subDays(7),
                now(),
                7 * 24
            ),
            default => PvData::getChartData(
                now()->subHour(),
                now(),
                60
            ),
        };
    }

    private function calculateChangePercent(float $newValue, float $oldValue): float
    {
        if ($oldValue == 0.0) {
            return 0.0;
        }

        return (($newValue - $oldValue) / $oldValue) * 100;
    }
}
