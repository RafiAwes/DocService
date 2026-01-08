<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\{Faq, pages as Pages};

class PagesController extends Controller
{
    /**
     * Get content by key (Public)
     * Supports: /api/pages?key=terms and /api/pages/{key}
     */
    public function show(Request $request, $key = null)
    {
        $lookupKey = $key ?? $request->query('key');

        if (!$lookupKey) {
            return response()->json([
                'status'  => false,
                'message' => 'Missing required parameter: key'
            ], 400);
        }

        $page = Pages::where('key', $lookupKey)->first();

        if (!$page) {
            return response()->json([
                'status'  => false,
                'message' => 'Page content not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $page
        ]);
    }

    /**
     * SAVE: Create OR Update a page (Admin Only)
     * POST /api/pages/save
     */
    public function savePage(Request $request)
    {
        $request->validate([
            'key'   => 'required|string', // e.g. "terms", "privacy", "about"
            'value' => 'required|string', // The content
        ]);

        // THE MAGIC LINE:
        // 1. Searches for a record where 'key' matches request->key.
        // 2. If found -> Updates 'value'.
        // 3. If NOT found -> Creates new row with 'key' and 'value'.
        $page = Pages::updateOrCreate(
            ['key' => $request->key], 
            ['value' => $request->value]
        );

        return response()->json([
            'status' => true,
            'message' => 'Page content saved successfully',
            'data'    => $page
        ]);
    }
    public function index()
    {
        $faqs = Faq::paginate(10);

        return response()->json(['status' => true, 'data' => $faqs]);
    }

    /**
     * Create FAQ (Admin Only)
     * POST /api/faqs
     */
    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        $faq = Faq::create([
            'question' => $request->question,
            'answer' => $request->answer,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'FAQ added successfully',
            'data' => $faq,
        ]);
    }

    /**
     * Update FAQ (Admin Only)
     * PUT /api/faqs/{id}
     */
    public function update(Request $request, $id)
    {
        $faq = Faq::find($id);

        if (! $faq) {
            return response()->json(['success' => false, 'message' => 'FAQ not found'], 404);
        }

        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        $faq->update([
            'question' => $request->question,
            'answer' => $request->answer,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'FAQ updated successfully',
            'data' => $faq,
        ]);
    }

    /**
     * Delete FAQ (Admin Only)
     * DELETE /api/faqs/{id}
     */
    public function destroy($id)
    {
        $faq = Faq::find($id);

        if (! $faq) {
            return response()->json(['success' => false, 'message' => 'FAQ not found'], 404);
        }

        $faq->delete();

        return response()->json([
            'status' => true,
            'message' => 'FAQ deleted successfully',
        ]);
    }
}
