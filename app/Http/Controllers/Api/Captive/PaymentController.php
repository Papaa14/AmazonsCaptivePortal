<?php

namespace App\Http\Controllers\Api\Captive;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\HotspotPackage;
use App\Models\Subscription;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PaymentController extends Controller
{

    // {
    //     Log::info('STK Callback Received:', $request->all());

    //     $callbackData = $request->input('Body')['stkCallback'];
    //     $resultCode = $callbackData['ResultCode'];
    //     $checkoutRequestID = $callbackData['CheckoutRequestID'];

    //     // Find the pending transaction using the CheckoutRequestID
    //     // This is a simplified version. You should query your 'pending_transactions' table here.
    //     // For this example, we'll assume we can find the user/package from logs (not for production).

    //     if ($resultCode == 0) {
    //         // SUCCESSFUL PAYMENT
    //         // 1. Find the details of the original transaction
    //         // **This part is crucial and requires a 'pending_transactions' table in a real app.**
    //         // Let's pretend we look it up and find the user and package.
    //         // For now, we will have to assume the callback includes enough info or we log it.
    //         // Let's assume we retrieve `$user` and `$package` based on the `$checkoutRequestID`.

    //         // This is a placeholder! You MUST implement a lookup system.
    //         // For example: $pending = PendingTransaction::where('checkout_id', $checkoutRequestID)->first();
    //         // $user = User::find($pending->user_id);
    //         // $package = HotspotPackage::find($pending->package_id);

    //         // Let's create a dummy user and package for the code to work. Replace with real logic.
    //         $metadata = $callbackData['CallbackMetadata']['Item'];
    //         $phoneNumber = collect($metadata)->firstWhere('Name', 'PhoneNumber')['Value'];
    //         $user = User::where('phone_number', $phoneNumber)->first();

    //         // You would need to pass the package_id in the AccountReference or find a way to retrieve it.
    //         // For now, let's assume a default package for demonstration.
    //         $package = HotspotPackage::find(1); // !! REPLACE WITH REAL LOOKUP !!

    //         if ($user && $package) {
    //              // 2. Generate a unique voucher code
    //             $voucherCode = Str::upper(Str::random(8)); // 8-char alphanumeric, e.g., 8A3F9B2C

    //             // 3. Create the active subscription
    //             Subscription::create([
    //                 'user_id' => $user->id,
    //                 'hotspot_package_id' => $package->id,
    //                 'voucher_code' => $voucherCode,
    //                 'activated_at' => Carbon::now(),
    //                 'expires_at' => Carbon::now()->addMinutes($package->duration_minutes),
    //             ]);

    //             Log::info("Subscription created for user {$user->id} with voucher {$voucherCode}.");
    //             // $pending->update(['status' => 'completed']);
    //         }
    //     } else {
    //         // FAILED OR CANCELLED PAYMENT
    //         Log::warning('STK Push Failed or Cancelled.', ['data' => $callbackData]);
    //         // $pending->update(['status' => 'failed']);
    //     }

    //     // Acknowledge receipt of the callback to M-Pesa
    //     return response()->json([
    //         'ResultCode' => 0,
    //         'ResultDesc' => 'The service was accepted successfully',
    //     ]);
    // }

    /**
     * Handles the C2B confirmation callback from M-Pesa.
     * This is where we capture the user's name and credit their account.
     */
    public function handleC2bConfirmation(Request $request)
    {
        Log::info('C2B Confirmation Received:', $request->all());

        try {
            // Extract data from the M-Pesa callback
            $transactionAmount = $request->input('TransAmount');
            $phoneNumber = '254' . substr($request->input('MSISDN'), -9); // Normalize

            // Get the name 
            $firstName = $request->input('FirstName');


            // Use a database transaction to ensure data integrity
            DB::transaction(function () use ($phoneNumber, $firstName, $transactionAmount) {

                // Find the user by phone number or create them if they don't exist.
                $user = User::firstOrCreate(
                    ['phone_number' => $phoneNumber]
                );


                // We now update the user in a separate, secure step.
                // This prevents overwriting an existing name and uses parameter binding         
                $user->name = $user->name ?? $firstName;
                $user->credit_points += $transactionAmount;
                $user->save();

                Log::info("C2B: Credited {$transactionAmount} to user {$user->id} ({$firstName}).");
            });
        } catch (\Exception $e) {
            Log::error('C2B Confirmation Processing Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            // Inform Safaricom that an error occurred on our end.
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'An internal server error occurred.']);
        }

        // Acknowledge receipt to Safaricom
        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'The service was accepted successfully',
        ]);
    }

    /**
     * Handles the C2B validation request from M-Pesa.
     * For this use case, we can simply accept all payments.
     */
    public function handleC2bValidation(Request $request)
    {
        Log::info('C2B Validation Received:', $request->all());

        // Acknowledge to Safaricom that the transaction is valid
        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }


    /**
     * Allows the frontend to poll for the status of a payment.
     */
    public function checkPaymentStatus($paymentReference)
    {
        if (empty($paymentReference)) {
            return response()->json(['status' => 'error', 'message' => 'Payment reference is required'], 400);
        }

        // Find the pending payment by its reference (CheckoutRequestID)
        $transaction = DB::table('pending_payments')->where('payment_reference', $paymentReference)->first();

        if (!$transaction) {
            return response()->json(['status' => 'pending', 'message' => 'Transaction not found.'], 404);
        }
        // Check if  the payment is complete. Now, find the subscription that was created from it.
        // We match by user_id and ensure it was created after the payment attempt.

        if ($transaction->status === 'completed') {


            $subscription = Subscription::where('user_id', $transaction->user_id)
                ->where('hotspot_package_id', $transaction->hotspot_package_id)
                ->where('created_at', '>=', $transaction->updated_at) // Match subscription created after payment was marked complete
                ->latest() // Get the most recent one in case of duplicates
                ->first();

            if (!$subscription) {
                // This is a rare edge case where the callback has finished but the subscription wasn't found.
                // It's safest to tell the frontend to keep waiting for a moment.
                return response()->json(['status' => 'pending', 'message' => 'Finalizing subscription...']);
            }

            // Success! Return the completed status AND the voucher code.
            return response()->json([
                'status' => 'completed',
                'message' => 'Payment successful. Subscription is active.',
                'voucher_code' => $subscription->voucher_code, // <-- The key piece of information
            ]);
        }

        // For any other status ('pending', 'failed'), return the status as before.
        return response()->json([
            'status' => $transaction->status,
            'message' => "The transaction status is {$transaction->status}.",
        ]);
    }
    public function handleSafaricomCallback(Request $request)
    {
        // 1. Log the entire incoming request for debugging purposes.
        Log::info('Safaricom Callback Received:', $request->all());

        // Use optional() and dot notation to safely access nested data.
        $callbackData = optional($request->input('Body'))['stkCallback'];

        // 2. Perform initial validation on the callback data.
        if (!$callbackData || !isset($callbackData['CheckoutRequestID'])) {
            Log::error('Invalid callback format received from Safaricom.');
            // Respond with an error but don't crash.
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Invalid callback format']);
        }

        $checkoutRequestID = $callbackData['CheckoutRequestID'];
        $resultCode = $callbackData['ResultCode'];

        // 3. Use a database transaction for atomicity. All or nothing.
        try {
            DB::transaction(function () use ($checkoutRequestID, $resultCode, $callbackData) {

                // 4. Find the original transaction in your `pending_payments` table.
                $pendingPayment = DB::table('pending_payments')
                    ->where('payment_reference', $checkoutRequestID)
                    ->first();

                if (!$pendingPayment) {
                    Log::error('Callback received for an unknown CheckoutRequestID.', ['id' => $checkoutRequestID]);
                    // Stop processing if we don't know this transaction.
                    return;
                }

                // 5. Check for Idempotency: Has this transaction already been completed?
                if ($pendingPayment->status === 'completed') {
                    Log::info('Duplicate callback received for an already completed transaction.', ['id' => $checkoutRequestID]);
                    // Acknowledge the callback but do nothing else.
                    return;
                }

                // 6. Handle the result based on the ResultCode.
                if ($resultCode == 0) {
                    // --- PAYMENT WAS SUCCESSFUL ---

                    // 7. Get the package details to determine the subscription duration.
                    $package = HotspotPackage::find($pendingPayment->hotspot_package_id);
                    if (!$package) {
                        // This is a critical error, so we throw an exception to roll back the transaction.
                        throw new \Exception("Package with ID {$pendingPayment->hotspot_package_id} not found.");
                    }

                    // 8. Generate a unique voucher code for the subscription.
                    $voucherCode = Str::upper(Str::random(8));

                    // 9. Create the user's active subscription.
                    Subscription::create([
                        'user_id' => $pendingPayment->user_id,
                        'hotspot_package_id' => $package->id,
                        'voucher_code' => $voucherCode,
                        'activated_at' => now(),
                        'expires_at' => now()->addMinutes($package->duration_minutes),
                    ]);

                    // 10. Mark the payment as completed in your database.
                    DB::table('pending_payments')->where('id', $pendingPayment->id)->update(['status' => 'completed']);
                    Log::info('Subscription created successfully.', ['CheckoutRequestID' => $checkoutRequestID, 'voucher' => $voucherCode]);
                } else {
                    // --- PAYMENT FAILED OR WAS CANCELLED BY THE USER ---
                    DB::table('pending_payments')->where('id', $pendingPayment->id)->update(['status' => 'failed']);
                    Log::warning('Payment failed or was cancelled.', [
                        'CheckoutRequestID' => $checkoutRequestID,
                        'ResultCode' => $resultCode,
                        'ResultDesc' => $callbackData['ResultDesc'] ?? 'No description'
                    ]);
                }
            });
        } catch (\Exception $e) {
            // If anything goes wrong inside the transaction, log the detailed error.
            Log::error('Error processing Safaricom callback: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'CheckoutRequestID' => $checkoutRequestID
            ]);
            // Return an error response to Safaricom
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'An internal server error occurred.']);
        }

        // 11. Finally, send a success acknowledgement response back to Safaricom.
        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'The service was accepted successfully',
        ]);
    }
}
