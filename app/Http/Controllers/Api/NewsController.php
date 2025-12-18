<?php

namespace App\Http\Controllers\Api;

use App\Models\News;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NewsController extends Controller
{
    public function createNews(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|max:2048',
        ]);

        if (!File::exists('images/news')) {
            File::makeDirectory('images/news', 0777, true, true);
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('news_images', 'public');
            $validatedData['image'] = $path;
        }

        $news = News::create($validatedData);

        return response()->json([
            'message' => 'News item created successfully',
            'data' => $news,
        ], 201);
    }
}
