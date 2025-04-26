<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\LMDUser;
use App\Models\Order;
use App\Models\Shop;
use App\Models\ShopCategory;
use App\Models\Branch;
use App\Models\CourierItemCategory;
use App\Models\CourierItem;
use App\Models\SubOrder;
use App\Models\LocationTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use App\Models\CourierImage;
use App\Models\CourierAdditionalinfo;
use App\Models\CourierOrder;
use App\Models\CourierLiveTracking;
use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller {

    public function signup( Request $request ) {
    Log::info('Request data:', $request->all());

       // $validated = $request->validate( [
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:lmd_users,email',
            'phone_no' => 'required|string|max:15|unique:lmd_users,phone_no',
            'password' => 'required|string|min:1',
            'address_type' => 'required|string|max:50',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'zip_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'cnic' => 'required|string|max:15|unique:lmd_users,cnic', 
            'profile_picture' => 'nullable|file|mimes:jpeg,png,jpg|max:10240', 
        ] );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors() // Get specific field errors
            ], 422);
        }
        $validated = $validator->validated(); // ✅ Get validated data
        DB::beginTransaction();
    
        try {
            // profile picture upload
            $profilePicturePath = null;
            if ( $request->hasFile( 'profile_picture' ) ) {
                $profilePicturePath = $request->file( 'profile_picture' )->store( 'customerImages', 'public' );
            }

            $userId = DB::table( 'lmd_users' )->insertGetId( [
                'name' => $validated[ 'name' ],
                'email' => $validated[ 'email' ],
                'phone_no' => $validated[ 'phone_no' ],
                'password' => $validated[ 'password' ],  
                'lmd_user_role' => 'customer',
                'cnic' => $validated[ 'cnic' ] ?? null, 
                'profile_picture' => $profilePicturePath,
                ] );

           
            DB::table( 'customers' )->insert( [
                'lmd_users_ID' => $userId,
            ] );

            DB::table( 'addresses' )->insert( [
                'address_type' => $validated[ 'address_type' ],
                'street' => $validated[ 'street' ],
                'city' => $validated[ 'city' ],
                'zip_code' => $validated[ 'zip_code' ],
                'country' => $validated[ 'country' ] ?? 'Pakistan',
                'latitude' => $validated[ 'latitude' ],
                'longitude' => $validated[ 'longitude' ],
                'lmd_users_ID' => $userId,
            ] );

            DB::commit();
            return response()->json( [
                'message' => 'Customer signed up successfully',
                'user_id' => $userId,
            ], 201 );
        } catch ( \Exception $e ) {
            DB::rollBack();
            Log::error('Signup failed', ['error' => $e->getMessage()]);
            return response()->json( [
                'message' => 'Signup failed',
                'error' => $e->getMessage(),
            ], 500 );
        }
    }

    public function getDeliveryRouteInfo($suborderId)
    {
        $suborder = DB::table('suborders')
            ->join('orders', 'suborders.orders_ID', '=', 'orders.id')
            ->join('addresses', 'orders.addresses_ID', '=', 'addresses.id')
            ->join('branches', 'suborders.branch_ID', '=', 'branches.id')
            ->where('suborders.id', $suborderId)
            ->select(
                'branches.latitude as branch_latitude',
                'branches.longitude as branch_longitude',
                'addresses.latitude as address_latitude',
                'addresses.longitude as address_longitude',
                'orders.order_date',
                'suborders.estimated_delivery_time',
                'suborders.delivery_time'
            )
            ->first();
    
        if (!$suborder) {
            return response()->json(['error' => 'Suborder not found.'], 404);
        }
    
        return response()->json([
            'message' => 'Delivery route information retrieved successfully.',
            'data' => [
                'pickup_location' => [
                    'latitude' => $suborder->branch_latitude,
                    'longitude' => $suborder->branch_longitude,
                ],
                'drop_location' => [
                    'latitude' => $suborder->address_latitude,
                    'longitude' => $suborder->address_longitude,
                ],
                'order_date' => $suborder->order_date,
                'estimated_delivery_time' => $suborder->estimated_delivery_time,
                'delivery_time' => $suborder->delivery_time,
            ],
        ]);
    }
    
    

    public function getCustomerData($id)
    {
        $baseUrl = url('/'); // Base URL, e.g. http://your-domain.com
    
        // Join customers with lmd_users where lmd_user_role is 'customer'
        $customerData = DB::table('customers')
            ->join('lmd_users', 'customers.lmd_users_ID', '=', 'lmd_users.id')
            ->select(
                'customers.id as customer_id',   // The customer table ID
                'lmd_users.name',
                'lmd_users.email',
                'lmd_users.phone_no',
                'lmd_users.cnic',
                'lmd_users.password',            // Include this only if needed
                'lmd_users.lmd_user_role',
                DB::raw("CASE 
                            WHEN lmd_users.profile_picture IS NULL OR lmd_users.profile_picture = '' 
                            THEN NULL 
                            ELSE CONCAT('$baseUrl/storage/', lmd_users.profile_picture) 
                          END as profile_picture")
                // Note: account_creation_date and lmd_users.id are intentionally omitted.
            )
            ->where('lmd_users.id', $id)
            ->where('lmd_users.lmd_user_role', 'customer')
            ->first();
    
        if ($customerData) {
            return response()->json($customerData, 200);
        }
    
        return response()->json(['error' => 'Customer not found'], 404);
    }
    


public function updateCustomer(Request $request, $id)
{

    Log::info('Request data:', ['data' => $request->all()]);

    $validatedData = $request->validate([
        'name' => 'sometimes|string|max:100',
        'email' => 'sometimes|email|max:255|unique:lmd_users,email,' . $id,
        'phone_no' => 'sometimes|string|max:15|unique:lmd_users,phone_no,' . $id,
        'password' => 'sometimes|string|min:1',
        'cnic' => 'sometimes|string|max:15|unique:lmd_users,cnic,' . $id, 
        'profile_picture' => 'sometimes|file|mimes:jpeg,png,jpg',
    ]);

    // Check if user exists and is a customer
    $user = DB::table('lmd_users')->where('id', $id)->where('lmd_user_role', 'customer')->first();

    if (!$user) {
        return response()->json(['error' => 'Customer not found or invalid role'], 404);
    }

    DB::beginTransaction();

    try {
        // Prepare only the provided fields for update
        $userData = array_filter([
            'name' => $validatedData['name'] ?? null,
            'email' => $validatedData['email'] ?? null,
            'phone_no' => $validatedData['phone_no'] ?? null,
            'password' => isset($validatedData['password']) ? $validatedData['password'] : null,// Hash password
            'cnic' => $validatedData['cnic'] ?? null,
        ]);

        // Handle profile picture update if provided
        if ($request->hasFile('profile_picture')) {
            $profilePicturePath = $request->file('profile_picture')->store('customerImages', 'public');
            $userData['profile_picture'] = $profilePicturePath; // Save new profile picture path
        }

        // Update only if there is valid data
        if (!empty($userData)) {
            DB::table('lmd_users')->where('id', $id)->update($userData);
        }

        DB::commit();

        return response()->json(['message' => 'Customer updated successfully'], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => 'Update failed', 'details' => $e->getMessage()], 500);
    }
}

   

    public function deleteCustomer( $id ) {
       
        DB::table( 'customers' )->where( 'lmd_users_ID', $id )->delete();

        $deleted = DB::table( 'lmd_users' )->where( 'id', $id )->where( 'lmd_user_role', 'customer' )->delete();

        if ( $deleted ) {
            return response()->json( [ 'message' => 'Customer and associated data deleted successfully' ], 200 );
        }

        return response()->json( [ 'error' => 'Customer not found' ], 404 );
    }



    
    

    public function getAllShopCategories()
    {
        $categories = ShopCategory::select('id', 'name')->get();

        return response()->json(
            $categories
        );
    }


