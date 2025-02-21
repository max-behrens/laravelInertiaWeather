<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DashboardAIService
{
    public function getAIResponse($userInput)
    {
        Log::info('User input:', ['userInput' => $userInput]);

        $response = Http::withoutVerifying()
            ->withToken(config('services.openai.secret'))
            ->withHeaders([
                'OpenAI-Organization' => config('services.openai.organisation'), // Your correct organization ID
            ])
            ->post('https://api.openai.com/v1/chat/completions', [
                "model" => "gpt-3.5-turbo-0125",
                "messages" => [
                    ["role" => "system", "content" => "You are a helpful assistant."],
                    ["role" => "user", "content" => $userInput]
                ]
            ]);

        $aiResponse = $response->json('choices.0.message.content');

        Log::info('AI Response:', ['response' => $aiResponse]);

        return $aiResponse;
    }
}
