<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\{JsonResponse, Request};
use App\Http\Controllers\Controller;
use App\Models\Delivery;

class DeliveryController extends Controller
{
    /**
     * Get all available delivery options
     * GET /api/deliveries
     */
    public function list(): JsonResponse
    {
        $deliveries = Delivery::all();

        return response()->json([
            'status' => 'true',
            'message' => 'Delivery options retrieved successfully',
            'data' => $deliveries,
        ]);
    }

    /**
     * Create a new delivery option
     * POST /api/admin/deliveries
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|unique:deliveries,title',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
            ]);

            $delivery = Delivery::create($validated);

            return response()->json([
                'status' => 'true',
                'message' => 'Delivery option created successfully',
                'data' => $delivery,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'false',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'false',
                'message' => 'Failed to create delivery option',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a delivery option
     * PUT /api/admin/deliveries/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $delivery = Delivery::findOrFail($id);

            $validated = $request->validate([
                'title' => 'sometimes|nullable|string|unique:deliveries,title,' . $id,
                'description' => 'sometimes|nullable|string',
                'price' => 'sometimes|nullable|numeric|min:0',
            ]);

            // Only update fields that are not empty
            $updateData = [];
            foreach ($validated as $key => $value) {
                if ($value !== null && $value !== '') {
                    $updateData[$key] = $value;
                }
            }

            if (!empty($updateData)) {
                $delivery->update($updateData);
            }

            return response()->json([
                'status' => 'true',
                'message' => 'Delivery option updated successfully',
                'data' => $delivery->fresh(),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'false',
                'message' => 'Delivery option not found',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'false',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'false',
                'message' => 'Failed to update delivery option',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a delivery option
     * DELETE /api/admin/deliveries/{id}
     */
    public function destroy($id): JsonResponse
    {
        try {
            $delivery = Delivery::findOrFail($id);

            // Check if delivery is being used in any orders
            if ($delivery->orders()->exists()) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Cannot delete delivery option that is associated with orders',
                ], 409);
            }

            $delivery->delete();

            return response()->json([
                'status' => 'true',
                'message' => 'Delivery option deleted successfully',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'false',
                'message' => 'Delivery option not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'false',
                'message' => 'Failed to delete delivery option',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