public function getCustomerMainScreenInformation($customerID)
{
    $baseUrl = url('/'); // Base URL for image paths

    // Fetch shop and branch details with vendor information
    $shopInformation = DB::table('branches')
        ->join('shops', 'branches.shops_ID', '=', 'shops.id') 
        ->join('shopcategory', 'shops.shopcategory_ID', '=', 'shopcategory.id') 
        ->join('vendors', 'shops.vendors_ID', '=', 'vendors.id') 
        ->leftJoin('apivendor', 'branches.id', '=', 'apivendor.branches_ID') // Join API vendor if available
        ->select(
            'vendors.id as vendor_id',
            'shops.id as shop_id',
            'branches.id as branch_id',
            'shops.shopcategory_ID as shopcategory_ID',
            'shopcategory.name as shop_category_name',
            'shops.name as shop_name',
            'shops.description as shop_description',
            'shops.status as shop_status',
            'branches.latitude',
            'branches.longitude',
            'branches.description as branch_description',
                'branches.opening_hours',
                'branches.closing_hours',
                'branches.contact_number',
            'branches.status as branch_status',
            'branches.approval_status',
            DB::raw("CASE 
                        WHEN branches.branch_picture IS NULL OR branches.branch_picture = '' 
                        THEN NULL 
                        ELSE CONCAT('$baseUrl/storage/', branches.branch_picture) 
                    END as branch_picture"),
            'vendors.vendor_type',
            'apivendor.api_base_url',
            'apivendor.id as apivendor_id'
        )
        ->where('branches.status', 'active')
        ->where('branches.approval_status', 'approved')
        ->get();

    foreach ($shopInformation as $shop) {
        if ($shop->vendor_type === 'In-App Vendor') {
            // Fetch total reviews and average rating for In-App Vendors
            $ratings = DB::table('itemrating')
                ->join('itemdetails', 'itemrating.itemdetails_ID', '=', 'itemdetails.id')
                ->join('items', 'itemdetails.item_ID', '=', 'items.id')
                ->where('items.branches_ID', $shop->branch_id)
                ->select(
                    DB::raw('COUNT(itemrating.id) as total_reviews'),
                    DB::raw('IFNULL(AVG(itemrating.rating_stars), 0) as average_rating')
                )
                ->first();

            $shop->total_reviews = $ratings->total_reviews;
            $shop->average_rating = $ratings->average_rating;
            $shop->rating_url = null;
        } 
        else if ($shop->vendor_type === 'API Vendor' && !empty($shop->apivendor_id)) {
            // Fetch API method details
            $apiMethod = DB::table('apimethods')
                ->where('apivendor_ID', $shop->apivendor_id)
                ->where('method_name', 'rating')
                ->select('http_method', 'endpoint')
                ->first();

            if ($apiMethod) {
                // Construct the full API URL
                $ratingUrl = rtrim($shop->api_base_url, '/') . '/' . ltrim($apiMethod->endpoint, '/');
                $shop->rating_url = $ratingUrl;

                // Fetch ratings from API vendor
                $client = new \GuzzleHttp\Client();
                try {
                    $response = $client->request($apiMethod->http_method, $ratingUrl);
                    $apiRatings = json_decode($response->getBody(), true);

                    // Map API Vendor fields to LMD fields using variables and mapping tables
                    $mappedRatings = DB::table('mapping')
                        ->join('variables', 'mapping.variable_ID', '=', 'variables.id')
                        ->where('mapping.apivendor_ID', $shop->apivendor_id)
                        ->select('variables.tags', 'mapping.api_values')
                        ->get();

                    $mappedData = [];
                    foreach ($mappedRatings as $map) {
                        $mappedData[$map->tags] = $apiRatings[$map->api_values] ?? 0;
                    }

                    // Assign mapped values
                    $shop->total_reviews = $mappedData['reviews_count'] ?? 0;
                    $shop->average_rating = $mappedData['avg_Rating'] ?? 0;
                } catch (\Exception $e) {
                    // Log the error
                    Log::error("API Rating Fetch Error: " . $e->getMessage());
                    $shop->total_reviews = 0;
                    $shop->average_rating = 0;
                }
            } else {
                $shop->total_reviews = 0;
                $shop->average_rating = 0;
                $shop->rating_url = null;
            }
        }
    }
    $shopInformation = $shopInformation->sortByDesc('average_rating')->values();
    return response()->json($shopInformation->map(function ($shop) {
        return [
            'vendor_id' => $shop->vendor_id,
            'shop_id' => $shop->shop_id,
            'branch_id' => $shop->branch_id,
            'shopcategory_ID' => $shop->shopcategory_ID,
            'shop_category_name' => $shop->shop_category_name,
            'shop_name' => $shop->shop_name,
            'shop_description' => $shop->shop_description,
            'shop_status' =>$shop->shop_status,
            'branch_description' => $shop->branch_description,
            'contact_number' => $shop->contact_number,
            'opening_hours' => $shop->opening_hours,
            'closing_hours' => $shop->closing_hours,
            'latitude' => $shop->latitude,
            'longitude' => $shop->longitude,
            'branch_status' => $shop->branch_status,
            'approval_status' => $shop->approval_status,
            'branch_picture' => $shop->branch_picture,
            'vendor_type' => $shop->vendor_type,
            'reviews_count' => $shop->total_reviews ?? 0,
            'avg_Rating' => $shop->average_rating ?? 0,
        ];
    }));
    
}





public function getVendorShopBranchMenu($vendorId, $shopId, $branchId)
{
    // Fetch the vendor type
    $vendor = DB::table('vendors')
        ->where('id', $vendorId)
        ->select('vendor_type')
        ->first();

    if (!$vendor) {
        return response()->json(['error' => 'Vendor not found'], 404);
    }

    // Handle In-App Vendor
    if ($vendor->vendor_type === 'In-App Vendor') {
        $menuInformation = DB::table('shops')
        ->join('vendors', 'shops.vendors_ID', '=', 'vendors.id')
        ->join('shopcategory', 'shops.shopcategory_ID', '=', 'shopcategory.id')
        ->join('branches', 'shops.id', '=', 'branches.shops_ID')
        ->leftJoin('items', 'branches.id', '=', 'items.branches_ID')
        ->leftJoin('itemcategories', 'items.category_ID', '=', 'itemcategories.id')
        ->leftJoin('itemdetails', 'items.id', '=', 'itemdetails.item_ID')
        ->leftJoin('itemattributes', 'itemdetails.id', '=', 'itemattributes.itemdetail_id')
        ->select(
            'vendors.id as vendor_id',
            'vendors.vendor_type',
            'shops.id as shop_id',
            'shops.name as shop_name',
            'shops.description as shop_description',
            'shopcategory.name as shop_category_name',
            'branches.id as branch_id',
            'branches.latitude',
            'branches.longitude',
            'branches.description as branch_description',
            'branches.opening_hours',
            'branches.closing_hours',
            'branches.contact_number',
            'branches.status as branch_status',
            'branches.approval_status as branch_approval_status',
            'branches.branch_picture',
            'items.id as item_id',
            'items.name as item_name',
            'items.description as item_description',
            'itemcategories.id as category_ID',
            'itemcategories.name as item_category_name',
            'itemdetails.id as item_detail_id',
            'itemdetails.variation_name',
            'itemdetails.timesensitive',
            'itemdetails.preparation_time',
            'itemdetails.picture',
            'itemdetails.price as item_detail_price',
            'itemdetails.additional_info',
            'itemattributes.id as attribute_id',
            'itemattributes.key as attribute_key',
            'itemattributes.value as attribute_value'
        )
        ->where('vendors.id', $vendorId)
        ->where('shops.id', $shopId)
        ->where('branches.id', $branchId)
        ->where('branches.status', 'active')
        ->where(function ($query) {
            $query->whereNotNull('items.id')
                  ->orWhereNotNull('itemdetails.id');
        }) // Ensure at least one is not null
        ->get();
    
        if ($menuInformation->isEmpty()) {
            return response()->json( 'No menu information found for this vendor/shop/branch', 404);
        }
        // Format the response for In-App Vendor
        return $this->formatInAppMenuResponse($menuInformation);
    }

    if ($vendor->vendor_type === 'API Vendor') {
        // Get the API details using the branchId
        $apiDetails = DB::table('apivendor')
            ->join('apimethods', 'apivendor.id', '=', 'apimethods.apivendor_ID')
            ->where('apivendor.branches_ID', $branchId) // Filter by branchId
            ->where('apimethods.method_name', 'getMenu') // Assuming the method is 'getMenu'
            ->select('apivendor.api_base_url', 'apimethods.endpoint', 'apimethods.http_method', 'apivendor.api_key', 'apivendor.id as apivendor_ID')
            ->first();
    
        if (!$apiDetails) {
            return response()->json(['error' => 'API method or branch not found'], 404);
        }
    
        // Build the full URL dynamically
        $url = $apiDetails->api_base_url . $apiDetails->endpoint;
    
        // Check if the server is reachable
        try {
            $serverStatus = Http::timeout(5)->get($url);
            if (!$serverStatus->successful()) {
                return response()->json(['error' => 'Vendor server is not running or unreachable'], 503);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Vendor server is not reachable: ' . $e->getMessage()], 503);
        }
    
        // Make the actual API request
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiDetails->api_key,
            ])->{$apiDetails->http_method}($url);
    
            if ($response->successful()) {
                $apiResponse = $response->json();
    
                // Fetch mappings for the API vendor
                $mappings = DB::table('mapping')
                    ->join('variables', 'mapping.variable_ID', '=', 'variables.id')
                    ->where('mapping.apivendor_ID', $apiDetails->apivendor_ID)
                    ->select('mapping.api_values', 'variables.tags')
                    ->get();
    
                // Convert mappings to an associative array
                $mapArray = $mappings->pluck('tags', 'api_values')->toArray();
    
                // Function to recursively map response
                function mapResponseKeys($data, $mapArray) {
                    if (is_array($data)) {
                        $mappedData = [];
                        foreach ($data as $key => $value) {
                            $newKey = $mapArray[$key] ?? $key; // Use mapped key or fallback to original
                            $mappedData[$newKey] = is_array($value) ? mapResponseKeys($value, $mapArray) : $value;
                        }
                        return $mappedData;
                    }
                    return $data;
                }
    
                // Transform API response keys based on mapping
                $mappedResponse = mapResponseKeys($apiResponse, $mapArray);
    
                return response()->json($mappedResponse);
            } else {
                return response()->json(['error' => 'Failed to fetch menu from vendor API'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'API request failed: ' . $e->getMessage()], 500);
        }
    }
    

    return response()->json(['error' => 'Invalid vendor type'], 400);
}


