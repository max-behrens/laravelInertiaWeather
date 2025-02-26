<?php

// app/Http/Controllers/ParseXmlController.php

namespace App\Http\Controllers\Dashboard;

use Inertia\Inertia;
use App\Services\Dashboard\ParseXmlService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;



class ParseXmlController extends Controller
{

    protected $parseXmlService;

    public function __construct(ParseXmlService $parseXmlService)
    {
        $this->parseXmlService = $parseXmlService;
    }


    public function show()
    {
        return Inertia::render('Dashboard/XmlParser/Index'); // Points to the Inertia page where your component is located
    }


    public function getTimestamps(Request $request)
    {
        // Assuming the XML is stored at storage/app/xml/log.xml
        $xml = file_get_contents(storage_path('app/xml/log.xml'));

        $description = $request->input('description');
        $timestamps = $this->parseXmlService->getTimestampsByDescription($xml, $description);

        return response()->json($timestamps);
    }
}
