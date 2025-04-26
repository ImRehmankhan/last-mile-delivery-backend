<?php

namespace App\Http\Controllers;

use App\Models\DeliveryBoy;
use App\Models\DeliveryBoyRejectionReason;
use App\Models\LMDUser;
use App\Models\Address;
use App\Models\Vehicle;
use App\Models\SubOrder;
use App\Models\LocationTracking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class DeliveryBoyController extends Controller {

    

    public function signup(Request $request)
    {
        Log::info('Request data:', $request->all());
        try {
            // Validate the request data
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'email' => 'required|email|unique:lmd_users,email',
                'phone_no' => 'required|string|max:15|unique:lmd_users,phone_no',
                'password' => 'required|string|min:1', 
                'cnic' => 'required|string|max:15|unique:lmd_users,cnic',
                'profile_picture' => 'required|file|mimes:jpeg,png,jpg|',
                'license_no' => 'required|string|max:20|unique:deliveryboys,license_no',
                'license_expiration_date' => 'nullable|date',
                'license_front' => 'required|file|mimes:jpeg,png,jpg|',
                'license_back' => 'required|file|mimes:jpeg,png,jpg|',
                'address_type' => 'required|string|max:50',
                'street' => 'required|string|max:255',
                'city' => 'required|string|max:100',
                'zip_code' => 'nullable|string|max:10',
                'country' => 'nullable|string|max:100',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'organization_id' => 'nullable|integer'
            ]);
    
            DB::beginTransaction();
    
            $userId = DB::table('lmd_users')->insertGetId([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone_no' => $validated['phone_no'],
                'password' => ($validated['password']), 
                'cnic' => $validated['cnic'],
                'lmd_user_role' => 'deliveryboy',
                'account_creation_date' => now(),
                'profile_picture' => $request->file('profile_picture')->store('deliveryboyImages', 'public'),
            ]);
    
         
            $deliveryBoyId = DB::table('deliveryboys')->insertGetId([
                'total_deliveries' => 0,
                'license_no' => $validated['license_no'],
                'license_expiration_date' => $validated['license_expiration_date'],
                'status' => 'Available',
                'approval_status' => 'pending',
                'license_front' => $request->file('license_front')->store('deliveryboyImages', 'public'),
                'license_back' => $request->file('license_back')->store('deliveryboyImages', 'public'),
                'lmd_users_ID' => $userId,
                'organization_id' => $validated['organization_id'],
            ]);
    
            $addressId = DB::table('addresses')->insertGetId([
                'address_type' => $validated['address_type'],
                'street' => $validated['street'],
                'city' => $validated['city'],
                'zip_code' => $validated['zip_code'],
                'country' => $validated['country'] ?? 'Pakistan',
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'lmd_users_ID' => $userId,
            ]);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Delivery boy signed up successfully',
                'delivery_boy_id' => $deliveryBoyId,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Signup failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    

    
    public function getDeliveryBoyData($id)
    {
        $baseUrl = url('/'); // e.g., http://yourdomain.com
    
        $deliveryBoy = DB::table('deliveryboys')
            ->join('lmd_users', 'deliveryboys.lmd_users_ID', '=', 'lmd_users.id')
            ->leftJoin('addresses', 'addresses.lmd_users_ID', '=', 'lmd_users.id')
            ->where('lmd_users.id', $id)
            ->where('lmd_users.lmd_user_role', 'deliveryboy')
            ->select(
                'deliveryboys.id as delivery_boy_id',
                'lmd_users.name',
                'lmd_users.email',
                'lmd_users.phone_no',
                'lmd_users.cnic',
                'lmd_users.id as lmd_id',
                'lmd_users.lmd_user_role',
                DB::raw("CASE 
                            WHEN lmd_users.profile_picture IS NULL OR lmd_users.profile_picture = '' 
                            THEN NULL 
                            ELSE CONCAT('$baseUrl/storage/', lmd_users.profile_picture) 
                         END as profile_picture"),
                'deliveryboys.license_no',
                'deliveryboys.license_expiration_date',
                DB::raw("CASE 
                            WHEN deliveryboys.license_front IS NULL OR deliveryboys.license_front = '' 
                            THEN NULL 
                            ELSE CONCAT('$baseUrl/storage/', deliveryboys.license_front) 
                         END as license_front"),
                DB::raw("CASE 
                            WHEN deliveryboys.license_back IS NULL OR deliveryboys.license_back = '' 
                            THEN NULL 
                            ELSE CONCAT('$baseUrl/storage/', deliveryboys.license_back) 
                         END as license_back"),
                'deliveryboys.status',
                'deliveryboys.approval_status',
                'addresses.address_type',
                'addresses.street',
                'addresses.city',
                'addresses.zip_code',
                'addresses.country',
                'addresses.latitude',
                'addresses.longitude'
            )
            ->first();
    
        if ($deliveryBoy) {
            return response()->json($deliveryBoy, 200);
        }
    
        return response()->json(['error' => 'Delivery boy not found'], 404);
    }
    
    
    public function updateDeliveryBoy(Request $request, $id)
    {
        $user = LmdUser::find($id);
        $deliveryBoy = DeliveryBoy::where('lmd_users_ID', $id)->first();
        $address = Address::where('lmd_users_ID', $id)->first();
    
        if (!$user || !$deliveryBoy || !$address) {
            return response()->json(['message' => 'Data not found'], 404);
        }
    
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|max:255|unique:lmd_users,email,' . $id,
            'phone_no' => 'sometimes|string|max:15|unique:lmd_users,phone_no,' . $id,
            'password' => 'sometimes|string|min:1',
            'cnic' => 'sometimes|string|max:15|unique:lmd_users,cnic,' . $id,
            'license_no' => 'sometimes|string|max:20|unique:deliveryboys,license_no,' . $deliveryBoy->id,
            'license_expiration_date' => 'sometimes|nullable|date',
            'address_type' => 'sometimes|string|max:50',
            'street' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:100',
            'zip_code' => 'sometimes|nullable|string|max:10',
            'country' => 'sometimes|nullable|string|max:100',
            'latitude' => 'sometimes|nullable|numeric',
            'longitude' => 'sometimes|nullable|numeric',
            'license_front' => 'sometimes|file',
            'license_back' => 'sometimes|file',
            'profile_picture' => 'sometimes|file',
        ]);
    
        DB::beginTransaction();
    
        try {
            $user->fill([
                'name' => $validatedData['name'] ?? $user->name,
                'email' => $validatedData['email'] ?? $user->email,
                'phone_no' => $validatedData['phone_no'] ?? $user->phone_no,
                'cnic' => $validatedData['cnic'] ?? $user->cnic,
            ]);
    
            if (isset($validatedData['password'])) {
                $user->password = ($validatedData['password']);
            }
    
            if ($request->hasFile('profile_picture')) {
                if ($user->profile_picture) {
                    Storage::disk('public')->delete($user->profile_picture);
                }
                $user->profile_picture = $request->file('profile_picture')->store('deliveryboyImages', 'public');
            }

            Log::debug('User Profile Picture: ' . $user->profile_picture);
            $user->save();
            $deliveryBoy->fill([
                'license_no' => $validatedData['license_no'] ?? $deliveryBoy->license_no,
                'license_expiration_date' => $validatedData['license_expiration_date'] ?? $deliveryBoy->license_expiration_date,
            ]);
            if ($request->hasFile('license_front')) {
                if ($deliveryBoy->license_front) {
                    Storage::disk('public')->delete($deliveryBoy->license_front);
                }
                $deliveryBoy->license_front = $request->file('license_front')->store('deliveryboyImages', 'public');
            }
    
            
            if ($request->hasFile('license_back')) {
                if ($deliveryBoy->license_back) {
                    Storage::disk('public')->delete($deliveryBoy->license_back);
                }
                $deliveryBoy->license_back = $request->file('license_back')->store('deliveryboyImages', 'public');
            }
    
            Log::debug('Delivery Boy License Front: ' . $deliveryBoy->license_front);
            Log::debug('Delivery Boy License Back: ' . $deliveryBoy->license_back);
    
            $deliveryBoy->save();
            $address->fill([
                'address_type' => $validatedData['address_type'] ?? $address->address_type,
                'street' => $validatedData['street'] ?? $address->street,
                'city' => $validatedData['city'] ?? $address->city,
                'zip_code' => $validatedData['zip_code'] ?? $address->zip_code,
                'country' => $validatedData['country'] ?? $address->country,
                'latitude' => $validatedData['latitude'] ?? $address->latitude,
                'longitude' => $validatedData['longitude'] ?? $address->longitude,
            ]);
            $address->save();
    
            DB::commit();

            $uploadedImages = [
                'profile_picture' => $user->profile_picture ? asset('storage/' . $user->profile_picture) : null,
                'license_front' => $deliveryBoy->license_front ? asset('storage/' . $deliveryBoy->license_front) : null,
                'license_back' => $deliveryBoy->license_back ? asset('storage/' . $deliveryBoy->license_back) : null,
            ];

            Log::debug('Uploaded Images: ', $uploadedImages);
    
            return response()->json([
                'message' => 'Updated successfully',
                'user' => $user,
                'deliveryBoy' => $deliveryBoy,
                'address' => $address,
                'uploadedImages' => $uploadedImages,
            ], 200);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Update failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    

    public function addAddress( Request $request, $deliveryBoyId ) {
        $validated = $request->validate( [
            'address_type' => 'required|string|max:50',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'zip_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ] );

        DB::table( 'addresses' )->insert( [
            'address_type' => $validated[ 'address_type' ],
            'street' => $validated[ 'street' ],
            'city' => $validated[ 'city' ],
            'zip_code' => $validated[ 'zip_code' ],
            'country' => $validated[ 'country' ] ?? 'Pakistan',
            'latitude' => $validated[ 'latitude' ],
            'longitude' => $validated[ 'longitude' ],
            'lmd_users_ID' => $deliveryBoyId,
            'created_at' => now(),
            'updated_at' => now(),
        ] );

        return response()->json( [ 'message' => 'Address added successfully' ], 201 );
    }

    public function updateAddress( Request $request, $deliveryBoyId, $addressId ) {
        $validated = $request->validate( [
            'address_type' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ] );

        $deliveryBoy = DB::table( 'deliveryboys' )
        ->where( 'ID', $deliveryBoyId )
        ->first();

        if ( !$deliveryBoy ) {
            return response()->json( [ 'message' => 'DeliveryBoy not found' ], 404 );
        }

        $address = DB::table( 'addresses' )
        ->where( 'ID', $addressId )
        ->where( 'lmd_users_ID', $deliveryBoy->lmd_users_ID )
        ->first();

        if ( !$address ) {
            return response()->json( [ 'message' => 'Address not found or does not belong to this deliveryboy' ], 404 );
        }

        DB::table( 'addresses' )
        ->where( 'ID', $addressId )
        ->update( array_merge( $validated, [
            'updated_at' => now(),
        ] ) );

        return response()->json( [ 'message' => 'Address updated successfully' ], 200 );
    }

    public function deleteAddress( $deliveryBoyId, $addressId ) {
        $deliveryBoy = DB::table( 'deliveryboys' )
        ->where( 'ID', $deliveryBoyId )
        ->first();

        if ( !$deliveryBoy ) {
            return response()->json( [ 'message' => 'DeliveryBoy not found' ], 404 );
        }

        $address = DB::table( 'addresses' )
        ->where( 'ID', $addressId )
        ->where( 'lmd_users_ID', $deliveryBoy->lmd_users_ID )
        ->first();

        if ( !$address ) {
            return response()->json( [ 'message' => 'Address not found or does not belong to this deliveryBoyId' ], 404 );
        }

        DB::table( 'addresses' )
        ->where( 'ID', $addressId )
        ->delete();

        return response()->json( [ 'message' => 'Address deleted successfully' ], 200 );
    }

    public function getAllAddresses( $deliveryBoyId ) {
        $addresses = DB::table( 'addresses' )
        ->where( 'lmd_users_ID', $deliveryBoyId )
        ->get();

        if ( $addresses->isEmpty() ) {
            return response()->json( [ 'message' => 'No addresses found for this deliveryBoy' ], 404 );
        }

        return response()->json( [ 'addresses' => $addresses ], 200 );
    }

  
    public function updateDeliveryBoyStatus($id)
    {
        DB::beginTransaction();
    
        try {
            $deliveryBoy = DB::table('deliveryboys')->where('id', $id)->first();
    
            if (!$deliveryBoy) {
                return response()->json(['message' => 'Delivery Boy not found.'], 404);
            }
    
            Log::info("Current Delivery Boy Status: " . $deliveryBoy->status);
    
            $newStatus = $deliveryBoy->status === 'Available' ? 'Not Available' : 'Available';
    
            if ($newStatus === 'Not Available') {
                DB::table('deliveryboyslogs')->insert([
                    'status' => 'OFF',
                    'start_time' => now(),
                    'deliveryboys_ID' => $id,
                ]);
            } elseif ($newStatus === 'Available') {
                DB::table('deliveryboyslogs')
                    ->where('deliveryboys_ID', $id)
                    ->where('status', 'OFF')
                    ->orderBy('start_time', 'desc')
                    ->limit(1)
                    ->update([
                        'status' => 'ON',
                        'end_time' => now(),
                    ]);
            }
    
            DB::table('deliveryboys')->where('id', $id)->update([
                'status' => $newStatus,
            ]);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Delivery Boy status updated successfully!',
                'delivery_boy' => [
                    'id' => $deliveryBoy->id,
                    'previous_status' => $deliveryBoy->status,
                    'new_status' => $newStatus,
                ],
            ], 200);
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update delivery boy status for ID: $id. Error: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update delivery boy status.', 'error' => $e->getMessage()], 500);
        }
    }
    
    

    public function addVehicle( Request $request, $deliveryBoyId ) {
        $validated = $request->validate( [
            'plate_no' => 'required|string|max:20|unique:vehicles',
            'color' => 'nullable|string|max:50',
            'vehicle_type' => 'required|string|max:50',
            'model' => 'nullable|string|max:50',
        ] );

        $vehicle = Vehicle::create( [
            'plate_no' => $validated[ 'plate_no' ],
            'color' => $validated[ 'color' ],
            'vehicle_type' => $validated[ 'vehicle_type' ],
            'model' => $validated[ 'model' ],
            'deliveryboys_ID' => $deliveryBoyId,
        ] );

        return response()->json( [
            'message' => 'Vehicle added successfully',
            'vehicle' => $vehicle,
        ], 201 );
    }

    public function getVehiclesByDeliveryBoy($deliveryBoyId)
    {
        
        $deliveryBoy = DB::table('deliveryboys')->where('ID', $deliveryBoyId)->first();
    
        if (!$deliveryBoy) {
            return response()->json(['message' => 'Delivery Boy not found'], 404);
        }
    
        $vehicles = Vehicle::where('deliveryboys_ID', $deliveryBoyId)->get();
    
        if ($vehicles->isEmpty()) {
            return response()->json(['message' => 'No vehicles found for this delivery boy'], 404);
        }
    
        return response()->json([
            'message' => 'Vehicles retrieved successfully',
            'vehicles' => $vehicles,
        ], 200);
    }
    

    public function updateVehicle( Request $request,  $deliveryBoyId, $vehicleId ) {
        $validated = $request->validate( [
            'plate_no' => 'nullable|string|max:20|unique:vehicles,plate_no,' . $vehicleId,
            'color' => 'nullable|string|max:50',
            'vehicle_type' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:50',
        ] );

        $deliveryBoy = DB::table( 'deliveryboys' )->where( 'ID', $deliveryBoyId )->first();
        if ( !$deliveryBoy ) {
            return response()->json( [ 'message' => 'Delivery Boy not found' ], 404 );
        }
        $vehicle = Vehicle::where( 'id', $vehicleId )
        ->where( 'deliveryboys_ID', $deliveryBoyId )
        ->first();

        if ( !$vehicle ) {
            return response()->json( [ 'message' => 'Vehicle not found or does not belong to this delivery boy' ], 404 );
        }

        $vehicle->update( $validated );

        return response()->json( [
            'message' => 'Vehicle updated successfully',
            'vehicle' => $vehicle,
        ] );
    }

    public function deleteVehicle( $deliveryBoyId, $vehicleId ) {
        $vehicle = Vehicle::where( 'id', $vehicleId )
        ->where( 'deliveryboys_ID', $deliveryBoyId )
        ->firstOrFail();
        $vehicle->delete();

        return response()->json( [
            'message' => 'Vehicle deleted successfully',
        ] );
    }


        /////////////////////////////////////////////////////////////////////
    ////////////////////////    COURIER ORDER    ////////////////////////
    ////////////////////////////////////////////////////////////////////

public function getPendingCourierOrders()
{
    $pendingOrders = DB::table('courierorder')
        ->where('order_status', 'pending')
        ->get();

    foreach ($pendingOrders as $order) {
       
        $order->pickup_address = DB::table('addresses')
            ->where('id', $order->pickup_address_ID)
            ->first();

        $order->delivery_address = DB::table('addresses')
            ->where('id', $order->delivery_address_ID)
            ->first();

       
        $order->additionalInfo = DB::table('courieradditionalinfo')
            ->where('courierorder_ID', $order->id)
            ->first();

        $order->item = DB::table('courieritem')
            ->join('courieritemcategory', 'courieritem.courieritemcategory_ID', '=', 'courieritemcategory.id')
            ->select(
                'courieritem.id as item_id',
                'courieritem.item_name',
                'courieritem.additonal_info',
                'courieritemcategory.category_name'
            )
            ->where('courieritem.id', $order->item_ID) 
            ->first(); 
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Pending courier orders retrieved successfully!',
        'orders' => $pendingOrders,
    ]);
}




public function assignCourierOrderToDeliveryBoy($courierOrderId, $deliveryboys_ID)
{
    $deliveryBoyExists = DB::table('deliveryboys')->where('id', $deliveryboys_ID)->exists();
    if (!$deliveryBoyExists) {
        return response()->json([
            'status' => 'error',
            'message' => 'Delivery boy not found.'
        ], 404);
    }

    $courierOrder = DB::table('courierorder')
        ->join('addresses', 'courierorder.pickup_address_ID', '=', 'addresses.id')
        ->select(
            'courierorder.id as courierOrderId',
            'courierorder.order_status',
            'addresses.latitude',
            'addresses.longitude'
        )
        ->where('courierorder.id', $courierOrderId)
        ->where('courierorder.order_status', 'pending')
        ->first();

    if (!$courierOrder) {
        return response()->json([
            'status' => 'error',
            'message' => 'Courier order not found or already assigned.'
        ], 404);
    }

    DB::table('courierorder')
        ->where('id', $courierOrderId)
        ->update([
            'deliveryboys_ID' => $deliveryboys_ID,
            'order_status' => 'assigned',
        ]);

    DB::table('courierlivetracking')->insert([
        'courierorder_ID' => $courierOrderId,
        'latitude' => $courierOrder->latitude,
        'longitude' => $courierOrder->longitude,
        'time_stamp' => now(),
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Courier order assigned to delivery boy successfully!',
    ]);
}


public function markOrderAsPicked($courierOrderId, $deliveryBoyId)
{
    $deliveryBoyExists = DB::table('deliveryboys')->where('id', $deliveryBoyId)->exists();
    if (!$deliveryBoyExists) {
        return response()->json([
            'status' => 'error',
            'message' => 'Delivery boy not found.'
        ], 404);
    }

   
    $courierOrder = DB::table('courierorder')
        ->where('id', $courierOrderId)
        ->where('deliveryboys_ID', $deliveryBoyId) 
        ->where('order_status', 'assigned') 
        ->first();

    if (!$courierOrder) {
        return response()->json([
            'status' => 'error',
            'message' => 'Courier order not found or not assigned to this delivery boy.'
        ], 404);
    }
    $deliveryAddress = DB::table('addresses')
        ->where('id', $courierOrder->delivery_address_ID)
        ->first();

    if (!$deliveryAddress) {
        return response()->json([
            'status' => 'error',
            'message' => 'Delivery address not found.'
        ], 404);
    }

    DB::table('courierorder')
        ->where('id', $courierOrderId)
        ->update([
            'pickup_time' => now(), 
            'order_status' => 'picked_up',
        ]);

   
    DB::table('courierlivetracking')->insert([
        'courierorder_ID' => $courierOrderId,
        'latitude' => $deliveryAddress->latitude, 
        'longitude' => $deliveryAddress->longitude, 
        'time_stamp' => now(), 
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Order marked as picked up successfully, and live tracking updated for delivery location!',
    ]);
}

public function markOrderAsDelivered($courierOrderId, $deliveryBoyId)
{
    $deliveryBoyExists = DB::table('deliveryboys')->where('id', $deliveryBoyId)->exists();
    if (!$deliveryBoyExists) {
        return response()->json([
            'status' => 'error',
            'message' => 'Delivery boy not found.'
        ], 404);
    }

    $courierOrder = DB::table('courierorder')
        ->join('addresses', 'courierorder.delivery_address_ID', '=', 'addresses.id')
        ->select(
            'courierorder.id as courierOrderId',
            'addresses.latitude as delivery_address_latitude',
            'addresses.longitude as delivery_address_longitude'
        )
        ->where('courierorder.id', $courierOrderId)
        ->where('courierorder.deliveryboys_ID', $deliveryBoyId) 
        ->where('courierorder.order_status', 'picked_up') 
        ->first();

    if (!$courierOrder) {
        return response()->json([
            'status' => 'error',
            'message' => 'Courier order not found or not ready for delivery.'
        ], 404);
    }

    DB::table('courierorder')
        ->where('id', $courierOrderId)
        ->update([
            'delivery_time' => now(), 
            'order_status' => 'delivered',
        ]);

    DB::table('courierlivetracking')->insert([
        'courierorder_ID' => $courierOrderId,
        'latitude' => $courierOrder->delivery_address_latitude, 
        'longitude' => $courierOrder->delivery_address_longitude,
        'time_stamp' => now(), 
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Order marked as delivered successfully!',
    ]);
}


public function updateLiveTracking(Request $request)
{
    $validatedData = $request->validate([
        'courierorder_ID' => 'required|exists:courierorder,id',
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'deliveryboys_ID' => 'required|exists:deliveryboys,id',
    ]);

    $courierOrder = DB::table('courierorder')
        ->where('id', $validatedData['courierorder_ID'])
        ->where('deliveryboys_ID', $validatedData['deliveryboys_ID'])
        ->whereIn('order_status', ['assigned', 'picked_up'])
        ->first();

    if (!$courierOrder) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid order or delivery boy.',
        ], 404);
    }

    DB::table('courierlivetracking')->insert([
        'courierorder_ID' => $validatedData['courierorder_ID'],
        'latitude' => $validatedData['latitude'],
        'longitude' => $validatedData['longitude'],
        'time_stamp' => now(),
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Live tracking updated successfully!',
    ]);
}







