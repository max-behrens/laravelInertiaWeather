<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use App\Services\Dashboard\DashboardAIService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;



class DashboardAIController extends Controller
{
    protected $dashboardAIService;

    public function __construct(DashboardAIService $dashboardAIService)
    {
        $this->dashboardAIService = $dashboardAIService;
    }

    public function askOpenAI(Request $request)
    {
        $request->validate([
            'user_input' => 'required|string|max:500'
        ]);

        $response = $this->dashboardAIService->getAIResponse($request->user_input);

        return response()->json(['aiResponse' => $response]);
    }
}
