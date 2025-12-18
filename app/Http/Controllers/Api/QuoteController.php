<?php

namespace App\Http\Controllers\Api;

use App\Models\User; 
use App\Models\Quote;
use App\Models\Answers;
use App\Models\CustomQuote;
use App\Models\ServiceQuote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage; 

class QuoteController extends Controller
{
    /**
     * Create a new Quote (Custom or Service based)
     */
    public function createQuote(Request $request)
    {
        // 1. Validation
        $validated = $request->validate([
            // Common Fields
            // 'user_id' => 'required|exists:users,id',
            'type'    => 'required|string|in:custom,service',

            // Custom Quote Fields (Required if type is custom)
            'name'             => 'required_if:type,custom|nullable|string|max:255',
            'email'            => 'required_if:type,custom|nullable|email|max:255',
            'contact_number'   => 'required_if:type,custom|nullable|string|max:20',
            'document_request' => 'required_if:type,custom|nullable|string',
            'drc'              => 'required_if:type,custom|nullable|string|max:100', // Document Return Country
            'duc'              => 'required_if:type,custom|nullable|string|max:100', // Document Use Country
            'residence_country'=> 'required_if:type,custom|nullable|string|max:100',

            // Service Quote Fields (Required if type is service)
            'service_id' => 'required_if:type,service|exists:services,id',

            // Answers Fields (For Service Quotes)
            'delivery_details_ids'   => 'nullable|array', 
            'delivery_details_ids.*' => 'integer|exists:delivery_details,id',
            'south_african'          => 'nullable|boolean',
            'age'                    => 'nullable|integer',
            'about_yourself'         => 'nullable|string',
            'birth_certificate'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB Max
            'nid_card'               => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        try {
            $result = DB::transaction(function () use ($request, $validated) {
                
                // A. Create Parent Quote
                $quote = Quote::create([
                    'user_id' => $request->user()->id,
                    'type'    => $validated['type'],
                ]);

                // B. Handle "Custom" Quote Logic
                if ($validated['type'] === 'custom') {
                    CustomQuote::create([
                        'quote_id'          => $quote->id,
                        'name'              => $validated['name'],
                        'email'             => $validated['email'],
                        'contact_number'    => $validated['contact_number'],
                        'document_request'  => $validated['document_request'],
                        'drc'               => $validated['drc'],
                        'duc'               => $validated['duc'],
                        'residence_country' => $validated['residence_country'],
                    ]);
                }

                // C. Handle "Service" Quote Logic
                if ($validated['type'] === 'service') {
                    
                    // 1. Create Service Quote Link
                    $serviceQuote = ServiceQuote::create([
                        'quote_id'   => $quote->id,
                        'service_id' => $validated['service_id'],
                        'delivery_details_ids'=> $validated['delivery_details_ids'] ?? [],
                    ]);

                    // 2. Handle File Uploads (Using 'public' disk for accessibility)
                    // The 'store' method automatically creates directories. No need for manual File::makeDirectory.
                    $birthCertPath = null;
                    if ($request->hasFile('birth_certificate')) {
                        $birthCertPath = $request->file('birth_certificate')->store('documents/birth_certs', 'public'); 
                    }

                    $nidPath = null;
                    if ($request->hasFile('nid_card')) {
                        $nidPath = $request->file('nid_card')->store('documents/nids', 'public');
                    }

                    // 3. Create The Answer Record
                    Answers::create([
                        'service_quote_id'     => $serviceQuote->id,
                        'delivery_details_ids' => $validated['delivery_details_ids'] ?? [],
                        'south_african'        => $validated['south_african'] ?? false,
                        'age'                  => $validated['age'] ?? null,
                        'about_yourself'       => $validated['about_yourself'] ?? null,
                        'birth_certificate'    => $birthCertPath,
                        'nid_card'             => $nidPath,
                    ]);
                }

                return $quote;
            });

            // Return Success
            return response()->json([
                'status' => true,
                'message' => 'Quote created successfully',
                'data'    => $result->load(['customQuote', 'serviceQuote.answer'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create quote', 
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // public function updateQuote(Request $request, Quote $quote)
    // {
    //        $validated = $request->validate([
    //         // Common Fields
    //         // 'user_id' => 'required|exists:users,id',
    //         'type'    => 'required|string|in:custom,service',

    //         // Custom Quote Fields (Required if type is custom)
    //         'name'             => 'required_if:type,custom|nullable|string|max:255',
    //         'email'            => 'required_if:type,custom|nullable|email|max:255',
    //         'contact_number'   => 'required_if:type,custom|nullable|string|max:20',
    //         'document_request' => 'required_if:type,custom|nullable|string',
    //         'drc'              => 'required_if:type,custom|nullable|string|max:100', // Document Return Country
    //         'duc'              => 'required_if:type,custom|nullable|string|max:100', // Document Use Country
    //         'residence_country'=> 'required_if:type,custom|nullable|string|max:100',

    //         // Service Quote Fields (Required if type is service)
    //         'service_id' => 'required_if:type,service|exists:services,id',

    //         // Answers Fields (For Service Quotes)
    //         'delivery_details_ids'   => 'nullable|array', 
    //         'delivery_details_ids.*' => 'integer|exists:delivery_details,id',
    //         'south_african'          => 'nullable|boolean',
    //         'age'                    => 'nullable|integer',
    //         'about_yourself'         => 'nullable|string',
    //         'birth_certificate'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB Max
    //         'nid_card'               => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
    //     ]);

    //     try {
    //         $result = DB::transaction(function () use ($request, $validated, $id) {
                
    //             // A. Find Parent Quote
    //             $quote = Quote::findOrFail($quote->id);

    //             // B. Update "Custom" Quote Logic
    //             if ($quote->type === 'custom') {
    //                 $customQuote = CustomQuote::where('quote_id', $quote->id)->firstOrFail();
    //                 $customQuote->update([
    //                     'name'              => $validated['name'],
    //                     'email'             => $validated['email'],
    //                     'contact_number'    => $validated['contact_number'],
    //                     'document_request'  => $validated['document_request'],
    //                     'drc'               => $validated['drc'],
    //                     'duc'               => $validated['duc'],
    //                     'residence_country' => $validated['residence_country'],
    //                 ]);
    //             }

    //             // C. Update "Service" Quote Logic
    //             if ($quote->type === 'service') {
    //                 $serviceQuote = ServiceQuote::where('quote_id', $quote->id)->firstOrFail();
    //                 $serviceQuote->update([
    //                     'service_id' => $validated['service_id'],
    //                 ]);
    //             }

    //             return $quote;
    //         });

    //         // Return Success
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Quote updated successfully',
    //             'data'    => $result->load(['customQuote', 'serviceQuote.answer'])
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to update quote', 
    //             'error'   => $e->getMessage()
    //         ], 500);
    //     }
        
    // }

    public function deleteQuote(Request $request, Quote $quote)
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
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}