<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Weather;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Dashboard\WeatherService;
use App\Services\Dashboard\WeatherCalculationService;
use Illuminate\Support\Facades\Log;

class WeatherController extends Controller
{
    protected $weatherService;
    protected $weatherCalculationService;

    public function __construct(WeatherService $weatherService, WeatherCalculationService $weatherCalculationService)
    {
        $this->authorizeResource(Weather::class, 'weather');
        $this->weatherService = $weatherService;
        $this->weatherCalculationService = $weatherCalculationService;
    }

    public function index()
    {
        return Inertia::render(
            'Dashboard/Weather/Index'
        );
    }


    public function getWeather(Request $request)
    {
        $city = $request->input('city');
        $weatherData = $this->weatherService->getWeatherData($city);


        Log::info('weatherData: ' , ['weatherData' => $weatherData]);

        if (!is_array($weatherData) || count($weatherData) === 0) {
            return new JsonResponse(['error' => 'Weather data is empty or invalid'], 500);
        }
    
        // Calculate step size to evenly select 5 forecasts
        $totalDataPoints = count($weatherData);

        Log::info('totalDataPoints: ' , ['totalDataPoints' => $totalDataPoints]);

        $stepSize = max(1, intval(ceil($totalDataPoints / 5)));

        Log::info('stepSize: ' , ['stepSize' => $stepSize]);
    
        $selectedForecasts = [];
        for ($i = 1; $i < $totalDataPoints; $i += $stepSize) {

            Log::info('weatherData[$i]: ' , ['weatherData[$i]' => $weatherData[$i]]);

        
            $selectedForecasts[] = $weatherData[$i];
            if (count($selectedForecasts) >= 5) break;
        }

        Log::info('selectedForecasts: ' , ['selectedForecasts' => $selectedForecasts]);


    
        // Perform calculations for temperature, humidity, and pressure
        $temperatureChanges = $this->weatherCalculationService->calculateRateOfChange($selectedForecasts, 'temperature');
        $humidityChanges = $this->weatherCalculationService->calculateRateOfChange($selectedForecasts, 'humidity');
        $pressureChanges = $this->weatherCalculationService->calculateRateOfChange($selectedForecasts, 'pressure');

        $averageTemperatureChanges = $this->weatherCalculationService->calculateAverageRateOfChange($temperatureChanges);
        $averageHumidityChanges = $this->weatherCalculationService->calculateAverageRateOfChange($humidityChanges);
        $averagePressureChanges = $this->weatherCalculationService->calculateAverageRateOfChange($pressureChanges);

    
        // Return the processed data as JSON
        // if ($request->isXmlHttpRequest()) {
            return response()->json([
                'forecasts' => $selectedForecasts,
                'temperatureChanges' => $temperatureChanges,
                'humidityChanges' => $humidityChanges,
                'pressureChanges' => $pressureChanges,
                'averageTemperatureChanges' => $averageTemperatureChanges,
                'averageHumidityChanges' => $averageHumidityChanges,
                'averagePressureChanges' => $averagePressureChanges,
            ]);
        // }
    }



    
}
