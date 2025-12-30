<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Quote;
use App\Models\Answers;
use App\Models\CustomQuote;
use App\Models\ServiceQuote;
use Illuminate\Http\Request;
use App\Models\Questionaries;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Notifications\NewQuoteRequest;
use Illuminate\Support\Facades\Notification;

class QuoteController extends Controller
{
    /**
     * Create a new Quote (Custom or Service based)
     */
    public function createCustomQuote(Request $request)
    {
        // 1. Validation
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'contact_number' => 'required|string|max:20',
            'document_request' => 'required|string',
            'drc' => 'required|string|max:100', // Document Return Country
            'duc' => 'required|string|max:100', // Document Use Country
            'residence_country' => 'required|string|max:100',
        ]);

        try {
            // 2. Create Quote and CustomQuote Records
            $quote = DB::transaction(function () use ($request, $validated) {
                // A. Create Parent Quote
                $quote = Quote::create([
                    'user_id' => $request->user()->id,
                    'type' => 'custom',
                ]);

                // B. Create Custom Quote Details
                CustomQuote::create([
                    'quote_id' => $quote->id,
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'contact_number' => $validated['contact_number'],
                    'document_request' => $validated['document_request'],
                    'drc' => $validated['drc'],
                    'duc' => $validated['duc'],
                    'residence_country' => $validated['residence_country'],
                ]);

                // sending notification to the use and admins
                $user = Auth::user();
                Notification::send($user, new NewQuoteRequest($quote));

                $admins = User::where('role', 'admin')->get();
                if ($admins->isNotEmpty()) {
                    Notification::send($admins, new NewQuoteRequest($quote));
                }


                return $quote;
            });

            // Return Success
            return response()->json([
                'status' => true,
                'message' => 'Custom quote created successfully',
                'data' => $quote->load('customQuote'),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create custom quote',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    public function createServiceQuote(Request $request)
    {
        // 1. Basic Validation
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'delivery_details_ids' => 'nullable|array',
            // Answers is now an array of objects
            'answers' => 'nullable|array',
            'answers.*.question_id' => 'required|exists:questionaries,id',
            // Value can be string OR file, so we don't strictly validate type here initially
            'answers.*.value' => 'nullable',
        ]);

        try {
            $result = DB::transaction(function () use ($request) {

                // A. Create Parent Quote
                $quote = Quote::create([
                    'user_id' => Auth::user()->id,
                    'type' => 'service',
                ]);

                // B. Create Service Quote Link
                $serviceQuote = ServiceQuote::create([
                    'quote_id' => $quote->id,
                    'service_id' => $request->service_id,
                    // Store delivery details if you still use them, otherwise remove
                    'delivery_details_ids' => $request->delivery_details_ids ?? [],
                ]);


                // C. Process Dynamic Answers
                if ($request->has('answers')) {

                    foreach ($request->answers as $index => $answerData) {

                        // Fetch the question definition to check its type (File vs Text)
                        $question = Questionaries::findOrFail($answerData['question_id']);
                        $storedValue = null;

                        // Normalize type for consistent handling
                        $type = method_exists($question, 'getAttribute') ? ($question->normalized_type ?? strtolower(str_replace(' ', '', $question->type))) : strtolower(str_replace(' ', '', $question->type));
                        // Case 1: File Upload (only if type explicitly 'file')
                        if ($type === 'file') {
                            // Check if the file exists in the request at this specific index
                            if ($request->hasFile("answers.{$index}.value")) {
                                $file = $request->file("answers.{$index}.value");
                                // Store in specific folder
                                $storedValue = $file->store('documents/quotes', 'public');
                            }
                        } elseif ($type === 'checkout') {
                            // Normalize checkbox values (true/false, 'on', '1', etc.)
                            $raw = $answerData['value'] ?? null;
                            $storedValue = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                        } else {
                            // Textbox, Input field, Drop down
                            $storedValue = $answerData['value'] ?? null;
                        }
                        // Save to Database
                        $answers = Answers::create([
                            'user_id' => Auth::id(),
                            'service_quote_id' => $serviceQuote->id,
                            'questionary_id' => $question->id,
                            'value' => $storedValue,
                        ]);

                        // sending notification to the use and admins
                        $user = Auth::user();
                        Notification::send($user, new NewQuoteRequest($quote));

                        $admins = User::where('role', 'admin')->get();
                        if ($admins->isNotEmpty()) {
                            Notification::send($admins, new NewQuoteRequest($quote));
                        }
                    }
                }

                return $quote;
            });

            // Return with eager loaded dynamic answers
            return response()->json([
                'status' => true,
                'message' => 'Quote created successfully',
                'data' => $result->load(['serviceQuote.answers.questionary']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create quote',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteQuote(Quote $quote)
    {
        try {
            $quote = Quote::findOrFail($quote->id);

            if ($quote->type === 'custom') {
                CustomQuote::where('quote_id', $quote->id)->delete();
            }
            $quote->delete();

            if ($quote->type === 'service') {
                $serviceQuote = ServiceQuote::where('quote_id', $quote->id)->first();
                if ($serviceQuote) {
                    Answers::where('service_quote_id', $serviceQuote->id)->delete();
                    $serviceQuote->delete();
                }
                $serviceQuote->delete();
            }
            $quote->delete();

            return response()->json([
                'status' => true,
                'message' => 'Quote deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete quote',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function quoteDetails(Quote $quote)
    {
        try {
            // Updated 'serviceQuote.answers' -> 'serviceQuote.answers.questionary'
            $quote = Quote::with([
                'user',
                'customQuote',
                'serviceQuote.service',
                'serviceQuote.service.category',
                'serviceQuote.answers.questionary', // <--- The Magic Change
            ])->findOrFail($quote->id);

            return response()->json([
                'status' => true,
                'message' => 'Quote fetched successfully',
                'data' => $quote,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch quote',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function customQuoteList()
    {
        try {
            $perPage = request()->query('per_page', 10);
            $quotes = CustomQuote::with(['quote.user'])->latest('id')->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Custom Quotes fetched successfully',
                'data' => $quotes,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch custom quotes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function ServiceQuoteList()
    {
        try {
            $perPage = request()->query('per_page', 10);
            $quotes = Quote::with(['user', 'serviceQuote', 'serviceQuote.service', 'serviceQuote.service.category'])->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Quotes fetched successfully',
                'data' => $quotes,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch quotes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
