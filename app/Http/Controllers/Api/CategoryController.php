<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, File, Storage, image};
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;


class CategoryController extends Controller
{
    public function createCategory(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|max:10240',
        ]);

        // image name is set to null initially
        $imageName = null;

        // checking if the directory exists. else it will create the directory
        if(!File::exists('images/category'))
        {
            File::makeDirectory('images/category',0777,true,true);
        }

        // processing image to upload
        if ($request->hasFile('image')) 
        {
            $imageName = time().'.'.$request->image->getClientOriginalExtension();
            $request->image->move(public_path('images/category'), $imageName);
        }

        $category = new Category();
        $category->name = $data['name'];
        if ($imageName) {
            $category->fill(['image' => 'images/category/'.$imageName]);
        }
        $category->save();

        return CategoryResource::make($category)->additional([
            'status' => true,
            'message' => 'Category created successfully',
        ]);
      
    }

    public function editCategory(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'image' => 'nullable|image|max:10240',
        ]);

        // checking if the directory exists. else it will create the directory
        if(!File::exists('images/category'))
        {
            File::makeDirectory('images/category',0777,true,true);
        }

        // processing image to upload
        if ($request->hasFile('image')) 
        {
            if ($category->image && File::exists(public_path($category->image))) {
                File::delete(public_path($category->image));
            }

            $imageName = time().'.'.$request->image->getClientOriginalExtension();
            $request->image->move(public_path('images/category'), $imageName);
            $category->fill(['image' => 'images/category/'.$imageName]);
        }

        if (isset($data['name'])) {
            $category->name = $data['name'];
        }
        $category->save();

        return CategoryResource::make($category)->additional([
            'status' => true,
            'message' => 'Category updated successfully',
        ]);
    }

    public function deleteCategory(Category $category)
    {
        // Prevent deletion when services still exist under this category
        if ($category->services()->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete this category because it still has services. Please delete or reassign those services first.',
            ], 422);
        }

        // delete image if exists
        if ($category->image && File::exists(public_path($category->image))) {
            File::delete(public_path($category->image));
        }

        $category->delete();

        return response()->json([
            'status'=> true,
            'message' => 'Category deleted successfully',
        ], 200);
    }

    public function listCategories(Request $request)
    {
        $query = Category::query()->withCount('services');

         // Search functionality
    
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            // Using 'like' allows for partial matches (e.g. searching "839" finds "839201")
            $query->where('name', 'like', "%{$searchTerm}%");
        }

        $perPage = request()->query('per_page', 10);
        $categories = $query->orderBy('created_at', 'desc')->paginate($perPage);
        if(!$categories || $categories->isEmpty()){
            return response()->json([
                'status'=> false,
                'message' => 'No categories found',
            ], 404);
        }
        return CategoryResource::collection($categories)->additional([
            'status' => true,
            'message' => 'Categories retrieved successfully',
        ]);
    }

    public function categoryDetails(Category $category)
    {
        $category = Category::find($category->id);
        return CategoryResource::make($category)->additional([
            'status' => true,
            'message' => 'Category details retrieved successfully',
        ]);
    }
}
