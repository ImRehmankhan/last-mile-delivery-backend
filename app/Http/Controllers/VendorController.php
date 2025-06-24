<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\Shop;
use App\Models\Branch;
use App\Models\Suborder;
use App\Models\ShopCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Exception;

class VendorController extends Controller
{
    public function vendorSignup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:lmd_users,email',
            'phone_no' => 'required|string|max:15|unique:lmd_users,phone_no',
            'password' => 'required|string|min:1',
            'cnic' => 'required|string|max:15|unique:lmd_users,cnic',
            'profile_picture' => 'nullable|file|mimes:jpeg,png,jpg|max:10240',
            'vendor_type' => 'required|string|in:In-App Vendor,API Vendor',
            'address_type' => 'required|string|max:50',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'zip_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors() // Get specific field errors
            ], 422);
        }
        $validated = $validator->validated(); // ✅ Get validated data
        DB::beginTransaction();

        try {
            // Handle profile picture upload
            $profilePicturePath = null;
            if ($request->hasFile('profile_picture')) {
                $profilePicturePath = $request->file('profile_picture')->store('vendorImages', 'public');
            }

            // Insert into lmd_users table
            $userId = DB::table('lmd_users')->insertGetId([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone_no' => $validated['phone_no'],
                'password' => $validated['password'],
                'cnic' => $validated['cnic'],
                'profile_picture' => $profilePicturePath,
                'lmd_user_role' => 'vendor',

            ]);

            // Insert into vendors table
            DB::table('vendors')->insert([
                'vendor_type' => $validated['vendor_type'],
                'lmd_users_ID' => $userId,

            ]);

            // Insert into addresses table
            DB::table('addresses')->insert([
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
                'message' => 'Vendor signed up successfully',
                'user_id' => $userId,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Signup failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }





    public function getVendorData($id)
    {
        $baseUrl = url('/'); // Get the base URL (e.g., http://192.168.43.63:8000)

        $vendor = DB::table('lmd_users')
            ->join('vendors', 'lmd_users.id', '=', 'vendors.lmd_users_ID')
            ->where('lmd_users.id', $id)
            ->where('lmd_users.lmd_user_role', 'vendor')
            ->select(
                'lmd_users.id as lmd_user_id',
                'lmd_users.name',
                'lmd_users.email',
                'lmd_users.phone_no',
                'lmd_users.cnic',
                'vendors.id as vendor_id',
                'vendors.vendor_type',
                DB::raw("CASE 
                            WHEN lmd_users.profile_picture IS NULL OR lmd_users.profile_picture = '' 
                            THEN NULL 
                            ELSE CONCAT('$baseUrl/storage/', lmd_users.profile_picture) 
                        END as profile_picture")
            )
            ->first();

        if ($vendor) {
            return response()->json($vendor, 200);
        }

        return response()->json(['error' => 'Vendor not found ' . $id], 404);
    }


    public function updateVendor(Request $request, $id)
    {
        // Validate only the fields provided in the request
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|max:255|unique:lmd_users,email,' . $id,
            'phone_no' => 'sometimes|string|max:15|unique:lmd_users,phone_no,' . $id,
            'password' => 'sometimes|string|min:8',
            'cnic' => 'sometimes|string|max:15|unique:lmd_users,cnic,' . $id,
            'profile_picture' => 'sometimes|file|mimes:jpeg,png,jpg|max:2048', // File validation
            'vendor_type' => 'sometimes|string|in:In-App Vendor,API Vendor',
            'address_type' => 'sometimes|string|max:50',
            'street' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:100',
            'zip_code' => 'sometimes|nullable|string|max:10',
            'country' => 'sometimes|nullable|string|max:100',
            'latitude' => 'sometimes|nullable|numeric',
            'longitude' => 'sometimes|nullable|numeric',
        ]);

        // Verify that the user is a vendor
        $user = DB::table('lmd_users')->where('id', $id)->where('lmd_user_role', 'vendor')->first();

        if (!$user) {
            return response()->json(['error' => 'Vendor not found or invalid role'], 404);
        }

        DB::beginTransaction();

        try {
            // Handle profile picture upload
            $profilePicturePath = $user->profile_picture; // Retain existing picture if not updated
            if ($request->hasFile('profile_picture')) {
                $profilePicturePath = $request->file('profile_picture')->store('vendorImages', 'public');
            }

            // Update lmd_users table
            $userData = array_filter([
                'name' => $validatedData['name'] ?? null,
                'email' => $validatedData['email'] ?? null,
                'phone_no' => $validatedData['phone_no'] ?? null,
                'password' => isset($validatedData['password']) ? ($validatedData['password']) : null,
                'cnic' => $validatedData['cnic'] ?? null,
                'profile_picture' => $profilePicturePath,
            ]);

            if (!empty($userData)) {
                DB::table('lmd_users')->where('id', $id)->update($userData);
            }

            // Update vendor type in vendors table
            if (isset($validatedData['vendor_type'])) {
                DB::table('vendors')->where('lmd_users_ID', $id)->update([
                    'vendor_type' => $validatedData['vendor_type']
                ]);
            }

            // Update or create address for the vendor
            $addressData = array_filter([
                'address_type' => $validatedData['address_type'] ?? null,
                'street' => $validatedData['street'] ?? null,
                'city' => $validatedData['city'] ?? null,
                'zip_code' => $validatedData['zip_code'] ?? null,
                'country' => $validatedData['country'] ?? null,
                'latitude' => $validatedData['latitude'] ?? null,
                'longitude' => $validatedData['longitude'] ?? null,
            ]);

            if (!empty($addressData)) {
                DB::table('addresses')->updateOrInsert(
                    ['lmd_users_ID' => $id], // Match condition
                    $addressData // Data to update or insert
                );
            }

            DB::commit();

            return response()->json(['message' => 'Vendor updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Update failed', 'details' => $e->getMessage()], 500);
        }
    }



    // Create a shop
    //  public function createShop(Request $request)
    //  {
    //      $validatedData = $request->validate([
    //          'name' => 'required|string|max:255',
    //          'description' => 'nullable|string|max:255',
    //          'shopcategory_ID' => 'required|exists:shopcategory,ID',
    //          'vendors_ID' => 'required|exists:vendors,ID',
    //      ]);

    //      $shop = Shop::create($validatedData);

    //      return response()->json(['message' => 'Shop created successfully', 'shop' => $shop], 201);
    //  }


    public function createShop(Request $request)
    {
        Log::info('Shop creation request data:', $request->all());

        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'shopcategory_ID' => 'required|exists:shopcategory,ID',
            'vendors_ID' => 'required|exists:vendors,ID',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors() // Return field-specific validation errors
            ], 422);
        }

        $validated = $validator->validated();

        DB::beginTransaction();
        try {
            // ✅ Using Eloquent (timestamps handled automatically)
            $shop = Shop::create($validated);

            DB::commit();
            return response()->json([
                'success' => true,
                'shop' => $shop
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Shop creation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // View all shops of a vendor
    //  public function getVendorShops($vendorId)
    //  {
    //      $shops = Shop::where('vendors_ID', $vendorId)->get();

    //      if ($shops->isEmpty()) {
    //          return response()->json(['message' => 'No shops found for this vendor'], 404);
    //      }

    //      return response()->json(['shops' => $shops], 200);
    //  }
    public function getVendorShops($vendorId)
    {
        $shops = Shop::join('shopcategory', 'shops.shopcategory_ID', '=', 'shopcategory.id')
            ->where('shops.vendors_ID', $vendorId)
            ->get(['shops.id', 'shops.name', 'shops.description', 'shops.status', 'shops.shopcategory_ID', 'shopcategory.name as shopcategory_name', 'shops.vendors_ID']);

        if ($shops->isEmpty()) {
            return response()->json(['message' => 'No shops found for this vendor'], 404);
        }

        return response()->json(['shops' => $shops], 200);
    }

    // Update shop details
    public function updateShop(Request $request, $shopId)
    {
        $shop = Shop::find($shopId);

        if (!$shop) {
            return response()->json(['message' => 'Shop not found'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'shopcategory_ID' => 'nullable|exists:shopcategory,ID',
        ]);

        $shop->update($validatedData);

        return response()->json(['message' => 'Shop updated successfully', 'shop' => $shop], 200);
    }

    // Deactivate (delete) a shop
    public function deactivateShop($shopId)
    {
        $shop = Shop::find($shopId);

        if (!$shop) {
            return response()->json(['message' => 'Shop not found'], 404);
        }

        $shop->update(['status' => 'inactive']);

        return response()->json(['message' => 'Shop deactivated successfully'], 200);
    }

    // Activate a shop
    public function activateShop($shopId)
    {
        $shop = Shop::find($shopId);

        if (!$shop) {
            return response()->json(['message' => 'Shop not found'], 404);
        }

        $shop->update(['status' => 'active']);

        return response()->json(['message' => 'Shop activated successfully'], 200);
    }




    public function createBranch(Request $request)
    {
        // Validate the incoming request with proper error handling
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'description' => 'nullable|string|max:255',
            'opening_hours' => 'required|date_format:H:i',
            'closing_hours' => 'required|date_format:H:i|after:opening_hours',
            'contact_number' => 'required|string|max:15',
            'city_ID' => 'required|exists:city,id',
            'area_name' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'shops_ID' => 'required|exists:shops,id',
            'branch_picture' => 'nullable|file|mimes:jpeg,png,jpg', // Branch picture validation
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        DB::beginTransaction();
        try {
            // Check if area exists
            $area = DB::table('area')->where([
                'name' => $validated['area_name'],
                'city_ID' => $validated['city_ID']
            ])->first();

            if (!$area) {
                // Insert and get ID
                $area_ID = DB::table('area')->insertGetId([
                    'name' => $validated['area_name'],
                    'city_ID' => $validated['city_ID'],
                    'postal_code' => $validated['postal_code']
                ]);
            } else {
                // If exists, update and use existing ID
                DB::table('area')->where('id', $area->id)->update([
                    'postal_code' => $validated['postal_code']
                ]);
                $area_ID = $area->id;
            }

            // Handle branch picture upload
            $branch_picture_path = null;
            if ($request->hasFile('branch_picture')) {
                $branch_picture_path = $request->file('branch_picture')->store('vendorImages', 'public');
            }

            // Insert branch
            DB::table('branches')->insert([
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'description' => $validated['description'],
                'opening_hours' => $validated['opening_hours'],
                'closing_hours' => $validated['closing_hours'],
                'contact_number' => $validated['contact_number'],
                'status' => 'active', // Default status
                'approval_status' => 'pending', // Default approval status
                'branch_picture' => $branch_picture_path,
                'shops_ID' => $validated['shops_ID'],
                'area_ID' => $area_ID,
            ]);

            // Commit transaction
            DB::commit();

            // Get base URL
            $baseUrl = url('/');

            // Prepare branch picture URL
            $branch_picture_url = $branch_picture_path ? "$baseUrl/storage/$branch_picture_path" : null;

            return response()->json([
                'message' => 'Branch created successfully!',
                'data' => array_merge($validated, [
                    'branch_picture' => $branch_picture_url,
                    'area_ID' => $area_ID
                ])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create bra.',
                'error' => $e->getMessage()
            ], 500);
        }
    }





    public function updateBranch(Request $request, $branchId)
    {
        Log::info('Update Branch Request:', $request->all());
        // Validate the incoming request with proper error handling

        Log::info('Update Branch Request:');
        Log::info('Method: ' . $request->method());
        Log::info('Latitude: ' . $request->input('latitude'));
        Log::info('Longitude: ' . $request->input('longitude'));
        Log::info('Description: ' . $request->input('description'));
        Log::info('Opening Hours: ' . $request->input('opening_hours'));
        Log::info('Closing Hours: ' . $request->input('closing_hours'));
        Log::info('Contact Number: ' . $request->input('contact_number'));
        Log::info('City ID: ' . $request->input('city_ID'));
        Log::info('Area Name: ' . $request->input('area_name'));
        Log::info('Postal Code: ' . $request->input('postal_code'));
        Log::info('Shops ID: ' . $request->input('shops_ID'));

        // Check if a file is being received
        if ($request->hasFile('branch_picture')) {
            Log::info('Branch Picture: File received.');
        } else {
            Log::info('Branch Picture: No file received.');
        }


        $validator = Validator::make($request->all(), [
            'latitude' => 'sometimes|nullable|numeric',
            'longitude' => 'sometimes|nullable|numeric',
            'description' => 'sometimes|nullable|string|max:255',
            'opening_hours' => 'sometimes|nullable|date_format:H:i',
            'closing_hours' => 'sometimes|nullable|date_format:H:i|after:opening_hours',
            'contact_number' => 'sometimes|nullable|string|max:15',
            'city_ID' => 'sometimes|nullable|exists:city,id',
            'area_name' => 'sometimes|nullable|string|max:100',
            'postal_code' => 'sometimes|nullable|string|max:20',
            'shops_ID' => 'sometimes|nullable|exists:shops,id',
            'branch_picture' => 'sometimes|nullable|file|mimes:jpeg,png,jpg',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        try {
            // Fetch the branch
            $branch = DB::table('branches')->where('ID', $branchId)->first();

            if (!$branch) {
                return response()->json(['message' => 'Branch not found.'], 404);
            }

            DB::beginTransaction();

            // Handle branch picture upload if provided
            if ($request->hasFile('branch_picture')) {
                $branchPicturePath = $request->file('branch_picture')->store('vendorImages', 'public');
                $validated['branch_picture'] = $branchPicturePath;
            }

            // Prepare update data, excluding city_ID, area_name, and postal_code
            $updateData = collect($validated)
                ->except(['city_ID', 'area_name', 'postal_code'])
                ->filter() // Remove null values
                ->toArray();

            // Check if there is any data to update
            if (empty($updateData)) {
                return response()->json(['message' => 'No data provided to update.'], 400);
            }

            // Update the branch
            DB::table('branches')->where('ID', $branchId)->update($updateData);

            DB::commit();
            return response()->json(['message' => 'Branch updated successfully!'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update branch.',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function deleteBranch($branchId)
    {
        try {
            // Check if the branch exists and is not approved
            $branch = DB::table('branches')->where('id', $branchId)->first();

            if (!$branch || $branch->approval_status === 'approved') {
                return response()->json(['message' => 'Branch not found or cannot be deleted.'], 404);
            }

            // Delete the branch
            DB::table('branches')->where('id', $branchId)->delete();

            return response()->json(['message' => 'Branch deleted successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete branch.', 'error' => $e->getMessage()], 500);
        }
    }

    ///////////////////////////////



    public function getBranchesByShopId($shopId)
    {
        try {
            // Validate if the shop exists
            $shopExists = DB::table('shops')->where('id', $shopId)->exists();

            if (!$shopExists) {
                return response()->json(['message' => 'Shop not found.'], 404);
            }

            // Fetch all branches linked to the shop
            $branches = DB::table('branches')
                ->where('shops_id', $shopId)
                ->select(
                    'id AS branch_id',
                    'latitude',
                    'longitude',
                    'description',
                    'opening_hours',
                    'closing_hours',
                    'contact_number',
                    'approval_status',
                    'branch_picture',
                    'status',
                    'area_ID'
                )
                ->get();

            // Check if no branches found
            if ($branches->isEmpty()) {
                return response()->json(['message' => 'No branches found for this shop.'], 404);
            }

            // Modify branch_picture to return full path
            $branches->transform(function ($branch) {
                $branch->branch_picture = $branch->branch_picture
                    ? url(Storage::url("{$branch->branch_picture}")) // Generate full URL
                    : null; // Return null if no picture
                return $branch;
            });

            return response()->json(['branches' => $branches], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to fetch branches.', 'error' => $e->getMessage()], 500);
        }
    }

    public function toggleBranchStatus($branchId)
    {
        try {
            // Fetch the branch
            $branch = DB::table('branches')->where('id', $branchId)->first();

            if (!$branch) {
                return response()->json(['message' => 'Branch not found.'], 404);
            }

            // Determine the new status
            $newStatus = $branch->status === 'active' ? 'inactive' : 'active';

            // Update the branch status
            DB::table('branches')->where('id', $branchId)->update([
                'status' => $newStatus
            ]);

            return response()->json([
                'message' => 'Branch status updated successfully!',
                'new_status' => $newStatus,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update branch status.', 'error' => $e->getMessage()], 500);
        }
    }


    public function addCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            DB::table('itemcategories')->insert([
                'name' => $validated['name'],
            ]);

            return response()->json(['message' => 'Category added successfully!'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to add category.', 'error' => $e->getMessage()], 500);
        }
    }



    public function getItemCategories($shopCategoryId)
    {
        // Fetch item categories based on the provided shop_category_ID
        $categories = DB::table('itemcategories')
            ->where('shop_category_ID', $shopCategoryId)
            ->select('id', 'name', 'shop_category_ID')
            ->get();

        if ($categories->isEmpty()) {
            return response()->json(['message' => 'No item categories found for the given shop category'], 404);
        }

        return response()->json([
            'message' => 'Item categories retrieved successfully',
            'categories' => $categories,
        ], 200);
    }

    public function getItemVariations($itemCategoryId)
    {
        // Fetch item variations based on the provided item_category_ID
        $variations = DB::table('defaultvariations')
            ->where('itemcategory_ID', $itemCategoryId)
            ->select('name', 'itemcategory_ID')
            ->get();

        if ($variations->isEmpty()) {
            return response()->json(['message' => 'No item variations found for the given item category'], 404);
        }

        return response()->json([
            'message' => 'Item variations retrieved successfully',
            'variations' => $variations,
        ], 200);
    }

    public function getPredefinedAttributes($itemCategoryId)
    {
        // Fetch predefined attributes for the selected item category
        $attributes = DB::table('defaultattributes')
            ->where('itemcategory_ID', $itemCategoryId)
            ->select('key_name', 'value')
            ->get();

        // Organize attributes into a structured format
        $groupedAttributes = [];
        foreach ($attributes as $attribute) {
            $groupedAttributes[$attribute->key_name][] = $attribute->value;
        }

        return response()->json([
            'message' => 'Predefined attributes retrieved successfully',
            'attributes' => $groupedAttributes,
        ], 200);
    }


    public function addItem(Request $request, $vendorId, $shopId, $branchId)
    {
        //dd($request->all());
        if ($request->has('attributes')) {
            $decodedAttributes = json_decode($request->input('attributes'), true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $request->merge(['attributes' => $decodedAttributes]);
            } else {
                return response()->json(['message' => 'Invalid JSON format for attributes.'], 400);
            }
        }

        Log::info('ITEMS', $request->all());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'timesensitive' => 'nullable|in:Yes,No', // Validate timesensitive field
            'preparation_time' => 'nullable|integer|min:0', // Validate preparation_time field
            'description' => 'nullable|string',
            'category_ID' => 'required|exists:itemcategories,id',
            'branches_ID' => 'required|exists:branches,id',
            'variation_name' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'additional_info' => 'nullable|string|max:255',
            'itemPicture' => 'nullable|file|mimes:jpeg,png,jpg',
            'attributes' => 'nullable|array',
            'attributes.*.key' => 'required|string',
            'attributes.*.value' => 'required|string',
            'stock_qty' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            // vendor ownership of shop and branch
            $shop = Shop::where('id', $shopId)->where('vendors_ID', $vendorId)->first();
            $branch = Branch::where('id', $branchId)->where('shops_ID', $shopId)->first();

            if (!$shop || !$branch) {
                return response()->json(['message' => 'Invalid shop or branch for the vendor'], 400);
            }

            // Create the item
            $itemId = DB::table('items')->insertGetId([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'category_ID' => $validated['category_ID'],
                'branches_ID' => $branchId,
            ]);

            // Handle picture upload
            $picturePath = null;
            if (!empty($validated['itemPicture'])) {
                $picturePath = $validated['itemPicture']->store('itemImages', 'public');
            }

            // Insert the variation
            $itemDetailId = DB::table('itemdetails')->insertGetId([
                'variation_name' => $validated['variation_name'],
                'price' => $validated['price'],
                'additional_info' => $validated['additional_info'],
                'timesensitive' => $validated['timesensitive'] ?? 'No', // Use default 'No' if not provided
                'preparation_time' => $validated['preparation_time'] ?? 0, // Use default 0 if not provided

                'picture' => $picturePath,
                'item_ID' => $itemId,
            ]);



            // 🔥 Insert into stock table
            DB::table('stock')->insert([
                'item_detail_ID' => $itemDetailId,
                'stock_qty' => $validated['stock_qty'] ?? 0, // default to 0 if not provided
                'last_updated' => now(),
            ]);

            // Insert dynamic attributes
            if (!empty($validated['attributes'])) {
                $attributes = array_map(function ($attr) use ($itemDetailId) {
                    return [
                        'key' => $attr['key'],
                        'value' => $attr['value'],
                        'itemdetail_id' => $itemDetailId,
                    ];
                }, $validated['attributes']);

                DB::table('itemattributes')->insert($attributes);
            }

            DB::commit();
            return response()->json(['message' => 'Item added successfully!'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to add item.', 'error' => $e->getMessage()], 500);
        }
    }




    public function addVariation(Request $request, $itemId)
    {
        $validated = $request->validate([
            'variation_name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'additional_info' => 'nullable|string|max:255',
            'picture' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
            'attributes' => 'nullable|array',
            'attributes.*.key' => 'required|string',
            'attributes.*.value' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            // Handle picture upload
            $picturePath = null;
            if (!empty($request->file('picture'))) {
                $picturePath = $request->file('picture')->store('itemImages', 'public');
            }

            //
            $itemDetailId = DB::table('itemdetails')->insertGetId([
                'variation_name' => $validated['variation_name'],
                'price' => $validated['price'],
                'additional_info' => $validated['additional_info'],
                'picture' => $picturePath,
                'item_ID' => $itemId,
            ]);

            //
            if (!empty($validated['attributes'])) {
                $attributes = array_map(function ($attr) use ($itemDetailId) {
                    return [
                        'key' => $attr['key'],
                        'value' => $attr['value'],
                        'itemdetail_id' => $itemDetailId,
                    ];
                }, $validated['attributes']);

                DB::table('itemattributes')->insert($attributes);
            }

            DB::commit();
            return response()->json(['message' => 'Variation added successfully!'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to add variation.', 'error' => $e->getMessage()], 500);
        }
    }


    public function toggleItemStatus($itemDetailId)
    {
        try {
            $item = DB::table('itemdetails')->find($itemDetailId);

            if (!$item) {
                return response()->json(['message' => 'Item not found.'], 404);
            }

            // Toggle the status
            $newStatus = $item->status === 'enabled' ? 'disabled' : 'enabled';

            // Update the status in the database
            DB::table('itemdetails')->where('id', $itemDetailId)->update(['status' => $newStatus]);

            return response()->json(['message' => 'Item status updated successfully!', 'status' => $newStatus], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to toggle item status.', 'error' => $e->getMessage()], 500);
        }
    }



    public function getItemsWithDetails($vendorId, $shopId, $branchId)
    {
        $baseUrl = url('/'); // Get the base URL dynamically

        $items = DB::table('items')
            ->leftJoin('itemdetails', 'items.id', '=', 'itemdetails.item_ID') // LEFT JOIN for items without details
            ->join('branches', 'items.branches_ID', '=', 'branches.id')
            ->join('shops', 'branches.shops_ID', '=', 'shops.id')
            ->leftJoin('itemattributes', 'itemdetails.id', '=', 'itemattributes.itemdetail_id')
            ->where('shops.vendors_ID', $vendorId)
            ->where('shops.id', $shopId)
            ->where('branches.id', $branchId)
            ->select(
                'items.id as item_id',
                'items.name as item_name',
                'items.description as item_description',
                'itemdetails.timesensitive', // ✅ Fetch time sensitive
                'itemdetails.preparation_time', // ✅ Fetch preparation time
                'itemdetails.id as itemdetail_id',
                'itemdetails.variation_name',
                'itemdetails.price',
                'itemdetails.additional_info',
                'itemdetails.picture',
                'itemattributes.itemdetail_id as attribute_itemdetail_id',
                'itemattributes.key as attribute_key',
                'itemattributes.value as attribute_value'
            )
            ->get();

        // Format the response
        $formattedItems = [];

        // Group items by item_id (instead of itemdetail_id to prevent null issues)
        $groupedItems = $items->groupBy('item_id');

        foreach ($groupedItems as $itemDetails) {
            $first = $itemDetails->first();

            // Collect attributes only if they exist
            $attributes = $itemDetails->filter(fn($detail) => !is_null($detail->attribute_key) && !is_null($detail->attribute_value))
                ->map(fn($detail) => [
                    'key' => $detail->attribute_key,
                    'value' => $detail->attribute_value
                ])
                ->values();

            // Format item response
            $formattedItems[] = [
                'item_id' => $first->item_id,
                'item_name' => $first->item_name,
                'item_description' => $first->item_description,
                'timesensitive' => $first->timesensitive ?? null, // ✅ Added
                'preparation_time' => $first->preparation_time ?? null, // ✅ Added
                'itemdetail_id' => $first->itemdetail_id ?? null, // Ensure NULL is handled properly
                'variation_name' => $first->variation_name ?? null,
                'price' => $first->price ?? null,
                'additional_info' => $first->additional_info ?? null,
                'picture' => $first->picture ? $baseUrl . '/storage/' . ltrim($first->picture, '/') : null,
                'attributes' => $attributes, // Grouped attributes
            ];
        }

        return response()->json($formattedItems);
    }


    //
    public function getCategories($vendorId, $shopId, $branchId)
    {
        $categories = DB::table('itemcategories')
            ->join('items', 'itemcategories.id', '=', 'items.category_ID')
            ->join('branches', 'items.branches_ID', '=', 'branches.id')
            ->join('shops', 'branches.shops_ID', '=', 'shops.id')
            ->where('shops.vendors_ID', $vendorId)
            ->where('shops.id', $shopId)
            ->where('branches.id', $branchId)
            ->select('itemcategories.*')
            ->distinct()
            ->get();

        return response()->json($categories);
    }


    /////////////////////////////////////Get suborders.....



    public function getSubOrdersByVendor($vendorId)
    {
        $data = DB::table('suborders')
            ->join('orders', 'suborders.orders_ID', '=', 'orders.id')
            ->join('customers', 'orders.customers_ID', '=', 'customers.id')
            ->join('lmd_users', 'customers.lmd_users_ID', '=', 'lmd_users.id')
            ->leftJoin('addresses', function ($join) {
                $join->on('lmd_users.id', '=', 'addresses.lmd_users_ID')
                    ->where('addresses.address_type', '=', 'home'); // you can change this to default or most recent if needed
            })
            ->select(
                'orders.id as order_id',
                'orders.order_date',
                'orders.order_status',
                'orders.payment_status as order_payment_status',
                'orders.payment_method',
                'orders.total_amount as order_total',

                'suborders.id as suborder_id',
                'suborders.status as suborder_status',
                'suborders.payment_status as suborder_payment_status',
                'suborders.total_amount as suborder_total',
                'suborders.vendor_type',
                'suborders.shop_ID',
                'suborders.branch_ID',

                // Customer Info
                'lmd_users.name as customer_name',
                'lmd_users.email as customer_email',
                'lmd_users.phone_no as customer_phone',

                // Address Info
                'addresses.address_type',
                'addresses.street',
                'addresses.city',
                'addresses.country',

                'addresses.latitude',
                'addresses.longitude'
            )
            ->where('suborders.vendor_ID', $vendorId)
            ->orderByDesc(column: 'orders.order_date')
            ->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'No orders found for this vendor'], 404);
        }

        $groupedOrders = $data->groupBy('order_id')->map(function ($orderGroup) {
            $first = $orderGroup->first();
            return [
                'order_id' => $first->order_id,
                'order_date' => $first->order_date,
                'order_status' => $first->order_status,
                'payment_status' => $first->order_payment_status,
                'payment_method' => $first->payment_method,
                'total_amount' => $first->order_total,

                'customer' => [
                    'name' => $first->customer_name,
                    'email' => $first->customer_email,
                    'phone_no' => $first->customer_phone,
                    'address' => [
                        'type' => $first->address_type,
                        'street' => $first->street,
                        'city' => $first->city,
                        'country' => $first->country,
                        'latitude' => $first->latitude,
                        'longitude' => $first->longitude,
                    ]
                ],

                'suborders' => $orderGroup->groupBy('suborder_id')->map(function ($suborderGroup) {
                    $s = $suborderGroup->first();
                    return [
                        'suborder_id' => $s->suborder_id,
                        'status' => $s->suborder_status,
                        'payment_status' => $s->suborder_payment_status,
                        'total' => $s->suborder_total,
                        'vendor_type' => $s->vendor_type,
                        'shop_id' => $s->shop_ID,
                        'branch_id' => $s->branch_ID,
                    ];
                })->values(),
            ];
        });

        return response()->json(['orders' => $groupedOrders->values()], 200);
    }
    public function getSubOrders($vendorId, $shopId, $branchId)
    {
        // Fetch suborders with related data
        $suborders = DB::table('suborders')
            ->join('orders', 'suborders.orders_ID', '=', 'orders.id')
            ->join('orderdetails', 'suborders.id', '=', 'orderdetails.suborders_ID')
            ->Join('itemdetails', 'orderdetails.itemdetails_ID', '=', 'itemdetails.id')
            ->leftJoin('items', 'itemdetails.item_ID', '=', 'items.id')
            ->select(
                'suborders.id as suborder_id',
                'suborders.status as suborder_status',
                'suborders.total_amount as suborder_total',
                'suborders.vendor_type as vendor_type',
                'orderdetails.id as order_detail_id',
                'orderdetails.quantity',
                'orderdetails.price',
                'orderdetails.total as order_detail_total',
                'itemdetails.picture as item_picture',
                'items.name as item_name'
            )
            ->where('suborders.vendor_ID', $vendorId)
            ->where('suborders.shop_ID', $shopId)
            ->where('suborders.branch_ID', $branchId)
            ->get();

        // Check if no suborders were found
        if ($suborders->isEmpty()) {
            return response()->json(['message' => 'No suborders found'], 404);
        }

        // Group suborders by suborder_id
        $groupedSuborders = $suborders->groupBy('suborder_id')->map(function ($suborderGroup) {
            // Map each suborder group
            return [
                'suborder_id' => $suborderGroup->first()->suborder_id,
                'suborder_status' => $suborderGroup->first()->suborder_status,
                'suborder_total' => $suborderGroup->first()->suborder_total,
                'order_details' => $suborderGroup->map(function ($suborder) {
                    return [
                        'order_detail_id' => $suborder->order_detail_id,
                        'quantity' => $suborder->quantity,
                        'price' => $suborder->price,
                        'order_detail_total' => $suborder->order_detail_total,
                        'item_picture' => $suborder->vendor_type !== 'API Vendor' ? $suborder->item_picture : null,
                        'item_name' => $suborder->item_name,

                    ];
                }),
            ];
        });

        return response()->json(['suborders' => $groupedSuborders->values()], 200);
    }




    // public function getOrderedItemInformation($suborderId)
    // {
    //     // Fetch suborder details with related item and variation details
    //     $suborderDetails = DB::table('orderdetails')
    //         ->join('itemdetails', 'orderdetails.itemdetails_ID', '=', 'itemdetails.id')
    //         ->join('items', 'itemdetails.item_ID', '=', 'items.id')
    //         ->select(
    //             'orderdetails.id as order_detail_id',
    //             'orderdetails.quantity',
    //             'orderdetails.price as order_detail_price',
    //             'orderdetails.total as order_detail_total',
    //             'itemdetails.id as item_detail_id',
    //             'itemdetails.variation_name',
    //             'itemdetails.price as item_detail_price',
    //             'itemdetails.additional_info',
    //             'itemdetails.picture as item_picture',
    //             'items.id as item_id',
    //             'items.name as item_name',
    //             'items.description as item_description'
    //         )
    //         ->where('orderdetails.suborders_ID', $suborderId)
    //         ->get();

    //     // Check if no details were found
    //     if ($suborderDetails->isEmpty()) {
    //         return response()->json(['message' => 'No details found for this suborder'], 404);
    //     }

    //     // Format the response
    //     $response = [
    //         'suborder_id' => $suborderId,
    //         'order_details' => $suborderDetails->map(function ($detail) {
    //             return [
    //                 'order_detail_id' => $detail->order_detail_id,
    //                 'quantity' => $detail->quantity,
    //                 'order_detail_price' => $detail->order_detail_price,
    //                 'order_detail_total' => $detail->order_detail_total,
    //                 'item' => [
    //                     'item_id' => $detail->item_id,
    //                     'item_name' => $detail->item_name,
    //                     'item_description' => $detail->item_description,
    //                     'item_detail_id' => $detail->item_detail_id,
    //                     'variation_name' => $detail->variation_name,
    //                     'item_detail_price' => $detail->item_detail_price,
    //                     'additional_info' => $detail->additional_info,
    //                     'item_picture' => $detail->item_picture,
    //                 ],
    //             ];
    //         }),
    //     ];

    //     return response()->json($response, 200);
    // }

    public function getOrderedItemInformation($vendorId, $shopId, $branchId, $suborderId)
{
    $baseUrl = url('/');

    // Call menu from API (CustomerController)
    $customerController = new \App\Http\Controllers\CustomerController();
    $menuResponse = $customerController->getVendorShopBranchMenu($vendorId, $shopId, $branchId);

    $menuData = $menuResponse instanceof \Illuminate\Http\JsonResponse
        ? $menuResponse->getData(true)
        : json_decode($menuResponse, true);

    $menuCollection = collect($menuData);

    // Get order details from LMD DB without joining items/itemdetails
    $orderDetails = DB::table('orderdetails')
        ->join('suborders', 'orderdetails.suborders_ID', '=', 'suborders.id')
        ->where('orderdetails.suborders_ID', $suborderId)
        ->where('suborders.vendor_ID', $vendorId)
        ->where('suborders.shop_ID', $shopId)
        ->where('suborders.branch_ID', $branchId)
        ->select(
            'orderdetails.id as order_detail_id',
            'orderdetails.quantity',
            'orderdetails.price as order_detail_price',
            'orderdetails.total as order_detail_total',
            'orderdetails.itemdetails_ID as item_detail_id',
            'suborders.status as suborder_status',
            'suborders.payment_status',
            'suborders.total_amount',
            'suborders.estimated_delivery_time',
            'suborders.delivery_time',
            'suborders.deliveryboys_ID',
            'suborders.vendor_type',
            'suborders.vendor_order_id'
        )
        ->get();

    if ($orderDetails->isEmpty()) {
        return response()->json(['message' => 'Suborder not found or access denied.'], 404);
    }

    $first = $orderDetails->first();
    $suborderInfo = [
        'suborder_id' => $suborderId,
        'status' => $first->suborder_status,
        'payment_status' => $first->payment_status,
        'total_amount' => $first->total_amount,
        'estimated_delivery_time' => $first->estimated_delivery_time,
        'delivery_time' => $first->delivery_time,
        'deliveryboy_id' => $first->deliveryboys_ID,
        'vendor_type' => $first->vendor_type,
        'vendor_order_id' => $first->vendor_order_id,
    ];

    $response = [
        'suborder_info' => $suborderInfo,
        'order_detail_info' => $orderDetails->map(function ($detail) use ($menuCollection) {
            $menuItem = $menuCollection->firstWhere('itemdetail_id', (int)$detail->item_detail_id);

            return [
                'order_detail_id' => $detail->order_detail_id,
                'quantity' => $detail->quantity,
                'order_detail_price' => $detail->order_detail_price,
                'order_detail_total' => $detail->order_detail_total,
                'item' => [
                    'item_id' => $menuItem['item_id'] ?? null,
                    'item_name' => $menuItem['item_name'] ?? null,
                    'item_description' => $menuItem['item_description'] ?? null,
                    'item_detail_id' => $detail->item_detail_id,
                    'variation_name' => $menuItem['variation_name'] ?? null,
                    'item_detail_price' => $menuItem['price'] ?? null,
                    'additional_info' => $menuItem['additional_info'] ?? null,
                    'item_picture' => $menuItem['itemPicture'] ?? null,
                    'attributes' => $menuItem['attributes'] ?? [],
                    'timesensitive' => $menuItem['timesensitive'] ?? null,
                    'preparation_time' => $menuItem['preparation_time'] ?? null,
                    'item_category_id' => $menuItem['item_category_id'] ?? null,
                    'item_category_name' => $menuItem['item_category_name'] ?? null,
                    'error_message' => $menuItem ? null : "Item details not found in menu for itemdetail_id: " . $detail->item_detail_id,
                ]
            ];
        }),
    ];

    return response()->json($response, 200);
}



    public function markInProgress($suborderId)
    {
        $suborder = Suborder::findOrFail($suborderId);

        // Fetch vendor
        $vendor = DB::table('vendors')->where('id', $suborder->vendor_ID)->first();
        if (!$vendor) {
            return response()->json(['error' => 'Vendor not found.'], 404);
        }

        // Ensure suborder is in pending state
        if (strtolower($suborder->status) !== 'pending') {
            return response()->json(['error' => 'Suborder cannot be marked as in progress in the current state.'], 400);
        }

        if ($vendor->vendor_type === 'API Vendor') {
            // Get API vendor and endpoint info
            $apiVendor = DB::table('vendors')
                ->join('shops', 'vendors.id', '=', 'shops.vendors_ID')
                ->join('branches', 'shops.id', '=', 'branches.shops_ID')
                ->join('apivendor', 'branches.id', '=', 'apivendor.branches_ID')
                ->where('vendors.id', $suborder->vendor_ID)
                ->where('branches.id', $suborder->branch_ID)
                ->select(
                    'apivendor.id as apivendor_ID',
                    'apivendor.api_base_url',
                    'apivendor.api_key',
                    'apivendor.response_format'
                )
                ->first();

            if (!$apiVendor) {
                return response()->json(['error' => 'API vendor configuration not found for the given branch and vendor.'], 404);
            }

            // Get method for marking as processing
            $apiMethod = DB::table('apimethods')
                ->where('apimethods.apivendor_ID', $apiVendor->apivendor_ID)
                ->where('apimethods.method_name', 'mark-processing') // make sure this matches your DB
                ->select('apimethods.endpoint', 'apimethods.http_method')
                ->first();

            if (!$apiMethod) {
                return response()->json(['error' => 'API method for mark-processing not found.'], 404);
            }

            // Construct full URL
            $vendorOrderId = $suborder->vendor_order_id;
            if (!$vendorOrderId) {
                return response()->json([
                    'error' => 'Vendor order ID is missing for this suborder. Cannot proceed with API call.'
                ], 400);
            }

            $fullUrl = rtrim($apiVendor->api_base_url, '/') . '/' . ltrim($apiMethod->endpoint, '/');
            $fullUrl = str_replace('{$vendorOrderId}', $vendorOrderId, $fullUrl);

            try {
                // Send request dynamically based on HTTP method
                $httpMethod = strtolower($apiMethod->http_method);

                // 🔍 Log full URL before making request
                Log::info("Calling API method [{$httpMethod}] on URL: {$fullUrl}");

                $response = match ($httpMethod) {
                    'post' => Http::post($fullUrl),
                    'put' => Http::put($fullUrl),
                    'get' => Http::get($fullUrl),
                    'delete' => Http::delete($fullUrl),
                    default => null,
                };

                // 🔍 Optional: Log full response status and body
                Log::info("Vendor response status: " . ($response ? $response->status() : 'null'));
                Log::info("Vendor response body: " . ($response ? $response->body() : 'null'));

                if (!$response || !$response->successful()) {

                    return response()->json([
                        'error' => 'Failed to update API vendor order. Vendor server responded with an error.'
                    ], 500);
                }
            } catch (\Exception $e) {

                return response()->json([
                    'error' => 'Failed to connect to API vendor server.'
                ], 500);
            }
        }

        // Continue local update for both In-App and API vendors
        $suborder->status = 'in_progress';
        $suborder->save();

        // Update main order status if needed
        $order = DB::table('orders')->where('id', $suborder->orders_ID)->first();
        if ($order && strtolower($order->order_status) === 'pending') {
            DB::table('orders')
                ->where('id', $suborder->orders_ID)
                ->update([
                    'order_status' => 'confirmed',
                    'updated_at' => now()
                ]);
        }

        return response()->json([
            'message' => 'Suborder marked as in progress, and order status updated if applicable.'
        ]);
    }


    // public function markInProgress($suborderId)
    // {
    //     $suborder = Suborder::findOrFail($suborderId);

    //     // Ensure suborder is in pending state
    //     if (strtolower($suborder->status) !== 'pending') {
    //         return response()->json(['error' => 'Suborder cannot be marked as in progress in the current state.'], 400);
    //     }

    //     // Mark suborder as in_progress
    //     $suborder->status = 'in_progress';
    //     $suborder->save();

    //     // Fetch related order
    //     $order = DB::table('orders')->where('id', $suborder->orders_ID)->first();

    //     // Check if order exists and is still pending (case-insensitive)
    //     if ($order && strtolower($order->order_status) === 'pending') {
    //         // Update order_status to confirmed
    //         DB::table('orders')
    //             ->where('id', $suborder->orders_ID)
    //             ->update([
    //                 'order_status' => 'confirmed',
    //                 'updated_at' => now()
    //             ]);
    //     }

    //     return response()->json(['message' => 'Suborder marked as in progress, and order status updated if applicable.']);
    // }


    // public function markReady($suborderId)
    // {
    //     $suborder = Suborder::findOrFail($suborderId);

    //     if ($suborder->status !== 'in_progress') {
    //         return response()->json(['error' => 'Order cannot be marked as ready in the current state.'], 400);
    //     }

    //     $suborder->status = 'ready';
    //     $suborder->save();

    //     return response()->json(['message' => 'Order marked as ready for delivery.']);
    // }


    public function markReady($suborderId)
    {
        $suborder = Suborder::findOrFail($suborderId);

        // Check if suborder is in the correct state
        if (strtolower($suborder->status) !== 'in_progress') {
            return response()->json(['error' => 'Suborder cannot be marked as ready in the current state.'], 400);
        }

        // Get vendor
        $vendor = DB::table('vendors')->where('id', $suborder->vendor_ID)->first();
        if (!$vendor) {
            return response()->json(['error' => 'Vendor not found.'], 404);
        }

        if ($vendor->vendor_type === 'API Vendor') {
            // Get API vendor info
            $apiVendor = DB::table('vendors')
                ->join('shops', 'vendors.id', '=', 'shops.vendors_ID')
                ->join('branches', 'shops.id', '=', 'branches.shops_ID')
                ->join('apivendor', 'branches.id', '=', 'apivendor.branches_ID')
                ->where('vendors.id', $suborder->vendor_ID)
                ->where('branches.id', $suborder->branch_ID)
                ->select(
                    'apivendor.id as apivendor_ID',
                    'apivendor.api_base_url',
                    'apivendor.api_key',
                    'apivendor.response_format'
                )
                ->first();

            if (!$apiVendor) {
                return response()->json(['error' => 'API vendor configuration not found for the given branch and vendor.'], 404);
            }

            // Get API method for marking as ready
            $apiMethod = DB::table('apimethods')
                ->where('apimethods.apivendor_ID', $apiVendor->apivendor_ID)
                ->where('apimethods.method_name', 'mark-ready') // Make sure this matches the correct method name
                ->select('apimethods.endpoint', 'apimethods.http_method')
                ->first();

            if (!$apiMethod) {
                return response()->json(['error' => 'API method for mark-ready not found.'], 404);
            }

            $vendorOrderId = $suborder->vendor_order_id;
            if (!$vendorOrderId) {
                return response()->json(['error' => 'Vendor order ID is missing for this suborder.'], 400);
            }

            // Construct full URL
            // $fullUrl = rtrim($apiVendor->api_base_url, '/') . '/' . ltrim($apiMethod->endpoint, '/');
            // $fullUrl = str_replace('{$vendorOrderId}', $vendorOrderId, $fullUrl);
            $endpoint = str_replace('{id}', $vendorOrderId, $apiMethod->endpoint);
            $fullUrl = rtrim($apiVendor->api_base_url, '/') . '/' . ltrim($endpoint, '/');
            try {
                $httpMethod = strtolower($apiMethod->http_method);

                Log::info("Calling API method [{$httpMethod}] on URL: {$fullUrl}");

                $response = match ($httpMethod) {
                    'post' => Http::post($fullUrl),
                    'put' => Http::put($fullUrl),
                    'get' => Http::get($fullUrl),
                    'delete' => Http::delete($fullUrl),
                    default => null,
                };

                Log::info("Vendor response status: " . ($response ? $response->status() : 'null'));
                Log::info("Vendor response body: " . ($response ? $response->body() : 'null'));

                if (!$response || !$response->successful()) {
                    return response()->json([
                        'error' => 'Failed to mark as ready on API vendor server. Vendor responded with an error.'
                    ], 500);
                }
            } catch (Exception $e) {
                return response()->json([
                    'error' => 'Failed to connect to API vendor server.'
                ], 500);
            }
        }

        // Update local suborder status
        $suborder->status = 'ready';
        $suborder->save();

        return response()->json([
            'message' => 'Suborder marked as ready successfully.'
        ]);
    }



    // public function confirmHandover($suborderId)
    // {
    //     $suborder = Suborder::findOrFail($suborderId);

    //     if ($suborder->status !== 'picked_up') {
    //         return response()->json(['error' => 'Order cannot be confirmed for handover in the current state.'], 400);
    //     }

    //     $suborder->status = 'handover_confirmed';
    //     $suborder->save();

    //     return response()->json(['message' => 'Order handover to deliveryboy .']);
    // }
    public function confirmHandover($suborderId)
    {
        $suborder = Suborder::findOrFail($suborderId);

        if ($suborder->status !== 'picked_up') {
            return response()->json(['error' => 'Order cannot be confirmed for handover in the current state.'], 400);
        }

        // Get vendor
        $vendor = DB::table('vendors')->where('id', $suborder->vendor_ID)->first();
        if (!$vendor) {
            return response()->json(['error' => 'Vendor not found.'], 404);
        }

        // For API Vendor, sync handover status to vendor
        if ($vendor->vendor_type === 'API Vendor') {
            // Get API Vendor config
            $apiVendor = DB::table('vendors')
                ->join('shops', 'vendors.id', '=', 'shops.vendors_ID')
                ->join('branches', 'shops.id', '=', 'branches.shops_ID')
                ->join('apivendor', 'branches.id', '=', 'apivendor.branches_ID')
                ->where('vendors.id', $suborder->vendor_ID)
                ->where('branches.id', $suborder->branch_ID)
                ->select(
                    'apivendor.id as apivendor_ID',
                    'apivendor.api_base_url',
                    'apivendor.api_key',
                    'apivendor.response_format'
                )
                ->first();

            if (!$apiVendor) {
                return response()->json(['error' => 'API vendor configuration not found for the given branch and vendor.'], 404);
            }

            // Get API Method for handover confirmation
            $apiMethod = DB::table('apimethods')
                ->where('apimethods.apivendor_ID', $apiVendor->apivendor_ID)
                ->where('apimethods.method_name', 'mark-confirmed') // Make sure this matches
                ->select('apimethods.endpoint', 'apimethods.http_method')
                ->first();

            if (!$apiMethod) {
                return response()->json(['error' => 'API method for handover-confirmed not found.'], 404);
            }

            $vendorOrderId = $suborder->vendor_order_id;
            if (!$vendorOrderId) {
                return response()->json(['error' => 'Vendor order ID is missing for this suborder.'], 400);
            }

            // Construct endpoint URL
            $endpoint = str_replace('{id}', $vendorOrderId, $apiMethod->endpoint);
            $fullUrl = rtrim($apiVendor->api_base_url, '/') . '/' . ltrim($endpoint, '/');

            try {
                $httpMethod = strtolower($apiMethod->http_method);

                Log::info("Calling vendor handover-confirmed [{$httpMethod}] URL: {$fullUrl}");

                $response = match ($httpMethod) {
                    'post' => Http::post($fullUrl),
                    'put' => Http::put($fullUrl),
                    'get' => Http::get($fullUrl),
                    'delete' => Http::delete($fullUrl),
                    default => null,
                };

                Log::info("Vendor response status: " . ($response ? $response->status() : 'null'));
                Log::info("Vendor response body: " . ($response ? $response->body() : 'null'));

                if (!$response || !$response->successful()) {
                    return response()->json([
                        'error' => 'Failed to confirm handover on API vendor server. Vendor responded with error.'
                    ], 500);
                }
            } catch (Exception $e) {
                return response()->json([
                    'error' => 'Failed to connect to API vendor server.'
                ], 500);
            }
        }

        // Update local suborder status
        $suborder->status = 'handover_confirmed';
        $suborder->save();

        // DO NOT change this response (frontend depends on it)
        return response()->json(['message' => 'Order handover to deliveryboy .']);
    }




    // public function confirmPaymentByVendor($suborderId)
    // {
    //     $suborder = Suborder::findOrFail($suborderId);

    //     if ($suborder->payment_status !== 'confirmed_by_deliveryboy') {
    //         return response()->json(['error' => 'Delivery boy must confirm payment first.'], 400);
    //     }

    //     $suborder->payment_status = 'confirmed_by_vendor';
    //     $suborder->save();

    //     return response()->json(['message' => 'Payment confirmed by vendor.']);
    // }

    public function confirmPaymentByVendor($suborderId)
    {
        $suborder = Suborder::findOrFail($suborderId);

        // Validate the current status
        if ($suborder->payment_status !== 'confirmed_by_deliveryboy') {
            return response()->json(['error' => 'Delivery boy must confirm payment first.'], 400);
        }

        // Fetch vendor info
        $vendor = DB::table('vendors')->where('id', $suborder->vendor_ID)->first();
        if (!$vendor) {
            return response()->json(['error' => 'Vendor not found.'], 404);
        }

        // If vendor is an API Vendor
        if ($vendor->vendor_type === 'API Vendor') {
            // Get API vendor configuration
            $apiVendor = DB::table('vendors')
                ->join('shops', 'vendors.id', '=', 'shops.vendors_ID')
                ->join('branches', 'shops.id', '=', 'branches.shops_ID')
                ->join('apivendor', 'branches.id', '=', 'apivendor.branches_ID')
                ->where('vendors.id', $suborder->vendor_ID)
                ->where('branches.id', $suborder->branch_ID)
                ->select(
                    'apivendor.id as apivendor_ID',
                    'apivendor.api_base_url',
                    'apivendor.api_key',
                    'apivendor.response_format'
                )
                ->first();

            if (!$apiVendor) {
                return response()->json(['error' => 'API vendor configuration not found.'], 404);
            }

            // Get API method for confirm payment by vendor
            $apiMethod = DB::table('apimethods')
                ->where('apivendor_ID', $apiVendor->apivendor_ID)
                ->where('method_name', 'mark-confirm-payment-by-vendor')
                ->select('endpoint', 'http_method')
                ->first();

            if (!$apiMethod) {
                return response()->json(['error' => 'API method for confirm-payment-by-vendor not found.'], 404);
            }

            $vendorOrderId = $suborder->vendor_order_id;
            if (!$vendorOrderId) {
                return response()->json(['error' => 'Vendor order ID is missing.'], 400);
            }

            // Replace placeholder in endpoint and build full URL
            $endpoint = str_replace('{id}', $vendorOrderId, $apiMethod->endpoint);
            $fullUrl = rtrim($apiVendor->api_base_url, '/') . '/' . ltrim($endpoint, '/');

            try {
                $httpMethod = strtolower($apiMethod->http_method);
                Log::info("Calling API method [{$httpMethod}] on URL: {$fullUrl}");

                $response = match ($httpMethod) {
                    'post' => Http::post($fullUrl),
                    'put' => Http::put($fullUrl),
                    'get' => Http::get($fullUrl),
                    'delete' => Http::delete($fullUrl),
                    default => null,
                };

                Log::info("Vendor response status: " . ($response ? $response->status() : 'null'));
                Log::info("Vendor response body: " . ($response ? $response->body() : 'null'));

                if (!$response || !$response->successful()) {
                    return response()->json([
                        'error' => 'Failed to confirm payment on API vendor server. Vendor responded with an error.'
                    ], 500);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Failed to connect to API vendor server.',
                    'exception' => $e->getMessage()
                ], 500);
            }
        }

        // Update local suborder payment status
        $suborder->payment_status = 'confirmed_by_vendor';
        $suborder->save();

        // DO NOT CHANGE THE RESPONSE (as requested)
        return response()->json(['message' => 'Payment confirmed by vendor.']);
    }


    // public function updateSuborderStatus(Request $request, $suborderId)
    // {
    //     // Step 1: Validate input
    //     $validator = Validator::make($request->all(), [
    //         'status' => 'required|string',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation failed.',
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     $newStatus = strtolower($request->input('status')); // Convert to lowercase for comparison

    //     // Step 2: Fetch suborder
    //     $suborder = Suborder::find($suborderId);
    //     if (!$suborder) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Suborder not found.',
    //         ], 404);
    //     }

    //     $currentStatus = strtolower($suborder->status); // Current status in lowercase
    //     $currentPaymentStatus = strtolower($suborder->payment_status);
    //     // Step 3: Define allowed transitions
    //     $validTransitions = [
    //         'pending' => 'in_progress',
    //         'in_progress' => 'ready',
    //         'picked_up' => 'handover_confirmed',
    //         // 'handover_confirmed' => 'in_transit',
    //         // 'in_transit' => 'confirmed_by_vendor',
    // //  'confirmed_by_deliveryboy' => 'confirmed_by_vendor',
    //     ];

    //     // Step 4: Check if transition is allowed
    //     if (!isset($validTransitions[$currentStatus]) || $validTransitions[$currentStatus] !== $newStatus) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => "Invalid status transition from '$currentStatus' to '$newStatus'.",
    //         ], 400);
    //     }

    //     // Step 5: Special handling for handover_confirmed → in_transit (automatically apply both)
    //     if ($newStatus === 'handover_confirmed') {
    //         $suborder->status = 'handover_confirmed';
    //         $suborder->save();

    //         // Now immediately move to in_transit
    //         $suborder->status = 'in_transit';
    //         $suborder->save();

    //         return response()->json([
    //             'success' => true,
    //             'message' => "Suborder status updated to 'handover_confirmed' and automatically moved to 'in_transit'.",
    //             'data' => ['status' => 'in_transit']
    //         ]);
    //     }

    //     // Step 6: Normal update
    //     $suborder->status = $newStatus;
    //     $suborder->save();

    //     return response()->json([
    //         'success' => true,
    //         'message' => "Suborder status updated to '$newStatus'.",
    //         'data' => ['status' => $newStatus]
    //     ]);
    // }

    public function updateSuborderStatus(Request $request, $suborderId)
    {
        // Step 1: Validate input
        $validator = Validator::make($request->all(), [
            'status' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $newStatus = strtolower($request->input('status'));

        // Step 2: Fetch suborder
        $suborder = Suborder::find($suborderId);
        if (!$suborder) {
            return response()->json([
                'success' => false,
                'message' => 'Suborder not found.',
            ], 404);
        }

        $currentStatus = strtolower($suborder->status);
        $currentPaymentStatus = strtolower($suborder->payment_status);

        // Step 3: Handle special case for payment status update
        if ($newStatus === 'confirmed_by_vendor') {
            if ($currentPaymentStatus !== 'confirmed_by_deliveryboy') {
                return response()->json([
                    'success' => false,
                    'message' => 'Delivery boy must confirm payment first.',
                ], 400);
            }

            // Update payment_status
            $suborder->payment_status = 'confirmed_by_vendor';
            $suborder->save();

            return response()->json([
                'success' => true,
                'message' => 'Payment confirmed by vendor.',
                'data' => ['payment_status' => 'confirmed_by_vendor']
            ]);
        }

        // Step 4: Define allowed transitions
        $validTransitions = [
            'pending' => 'in_progress',
            'in_progress' => 'ready',
            'picked_up' => 'handover_confirmed',
        ];

        // Step 5: Check if transition is allowed
        if (!isset($validTransitions[$currentStatus]) || $validTransitions[$currentStatus] !== $newStatus) {
            return response()->json([
                'success' => false,
                'message' => "Invalid status transition from '$currentStatus' to '$newStatus'.",
            ], 400);
        }

        // Step 6: Special case for handover_confirmed → in_transit (auto)
        if ($newStatus === 'handover_confirmed') {
            $suborder->status = 'handover_confirmed';
            $suborder->save();

            $suborder->status = 'in_transit';
            $suborder->save();

            return response()->json([
                'success' => true,
                'message' => "Suborder status updated to 'handover_confirmed' and automatically moved to 'in_transit'.",
                'data' => ['status' => 'in_transit']
            ]);
        }

        // Step 7: Normal update
        $suborder->status = $newStatus;
        $suborder->save();

        return response()->json([
            'success' => true,
            'message' => "Suborder status updated to '$newStatus'.",
            'data' => ['status' => $newStatus]
        ]);
    }











    public function connectVendorToOrganization(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vendor_ID' => 'required|exists:vendors,id',
            'organization_ID' => 'required|exists:organizations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Insert with status always set to 'active'
            DB::table('vendororganization')->insert([
                'vendor_ID' => $request->vendor_ID,
                'organization_ID' => $request->organization_ID,
                'status' => 'active',  // Always active
            ]);

            return response()->json([
                'message' => 'Vendor connected to organization successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to connect vendor to organization',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // public function getAvailableOrganizationsForVendor($vendorId)
    // {
    //     $baseUrl = url('/');

    //     // Subquery: get all organization IDs that this vendor has already sent requests to
    //     $alreadyRequestedOrgIds = DB::table('vendororganization')
    //         ->where('vendor_ID', $vendorId)
    //         ->pluck('organization_ID');

    //     // Main query: get organizations that are NOT already requested or connected
    //     $organizations = DB::table('organizations')
    //         ->join('lmd_users', 'organizations.lmd_users_ID', '=', 'lmd_users.id')
    //         ->where('lmd_users.lmd_user_role', 'organization')
    //         ->whereNotIn('organizations.id', $alreadyRequestedOrgIds)
    //         ->select(
    //             'organizations.id as organization_id',
    //             'lmd_users.name',
    //             'lmd_users.email',
    //             'lmd_users.phone_no',
    //             'lmd_users.cnic',
    //             'lmd_users.lmd_user_role',
    //             DB::raw("CASE 
    //                         WHEN lmd_users.profile_picture IS NULL OR lmd_users.profile_picture = '' 
    //                         THEN NULL 
    //                         ELSE CONCAT('$baseUrl/storage/', lmd_users.profile_picture) 
    //                     END as profile_picture")
    //         )
    //         ->get();

    //     return response()->json(['available_organizations' => $organizations], 200);
    // }
    public function getAvailableOrganizationsForVendor($vendorId)
    {
        $baseUrl = url('/');

        // Get all organization IDs that this vendor has already sent requests to
        $requestedOrConnectedOrgIds = DB::table('vendororganization')
            ->where('vendor_ID', $vendorId)
            ->pluck('organization_ID');

        // Organizations NOT yet requested (available to send request)
        $availableOrganizations = DB::table('organizations')
            ->join('lmd_users', 'organizations.lmd_users_ID', '=', 'lmd_users.id')
            ->where('lmd_users.lmd_user_role', 'organization')
            ->whereNotIn('organizations.id', $requestedOrConnectedOrgIds)
            ->select(
                'organizations.id as organization_id',
                'lmd_users.name',
                'lmd_users.email',
                'lmd_users.phone_no',
                'lmd_users.cnic',
                'lmd_users.lmd_user_role',
                DB::raw("CASE 
                        WHEN lmd_users.profile_picture IS NULL OR lmd_users.profile_picture = '' 
                        THEN NULL 
                        ELSE CONCAT('$baseUrl/storage/', lmd_users.profile_picture) 
                    END as profile_picture")
            )
            ->get();

        // Organizations already requested or connected
        $requestedOrConnectedOrganizations = DB::table('organizations')
            ->join('lmd_users', 'organizations.lmd_users_ID', '=', 'lmd_users.id')
            ->join('vendororganization', 'organizations.id', '=', 'vendororganization.organization_ID')
            ->where('vendororganization.vendor_ID', $vendorId)
            ->select(
                'organizations.id as organization_id',
                'lmd_users.name',
                'lmd_users.email',
                'lmd_users.phone_no',
                'lmd_users.cnic',
                'lmd_users.lmd_user_role',
                'vendororganization.approval_status',
                DB::raw("CASE 
                        WHEN lmd_users.profile_picture IS NULL OR lmd_users.profile_picture = '' 
                        THEN NULL 
                        ELSE CONCAT('$baseUrl/storage/', lmd_users.profile_picture) 
                    END as profile_picture")
            )
            ->get();

        return response()->json([
            'available_organizations' => $availableOrganizations,
            'requested_or_connected_organizations' => $requestedOrConnectedOrganizations
        ], 200);
    }


    public function getVendorSummary($vendorId)
    {
        // Total Shops
        $totalShops = DB::table('shops')
            ->where('vendors_ID', $vendorId)
            ->count();

        // Total Branches linked through shops
        $totalBranches = DB::table('branches')
            ->whereIn('shops_ID', function ($query) use ($vendorId) {
                $query->select('id')
                    ->from('shops')
                    ->where('vendors_ID', $vendorId);
            })
            ->count();

        // Total Approved Branches
        $approvedBranches = DB::table('branches')
            ->whereIn('shops_ID', function ($query) use ($vendorId) {
                $query->select('id')
                    ->from('shops')
                    ->where('vendors_ID', $vendorId);
            })
            ->where('approval_status', 'approved')
            ->count();

        // Total Suborders
        $totalSuborders = DB::table('suborders')
            ->where('vendor_ID', $vendorId)
            ->count();

        // Delivered Suborders
        $deliveredSuborders = DB::table('suborders')
            ->where('vendor_ID', $vendorId)
            ->where('status', 'delivered')
            ->count();

        // Pending Suborders
        $pendingSuborders = DB::table('suborders')
            ->where('vendor_ID', $vendorId)
            ->where('status', 'pending')
            ->count();

        // Distinct Orders
        $totalOrders = DB::table('suborders')
            ->where('vendor_ID', $vendorId)
            ->distinct('orders_ID')
            ->count('orders_ID');

        // Total Revenue (ensure it's always float with 2 decimals)
        $totalRevenue = round(floatval(DB::table('suborders')
            ->where('vendor_ID', $vendorId)
            ->sum('total_amount')), 2);

        // Average Revenue per Order (rounded to 2 decimals)
        $avgRevenuePerOrder = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0.00;

        // Linked Organizations
        $totalOrganizations = DB::table('vendororganization')
            ->where('vendor_ID', $vendorId)
            ->count();

        return response()->json([
            'total_shops' => $totalShops,
            'total_branches' => $totalBranches,
            'total_approved_branches' => $approvedBranches,
            'total_suborders' => $totalSuborders,
            'delivered_suborders' => $deliveredSuborders,
            'pending_suborders' => $pendingSuborders,
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'avg_revenue_per_order' => $avgRevenuePerOrder,
            'total_linked_organizations' => $totalOrganizations,
        ]);
    }







//For Website api vendor

public function updateSuborderStatusByVendorOrderId(Request $request)
{
    // ✅ Step 0: Extract API key from Authorization header
    $authHeader = $request->header('Authorization');

    Log::info('Authorization Header:', ['Authorization' => $authHeader]);

    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        return response()->json([
            'success' => false,
            'message' => 'Missing or invalid Authorization header.',
        ], 401);
    }

    $apiKey = trim(str_replace('Bearer', '', $authHeader));
    Log::info('Extracted API Key: ' . $apiKey);

    // ✅ Step 1: Lookup vendor using API key
    $vendor = DB::table('apivendor')->where('api_key', $apiKey)->first();

    if (!$vendor) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid API key.',
        ], 401);
    }

    $branchId = $vendor->branches_ID;

    // ✅ Step 2: Validate request
    $validated = $request->validate([
        'vendor_order_id' => 'required|integer',
        'status_type' => 'required|in:order,payment',
        'status' => 'required|string',
    ]);

    $orderId = $validated['vendor_order_id'];
    $statusType = $validated['status_type'];
    $incomingStatus = strtolower($validated['status']);

    // ✅ Step 3: Find the suborder using vendor_order_id + branch_ID
    $order = DB::table('suborders')
        ->where('vendor_order_id', $orderId)
        ->where('branch_ID', $branchId)
        ->first();

    if (!$order) {
        return response()->json([
            'success' => false,
            'message' => 'Suborder not found for given vendor_order_id and branch.',
        ], 404);
    }

    // ✅ Step 4: Update payment status
    if ($statusType === 'payment') {
        if ($incomingStatus === 'confirmed_by_vendor' && $order->payment_status !== 'confirmed_by_deliveryboy') {
            return response()->json([
                'success' => false,
                'message' => 'Delivery boy must confirm payment first.',
            ], 400);
        }

        $validPaymentStatuses = [
            'pending',
            'confirmed_by_customer',
            'confirmed_by_deliveryboy',
            'confirmed_by_vendor'
        ];

        if (!in_array($incomingStatus, $validPaymentStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment status.',
            ], 400);
        }

        DB::table('suborders')->where('id', $order->id)->update([
            'payment_status' => $incomingStatus,
        ]);
    }

    // ✅ Step 5: Update order status
    if ($statusType === 'order') {
        $validTransitions = [
            'pending' => 'in_progress',
            'in_progress' => 'ready',
            'picked_up' => 'handover_confirmed',
            'handover_confirmed' => 'in_transit',
            'in_transit' => 'delivered',
            'delivered' => 'completed',
        ];

        $current = strtolower($order->status);
        $allowedNext = $validTransitions[$current] ?? null;

        if ($allowedNext !== $incomingStatus) {
            return response()->json([
                'success' => false,
                'message' => "Invalid order status transition from '$current' to '$incomingStatus'.",
            ], 400);
        }

        // Optional: update main order if becoming in_progress
        if ($incomingStatus === 'in_progress') {
            $mainOrder = DB::table('orders')->where('id', $order->orders_ID)->first();
            if ($mainOrder && $mainOrder->order_status === 'pending') {
                DB::table('orders')->where('id', $order->orders_ID)->update([
                    'order_status' => 'confirmed',
                ]);
            }
        }

        DB::table('suborders')->where('id', $order->id)->update([
            'status' => $incomingStatus,
        ]);
    }

    // ✅ Step 6: Return response
    return response()->json([
        'success' => true,
        'message' => 'Suborder status updated successfully on LMD side.',
        'data' => [
            'status' => $incomingStatus,
            'payment_status' => $statusType === 'payment' ? $incomingStatus : $order->payment_status,
        ]
    ]);
}

}
