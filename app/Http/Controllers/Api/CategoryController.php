<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class CategoryController extends Controller
{
    public function createCategory(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|max:255',
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
            $category->image = $imageName;
        }
        $category->save();

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category,
        ], 201);
      
    }

    public function editCategory(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'image' => 'nullable|image|max:255',
        ]);

        // checking if the directory exists. else it will create the directory
        if(!File::exists('images/category'))
        {
            File::makeDirectory('images/category',0777,true,true);
        }

        // processing image to upload
        if ($request->hasFile('image')) 
        {
            // delete old image if exists
            if ($category->image && File::exists(public_path('images/category/'.$category->image))) {
                File::delete(public_path('images/category/'.$category->image));
            }

            $imageName = time().'.'.$request->image->getClientOriginalExtension();
            $request->image->move(public_path('images/category'), $imageName);
            $category->image = $imageName;
        }

        if (isset($data['name'])) {
            $category->name = $data['name'];
        }
        $category->save();

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category,
        ], 200);
    }

    public function deleteCategory(Category $category)
    {
        // delete image if exists
        if ($category->image && File::exists(public_path('images/category/'.$category->image))) {
            File::delete(public_path('images/category/'.$category->image));
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ], 200);
    }
}
