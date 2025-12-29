<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Notifications\OrderCompleted;

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

            $perPage = $request->query('per_page', 10);
            $status = $request->query('status');

            // Fetch orders for THIS user only, ordered by newest first
            $orders = Order::with([
                'items.service',
                'items.service.category',
                'items.deliveryOptions',
                'items.answers',
                'items.answers.questionary',
                'transactions',
                'rating'
            ])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->when($status, function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->paginate($perPage); // Show 10 per page

            return response()->json([
                'status' => true,
                'message' => 'User orders fetched successfully',
                'data' => $orders,
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * ADMIN: List Orders (Filterable & Searchable)
     * GET /api/admin/orders?status=completed
     * GET /api/admin/orders?status=pending
     * GET /api/admin/orders?search=839201
     */
    public function adminOrders(Request $request)
    {
        try {
            // 1. Start with the Base Query and Eager Load EVERYTHING
            // We include 'answer' because that contains the user's specific inputs (Age, Docs)
            $query = Order::with([
                'user',
                'answer',
                'items.service',
                'items.service.category',
                'items.service.requiredDocuments',
                'items.service.processingTimes',
                'items.service.includedServices',
                'items.service.deliveryDetails',
                'items.deliveryOptions',
                'items.answers',
                'items.answers.questionary',
                'transactions',
            ]);

            // 2. SEARCH Logic (Search by orderid)
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                // Using 'like' allows for partial matches (e.g. searching "839" finds "839201")
                $query->where('orderid', 'like', "%{$searchTerm}%");
            }

            // 3. STATUS Logic (The "Switch")
            if ($request->has('status')) {
                if ($request->status === 'completed') {
                    // Strict check for completed
                    $query->where('status', 'completed');
                } elseif ($request->status === 'pending') {
                    // "Pending" view includes both 'pending' (unpaid) and 'paid' (processing)
                    // basically anything that is NOT completed
                    $query->whereIn('status', ['pending', 'paid']);
                }
            }

            // 4. Order & Pagination
            $perPage = $request->query('per_page', 10);
            $orders = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Orders fetched successfully',
                'data' => $orders,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch orders',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 3. SHARED: View Single Order Details
     * GET /api/orders/{id}
     */
    public function details($id)
    {
        try {
            $user = Auth::user();

            // Allow lookup by numeric id or public orderid
            $order = Order::with([
                'user',
                'items.service',
                'items.service.category',
                'items.service.requiredDocuments',
                'items.service.processingTimes',
                'items.service.includedServices',
                'items.service.questionaries',
                'items.service.questionaries.answers',
                'items.service.deliveryDetails',
                'items.deliveryOptions',
                'items.answers',
                'items.answers.questionary',
                'transactions',
            ])
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('orderid', $id);
            })
            ->firstOrFail();

            // Security: If not admin, ensure user owns this order
            // (Assuming you have a role/type column, otherwise just check ID)
            if ($user->role !== 'admin' && $order->user_id !== $user->id) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
            }

            return response()->json([
                'status' => true,
                'data' => $order,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['status' => false, 'message' => 'Order not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to fetch order', 'error' => $e->getMessage()], 500);
        }
    }

    public function completeOrder(Request $request, $orderId)
    {
        try {
            $order = Order::findOrFail($orderId);

            // Update order status to 'completed'
            $order->status = 'completed';
            $order->save();

            // Send Notification to User
            $user = $order->user;
            $order->user->notify(new OrderCompleted($order));

            return response()->json([
                'status' => true,
                'message' => 'Order completed successfully',
                'data' => $order,
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // retrive Completed Orders
    // private function getCompletedOrders()
    // {
    //     $perPage = request()->query('per_page', 10);
    //     $completedOrders = Order::with([
    //         'user',
    //         'items.service',
    //         'items.service.category',
    //         'items.service.requiredDocuments',
    //         'items.service.processingTimes',
    //         'items.service.includedServices',
    //         'items.service.questionaries',
    //         'items.service.questionaries.answers',
    //         'items.service.deliveryDetails'
    //     ])
    //         ->where('status', 'completed')
    //         ->orderBy('created_at', 'desc')
    //         ->paginate($perPage);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Completed orders fetched successfully',
    //         'data' => $completedOrders,
    //     ]);
    // }

    // retrive pending orders

    // private function pendingOrders()
    // {
    //     $perPage = request()->query('per_page', 10);
    //     $pendingOrders = Order::with([
    //         'user',
    //         'items.service',
    //         'items.service.category',
    //         'items.service.requiredDocuments',
    //         'items.service.processingTimes',
    //         'items.service.includedServices',
    //         'items.service.questionaries',
    //         'items.service.questionaries.answers',
    //         'items.service.deliveryDetails'
    //     ])
    //         ->where('status', 'pending')
    //         ->orderBy('created_at', 'desc')
    //         ->paginate($perPage);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Pending orders fetched successfully',
    //         'data' => $pendingOrders,
    //     ]);
    // }


    public function transactionsHistory(Request $request)
    {
        try {
            $user = Auth::user();

            $perPage = $request->query('per_page', 10);

            $transactions = Transaction::with('order.items.service')->where('user_id',Auth::user()->id)->latest('id')->paginate($perPage);
    
            return response()->json([
                'status' => true,
                'message' => 'Transaction history fetched successfully',
                'data' => $transactions,
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

}
