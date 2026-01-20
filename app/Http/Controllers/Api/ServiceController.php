<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, File};
use App\Models\Service;
use App\Http\Controllers\Controller;

class ServiceController extends Controller
{
    /**
     * 1. Create Base Service (without relations)
     */
    public function createBaseService(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'is_south_african' => 'required|boolean',
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'order_type' => 'nullable|in:quote,checkout,null',
            'type' => 'nullable|in:Quote,Checkout',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'how_it_works' => 'nullable|array',
            'how_it_works.*' => 'nullable|string|max:255',
        ]);

        try {
            $imageName = null;
            if (!File::exists('images/service')) {
                File::makeDirectory('images/service', 0777, true, true);
            }

            if ($request->hasFile('image')) {
                $imageName = time() . '.' . $request->image->getClientOriginalExtension();
                $request->image->move(public_path('images/service'), $imageName);
            }

            $service = Service::create([
                'category_id' => $validated['category_id'],
                'is_south_african' => $validated['is_south_african'],
                'title' => $validated['title'],
                'subtitle' => $validated['subtitle'] ?? null,
                'order_type' => $validated['order_type'] ?? 'null',
                'type' => $validated['type'] ?? null,
                'price' => $validated['price'] ?? null,
                'description' => $validated['description'] ?? null,
                'image' => $imageName ? 'images/service/' . $imageName : null,
                'short_description' => $validated['short_description'] ?? null,
            ]);

            // Create how it works entries
            if (!empty($validated['how_it_works'])) {
                foreach ($validated['how_it_works'] as $title) {
                    if (!empty($title)) {
                        $service->howItWorks()->create(['title' => $title]);
                    }
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Base service created successfully!',
                'data' => $service->load('howItWorks'),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create service.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 1.5 Update Base Service (without relations)
     */
    public function updateBaseService(Request $request, Service $service)
    {
        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'is_south_african' => 'sometimes|boolean',
            'title' => 'sometimes|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'order_type' => 'nullable|in:quote,checkout,null',
            'type' => 'nullable|in:Quote,Checkout',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'how_it_works' => 'nullable|array',
            'how_it_works.*' => 'nullable|string|max:255',
        ]);

        try {
            $updateData = [];

            // Handle image upload
            if ($request->hasFile('image')) {
                if (!File::exists('images/service')) {
                    File::makeDirectory('images/service', 0777, true, true);
                }

                // Delete old image if exists
                if ($service->image && File::exists(public_path($service->image))) {
                    File::delete(public_path($service->image));
                }

                $imageName = time() . '.' . $request->image->getClientOriginalExtension();
                $request->image->move(public_path('images/service'), $imageName);
                $updateData['image'] = 'images/service/' . $imageName;
            }

            // Add only provided fields
            foreach ($validated as $key => $value) {
                if ($key !== 'how_it_works' && $request->has($key) && $request->filled($key)) {
                    $updateData[$key] = $value;
                }
            }

            if (!empty($updateData)) {
                $service->update($updateData);
            }

            // Handle how it works update
            if ($request->has('how_it_works')) {
                $service->howItWorks()->delete();
                if (!empty($validated['how_it_works'])) {
                    foreach ($validated['how_it_works'] as $title) {
                        if (!empty($title)) {
                            $service->howItWorks()->create(['title' => $title]);
                        }
                    }
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Base service updated successfully!',
                'data' => $service->load('howItWorks'),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update service.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 2. Add Included Services
     */
    public function addIncludedServices(Request $request, Service $service)
    {
        $data = $request->validate([
            'included_services' => 'required|array|min:1',
            'included_services.*.service_type' => 'required|string',
            'included_services.*.included_details' => 'nullable|string',
            'included_services.*.price' => 'nullable|numeric|min:0',
        ]);

        try {
            $service->includedServices()->createMany($data['included_services']);

            return response()->json([
                'status' => true,
                'message' => 'Included services added successfully!',
                'data' => $service->load('includedServices'),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to add included services.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 3. Add Processing Times
     */
    public function addProcessingTimes(Request $request, Service $service)
    {
        $data = $request->validate([
            'processing_times' => 'required|array|min:1',
            'processing_times.*.details' => 'nullable|string',
            'processing_times.*.time' => 'required|string|max:255',
        ]);

        try {
            $service->processingTimes()->createMany($data['processing_times']);

            return response()->json([
                'status' => true,
                'message' => 'Processing times added successfully!',
                'data' => $service->load('processingTimes'),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to add processing times.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 4. Add Questions
     */
    public function addQuestions(Request $request, Service $service)
    {
        $data = $request->validate([
            'questions' => 'required|array|min:1',
            'questions.*.name' => 'required|string',
            'questions.*.type' => 'required|in:Textbox,Input field,Drop down,Check box',
            'questions.*.options' => 'nullable|json',
        ]);

        try {
            $service->questionaries()->createMany($data['questions']);

            return response()->json([
                'status' => true,
                'message' => 'Questions added successfully!',
                'data' => $service->load('questionaries'),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to add questions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 5. Add Required Documents
     */
    public function addRequiredDocuments(Request $request, Service $service)
    {
        $data = $request->validate([
            'required_documents' => 'required|array|min:1',
            'required_documents.*.title' => 'required|string',
        ]);

        try {
            // Filter out empty documents
            $documents = array_filter($data['required_documents'], function ($doc) {
                return !empty($doc['title']);
            });

            $service->requiredDocuments()->createMany($documents);

            return response()->json([
                'status' => true,
                'message' => 'Required documents added successfully!',
                'data' => $service->load('requiredDocuments'),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to add required documents.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * UPDATE OPERATIONS
     */

    /**
     * 7. Update Included Services
     */
    public function updateIncludedServices(Request $request, Service $service)
    {
        $data = $request->validate([
            'included_services' => 'required|array',
            'included_services.*.id' => 'nullable|integer|exists:included_services,id',
            'included_services.*.service_type' => 'nullable|string',
            'included_services.*.included_details' => 'nullable|string',
            'included_services.*.price' => 'nullable|numeric|min:0',
        ]);

        try {
            $items = array_filter($data['included_services'], function ($item) {
                return !empty($item['service_type']);
            });

            $keepIds = collect($items)->pluck('id')->filter()->toArray();

            if (!empty($keepIds)) {
                $service->includedServices()->whereNotIn('id', $keepIds)->delete();
            } else {
                $service->includedServices()->delete();
            }

            foreach ($items as $item) {
                if (isset($item['id']) && $item['id']) {
                    $service->includedServices()->where('id', $item['id'])->update($item);
                } else {
                    unset($item['id']);
                    $service->includedServices()->create($item);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Included services updated successfully!',
                'data' => $service->load('includedServices'),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update included services.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 8. Update Processing Times
     */
    public function updateProcessingTimes(Request $request, Service $service)
    {
        $data = $request->validate([
            'processing_times' => 'required|array',
            'processing_times.*.id' => 'nullable|integer|exists:processing_times,id',
            'processing_times.*.details' => 'nullable|string',
            'processing_times.*.time' => 'nullable|string|max:255',
        ]);

        try {
            $items = array_filter($data['processing_times'], function ($item) {
                return !empty($item['time']);
            });

            $keepIds = collect($items)->pluck('id')->filter()->toArray();

            if (!empty($keepIds)) {
                $service->processingTimes()->whereNotIn('id', $keepIds)->delete();
            } else {
                $service->processingTimes()->delete();
            }

            foreach ($items as $item) {
                if (isset($item['id']) && $item['id']) {
                    $service->processingTimes()->where('id', $item['id'])->update($item);
                } else {
                    unset($item['id']);
                    $service->processingTimes()->create($item);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Processing times updated successfully!',
                'data' => $service->load('processingTimes'),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update processing times.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 9. Update Questions
     */
    public function updateQuestions(Request $request, Service $service)
    {
        $data = $request->validate([
            'questions' => 'required|array',
            'questions.*.id' => 'nullable|integer|exists:questionaries,id',
            'questions.*.name' => 'nullable|string',
            'questions.*.type' => 'nullable|in:Textbox,Input field,Drop down,Check box',
            'questions.*.options' => 'nullable|json',
        ]);

        try {
            $items = array_filter($data['questions'], function ($item) {
                return !empty($item['name']);
            });

            $keepIds = collect($items)->pluck('id')->filter()->toArray();

            if (!empty($keepIds)) {
                $service->questionaries()->whereNotIn('id', $keepIds)->delete();
            } else {
                $service->questionaries()->delete();
            }

            foreach ($items as $item) {
                if (isset($item['id']) && $item['id']) {
                    $service->questionaries()->where('id', $item['id'])->update($item);
                } else {
                    unset($item['id']);
                    $service->questionaries()->create($item);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Questions updated successfully!',
                'data' => $service->load('questionaries'),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update questions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 10. Update Required Documents
     */
    public function updateRequiredDocuments(Request $request, Service $service)
    {
        $data = $request->validate([
            'required_documents' => 'required|array',
            'required_documents.*.id' => 'nullable|integer|exists:required_documents,id',
            'required_documents.*.title' => 'nullable|string',
        ]);

        try {
            $items = array_filter($data['required_documents'], function ($item) {
                return !empty($item['title']);
            });

            $keepIds = collect($items)->pluck('id')->filter()->toArray();

            if (!empty($keepIds)) {
                $service->requiredDocuments()->whereNotIn('id', $keepIds)->delete();
            } else {
                $service->requiredDocuments()->delete();
            }

            foreach ($items as $item) {
                if (isset($item['id']) && $item['id']) {
                    $service->requiredDocuments()->where('id', $item['id'])->update($item);
                } else {
                    unset($item['id']);
                    $service->requiredDocuments()->create($item);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Required documents updated successfully!',
                'data' => $service->load('requiredDocuments'),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update required documents.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function createService(Request $request)
    {
        $validated = $request->validate([
            // Service Base Data
            'category_id' => 'required|exists:categories,id',
            'is_south_african' => 'required|boolean',
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'order_type' => 'nullable|in:quote,checkout,null',
            'type' => 'nullable|in:Quote,Checkout',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'how_it_works' => 'nullable|array',
            'how_it_works.*' => 'string|max:255',

            // Relation: Included Services
            'included_services' => 'nullable|array',
            'included_services.*.service_type' => 'required_with:included_services|string',
            'included_services.*.included_details' => 'nullable|string',
            'included_services.*.price' => 'nullable|numeric',

            // Relation: Processing Time
            'processing_times' => 'nullable|array',
            'processing_times.*.details' => 'nullable|string',
            'processing_times.*.time' => 'nullable|string|max:255', // Updated to string as discussed

            // Relation: Questionaries
            'questions' => 'nullable|array',
            'questions.*.name' => 'required_with:questions|string',
            'questions.*.type' => 'required_with:questions|in:Textbox,Input field,Drop down,Check box',
            'questions.*.options' => 'nullable|json',

            // Relation: Required Documents
            'required_documents' => 'nullable|array',
            'required_documents.*.title' => 'required_with:required_documents|string',
        ]);

        try {
            // Start Transaction
            $service = DB::transaction(function () use ($validated, $request) {

                $imageName = null;
                 if(!File::exists('images/service'))
                 {
                     File::makeDirectory('images/service',0777,true,true);
                 }

                if($request->hasFile('image'))
                {
                    $imageName = time().'.'.$request->image->getClientOriginalExtension();
                    $request->image->move(public_path('images/service'), $imageName);
                }
                // 1. Create Main Service
                $service = Service::create([
                    'category_id' => $validated['category_id'],
                    'is_south_african' => $validated['is_south_african'],
                    'title' => $validated['title'],
                    'subtitle' => $validated['subtitle'] ?? null, // Added subtitle
                    'order_type' => $validated['order_type'] ?? 'null',
                    'type' => $validated['type'] ?? null,
                    'price' => $validated['price'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'image' => $imageName ? 'images/service/'.$imageName : null,
                    'short_description' => $validated['short_description'] ?? null,
                ]);

                // 2. Create Relations (using createMany for cleaner code)

                if (! empty($validated['included_services'])) {
                    $service->includedServices()->createMany($validated['included_services']);
                }

                if (! empty($validated['processing_times'])) {
                    $service->processingTimes()->createMany($validated['processing_times']);
                }

                if (! empty($validated['questions'])) {
                    $service->questionaries()->createMany($validated['questions']);
                }

                if (! empty($validated['required_documents'])) {
                    $service->requiredDocuments()->createMany($validated['required_documents']);
                }

                // Create how it works entries
                if (! empty($validated['how_it_works'])) {
                    foreach ($validated['how_it_works'] as $title) {
                        $service->howItWorks()->create(['title' => $title]);
                    }
                }

                return $service;
            });

            // SUCCESS RESPONSE
            return response()->json([
                'status' => true,
                'message' => 'Service created successfully with all relations!',
                'data' => $service->load([
                    'includedServices',
                    'processingTimes',
                    'questionaries',
                    'requiredDocuments',
                    'howItWorks',
                ]),
            ], 201);

        } catch (\Exception $e) {
            // ERROR RESPONSE
            // We cannot use $service here because it failed to create!
            return response()->json([
                'status' => false,
                'message' => 'Failed to create service.',
                'error' => $e->getMessage(), // This will tell you exactly what went wrong
            ], 500);
        }
    }

    /**
     * Update the specified service and its relations.
     */
    public function updateService(Request $request, Service $service)
    {
        // 1. Validation
        // We add 'id' validation to allow updating existing rows
        $validated = $request->validate([
            // Main Service Fields
            'category_id' => 'sometimes|exists:categories,id',
            'is_south_african' => 'sometimes|boolean',
            'title' => 'sometimes|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'order_type' => 'nullable|in:quote,checkout,null',
            'type' => 'nullable|in:Quote,Checkout',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'how_it_works' => 'nullable|array',
            'how_it_works.*' => 'string|max:255',

            // 1. Included Services
            'included_services' => 'nullable|array',
            'included_services.*.id' => 'nullable|integer|exists:included_services,id', // <--- CRITICAL
            'included_services.*.service_type' => 'nullable|string',
            'included_services.*.included_details' => 'nullable|string',
            'included_services.*.price' => 'nullable|numeric',

            // 2. Processing Times
            'processing_times' => 'nullable|array',
            'processing_times.*.id' => 'nullable|integer|exists:processing_times,id',
            'processing_times.*.details' => 'nullable|string',
            'processing_times.*.time' => 'nullable|string|max:255',

            // 3. Questionaries
            'questions' => 'nullable|array',
            'questions.*.id' => 'nullable|integer|exists:questionaries,id',
            'questions.*.name' => 'nullable|string',
            // Align with new types (capitalized with spaces)
            'questions.*.type' => 'nullable|in:Textbox,Input field,Drop down,Check box',
            'questions.*.options' => 'nullable|json',

            // 4. Required Documents
            'required_documents' => 'nullable|array',
            'required_documents.*.id' => 'nullable|integer|exists:required_documents,id',
            'required_documents.*.title' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($service, $request, $validated) {

                // A. Update Main Service Data
                // Only updates fields that are actually provided (not empty)
                $updateData = [];
                $fieldsToUpdate = [
                    'category_id', 'is_south_african', 'title', 'subtitle', 
                    'order_type', 'type', 'price', 'description', 'short_description'
                ];
                
                foreach ($fieldsToUpdate as $field) {
                    if ($request->has($field) && $request->filled($field)) {
                        $updateData[$field] = $request->input($field);
                    }
                }
                
                if (!empty($updateData)) {
                    $service->update($updateData);
                }

                /**
                 * Helper Function for "Smart Upsert"
                 *
                 * * @param string $relationName (The method name in Service Model)
                 * @param  array  $items  (The data array from request)
                 * @param  string  $requiredField  (Field that must be present for the item to be valid)
                 */
                $syncRelation = function ($relationName, $items, $requiredField = null) use ($service) {
                    if (is_null($items)) {
                        return;
                    } // If key not sent, do nothing

                    // Filter out empty items (items without required field)
                    if ($requiredField) {
                        $items = array_filter($items, function($item) use ($requiredField) {
                            return !empty($item[$requiredField]);
                        });
                    }

                    // 1. Identify IDs to Keep
                    // Collect all IDs present in the request. Any ID in DB but NOT here will be deleted.
                    $keepIds = collect($items)->pluck('id')->filter()->toArray();

                    // 2. Delete Missing Rows
                    if (!empty($keepIds)) {
                        $service->{$relationName}()->whereNotIn('id', $keepIds)->delete();
                    } else {
                        // If no items with IDs, delete all
                        $service->{$relationName}()->delete();
                    }

                    // 3. Update or Create Rows
                    foreach ($items as $item) {
                        if (isset($item['id']) && $item['id']) {
                            // Update existing record
                            $service->{$relationName}()->where('id', $item['id'])->update($item);
                        } else {
                            // Create new record (remove id key if it exists and is null)
                            unset($item['id']);
                            $service->{$relationName}()->create($item);
                        }
                    }
                };

                // B. Apply Sync to All Relations
                // We check $request->has() so we don't accidentally wipe data if the array wasn't sent at all.
                if ($request->has('included_services')) {
                    $syncRelation('includedServices', $validated['included_services'] ?? [], 'service_type');
                }

                if ($request->has('processing_times')) {
                    $syncRelation('processingTimes', $validated['processing_times'] ?? [], 'time');
                }

                if ($request->has('questions')) {
                    $syncRelation('questionaries', $validated['questions'] ?? [], 'name');
                }

                if ($request->has('required_documents')) {
                    $syncRelation('requiredDocuments', $validated['required_documents'] ?? [], 'title');
                }

                // Handle how it works
                if ($request->has('how_it_works')) {
                    // Delete all existing and create new ones
                    $service->howItWorks()->delete();
                    if (!empty($validated['how_it_works'])) {
                        foreach ($validated['how_it_works'] as $title) {
                            $service->howItWorks()->create(['title' => $title]);
                        }
                    }
                }
            });

            // Refresh model to get updated relations
            return response()->json([
                'status' => true,
                'message' => 'Service updated successfully!',
                'data' => $service->fresh()->load([
                    'includedServices',
                    'processingTimes',
                    'questionaries',
                    'requiredDocuments',
                    'howItWorks',
                    'requiredDocuments',
                ]),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update service.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteService(Service $service)
    {
        $service = Service::findOrFail($service->id);

        if (! $service) {
            return response()->json([
                'status' => false,
                'message' => 'Service not found.',
            ], 404);
        }

        try {
            $service->delete();

            return response()->json([
                'status' => true,
                'message' => 'Service deleted successfully!',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete service.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function serviceList(Request $request)
    {
        $query = Service::query();
        if ($request->has('is_south_african')) {
            $isSouthAfrican = $request->boolean('is_south_african');
            $query->where('is_south_african', $isSouthAfrican ? 1 : 0);
        }

         // Search functionality

        if($request->filled('category_id')) {
            $categoryId = $request->category_id;
            $query->where('category_id', $categoryId);
        }

        if($request->filled('order_type')){
            $orderType = strtolower($request->order_type);
            if (in_array($orderType, ['quote', 'checkout'])) {
                $query->where('order_type', $orderType);
            }
        }

        if ($request->filled('search')) {
            // 1. Remove accidental spaces from start/end
            $searchTerm = trim($request->search);

            // 2. Use LOWER() for case-insensitive matching
            // This works on MySQL, PostgreSQL, and SQLite safely
            $query->whereRaw('LOWER(title) LIKE ?', ['%'.strtolower($searchTerm).'%']);
        }

        $perPage = request()->query('per_page', 100);

        $services = $query->with([
            'includedServices',
            'processingTimes',
            'questionaries',
            'requiredDocuments',
            'category',
            'howItWorks',
        ])->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Services retrieved successfully!',
            'data' => $services,
        ], 200);
    }

    public function serviceDetails(Service $service)
    {
        $service = Service::with([
            'includedServices',
            'processingTimes',
            'questionaries',
            'requiredDocuments',
            'howItWorks',
        ])->find($service->id);

        // $totalPrice = $service->price;
        // foreach ($service->includedServices as $includedService) {
        //     $totalPrice += $includedService->price;
        // }
        // foreach ($service->deliveryDetails as $deliveryDetail) {
        //     $totalPrice += $deliveryDetail->price;
        // }
        // $total_price = $totalPrice;

        if (! $service) {
            return response()->json([
                'status' => false,
                'message' => 'Service not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Service details retrieved successfully!',
            'data' => $service,
            // 'total_price' => $total_price,
        ], 200);
    }

    public function serviceUnderCategory(Request $request)
    {
        // 1. Validate the Query Parameters
        $request->validate([
            'category_id'      => 'required|integer|exists:categories,id',
            'is_south_african' => 'required|boolean', // Accepts 1, 0, "true", "false"
            'search'           => 'nullable|string|max:100',
            'per_page'         => 'nullable|integer|min:1|max:100'
        ]);

        // 2. Retrieve variables from Request
        $categoryId = $request->input('category_id');
        $isSouthAfrican = $request->boolean('is_south_african'); // Helper handles "true"/"1"/1 correctly
        $perPage = $request->input('per_page', 10);

        // 3. Build the Query
        $query = Service::where('category_id', $categoryId)
            ->where('is_south_african', $isSouthAfrican ? 1 : 0);

        // 4. Handle Search
        if ($request->filled('search')) {
            $searchTerm = trim($request->search);
            // Using generic LIKE for compatibility
            $query->where('title', 'LIKE', '%' . $searchTerm . '%');
        }

        // 5. Fetch & Paginate
        $servicesPaginator = $query->with([
            'includedServices',
            'processingTimes',
            'questionaries',
            'requiredDocuments',
            'howItWorks',
        ])
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);

        // 6. Format the Data (Clean Output)
        $formattedServices = $servicesPaginator->through(function ($service) {
            return [
                'id' => $service->id,
                'title' => $service->title,
                'subtitle' => $service->subtitle,
                'price' => $service->price,
                'description' => $service->description,
                
                // Just basic counts or minimal info for the list view to keep it light
                // (You can add full nested details here if you really need them in the list)
                'processing_time' => $service->processingTimes->first()->time ?? 'N/A', 
                
                'created_at' => $service->created_at->toDateTimeString(),
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Services retrieved successfully!',
            'data' => $formattedServices, // This keeps pagination meta (current_page, etc.)
        ], 200);
    }

    // method to get the questions and required documents for a service
    public function serviceQuestions(Service $service)
    {
        // 1. Load the relationships
        $service->load(['questionaries', 'requiredDocuments']);

        // 2. Process Questions Logic
        $formattedQuestions = $service->questionaries->map(function ($q) {
            return [
                'id' => $q->id,
                'name' => $q->name,
                'type' => $q->type,
                'options' => $q->options,
                'is_required' => $q->required ?? false,
            ];
        });

        // 3. Process Documents Logic
        $formattedDocuments = $service->requiredDocuments->map(function ($doc) {
            return [
                'id' => $doc->id,
                'title' => $doc->title,
                'description' => $doc->description ?? null,
            ];
        });

        // 4. Return the clean variable answer
        return response()->json([
            'status' => true,
            'message' => 'Service requirements retrieved successfully!',
            'data' => [
                'service_id' => $service->id,
                'service_title' => $service->title,
                'questions' => $formattedQuestions,
                'required_documents' => $formattedDocuments,
            ],
        ], 200);
    }
}