private function formatInAppMenuResponse($menuInformation)
{
    return $menuInformation->groupBy('item_id')->map(function ($itemDetails, $itemId) {
        $firstItem = $itemDetails->first();
        
        return [
            'item_id' => $itemId,
            'item_name' => $firstItem->item_name,
            'item_description' => $firstItem->item_description,
            'timesensitive' => $firstItem->timesensitive,
            'preparation_time' => $firstItem->preparation_time,
            'itemPicture' => $firstItem->picture ? asset('storage/' . $firstItem->picture) : null,
            'itemdetail_id' => $firstItem->item_detail_id,
            'variation_name' => $firstItem->variation_name,
            'price' => $firstItem->item_detail_price,
            'additional_info' => $firstItem->additional_info,
            'item_category_id' => $firstItem->category_ID, // Include item category ID
            'item_category_name' => $firstItem->item_category_name, // Include item category name
            'attributes' => $itemDetails->filter(function ($item) {
                return $item->attribute_id !== null;
            })->map(function ($attribute) {
                return [
                    'key' => $attribute->attribute_key,
                    'value' => $attribute->attribute_value,
                ];
            })->values(),
        ];
    })->values();
}









public function createOrUpdateCart(Request $request)
{
    $customerId = $request->input('customer_id');

    // Check if customer already has a cart
    $cart = DB::table('cart')->where('customers_ID', $customerId)->first();

    if ($cart) {
        // Update the cart timestamp
        DB::table('cart')->where('id', $cart->id)->update([
            'updated_at' => now(),
        ]);
        return response()->json(['message' => 'Cart updated', 'cart_id' => $cart->id]);
    } else {
        // Create a new cart
        $cartId = DB::table('cart')->insertGetId([
            'cart_date' => now(),
            'total_amount' => 0.00,
            'cart_status' => 'pending',
            'customers_ID' => $customerId,
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Cart created', 'cart_id' => $cartId]);
    }
}



public function addItemToCart(Request $request)
{
    try {
        // ✅ Validate the request
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:customers,ID',
            'vendor_id' => 'required|integer|exists:vendors,ID',
            'shop_id' => 'required|integer|exists:shops,ID',
            'branch_id' => 'required|integer|exists:branches,ID',
            'itemdetails_id' => 'required|integer|exists:itemdetails,ID',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $customerId = $validated['customer_id'];
        $vendorId = $validated['vendor_id'];
        $shopId = $validated['shop_id'];
        $branchId = $validated['branch_id'];
        $itemDetailsId = $validated['itemdetails_id'];
        $quantity = $validated['quantity'];
        $price = $validated['price'];
        $total = $quantity * $price;


// ✅ Fetch vendor type dynamically
$vendor = DB::table('vendors')->where('id', $vendorId)->first();
if (!$vendor) {
    return response()->json(['message' => 'Vendor not found'], 404);
}
$vendorType = $vendor->vendor_type; // Get the vendor type


        // ✅ Fetch or create cart
        $cart = DB::table('cart')->where('customers_ID', $customerId)->first();
        if (!$cart) {
            $cartId = DB::table('cart')->insertGetId([
                'cart_date' => now(),
                'total_amount' => 0.00,
                'cart_status' => 'pending',
                'customers_ID' => $customerId,
                'updated_at' => now(),
            ]);
        } else {
            $cartId = $cart->id;
        }

        // ✅ Check for existing suborder
        $suborder = DB::table('cart_suborders')
            ->where('cart_ID', $cartId)
            ->where('vendor_ID', $vendorId)
            ->where('shop_ID', $shopId)
            ->where('branch_ID', $branchId)
            ->first();

        if (!$suborder) {
            $suborderId = DB::table('cart_suborders')->insertGetId([
                // 'vendor_type' => 'In-App Vendor',
                'vendor_type' => $vendorType, // Use the fetched vendor type dynamically
                'vendor_ID' => $vendorId,
                'shop_ID' => $shopId,
                'branch_ID' => $branchId,
                'total_amount' => 0.00,
                'delivery_fee' => 0.00,
                'cart_ID' => $cartId,
            ]);
        } else {
            $suborderId = $suborder->id;
        }

        // ✅ Check if item already exists in cart
        $existingItem = DB::table('cart_items')
            ->where('cart_suborders_ID', $suborderId)
            ->where('itemdetails_ID', $itemDetailsId)
            ->first();

        if ($existingItem) {
            $newQuantity = $existingItem->quantity + $quantity;
            $newTotal = $newQuantity * $price;

            DB::table('cart_items')
                ->where('id', $existingItem->id)
                ->update([
                    'quantity' => $newQuantity,
                    'total' => $newTotal,
                ]);
        } else {
            DB::table('cart_items')->insert([
                'quantity' => $quantity,
                'price' => $price,
                'total' => $total,
                'itemdetails_ID' => $itemDetailsId,
                'cart_suborders_ID' => $suborderId,
            ]);
        }

        // ✅ Update total_amount in cart_suborders
        $suborderTotal = DB::table('cart_items')
            ->where('cart_suborders_ID', $suborderId)
            ->sum('total');

        DB::table('cart_suborders')
            ->where('id', $suborderId)
            ->update(['total_amount' => $suborderTotal]);

        // ✅ Update total_amount in cart
        $cartTotal = DB::table('cart_suborders')
            ->where('cart_ID', $cartId)
            ->sum('total_amount');

        DB::table('cart')
            ->where('id', $cartId)
            ->update(['total_amount' => $cartTotal]);

        return response()->json([
            'success' => true,
            'message' => 'Item added/updated in cart',
        ]);
    } catch (ValidationException $e) {
        // ❌ Return validation errors as JSON response
        return response()->json([
            'success' => false,
            'message' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        // ❌ Handle other errors (unexpected issues)
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}



// public function removeItemFromCart(Request $request)
// {
//     $itemId = $request->input('cart_item_id');

//     // Check if item exists
//     $cartItem = DB::table('cart_items')->where('id', $itemId)->first();

//     if (!$cartItem) {
//         return response()->json(['message' => 'Item not found in cart'], 404);
//     }

//     // Get suborder ID before deleting
//     $suborderId = $cartItem->cart_suborders_ID;

//     // Delete item
//     DB::table('cart_items')->where('id', $itemId)->delete();

//     // Check if suborder has any items left
//     $remainingItems = DB::table('cart_items')->where('cart_suborders_ID', $suborderId)->count();

//     if ($remainingItems == 0) {
//         DB::table('cart_suborders')->where('id', $suborderId)->delete();
//     }

//     return response()->json(['message' => 'Item removed from cart']);
// }
public function removeItemFromCart(Request $request)
{
    $itemId = $request->input('cart_item_id');

    // Check if item exists
    $cartItem = DB::table('cart_items')->where('id', $itemId)->first();

    if (!$cartItem) {
        return response()->json(['message' => 'Item not found in cart'], 404);
    }

    // Get suborder and cart ID before deleting
    $suborderId = $cartItem->cart_suborders_ID;

    // Get suborder details
    $suborder = DB::table('cart_suborders')->where('id', $suborderId)->first();
    if (!$suborder) {
        return response()->json(['message' => 'Suborder not found'], 404);
    }

    // Get cart ID
    $cartId = $suborder->cart_ID;

    // Update suborder total amount
    DB::table('cart_suborders')
        ->where('id', $suborderId)
        ->update([
            'total_amount' => DB::raw("GREATEST(total_amount - {$cartItem->total}, 0)")
        ]);

    // Update main cart total amount
    DB::table('cart')
        ->where('id', $cartId)
        ->update([
            'total_amount' => DB::raw("GREATEST(total_amount - {$cartItem->total}, 0)")
        ]);

    // Delete item
    DB::table('cart_items')->where('id', $itemId)->delete();

    // Check if suborder has any items left
    $remainingItems = DB::table('cart_items')->where('cart_suborders_ID', $suborderId)->count();

    if ($remainingItems == 0) {
        // Delete suborder
        DB::table('cart_suborders')->where('id', $suborderId)->delete();

        // Check if there are any suborders left in the cart
        $remainingSuborders = DB::table('cart_suborders')->where('cart_ID', $cartId)->count();
        
        if ($remainingSuborders == 0) {
            // Delete the cart if no suborders left
            DB::table('cart')->where('id', $cartId)->delete();
        }
    }

    return response()->json(['message' => 'Item removed from cart and totals updated']);
}


public function clearCart(Request $request)
{
    $customerId = $request->input('customer_id');

    // Find cart
    $cart = DB::table('cart')->where('customers_ID', $customerId)->first();
    if (!$cart) {
        return response()->json(['message' => 'Cart already empty']);
    }

    // Delete all cart-related records
    DB::table('cart_items')->whereIn('cart_suborders_ID', function ($query) use ($cart) {
        $query->select('id')->from('cart_suborders')->where('cart_ID', $cart->id);
    })->delete();

    DB::table('cart_suborders')->where('cart_ID', $cart->id)->delete();
    DB::table('cart')->where('id', $cart->id)->delete();

    return response()->json(['message' => 'Cart cleared']);
}



public function getCartDetails(Request $request)
{
    $customerId = $request->input('customer_id');

    // Fetch the cart details
    $cart = DB::table('cart')->where('customers_ID', $customerId)->first();
    if (!$cart) {
        return response()->json([
            'cart' => null,
            'suborders' => []
        ]);
    }

    // Fetch suborders
    $suborders = DB::table('cart_suborders')
        ->where('cart_ID', $cart->id)
        ->get();

    foreach ($suborders as &$suborder) {
        // Fetch cart items for this suborder
        $suborder->items = DB::table('cart_items')
            ->where('cart_suborders_ID', $suborder->id)
            ->get();

        // Fetch vendor/shop/branch menu details
        $menuInformation = $this->getVendorShopBranchMenu(
            $suborder->vendor_ID,
            $suborder->shop_ID,
            $suborder->branch_ID
        );

Log::info("Raw menu response: " . json_encode($menuInformation));

// Check if it's a Laravel JsonResponse
if ($menuInformation instanceof \Illuminate\Http\JsonResponse) {
    $data = $menuInformation->getData(true); // Convert to array
    Log::info("Extracted from JsonResponse: " . json_encode($data));

    $menuInformation=($data);
} else {
    // If it's a plain JSON string, decode it
    $menuInformation = json_decode($menuInformation, true);
    Log::info("Decoded JSON: " . json_encode($menuInformation));
}

// Final log after processing
Log::info("Raw menu response AFTER processing: " . json_encode($menuInformation));

        if (empty($menuInformation)) {
            Log::info("Menu information is empty for suborder ID: " . $suborder->id);
            // $suborder->menu_info_status = "Menu details not found for this suborder.";
        } else {
            // $suborder->menu_info_status = null; 
            Log::info("MENUINFORMATION : " . json_encode($menuInformation));

        }
        $menuCollection = collect($menuInformation);
               
        foreach ($suborder->items as &$item) {
            $menuItem = $menuCollection->firstWhere('itemdetail_id', (int) $item->itemdetails_ID);
            Log::info("Looking for itemdetails_ID: " . $item->itemdetails_ID . " in menuCollection: " . json_encode($menuCollection));

            // Define a consistent structure with default null values
            $item->item_name = $menuItem['item_name'] ?? null;
            $item->item_detail_id = $menuItem['itemdetail_id'] ?? null;
            $item->item_description = $menuItem['item_description'] ?? null;
            $item->timesensitive = $menuItem['timesensitive'] ?? null;
            $item->preparation_time = $menuItem['preparation_time'] ?? null;
            $item->itemPicture = $menuItem['itemPicture'] ?? null;
            $item->variation_name = $menuItem['variation_name'] ?? null;
            $item->price = $menuItem['price'] ?? null;
            $item->additional_info = $menuItem['additional_info'] ?? null;
            $item->item_category_id = $menuItem['item_category_id'] ?? null;
            $item->item_category_name = $menuItem['item_category_name'] ?? null;
            $item->attributes = $menuItem['attributes'] ?? [];
            
            if (!$menuItem) {
                Log::info("No menu item found for itemdetails_ID: " . $item->itemdetails_ID);
                $item->error_message = "Item details not found for itemdetails_ID: " . $item->itemdetails_ID;
            } else {
                $item->error_message = null;
            }
        }
    }

    return response()->json([
        'cart' => $cart,
        'suborders' => $suborders
    ]);
}



public function placeOrder(Request $request)
{
    Log::info('Incoming Request Data:', $request->all());

    try {
        $validatedData = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'delivery_address_id' => 'required|integer|exists:addresses,id',
            'order_details' => 'required|array',
            'order_details.*.vendor_id' => 'required|integer|exists:vendors,id',
            'order_details.*.shop_id' => 'required|integer|exists:shops,id',
            'order_details.*.branch_id' => 'required|integer|exists:branches,id',
            'order_details.*.item_detail_id' => 'required|integer|',//exists:itemdetails,id',
        'order_details.*.price' => 'required|numeric|min:0',
        'order_details.*.quantity' => 'required|integer|min:1',
    ] );

    DB::beginTransaction();
    $orderId = DB::table( 'orders' )->insertGetId( [
        'customers_ID' => $validatedData[ 'customer_id' ],
        'addresses_ID' => $validatedData[ 'delivery_address_id' ],
        'order_date' => now(),
        'order_status' => 'pending',
        'total_amount' => 0,
    ] );

    $items = $validatedData[ 'order_details' ];
    $groupedData = [];
    $totalOrderAmount = 0;

    foreach ( $items as $item ) {
        $this->validateRelationships( $item );

        $vendor = DB::table( 'vendors' )->where( 'id', $item[ 'vendor_id' ] )->first();

        $groupKey = "{$item['vendor_id']}_{$item['shop_id']}_{$item['branch_id']}";

        if ( !isset( $groupedData[ $groupKey ] ) ) {
            $groupedData[ $groupKey ] = [
                'vendor_id' => $item[ 'vendor_id' ],
                'shop_id' => $item[ 'shop_id' ],
                'branch_id' => $item[ 'branch_id' ],
                'items' => [],
                'total_amount' => 0,
                'vendor_type' => $vendor->vendor_type,
            ];
        }

        $itemKey = $item[ 'item_detail_id' ];
        if ( !isset( $groupedData[ $groupKey ][ 'items' ][ $itemKey ] ) ) {
            $groupedData[ $groupKey ][ 'items' ][ $itemKey ] = [
                'item_detail_id' => $item[ 'item_detail_id' ],
                'quantity' => 0,
                'price' => $item[ 'price' ],
                'total' => 0,
            ];
        }

        $groupedData[ $groupKey ][ 'items' ][ $itemKey ][ 'quantity' ] += $item[ 'quantity' ];
        $groupedData[ $groupKey ][ 'items' ][ $itemKey ][ 'total' ] += $item[ 'price' ] * $item[ 'quantity' ];

        $totalOrderAmount += $item[ 'price' ] * $item[ 'quantity' ];
    }

    foreach ( $groupedData as $groupKey => &$groupData ) {
        $groupData[ 'items' ] = array_values( $groupData[ 'items' ] );
    }

    $responseGroups = [];
    foreach ( $groupedData as $groupKey => &$groupData ) {
        $groupData[ 'items' ] = array_values( $groupData[ 'items' ] );

        $totalAmount = 0;
        foreach ( $groupData[ 'items' ] as $item ) {
            $totalAmount += $item[ 'total' ];
        }

        $suborderId = $this->createOrUpdateSuborder( $groupData, $orderId );

        $suborderTotal = 0;
        foreach ( $groupData[ 'items' ] as $item ) {
            $suborderTotal += $item[ 'total' ];

            DB::table( 'orderdetails' )->insert( [
                'quantity' => $item[ 'quantity' ],
                'price' => $item[ 'price' ],
                'total' => $item[ 'total' ],
                'suborders_ID' => $suborderId,
                'itemdetails_ID' => $item[ 'item_detail_id' ],
            ] );
        }

        
        DB::table( 'suborders' )->where( 'id', $suborderId )->update( [ 'total_amount' => $suborderTotal ] );
//response for each vendor...
        $responseGroup = [
            'vendor_id' => $groupData[ 'vendor_id' ],
            'shop_id' => $groupData[ 'shop_id' ],
            'branch_id' => $groupData[ 'branch_id' ],
            'total_amount' => $totalAmount,
            'items' => $groupData[ 'items' ],
            'vendor_type' => $groupData[ 'vendor_type' ],
        ];

        // if  API Vendor, forward order to the vendor API
        if ($groupData['vendor_type'] === 'API Vendor') {
            $apiResponse = $this->forwardOrderToVendorAPI($groupData['vendor_id'], 
            array_merge($groupData, ['total_amount' => $totalAmount]),
               $suborderId );
            $responseGroup['api_response'] = $apiResponse;
        }
    
        $responseGroups[] = $responseGroup;
    }

        DB::table('orders')->where('id', $orderId)->update(['total_amount' => $totalOrderAmount]);

        DB::commit();

        return response()->json([
            'message' => 'Order placed successfully',
            'order_id' => $orderId,
            'total_amount' => $totalOrderAmount,
           // 'groups' => $responseGroups,
        ], 201);
    } catch (ValidationException $e) {
        return response()->json([
            'message' => 'Validation Failed',
            'errors' => $e->errors(),
        ], 422);

    } catch (Exception $e) {
        DB::rollBack();
        return response()->json(['error' => $e->getMessage()], 500);
    }
}


private function forwardOrderToVendorAPI($vendorId, $groupData, $suborderId)
{
    if (empty($groupData['branch_id']) || !is_numeric($groupData['branch_id'])) {
        return response()->json(['error' => 'Invalid or missing branch_id'], 400);
    }

    if (empty($groupData['items']) || !is_array($groupData['items'])) {
        return response()->json(['error' => 'Invalid or missing items'], 400);
    }

    if (empty($groupData['total_amount']) || !is_numeric($groupData['total_amount'])) {
        return response()->json(['error' => 'Invalid or missing total_amount'], 400);
    }

    $apiVendor = DB::table('vendors')
        ->join('shops', 'vendors.id', '=', 'shops.vendors_ID')
        ->join('branches', 'shops.id', '=', 'branches.shops_ID')
        ->join('apivendor', 'branches.id', '=', 'apivendor.branches_ID')
        ->where('vendors.id', $vendorId)
        ->where('branches.id', $groupData['branch_id'])
        ->select('apivendor.id as apivendor_ID', 'apivendor.api_base_url', 'apivendor.api_key', 'apivendor.response_format')
        ->first();

    if (!$apiVendor) {
        return response()->json(['error' => 'API vendor not found for the given vendor, shop, or branch'], 404);
    }

    $apiMethod = DB::table('apimethods')
        ->where('apimethods.apivendor_ID', $apiVendor->apivendor_ID)
        ->where('apimethods.method_name', 'placeOrder')
        ->select('apimethods.endpoint', 'apimethods.http_method')
        ->first();

    if (!$apiMethod) {
        return response()->json(['error' => 'API method not found for the given API vendor'], 404);
    }

    $mappings = DB::table('mapping')
        ->join('variables', 'mapping.variable_ID', '=', 'variables.ID')
        ->where('mapping.branch_ID', $groupData['branch_id'])
        ->where('mapping.apivendor_ID', $apiVendor->apivendor_ID)
        ->pluck('mapping.api_values', 'variables.tags')
        ->toArray();

    if (empty($mappings)) {
        return response()->json(['error' => 'No mappings found for the given branch and vendor'], 404);
    }

    $orderDetails = [];
    foreach ($groupData['items'] as $item) {
        $orderDetails[] = [
            $mappings['item_detail_id'] => $item['item_detail_id'],
            $mappings['quantity'] => $item['quantity'],
            $mappings['price'] => $item['price'],
            $mappings['total'] => $item['total'],
        ];
    }

    $apiData = [
        $mappings['total_amount'] => $groupData['total_amount'],
        $mappings['order_type'] => 'lmd',
        $mappings['order_details'] => $orderDetails,
    ];

    $url = $apiVendor->api_base_url . $apiMethod->endpoint;

    $contentType = ($apiVendor->response_format === 'xml' || $apiVendor->response_format === 'XML') ? 'application/xml' : 'application/json';

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiVendor->api_key,
        'Content-Type' => $contentType . ($apiVendor->response_format === 'xml' ? '; charset=utf-8' : ''),
    ])->{$apiMethod->http_method}($url, $apiData);

    if ($response->successful()) {
        $vendorResponse = $response->json();

        // Extract vendor_order_id (from resposne)and update suborders table
        $vendorOrderIdKey = $mappings['vendor_order_id'] ?? 'id'; //default is id if no mapping avaialable
        $vendorOrderId = $vendorResponse[$vendorOrderIdKey] ?? null;

        if ($vendorOrderId) {
            DB::table('suborders')->where('id', $suborderId)->update(['vendor_order_id' => $vendorOrderId ,'status' => 'in_progress']);
        }

        return response()->json([
            'status' => 'success',
            'vendor_order_id' => $vendorOrderId,
            'vendor_response' => $vendorResponse,
        ]);
    }

    return response()->json([
        'error' => 'Unable to forward order to vendor',
        'vendor_response' => $response->body(),
    ], 500);
}


private function validateRelationships($item)
{
    $shop = DB::table('shops')->where('id', $item['shop_id'])->where('vendors_ID', $item['vendor_id'])->first();
    if (!$shop) {
        throw new Exception("Invalid shop for vendor ID: {$item['vendor_id']}");
    }

    $branch = DB::table('branches')->where('id', $item['branch_id'])->where('shops_ID', $item['shop_id'])->first();
    if (!$branch) {
        throw new Exception("Invalid branch for shop ID: {$item['shop_id']}");
    }

    $itemDetails = DB::table('itemdetails')->where('id', $item['item_detail_id'])->first();
    if (!$itemDetails) {
        throw new Exception("Item details not found for item detail ID: {$item['item_detail_id']}");
    }
}
private function createOrUpdateSuborder($groupData, $orderId)
{
    $existingSuborder = DB::table('suborders')
        ->where('vendor_id', $groupData['vendor_id'])
        ->where('shop_id', $groupData['shop_id'])
        ->where('branch_id', $groupData['branch_id'])
        ->where('orders_ID', $orderId)
        ->first();

    if ($existingSuborder) {
        return $existingSuborder->id;
    }

    return DB::table('suborders')->insertGetId([
        'vendor_type' => $groupData['vendor_type'],
        'vendor_id' => $groupData['vendor_id'],
        'shop_id' => $groupData['shop_id'],
        'branch_id' => $groupData['branch_id'],
        'orders_ID' => $orderId,
        'status' => 'pending',
        'total_amount' => 0,
    ]);
}

   

        // public function getCustomerOrders( $customerId ) {
        //     $orders = DB::table( 'orders' )
        //     ->where( 'customers_ID', $customerId )
        //     ->get();

        //     if ( $orders->isEmpty() ) {
        //         return response()->json( [ 'message' => 'No orders found for this customer' ], 404 );
        //     }

        //     return response()->json(  $orders , 200 );
        // }
        public function getCustomerOrders($customerId)
        {
            $orders = DB::table('orders')
                ->where('customers_ID', $customerId)
                ->orderByDesc(column: 'orders.order_date')
                ->get();
        
            if ($orders->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No orders found for this customer',
                    'data' => []
                ], 404);
            }
        
            return response()->json([
                'success' => true,
                'message' => 'Orders retrieved successfully',
                'data' => $orders
            ], 200);
        }
        
        public function getOrderDetails($orderId)
        {
            $orderDetails = DB::table('orders')
                ->join('suborders', 'orders.id', '=', 'suborders.orders_ID')
                ->join('orderdetails', 'suborders.id', '=', 'orderdetails.suborders_ID')
                ->join('itemdetails', 'orderdetails.itemdetails_ID', '=', 'itemdetails.id')
                ->join('items', 'itemdetails.item_ID', '=', 'items.id')
                ->select(
                    'orders.id as order_id',
                    'orders.order_date',
                    'orders.order_status',
                    'orders.total_amount as order_total_amount',
                    'suborders.id as suborder_id',
                    'suborders.status as suborder_status',
                    'suborders.payment_status as suborder_payment_status',
                    'suborders.total_amount as suborder_total_amount',
                    'suborders.vendor_type',
                    'suborders.vendor_order_id',
                    'suborders.estimated_delivery_time',
                    'suborders.delivery_time',
                    'suborders.deliveryboys_ID',
                    'suborders.vendor_ID',
                    'suborders.shop_ID',
                    'suborders.branch_ID',
                    'suborders.created_at as suborder_created_at',
                    'suborders.updated_at as suborder_updated_at',
                    'itemdetails.id as item_detail_id',
                    'items.name as item_name',
                    'itemdetails.price as item_price',
                    'orderdetails.quantity as item_quantity',
                    'orderdetails.total as item_total'
                )
                ->where('orders.id', $orderId)
                ->orderByDesc(column: 'orders.order_date')
                ->get();
        
            if ($orderDetails->isEmpty()) {
                return response()->json(['message' => 'Order not found or no details available'], 404);
            }
        
            $groupedOrderDetails = [];
        
            foreach ($orderDetails as $detail) {
                $suborderId = $detail->suborder_id;
        
                if (!isset($groupedOrderDetails[$suborderId])) {
                    $groupedOrderDetails[$suborderId] = [
                        'suborder_id' =>$detail->suborder_id,
                        'suborder_status' => $detail->suborder_status,
                        'suborder_payment_status' => $detail->suborder_payment_status,
                        'suborder_total_amount' => $detail->suborder_total_amount,
                        'vendor_type' => $detail->vendor_type,
                        'vendor_order_id' => $detail->vendor_order_id,
                        'estimated_delivery_time' => $detail->estimated_delivery_time,
                        'delivery_time' => $detail->delivery_time,
                        'deliveryboys_ID' => $detail->deliveryboys_ID,
                        'vendor_ID' => $detail->vendor_ID,
                        'shop_ID' => $detail->shop_ID,
                        'branch_ID' => $detail->branch_ID,
                        'suborder_created_at' => $detail->suborder_created_at,
                        'suborder_updated_at' => $detail->suborder_updated_at,
                        'items' => [],
                    ];
        
                    // Call the menu function
                    $menuResponse = $this->getVendorShopBranchMenu(
                        $detail->vendor_ID,
                        $detail->shop_ID,
                        $detail->branch_ID
                    );
        

                    Log::info("IDs - Vendor: {$detail->vendor_ID}, Shop: {$detail->shop_ID}, Branch: {$detail->branch_ID}");

                    Log::info("Raw menu response: " . json_encode($menuResponse));
        
                    if ($menuResponse instanceof \Illuminate\Http\JsonResponse) {
                        $menuData = $menuResponse->getData(true);
                        Log::info("Extracted from JsonResponse: " . json_encode($menuData));
                     // this makes it usable
                        

                    } else {
                        $menuData = json_decode($menuResponse, true);
                        Log::info("Decoded JSON: " . json_encode($menuData));
                    }
        
                    Log::info("Final processed menu data: " . json_encode($menuData));
        
                    $groupedOrderDetails[$suborderId]['menu_info'] = collect($menuData);
                }
        
                $menuCollection = $groupedOrderDetails[$suborderId]['menu_info'];
                $menuItem = $menuCollection->firstWhere('itemdetail_id', (int)$detail->item_detail_id);
                Log::info("Looking for itemdetail_id: {$detail->item_detail_id} in menu IDs: " . collect($menuData)->pluck('itemdetail_id')->join(', '));

                $groupedOrderDetails[$suborderId]['items'][] = [
                    'item_detail_id' => $menuItem['itemdetail_id'] ?? $detail->item_detail_id,
                    'item_name' => $menuItem['item_name'] ?? $detail->item_name,
                    'item_quantity' => $detail->item_quantity,
                    'item_total' => $detail->item_total,
                    'item_description' => $menuItem['item_description'] ?? null,
                    'timesensitive' => $menuItem['timesensitive'] ?? null,
                    'preparation_time' => $menuItem['preparation_time'] ?? null,
                    'itemPicture' => $menuItem['itemPicture'] ?? null,
                    'variation_name' => $menuItem['variation_name'] ?? null,
                    'price' => $menuItem['price'] ?? $detail->item_price,
                    'additional_info' => $menuItem['additional_info'] ?? null,
                    'item_category_id' => $menuItem['item_category_id'] ?? null,
                    'item_category_name' => $menuItem['item_category_name'] ?? null,
                    'attributes' => $menuItem['attributes'] ?? [],
                    'error_message' => $menuItem ? null : "Item details not found for itemdetail_id: " . $detail->item_detail_id,
                ];
            }
        
            // Cleanup before response
            foreach ($groupedOrderDetails as &$suborder) {
                unset($suborder['menu_info']);
            }
        
            return response()->json([
                'order_id' => $orderId,
                'order_date' => $orderDetails->first()->order_date,
                'order_status' => $orderDetails->first()->order_status,
                'order_total_amount' => $orderDetails->first()->order_total_amount,
                'suborders' => array_values($groupedOrderDetails),
            ], 200);
        }






        public function getStatuses()
        {
            // Possible order statuses
            $orderStatuses = [
                'pending' => 'pending',
                'confirmed' => 'confirmed',
                'completed' => 'completed',
                'cancelled' => 'cancelled'
            ];
        
            // Possible suborder statuses
            $suborderStatuses = [
                'pending' => 'pending',
                'in_progress' => 'in_progress',
                'ready' => 'ready',
                'assigned' => 'assigned',
                'picked_up' => 'picked_up',
                'handover_confirmed' => 'handover_confirmed',
                'in_transit' => 'in_transit',
                'delivered' => 'delivered',
                'cancelled' => 'cancelled'
            ];
        
            // Possible order payment statuses
            $orderPaymentStatuses = [
                'pending' => 'pending',
                'completed' => 'completed',
                'failed' => 'failed'
            ];
        
            // Possible suborder payment statuses
            $suborderPaymentStatuses = [
                'pending' => 'pending',
                'confirmed_by_customer' => 'confirmed_by_customer',
                'confirmed_by_deliveryboy' => 'confirmed_by_deliveryboy',
                'confirmed_by_vendor' => 'confirmed_by_vendor'
            ];
        
            return response()->json([
                'orderStatuses' => $orderStatuses,
                'suborderStatuses' => $suborderStatuses,
                'orderPaymentStatuses' => $orderPaymentStatuses,
                'suborderPaymentStatuses' => $suborderPaymentStatuses
            ]);
        }
        








 
        public function addItemRating(Request $request)
        {
            $request->validate([
                'suborders_ID' => 'required|exists:suborders,id',
                'itemdetails_ID' => 'required|',//exists:itemdetails,id',
                'rating_stars' => 'required|integer|min:1|max:5',
                'comments' => 'nullable|string|max:255',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate image files
            ]);
        
            DB::beginTransaction();
        
            try {
                // Insert the rating into the itemrating table
                $ratingId = DB::table('itemrating')->insertGetId([
                    'suborders_ID' => $request->input('suborders_ID'),
                    'itemdetails_ID' => $request->input('itemdetails_ID'),
                    'rating_stars' => $request->input('rating_stars'),
                    'comments' => $request->input('comments'),
                    'rating_date' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        
                // Check if images are provided and handle uploads
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $image) {
                        // Store the image and get the file path
                        $path = $image->store('rated_item_images', 'public');
        
                        // Insert the image path into the rateditemimages table
                        DB::table('rateditemimages')->insert([
                            'image_path' => $path,
                            'suborders_ID' => $request->input('suborders_ID'),
                            'itemdetails_ID' => $request->input('itemdetails_ID'),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
        
                DB::commit();
        
                return response()->json(['message' => 'Rating and images added successfully'], 201);
            } catch (\Exception $e) {
                DB::rollBack();
        
                return response()->json([
                    'message' => 'Failed to add rating and images',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }
        
        public function reorder($orderId)
        {
            try {
                $orderDetails = DB::table('orderdetails')
                    ->join('suborders', 'orderdetails.suborders_ID', '=', 'suborders.ID')
                    ->join('itemdetails', 'orderdetails.itemdetails_ID', '=', 'itemdetails.ID')
                    ->join('orders', 'suborders.orders_ID', '=', 'orders.ID')
                    ->select(
                        'itemdetails.ID as item_detail_id',
                        'orderdetails.quantity',
                        'itemdetails.price',
                        'orders.customers_ID as customer_id',
                        'orders.addresses_ID as delivery_address_id',
                        'suborders.vendor_ID',
                        'suborders.shop_ID',
                        'suborders.branch_ID'
                    )
                    ->where('suborders.orders_ID', $orderId)
                    ->get();
        
                if ($orderDetails->isEmpty()) {
                    return response()->json(['message' => 'Order not found or has no items'], 404);
                }
        
                $customerId = $orderDetails->first()->customer_id;
                $deliveryAddressId = $orderDetails->first()->delivery_address_id;
        
                $orderRequest = [
                    'customer_id' => $customerId,
                    'delivery_address_id' => $deliveryAddressId,
                    'order_details' => $orderDetails->map(function ($detail) {
                        return [
                            'item_detail_id' => $detail->item_detail_id,
                            'quantity' => $detail->quantity,
                            'price' => $detail->price,
                            'vendor_id' => $detail->vendor_ID,
                            'shop_id' => $detail->shop_ID,
                            'branch_id' => $detail->branch_ID,
                        ];
                    })->toArray(),
                ];
        
                // Call placeOrder function
                return $this->placeOrder(new Request($orderRequest));
            } catch (\Exception $e) {
                return response()->json(['message' => 'An error occurred while reordering.', 'error' => $e->getMessage()], 500);
            }
        }
        
        

    


    //Addresses update, delete, add, getAllAddress

    public function addAddress( Request $request, $customerId ) {
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
            'lmd_users_ID' => $customerId,
            'created_at' => now(),
            'updated_at' => now(),
        ] );

        return response()->json( [ 'message' => 'Address added successfully' ], 201 );
    }

    public function updateAddress( Request $request, $customerId, $addressId ) {
        // Validate input
        $validated = $request->validate( [
            'address_type' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ] );

        // Check if customer exists
        $customer = DB::table( 'customers' )
        ->where( 'ID', $customerId )
        ->first();

        if ( !$customer ) {
            return response()->json( [ 'message' => 'Customer not found' ], 404 );
        }

        // Verify the address belongs to the customer
        $address = DB::table( 'addresses' )
        ->where( 'ID', $addressId )
        ->where( 'lmd_users_ID', $customer->lmd_users_ID )
        ->first();

        if ( !$address ) {
            return response()->json( [ 'message' => 'Address not found or does not belong to this customer' ], 404 );
        }

        // Update the address
        DB::table( 'addresses' )
        ->where( 'ID', $addressId )
        ->update( array_merge( $validated, [
            'updated_at' => now(),
        ] ) );

        return response()->json( [ 'message' => 'Address updated successfully' ], 200 );
    }

    public function deleteAddress( $customerId, $addressId ) {
        // Check if customer exists
        $customer = DB::table( 'customers' )
        ->where( 'ID', $customerId )
        ->first();

        if ( !$customer ) {
            return response()->json( [ 'message' => 'Customer not found' ], 404 );
        }

        // Verify the address belongs to the customer
        $address = DB::table( 'addresses' )
        ->where( 'ID', $addressId )
        ->where( 'lmd_users_ID', $customer->lmd_users_ID )
        ->first();

        if ( !$address ) {
            return response()->json( [ 'message' => 'Address not found or does not belong to this customer' ], 404 );
        }

        // Delete the address
        DB::table( 'addresses' )
        ->where( 'ID', $addressId )
        ->delete();

        return response()->json( [ 'message' => 'Address deleted successfully' ], 200 );
    }

    public function getAllAddresses( $customerId ) {
        $addresses = DB::table( 'addresses' )
        ->where( 'lmd_users_ID', $customerId )
        ->get();

        if ( $addresses->isEmpty() ) {
            return response()->json( [ 'message' => 'No addresses found for this customer' ], 404 );
        }

        return response()->json( [ 'addresses' => $addresses ], 200 );
    }

  


public function cancelOrder($orderId)
{
    // Retrieve the order by ID
    $order = DB::table('orders')->where('id', $orderId)->first();

    // Check if the order exists
    if (!$order) {
        return response()->json(['message' => 'Order not found'], 404);
    }

    // Check if the order is in a cancellable status
    if ($order->order_status !== 'pending') {
        return response()->json(['message' => 'Order cannot be cancelled because it is not pending'], 400);
    }

    // Retrieve all related suborders
    $suborders = DB::table('suborders')->where('orders_ID', $orderId)->get();

    // Check if all related suborders have a status of 'pending'
    $allPending = $suborders->every(function ($suborder) {
        return $suborder->status === 'pending';
    });

    if (!$allPending) {
        return response()->json(['message' => 'Order cannot be cancelled because not all suborders are pending'], 400);
    }

    // Begin a transaction
    DB::beginTransaction();

    try {
        // Update the order status to 'cancelled'
        DB::table('orders')
            ->where('id', $orderId)
            ->update([
                'order_status' => 'cancelled',
                'updated_at' => now(),
            ]);

        // Update the status of all related suborders to 'cancelled'
        DB::table('suborders')
            ->where('orders_ID', $orderId)
            ->update([
                'status' => 'cancelled',
                'updated_at' => now(),
            ]);

        // Commit the transaction
        DB::commit();

        return response()->json(['message' => 'Order and all related suborders cancelled successfully'], 200);
    } catch (\Exception $e) {
        // Rollback the transaction in case of an error
        DB::rollBack();

        return response()->json(['message' => 'Failed to cancel the order', 'error' => $e->getMessage()], 500);
    }
}


public function getLiveTracking($suborderId)
{
    try {
        // Retrieve all location tracking entries for the given suborder ID, ordered by timestamp
        $locations = LocationTracking::where('suborders_ID', $suborderId)
            ->orderBy('time_stamp', 'asc') // or 'desc' if you want latest first
            ->get(['latitude', 'longitude', 'status', 'time_stamp']);

        // Check if any location entries exist
        if ($locations->isEmpty()) {
            return response()->json(['error' => 'No location data available for this suborder.'], 404);
        }

        // Return all tracking points
        return response()->json([
            'message' => 'All tracking data for the suborder retrieved successfully.',
            'data' => $locations
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Something went wrong.',
            'details' => $e->getMessage()
        ], 500);
    }
}


public function getPaymentStatus($suborderId)
{
    $suborder = Suborder::find($suborderId);

    if (!$suborder) {
        return response()->json(['error' => 'Suborder not found.'], 404);
    }

    return response()->json([
        'suborder_id' => $suborderId,
        'payment_status' => $suborder->payment_status,
    ]);
}
public function confirmOrderDelivery($suborderId)
{
    $suborder = Suborder::findOrFail($suborderId);

    if ($suborder->status !== 'in_transit' && $suborder->status !== 'handover_confirmed') {
        return response()->json(['error' => 'Order cannot be confirmed as delivered in the current state.'], 400);
    }

 // Find the most recent LocationTracking record with status 'reached_destination' for the suborder
 $location = LocationTracking::where('suborders_ID', $suborderId)
 ->where('status', 'reached_destination')
 ->latest()  // Get the most recent entry
 ->first();

// If no reached destination record is found, return an error
if (!$location) {
return response()->json(['error' => 'No reached destination record found for this suborder.'], 400);
}

    $suborder->status = 'delivered';
    $suborder->save();

   
 // Insert a new location tracking record with the same latitude and longitude as the reached destination
 LocationTracking::create([
    'latitude' => $location->latitude,  // Use latitude from the reached destination record
    'longitude' => $location->longitude, // Use longitude from the reached destination record
    'status' => 'delivered',
    'suborders_ID' => $suborderId
]);


    return response()->json(['message' => 'Order confirmed as delivered.']);
}


public function confirmPaymentByCustomer($suborderId)
{
    $suborder = Suborder::findOrFail($suborderId);

    // Validate the current status
    if ($suborder->payment_status !== 'pending') {
        return response()->json(['error' => 'Payment is already confirmed or not in pending state.'], 400);
    }

    // Update payment status to confirmed by customer
    $suborder->payment_status = 'confirmed_by_customer';
    $suborder->save();

    return response()->json(['message' => 'Payment confirmed by customer.']);
}













    public function getPreviousWeekOrders( $customerId ) {
        $oneWeekAgo = Carbon::now()->subDays( 7 );

        $orders = Order::where( 'customers_ID', $customerId )
        ->where( 'order_date', '>=', $oneWeekAgo )
        ->get();

        if ( $orders->isEmpty() ) {
            return response()->json( [ 'message' => 'No orders found for the past week.' ], 404 );
        }

        return response()->json( $orders, 200 );
    }

    public function getPreviousMonthOrders( $customerId ) {
        $startOfMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfMonth = Carbon::now()->subMonth()->endOfMonth();

        $orders = Order::where( 'customers_ID', $customerId )
        ->whereBetween( 'order_date', [ $startOfMonth, $endOfMonth ] )
        ->get();

        if ( $orders->isEmpty() ) {
            return response()->json( [ 'message' => 'No orders found for the past month.' ], 404 );
        }

        return response()->json( $orders, 200 );
    }

    /////////////////////////////////////////////////////////////////////
    ////////////////////////    COURIER ORDER    ////////////////////////
    ////////////////////////////////////////////////////////////////////

    // Function to fetch all item categories
public function getAllItemCategories()
{
    // Fetch all categories from the database
    $categories = CourierItemCategory::all();

    // Check if categories exist
    if ($categories->isEmpty()) {
        return response()->json([
            'status' => 'error',
            'message' => 'No item categories found.',
        ], 404);
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Item categories fetched successfully.',
        'categories' => $categories,
    ]);
}

// Function to fetch items by category
public function getItemsByCategory($categoryId)
{
    // Fetch items by category ID
    $items = CourierItem::where('courieritemcategory_ID', $categoryId)->get();

    // Check if items exist for the selected category
    if ($items->isEmpty()) {
        return response()->json([
            'status' => 'error',
            'message' => 'No items found for this category.',
        ], 404);
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Items fetched successfully.',
        'items' => $items,
    ]);
}



public function placeCourierOrder(Request $request)
{
    // Validate input
    $validator = Validator::make($request->all(), [
        'pickup_address_ID' => 'required|exists:addresses,id',
        'delivery_address_ID' => 'required|exists:addresses,id',
        'payment_method' => 'nullable|string|max:50',
        'customers_ID' => 'required|exists:lmd_users,id',
        'item_ID' => 'required|exists:courieritem,id',
        'package_weight' => 'nullable|numeric',
        'height' => 'nullable|numeric',
        'width' => 'nullable|numeric',
        'depth' => 'nullable|numeric',
        'images' => 'nullable|array', // Optional array of images
        'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validate image files
    ]);

    // If validation fails, return the errors with a 422 status code
    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    // Create a new CourierOrder
    $order = new CourierOrder();
    $order->pickup_address_ID = $request->pickup_address_ID;
    $order->delivery_address_ID = $request->delivery_address_ID;
    $order->payment_method = $request->payment_method ?? 'cash_on_delivery'; // Default value
    $order->customers_ID = $request->customers_ID;
    $order->item_ID = $request->item_ID;
    $order->save();

    // Optionally, if the customer provided additional info like package weight, dimensions
    if ($request->has('package_weight') || $request->has('height') || $request->has('width') || $request->has('depth')) {
        $additionalInfo = new CourierAdditionalInfo();
        $additionalInfo->courierorder_ID = $order->id;
        $additionalInfo->package_weight = $request->package_weight;
        $additionalInfo->height = $request->height;
        $additionalInfo->width = $request->width;
        $additionalInfo->depth = $request->depth;
        $additionalInfo->save();
    }

    // Handle image uploads if provided
    if ($request->has('images')) {
        foreach ($request->file('images') as $image) {
            // Store each image and save its URL in the courierimages table
            $imagePath = $image->store('courierimages', 'public');
            $courierImage = new CourierImage();
            $courierImage->courierorder_ID = $order->id;
            $courierImage->image_url = $imagePath;
            $courierImage->save();
        }
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Courier order placed successfully!',
        'order' => $order
    ]);
}
public function getCustomerCourierOrders($customers_ID)
{
    // Validate the customer ID
    $customerExists = DB::table('lmd_users')->where('id', $customers_ID)->exists();

    if (!$customerExists) {
        return response()->json([
            'status' => 'error',
            'message' => 'Customer not found.'
        ], 404);
    }

    // Fetch all courier orders for the given customer ID
    $orders = DB::table('courierorder')
        ->where('customers_ID', $customers_ID)
        ->get();

    // Include additional information and images manually
    foreach ($orders as $order) {
        $order->additionalInfo = DB::table('courieradditionalinfo')
            ->where('courierorder_ID', $order->id)
            ->first();

        $order->images = DB::table('courierimages')
            ->where('courierorder_ID', $order->id)
            ->get();
    }

    // Return response
    return response()->json([
        'status' => 'success',
        'message' => 'Customer courier orders retrieved successfully!',
        'orders' => $orders
    ]);
}





public function confirmDelivery($courierOrderId, $customerId)
{
    // Validate the customer ID
    $customerExists = DB::table('customers')->where('id', $customerId)->exists();
    if (!$customerExists) {
        return response()->json([
            'status' => 'error',
            'message' => 'Customer not found.'
        ], 404);
    }

    // Fetch the courier order
    $courierOrder = DB::table('courierorder')
        ->where('id', $courierOrderId)
        ->where('customers_ID', $customerId) // Ensure the order belongs to this customer
        ->where('order_status', 'delivered') // Ensure the order is marked as 'delivered'
        ->first();

    if (!$courierOrder) {
        return response()->json([
            'status' => 'error',
            'message' => 'Courier order not found or not yet delivered.'
        ], 404);
    }

    // Update the courier order to confirm delivery
    DB::table('courierorder')
        ->where('id', $courierOrderId)
        ->update([
            'customer_confirmed' => true, // Set confirmation to true
        ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Order confirmed by customer successfully!',
    ]);
}


public function getLiveTrackingData($courierOrderId)
{
    $trackingData = DB::table('courierlivetracking')
        ->where('courierorder_ID', $courierOrderId)
        ->orderBy('time_stamp', 'desc')
        ->first();

    if (!$trackingData) {
        return response()->json([
            'status' => 'error',
            'message' => 'No tracking data available.',
        ], 404);
    }

    return response()->json([
        'status' => 'success',
        'data' => $trackingData,
    ]);
}







///////////////////////////////////////////
//////////////////////////////////////////////
/////////////////////////////////////////////
//GET KFC MENU
public function getMenuFromVendor($branchId)
{
    // Get the API details using the branchId
    $apiDetails = DB::table('apivendor')
        ->join('apimethods', 'apivendor.id', '=', 'apimethods.apivendor_ID')
        ->where('apivendor.branches_ID', $branchId) // Filter by branchId
        ->where('apimethods.method_name', 'getMenu') // Assuming the method is 'getMenu'
        ->select('apivendor.api_base_url', 'apimethods.endpoint', 'apimethods.http_method', 'apivendor.api_key')
        ->first();

    if (!$apiDetails) {
        return response()->json(['error' => 'API method or branch not found'], 404);
    }

    // Build the full URL dynamically
    $url = $apiDetails->api_base_url . $apiDetails->endpoint;

    // Debugging: Log the URL being checked
    Log::info('Checking server status with URL: ' . $url);

    // Check if the KFC server is reachable by calling the full URL
    try {
        $serverStatus = Http::timeout(5)->get($url); // Use the full URL instead of just the base URL

        // Log the server status response
        Log::info('Server Status Response: ' . $serverStatus->status());

        // If the server is not reachable (status code not 2xx), return an error
        if (!$serverStatus->successful()) {
            return response()->json(['error' => 'KFC server is not running or unreachable'], 503);
        }
    } catch (\Exception $e) {
        // If there’s an exception (e.g., server is down), catch it and return a response
        return response()->json(['error' => 'KFC server is not running or unreachable: Please Try Again Later' . $e->getMessage()], 503);
    }

    // Make the actual API request
    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiDetails->api_key,
        ])->{$apiDetails->http_method}($url);

        // Check if the response is successful
        if ($response->successful()) {
            return response()->json($response->json());
        }

        // Return error if the request failed
        return response()->json(['error' => 'Unable to fetch menu'], 500);
    } catch (\Exception $e) {
        // Handle any exceptions that occur during the API call
        return response()->json(['error' => 'Error during API request: ' . $e->getMessage()], 500);
    }
}

}

