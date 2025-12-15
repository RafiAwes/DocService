<?php

namespace App\Http\Controllers\Api;

use App\Models\Service;
// use App\Models\Category; // Not strictly needed unless you use Category model directly
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ServiceController extends Controller
{
    public function createService(Request $request)
    {
        $validated = $request->validate([
            // Service Base Data
            'category_id' => 'required|exists:categories,id',
            'title'       => 'required|string|max:255',
            'subtitle'    => 'nullable|string|max:255',
            'order_type'  => 'nullable|in:quote,checkout,null',
            'price'       => 'nullable|numeric|min:0',
            'description' => 'nullable|string',

            // Relation: Included Services
            'included_services'                   => 'nullable|array',
            'included_services.*.service_type'    => 'required_with:included_services|string',
            'included_services.*.included_details'=> 'nullable|string',
            'included_services.*.price'           => 'nullable|numeric',

            // Relation: Processing Time
            'processing_times'           => 'nullable|array',
            'processing_times.*.details' => 'nullable|string',
            'processing_times.*.time'    => 'nullable|string|max:255', // Updated to string as discussed

            // Relation: Delivery Details
            'delivery_details'                 => 'nullable|array',
            'delivery_details.*.delivery_type' => 'required_with:delivery_details|string',
            'delivery_details.*.details'       => 'required_with:delivery_details|string',
            'delivery_details.*.price'         => 'required_with:delivery_details|numeric',

            // Relation: Questionaries
            'questions'           => 'nullable|array',
            'questions.*.name'    => 'required_with:questions|string',
            'questions.*.type'    => 'required_with:questions|in:textbox,inputfield,dropdown,checkbox',
            'questions.*.options' => 'nullable|json',

            // Relation: Required Documents
            'required_documents'         => 'nullable|array',
            'required_documents.*.title' => 'required_with:required_documents|string',
        ]);

        try {
            // Start Transaction
            $service = DB::transaction(function () use ($validated) {
                // 1. Create Main Service
                $service = Service::create([
                    'category_id' => $validated['category_id'],
                    'title'       => $validated['title'],
                    'subtitle'    => $validated['subtitle'] ?? null, // Added subtitle
                    'order_type'  => $validated['order_type'] ?? 'null',
                    'price'       => $validated['price'] ?? null,
                    'description' => $validated['description'] ?? null,
                ]);

                // 2. Create Relations (using createMany for cleaner code)
                
                if (!empty($validated['included_services'])) {
                    $service->includedServices()->createMany($validated['included_services']);
                }

                if (!empty($validated['processing_times'])) {
                    $service->processingTimes()->createMany($validated['processing_times']);
                }

                if (!empty($validated['delivery_details'])) {
                    $service->deliveryDetails()->createMany($validated['delivery_details']);
                }

                if (!empty($validated['questions'])) {
                    $service->questionaries()->createMany($validated['questions']);
                }

                if (!empty($validated['required_documents'])) {
                    $service->requiredDocuments()->createMany($validated['required_documents']);
                }

                return $service;
            });

            // SUCCESS RESPONSE
            return response()->json([
                'status' => true,
                'message' => 'Service created successfully with all relations!',
                'data'    => $service->load([
                    'includedServices', 
                    'processingTimes', 
                    'deliveryDetails', 
                    'questionaries', 
                    'requiredDocuments'
                ]),
            ], 201);

        } catch (\Exception $e) {
            // ERROR RESPONSE
            // We cannot use $service here because it failed to create!
            return response()->json([
                'status' => false,
                'message' => 'Failed to create service.',
                'error'   => $e->getMessage() // This will tell you exactly what went wrong
            ], 500);
        }
    }

    /**
     * Update the specified service and its relations.
     */
    public function updateService(Request $request, $id)
    {
        // 1. Find the service first (fail fast if not found)
        $service = Service::findOrFail($id);

        // 2. Validate (Similar to create, but we can make top-level fields optional if using PATCH logic)
        // Note: For a full PUT update, we usually expect all data. 
        $validated = $request->validate([
            // Main Service Fields
            'category_id' => 'sometimes|exists:categories,id',
            'title'       => 'sometimes|string|max:255',
            'subtitle'    => 'nullable|string|max:255',
            'order_type'  => 'nullable|in:quote,checkout,null',
            'price'       => 'nullable|numeric|min:0',
            'description' => 'nullable|string',

            // Relations Validation (same rules as create)
            'included_services'                   => 'nullable|array',
            'included_services.*.service_type'    => 'required_with:included_services|string',
            'included_services.*.included_details'=> 'nullable|string',
            'included_services.*.price'           => 'nullable|numeric',

            'processing_times'           => 'nullable|array',
            'processing_times.*.details' => 'nullable|string',
            'processing_times.*.time'    => 'nullable|string|max:255',

            'delivery_details'                 => 'nullable|array',
            'delivery_details.*.delivery_type' => 'required_with:delivery_details|string',
            'delivery_details.*.details'       => 'required_with:delivery_details|string',
            'delivery_details.*.price'         => 'required_with:delivery_details|numeric',

            'questions'           => 'nullable|array',
            'questions.*.name'    => 'required_with:questions|string',
            'questions.*.type'    => 'required_with:questions|in:textbox,inputfield,dropdown,checkbox',
            'questions.*.options' => 'nullable|json',

            'required_documents'         => 'nullable|array',
            'required_documents.*.title' => 'required_with:required_documents|string',
        ]);

        try {
            DB::transaction(function () use ($service, $validated, $request) {
                
                // A. Update Main Service Data
                // verify only the fields present in the request are updated
                $service->update($request->only([
                    'category_id', 'title', 'subtitle', 'order_type', 'price', 'description'
                ]));

                // B. Update Relations
                // The logic: If the array is present in the request, we delete old ones and create new ones.
                
                // 1. Included Services
                if ($request->has('included_services')) {
                    $service->includedServices()->delete(); // Wipe old
                    if (!empty($validated['included_services'])) {
                        $service->includedServices()->createMany($validated['included_services']); // Create new
                    }
                }

                // 2. Processing Times
                if ($request->has('processing_times')) {
                    $service->processingTimes()->delete();
                    if (!empty($validated['processing_times'])) {
                        $service->processingTimes()->createMany($validated['processing_times']);
                    }
                }

                // 3. Delivery Details
                if ($request->has('delivery_details')) {
                    $service->deliveryDetails()->delete();
                    if (!empty($validated['delivery_details'])) {
                        $service->deliveryDetails()->createMany($validated['delivery_details']);
                    }
                }

                // 4. Questionaries
                if ($request->has('questions')) {
                    $service->questionaries()->delete();
                    if (!empty($validated['questions'])) {
                        $service->questionaries()->createMany($validated['questions']);
                    }
                }

                // 5. Required Documents
                if ($request->has('required_documents')) {
                    $service->requiredDocuments()->delete();
                    if (!empty($validated['required_documents'])) {
                        $service->requiredDocuments()->createMany($validated['required_documents']);
                    }
                }
            });

            // Refresh the model to get the new data from DB
            return response()->json([
                'status' => true,
                'message' => 'Service updated successfully!',
                'data'    => $service->fresh()->load([
                    'includedServices', 
                    'processingTimes', 
                    'deliveryDetails', 
                    'questionaries', 
                    'requiredDocuments'
                ]),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update service.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function deleteService($id)
    {
        $service = Service::findOrFail($id);

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
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}

