<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Post;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Services\Dashboard\PostService;
use App\Http\Requests\UpdatePostRequest;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Post::class, 'post');
    }

    public function index(Request $request, PostService $service)
    {
        $request->validate([
            'field' => ['nullable', 'in:id,title,slug,created_at,username,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'search' => ['nullable'],
        ]);

        Log::info('request:', ['request' => $request]);

        return Inertia::render(
            'Dashboard/Posts/Index',
            $service->datatable($request)
        );
    }

    public function create(Request $request)
    {
        // Check for any query parameters for dialogue data (calculationData, aiResponseResults, chatbotMessages)
        $dialogueData = [
            'calculationData' => json_decode($request->query('calculationData', '{}'), true),
            'aiResponseResults' => json_decode($request->query('aiResponseResults', '{}'), true),
            'chatbotMessages' => json_decode($request->query('chatbotMessages', '[]'), true),
        ];

        // Pass dialogue data to the Create view (inertia)
        return Inertia::render(
            'Dashboard/Posts/Create',
            [
                'dialogueData' => $dialogueData, // Pass the dialogue data as props
            ]
        );
    }


    public function store(StorePostRequest $request, PostService $service)
    {
        // Prepare the data for the new post
        $postData = $request->safe()->except('featured_image'); // Keep all fields except 'featured_image'

        Log::info('REQUEST ADD POST ', ['$request' => $request]);


        // Check if dialogue data is passed in the request
        if ($request->has('slug')) {

            Log::info('SLUG ');


            Log::info('$request->city ', ['$request->city' => $request->city]);

            // If slug is passed (containing dialogue data), assign it to the post's 'slug' field
            $postData['slug'] = $request->input('slug');

            // Check for any query parameters for dialogue data (calculationData, aiResponseResults, chatbotMessages)
            $dialogueData = [
                'calculationData' => $request->query('calculationData'),
                'aiResponseResults' => $request->query('aiResponseResults'),
                'chatbotMessages' => $request->query('chatbotMessages'),
            ];

            // Pass dialogue data to the Create view (inertia)
            return Inertia::render(
                'Dashboard/Posts/Create',
                [
                    'dialogueData' => $dialogueData, // Pass the dialogue data as props
                ]
            );
        }

        // Create the post
        $post = Post::create($postData);

        // Store the featured image (if applicable)
        $path = $service->storeFeaturedImage($post, $request);

        // Update the post with the featured image path
        $service->updatePostFeaturedImage($post, $path);

        // Return a response with the postId (if needed)
        return redirect()->route('posts.index')->with('message', 'Post Created Successfully');
    }

    public function edit(Post $post)
    {
        return Inertia::render(
            'Dashboard/Posts/Edit',
            [
                'post' => $post
            ]
        );
    }

    public function update(Post $post, UpdatePostRequest $request, PostService $service)
    {
        $post->update($request->safe()->except('featured_image'));

        $path = $service->storeFeaturedImage($post, $request);

        $service->updatePostFeaturedImage($post, $path);

        return redirect()->route('posts.index')->with('message', 'Post Updated Successfully');
    }

    public function destroy(Post $post)
    {
        $post->delete();

        return redirect()->route('posts.index')->with('message', 'Post Delete Successfully');
    }

    public function publish(Post $post, Request $request)
    {
        if ($request->user()->cannot('publish', $post)) {
            abort(404);
        }

        $request->validate([
            'is_active' => 'required|integer|in:0,1',
        ]);

        $post->update([
            'is_active' => $request->is_active
        ]);

        return redirect()->route('posts.index')->with('message', sprintf('Post %s Successfully', $request->is_active ? 'Published' : 'Unpublished'));
    }

}
