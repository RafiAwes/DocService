<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * 1. USER: List my own orders
     * GET /api/my-orders
     */
    public function userOrders(Request $request)
    {
        try {
            $user = Auth::user();

            // Fetch orders for THIS user only, ordered by newest first
            $orders = Order::with(['items.service', 'items.deliveryOptions'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10); // Show 10 per page

            return response()->json([
                'success' => true,
                'message' => 'User orders fetched successfully',
                'data' => $orders,
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 2. ADMIN: List ALL orders
     * GET /api/admin/orders
     */
    public function adminOrders(Request $request)
    {
        try {
            // Fetch ALL orders with User details
            // Filter by status if requested (e.g., ?status=paid)
            $query = Order::with(['user', 'items.service']);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $orders = $query->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'All orders fetched successfully',
                'data' => $orders,
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 3. SHARED: View Single Order Details
     * GET /api/orders/{id}
     */
    public function show($id)
    {
        try {
            $user = Auth::user();

            $order = Order::with(['user', 'items.service', 'items.deliveryOptions'])
                ->findOrFail($id);

            // Security: If not admin, ensure user owns this order
            // (Assuming you have a role/type column, otherwise just check ID)
            if ($user->role !== 'admin' && $order->user_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $order,
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }
    }
}