//////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////
/////////////////////////   Order   ////////////////////////////////////

public function getReadySubordersForDeliveryBoy($id)
{
    $baseUrl = url('/');

    $deliveryBoyUserId = $id;

    // Get delivery boy's organization ID
    $deliveryBoy = DB::table('deliveryboys')
        ->where('lmd_users_ID', $deliveryBoyUserId)
        ->first();

    if (!$deliveryBoy || !$deliveryBoy->organization_id) {
        return response()->json([
            'status' => 'error',
            'message' => 'Delivery boy is not associated with any organization.',
        ], 400);
    }

    $organizationId = $deliveryBoy->organization_id;

    $suborders = DB::table('suborders')
        ->join('branches', 'suborders.branch_ID', '=', 'branches.id')
        ->join('shops', 'branches.shops_ID', '=', 'shops.id')
        ->join('vendors', 'shops.vendors_ID', '=', 'vendors.id')
        ->join('vendororganization', function ($join) use ($organizationId) {
            $join->on('vendororganization.vendor_ID', '=', 'vendors.id')
                ->where('vendororganization.organization_ID', '=', $organizationId)
                ->where('vendororganization.approval_status', '=', 'approved');
        })
        ->join('area', 'branches.area_ID', '=', 'area.id')
        ->join('city', 'area.city_ID', '=', 'city.id')
        ->join('orders', 'suborders.orders_ID', '=', 'orders.id')
        ->join('customers', 'orders.customers_ID', '=', 'customers.id') // ✅ Fixed join
        ->join('lmd_users', 'customers.lmd_users_ID', '=', 'lmd_users.id') // ✅ Fixed join
        ->join('addresses', 'orders.addresses_ID', '=', 'addresses.id')
        ->where('suborders.status', 'ready')
        ->orderByDesc(column: 'orders.order_date')
        ->select(
            'suborders.id as suborder_id',
            'suborders.vendor_type',
            'suborders.vendor_order_id',
            'suborders.status as suborder_status',
            'suborders.payment_status as suborder_payment_status',
            'suborders.total_amount',
            'suborders.estimated_delivery_time',
            'suborders.delivery_time',
            'suborders.deliveryboys_ID',
            'suborders.orders_ID',
            'suborders.vendor_ID',
            'suborders.shop_ID',
            'suborders.branch_ID',
            'orders.order_date',
            'orders.addresses_ID',
            'shops.name as shop_name',
            'branches.description as branch_name',
            DB::raw("CASE 
                        WHEN branches.branch_picture IS NULL OR branches.branch_picture = '' 
                        THEN NULL 
                        ELSE CONCAT('$baseUrl/storage/', branches.branch_picture) 
                    END as branch_picture"),
            'branches.latitude as pickup_latitude',
            'branches.longitude as pickup_longitude',
            'area.name as pickup_area',
            'city.name as pickup_city',
            'lmd_users.name as customer_name',
            'lmd_users.phone_no as customer_phone',
            DB::raw("CASE 
                        WHEN lmd_users.profile_picture IS NULL OR lmd_users.profile_picture = '' 
                        THEN NULL 
                        ELSE CONCAT('$baseUrl/storage/', lmd_users.profile_picture) 
                    END as customer_picture"),
            'addresses.street as delivery_area',
            'addresses.city as delivery_city',
            'addresses.latitude as delivery_latitude',
            'addresses.longitude as delivery_longitude'
        )
        ->get()
        ->map(function ($item) {
            return [
                'suborder_id' => $item->suborder_id,
                'vendor_type' => $item->vendor_type,
                'vendor_order_id' => $item->vendor_order_id,
                'status' => $item->suborder_status,
                'payment_status' => $item->suborder_payment_status,
                'total_amount' => $item->total_amount,
                'estimated_delivery_time' => $item->estimated_delivery_time,
                'delivery_time' => $item->delivery_time,
                'deliveryboys_ID' => $item->deliveryboys_ID,
                'orders_ID' => $item->orders_ID,
                'vendor_ID' => $item->vendor_ID,
                'shop_ID' => $item->shop_ID,
                'branch_ID' => $item->branch_ID,
                'order_date' => $item->order_date,

                'shop' => [
                    'name' => $item->shop_name,
                    'branch' => [
                        'name' => $item->branch_name,
                        'picture' => $item->branch_picture,
                        'pickup_location' => [
                            'latitude' => $item->pickup_latitude,
                            'longitude' => $item->pickup_longitude,
                            'area' => $item->pickup_area,
                            'city' => $item->pickup_city,
                        ]
                    ]
                ],

                'customer' => [
                    'name' => $item->customer_name,
                    'phone' => $item->customer_phone,
                    'customer_picture' => $item->customer_picture,
                    'delivery_address' => [
                        'addresses_ID' => $item->addresses_ID,
                        'street' => $item->delivery_area,
                        'city' => $item->delivery_city,
                        'latitude' => $item->delivery_latitude,
                        'longitude' => $item->delivery_longitude,
                    ]
                ]
            ];
        });

    return response()->json([
        'status' => 'success',
        'data' => $suborders,
    ], 200);
}

public function acceptOrder($deliveryBoyId, $suborderId)
{
    try {
        $suborder = Suborder::findOrFail($suborderId);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['error' => 'Suborder not found.'], 404);
    }

    if ($suborder->status !== 'ready') {
        return response()->json(['error' => 'Order cannot be accepted in the current state.'], 400);
    }

    try {
        $deliveryBoy = DeliveryBoy::findOrFail($deliveryBoyId);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['error' => 'Delivery boy not found.'], 404);
    }

    $suborder->status = 'assigned';
    $suborder->deliveryboys_ID = $deliveryBoyId;
    $suborder->save();

    return response()->json(['message' => 'Order accepted successfully.']);
}



