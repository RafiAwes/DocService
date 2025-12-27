<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NewsResource;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class NewsController extends Controller
{
    public function createNews(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|max:10240',
        ]);

        // ensure images directory exists
        $imagesDir = public_path('images/news');
        if (! File::exists($imagesDir)) {
            File::makeDirectory($imagesDir, 0777, true, true);
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time().'_'.preg_replace('/[^A-Za-z0-9_.-]/', '_', $image->getClientOriginalName());
            $image->move($imagesDir, $imageName);
            $validatedData['image'] = $imageName;
        }

        $news = News::create($validatedData);

        return NewsResource::make($news)->additional([
            'status' => true,
            'message' => 'News item created successfully',
        ]);
    }

    public function updateNews(Request $request, News $news)
    {
        $validatedData = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'image' => 'nullable|image|max:10240',
        ]);

        // handle image replacement
        if ($request->hasFile('image')) {
            $imagesDir = public_path('images/news');
            if (! File::exists($imagesDir)) {
                File::makeDirectory($imagesDir, 0777, true, true);
            }

            // delete old image file if present
            if ($news->image && File::exists(public_path($news->image))) {
                File::delete(public_path($news->image));
            }

            $image = $request->file('image');
            $imageName = time().'_'.preg_replace('/[^A-Za-z0-9_.-]/', '_', $image->getClientOriginalName());
            $image->move($imagesDir, $imageName);
            $validatedData['image'] = $imageName;
        }

        $news->update($validatedData);

        return NewsResource::make($news)->additional([
            'status' => true,
            'message' => 'News item updated successfully',
        ]);
    }

    public function deleteNews(News $news)
    {
        if ($news->image && File::exists(public_path($news->image))) {
            File::delete(public_path($news->image));
        }
        $news->delete();

        return response()->json([
            'status' => true,
            'message' => 'News item deleted successfully',
        ], 200);
    }

    public function listNews(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $newsItems = News::orderBy('id', 'desc')->paginate($perPage);
        return NewsResource::collection($newsItems)->additional([
            'status' => true,
            'message' => 'News items retrieved successfully',
        ]);
    }

    public function newsDetails(News $news)
    {
        return NewsResource::make($news)->additional([
            'status' => true,
            'message' => 'News item details retrieved successfully',
        ]);
    }
}
