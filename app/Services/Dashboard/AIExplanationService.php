<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIExplanationService
{
    public function getAIWeatherExplanations($weatherExplanations, $city)
    {
        Log::info('Weather Data for AI:', ['data' => $weatherExplanations, 'city' => $city]);

        $response = Http::withoutVerifying()
            ->withToken(config('services.openai.secret'))
            ->withHeaders([
                'OpenAI-Organization' => config('services.openai.organisation'),
            ])
            ->post('https://api.openai.com/v1/chat/completions', [
                "model" => "gpt-3.5-turbo-0125",
                "messages" => [
                    ["role" => "system", "content" => "You are an AI that explains weather data for a given city."],
                    ["role" => "user", "content" => "The city is $city. Here are the weather fluctuations over the past few days: " . json_encode($weatherExplanations)],
                    ["role" => "assistant", "content" => "Explain the temperature, humidity, and pressure trends for $city based on the given data. 
                        Please split your answers into 3 sections with the following titles (where your response to each of these goes within the corresponding title), 
                        'Temperature', 'Humidity' and 'Pressure'."],
                ]
            ]);

        $aiResponse = $response->json('choices.0.message.content');

        Log::info('AI Weather Explanation:', ['response' => $aiResponse]);

        // Extract separate explanations for temperature, humidity, and pressure
        return [
            'temperatureExplanation' => $this->extractExplanation($aiResponse, 'Temperature'),
            'humidityExplanation' => $this->extractExplanation($aiResponse, 'Humidity'),
            'pressureExplanation' => $this->extractExplanation($aiResponse, 'Pressure'),
        ];
    }

    private function extractExplanation($aiResponse, $type)
    {
        // Basic parsing logic (assumes AI response is structured in sections)
        preg_match("/$type:\s*(.+?)(?:\n|$)/i", $aiResponse, $matches);
        return $matches[1] ?? "No explanation available.";
    }
}