public function getAssignedSubordersForDeliveryBoy($id)
{
    $baseUrl = url('/');

    $deliveryBoyUserId = $id;

    // Get delivery boy's internal ID
    $deliveryBoy = DB::table('deliveryboys')
        ->where('lmd_users_ID', $deliveryBoyUserId)
        ->first();

    if (!$deliveryBoy) {
        return response()->json([
            'status' => 'error',
            'message' => 'Delivery boy not found.',
        ], 400);
    }

    $deliveryBoyId = $deliveryBoy->id;

    $suborders = DB::table('suborders')
        ->join('branches', 'suborders.branch_ID', '=', 'branches.id')
        ->join('shops', 'branches.shops_ID', '=', 'shops.id')
        ->join('vendors', 'shops.vendors_ID', '=', 'vendors.id')
        ->join('area', 'branches.area_ID', '=', 'area.id')
        ->join('city', 'area.city_ID', '=', 'city.id')
        ->join('orders', 'suborders.orders_ID', '=', 'orders.id')
        ->join('customers', 'orders.customers_ID', '=', 'customers.id')
        ->join('lmd_users', 'customers.lmd_users_ID', '=', 'lmd_users.id')
        ->join('addresses', 'orders.addresses_ID', '=', 'addresses.id')
        ->where('suborders.deliveryboys_ID', $deliveryBoyId)
        ->orderByDesc(column: 'orders.order_date')

        ->select(
            'suborders.id as suborder_id',
            'suborders.vendor_type',
            'suborders.vendor_order_id',
            'suborders.status as suborder_status',
            'suborders.payment_status as suborder_payment_status',
            'suborders.total_amount',
            'suborders.estimated_delivery_time',
            'suborders.delivery_time',
            'suborders.deliveryboys_ID',
            'suborders.orders_ID',
            'suborders.vendor_ID',
            'suborders.shop_ID',
            'suborders.branch_ID',

            'orders.order_date',
            'orders.addresses_ID',

            'shops.name as shop_name',
            'branches.description as branch_name',
            DB::raw("CASE 
                        WHEN branches.branch_picture IS NULL OR branches.branch_picture = '' 
                        THEN NULL 
                        ELSE CONCAT('$baseUrl/storage/', branches.branch_picture) 
                    END as branch_picture"),

            'branches.latitude as pickup_latitude',
            'branches.longitude as pickup_longitude',
            'area.name as pickup_area',
            'city.name as pickup_city',

            'lmd_users.name as customer_name',
            'lmd_users.phone_no as customer_phone',
            DB::raw("CASE 
                WHEN lmd_users.profile_picture IS NULL OR lmd_users.profile_picture = '' 
                THEN NULL 
                ELSE CONCAT('$baseUrl/storage/', lmd_users.profile_picture) 
            END as customer_picture"),

            'addresses.street as delivery_area',
            'addresses.city as delivery_city',
            'addresses.latitude as delivery_latitude',
            'addresses.longitude as delivery_longitude'
        )
        ->get()
        ->map(function ($item) {
            return [
                'suborder_id' => $item->suborder_id,
                'vendor_type' => $item->vendor_type,
                'vendor_order_id' => $item->vendor_order_id,
                'status' => $item->suborder_status,
                'payment_status' => $item->suborder_payment_status,
                'total_amount' => $item->total_amount,
                'estimated_delivery_time' => $item->estimated_delivery_time,
                'delivery_time' => $item->delivery_time,
                'deliveryboys_ID' => $item->deliveryboys_ID,
                'orders_ID' => $item->orders_ID,
                'vendor_ID' => $item->vendor_ID,
                'shop_ID' => $item->shop_ID,
                'branch_ID' => $item->branch_ID,
                'order_date' => $item->order_date,

                'shop' => [
                    'name' => $item->shop_name,
                    'branch' => [
                        'name' => $item->branch_name,
                        'picture' => $item->branch_picture,
                        'pickup_location' => [
                            'latitude' => $item->pickup_latitude,
                            'longitude' => $item->pickup_longitude,
                            'area' => $item->pickup_area,
                            'city' => $item->pickup_city,
                        ]
                    ]
                ],

                'customer' => [
                    'name' => $item->customer_name,
                    'phone' => $item->customer_phone,
                    'customer_picture' => $item->customer_picture,
                    'delivery_address' => [
                        'addresses_ID' => $item->addresses_ID,
                        'street' => $item->delivery_area,
                        'city' => $item->delivery_city,
                        'latitude' => $item->delivery_latitude,
                        'longitude' => $item->delivery_longitude,
                    ]
                ]
            ];
        });

    return response()->json([
        'status' => 'success',
        'data' => $suborders,
    ], 200);
}

public function confirmPickup(Request $request, $suborderId)
{
    $suborder = Suborder::findOrFail($suborderId);

    if ($suborder->status !== 'assigned') {
        return response()->json(['error' => 'Order cannot be picked up in the current state.'], 400);
    }

    $request->validate([
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
    ]);

    $suborder->status = 'picked_up';
    $suborder->save();

    LocationTracking::create([
        'latitude' => $request->latitude,
        'longitude' => $request->longitude,
        'status' => 'in_transit',
        'suborders_ID' => $suborder->id,
    ]);

    return response()->json(['message' => 'Order picked up successfully.']);
}







public function updateLocation(Request $request, $suborderId)
{
    try {
        $suborder = Suborder::findOrFail($suborderId);

        // Allow location update only if status is valid
      /*   $allowedStatuses = ['handover_confirmed', 'in_transit'];
        if (!in_array($suborder->status, $allowedStatuses)) {
            return response()->json([
                'error' => 'Order location cannot be updated in the current state.',
                'current_status' => $suborder->status,
            ], 400);
        } */

        // Validate request
        $validatedData = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        // Create location entry
        LocationTracking::create([
            'latitude' => $validatedData['latitude'],
            'longitude' => $validatedData['longitude'],
            'status' => 'in_transit',
            'suborders_ID' => $suborder->id,
        ]);

        return response()->json(['message' => 'Location updated successfully.']);
    } catch (\Illuminate\Validation\ValidationException $ve) {
        return response()->json([
            'error' => 'Validation failed.',
            'messages' => $ve->errors(),
        ], 422);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'error' => 'Suborder not found.',
        ], 404);
    } catch (Exception $e) {
        Log::error('Error updating location: ' . $e->getMessage());
        return response()->json([
            'error' => 'Something went wrong while updating location.',
        ], 500);
    }
}





