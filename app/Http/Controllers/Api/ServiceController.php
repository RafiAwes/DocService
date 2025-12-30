<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
// use App\Models\Category; // Not strictly needed unless you use Category model directly
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
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

            // Relation: Included Services
            'included_services' => 'nullable|array',
            'included_services.*.service_type' => 'required_with:included_services|string',
            'included_services.*.included_details' => 'nullable|string',
            'included_services.*.price' => 'nullable|numeric',

            // Relation: Processing Time
            'processing_times' => 'nullable|array',
            'processing_times.*.details' => 'nullable|string',
            'processing_times.*.time' => 'nullable|string|max:255', // Updated to string as discussed

            // Relation: Delivery Details
            'delivery_details' => 'nullable|array',
            'delivery_details.*.delivery_type' => 'required_with:delivery_details|string',
            'delivery_details.*.details' => 'required_with:delivery_details|string',
            'delivery_details.*.price' => 'required_with:delivery_details|numeric',

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
            $service = DB::transaction(function () use ($validated) {
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
                ]);

                // 2. Create Relations (using createMany for cleaner code)

                if (! empty($validated['included_services'])) {
                    $service->includedServices()->createMany($validated['included_services']);
                }

                if (! empty($validated['processing_times'])) {
                    $service->processingTimes()->createMany($validated['processing_times']);
                }

                if (! empty($validated['delivery_details'])) {
                    $service->deliveryDetails()->createMany($validated['delivery_details']);
                }

                if (! empty($validated['questions'])) {
                    $service->questionaries()->createMany($validated['questions']);
                }

                if (! empty($validated['required_documents'])) {
                    $service->requiredDocuments()->createMany($validated['required_documents']);
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
                    'deliveryDetails',
                    'questionaries',
                    'requiredDocuments',
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
            'title' => 'sometimes|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'order_type' => 'nullable|in:quote,checkout,null',
            'type' => 'nullable|in:Quote,Checkout',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',

            // 1. Included Services
            'included_services' => 'nullable|array',
            'included_services.*.id' => 'nullable|integer|exists:included_services,id', // <--- CRITICAL
            'included_services.*.service_type' => 'required_with:included_services|string',
            'included_services.*.included_details' => 'nullable|string',
            'included_services.*.price' => 'nullable|numeric',

            // 2. Processing Times
            'processing_times' => 'nullable|array',
            'processing_times.*.id' => 'nullable|integer|exists:processing_times,id',
            'processing_times.*.details' => 'nullable|string',
            'processing_times.*.time' => 'nullable|string|max:255',

            // 3. Delivery Details
            'delivery_details' => 'nullable|array',
            'delivery_details.*.id' => 'nullable|integer|exists:delivery_details,id',
            'delivery_details.*.delivery_type' => 'required_with:delivery_details|string',
            'delivery_details.*.details' => 'required_with:delivery_details|string',
            'delivery_details.*.price' => 'required_with:delivery_details|numeric',

            // 4. Questionaries
            'questions' => 'nullable|array',
            'questions.*.id' => 'nullable|integer|exists:questionaries,id',
            'questions.*.name' => 'required_with:questions|string',
            // Align with new types (capitalized with spaces)
            'questions.*.type' => 'required_with:questions|in:Textbox,Input field,Drop down,Check box',
            'questions.*.options' => 'nullable|json',

            // 5. Required Documents
            'required_documents' => 'nullable|array',
            'required_documents.*.id' => 'nullable|integer|exists:required_documents,id',
            'required_documents.*.title' => 'required_with:required_documents|string',
        ]);

        try {
            DB::transaction(function () use ($service, $request, $validated) {

                // A. Update Main Service Data
                // Only updates fields provided in the request
                $service->update($request->only([
                    'category_id', 'title', 'subtitle', 'order_type', 'type', 'price', 'description',
                ]));

                /**
                 * Helper Function for "Smart Upsert"
                 *
                 * * @param string $relationName (The method name in Service Model)
                 * @param  array  $items  (The data array from request)
                 */
                $syncRelation = function ($relationName, $items) use ($service) {
                    if (is_null($items)) {
                        return;
                    } // If key not sent, do nothing

                    // 1. Identify IDs to Keep
                    // Collect all IDs present in the request. Any ID in DB but NOT here will be deleted.
                    $keepIds = collect($items)->pluck('id')->filter()->toArray();

                    // 2. Delete Missing Rows
                    $service->{$relationName}()->whereNotIn('id', $keepIds)->delete();

                    // 3. Update or Create Rows
                    foreach ($items as $item) {
                        $service->{$relationName}()->updateOrCreate(
                            ['id' => $item['id'] ?? null], // Search by ID (if null, it creates)
                            $item // Data to save
                        );
                    }
                };

                // B. Apply Sync to All Relations
                // We check $request->has() so we don't accidentally wipe data if the array wasn't sent at all.
                if ($request->has('included_services')) {
                    $syncRelation('includedServices', $validated['included_services'] ?? []);
                }

                if ($request->has('processing_times')) {
                    $syncRelation('processingTimes', $validated['processing_times'] ?? []);
                }

                if ($request->has('delivery_details')) {
                    $syncRelation('deliveryDetails', $validated['delivery_details'] ?? []);
                }

                if ($request->has('questions')) {
                    $syncRelation('questionaries', $validated['questions'] ?? []);
                }

                if ($request->has('required_documents')) {
                    $syncRelation('requiredDocuments', $validated['required_documents'] ?? []);
                }
            });

            // Refresh model to get updated relations
            return response()->json([
                'status' => true,
                'message' => 'Service updated successfully!',
                'data' => $service->fresh()->load([
                    'includedServices',
                    'processingTimes',
                    'deliveryDetails',
                    'questionaries',
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

        if ($request->filled('search')) {
            // 1. Remove accidental spaces from start/end
            $searchTerm = trim($request->search);

            // 2. Use LOWER() for case-insensitive matching
            // This works on MySQL, PostgreSQL, and SQLite safely
            $query->whereRaw('LOWER(title) LIKE ?', ['%'.strtolower($searchTerm).'%']);
        }

        $perPage = request()->query('per_page', 10);

        $services = $query->with([
            'includedServices',
            'processingTimes',
            'deliveryDetails',
            'questionaries',
            'requiredDocuments',
            'category',
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
            'deliveryDetails',
            'questionaries',
            'requiredDocuments',
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
            ->where('is_south_african', $isSouthAfrican);

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
            'deliveryDetails',
            'questionaries',
            'requiredDocuments',
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
                'delivery_options_count' => $service->deliveryDetails->count(),
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