public function reachDestination(Request $request, $deliveryBoyId, $suborderId)
{
    $validator = Validator::make($request->all(), [
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => 'Invalid location data provided.'], 400);
    }

    $suborder = Suborder::findOrFail($suborderId);

    if ($suborder->status !== 'in_transit' && $suborder->status !== 'handover_confirmed') {
        return response()->json(['error' => 'Order must be in transit/handover_confirmed before reaching destination.'], 400);
    }

  if ((int)$suborder->deliveryboys_ID !== (int)$deliveryBoyId) {
    return response()->json(['error' => 'This order is not assigned to the current delivery boy.'], 400);
}

    $suborder->save();

    
    LocationTracking::create([
        'latitude' => $request->latitude,
        'longitude' => $request->longitude,
        'status' => 'reached_destination',
        'suborders_ID' => $suborder->id
    ]);

    return response()->json(['message' => 'Delivery boy has reached the destination and location tracking updated.']);
}




public function confirmPaymentByDeliveryBoy($suborderId)
{
    $suborder = Suborder::findOrFail($suborderId);

    if ($suborder->payment_status !== 'confirmed_by_customer') {
        return response()->json(['error' => 'Customer must confirm payment first.'], 400);
    }

    $suborder->payment_status = 'confirmed_by_deliveryboy';
    $suborder->save();

    return response()->json(['message' => 'Payment confirmed by delivery boy.']);
}

public function getLatestLocationBySuborderId($suborderId)
{
    $latestLocation = LocationTracking::where('suborders_ID', $suborderId)
        ->orderByDesc('time_stamp')
        ->first();

    if (!$latestLocation) {
        return response()->json(['error' => 'No location found for this suborder.'], 404);
    }

    return response()->json([
        'latitude' => $latestLocation->latitude,
        'longitude' => $latestLocation->longitude,
        'status' => $latestLocation->status,
        'time_stamp' => $latestLocation->time_stamp,
    ]);
}

}
