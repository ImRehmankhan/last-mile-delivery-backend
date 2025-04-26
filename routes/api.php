<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LMDUserController;
use App\Http\Controllers\DeliveryBoyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\APIVendorController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\OrganizationController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


//Customer
Route::post('/signup', [CustomerController::class, 'signup']);
Route::get('/customers/{id}', [CustomerController::class, 'getCustomerData']);
Route::put('/customers/{id}', [CustomerController::class, 'updateCustomer']);
Route::delete('/customers/{id}', [CustomerController::class, 'deleteCustomer']);
Route::get('/customers/{id}/orders', [CustomerController::class, 'getCustomerOrders']);
Route::get('/orders/{id}/details', [CustomerController::class, 'getOrderDetails']);
Route::post('/itemrating', [CustomerController::class, 'addItemRating']);
Route::post('/customers/{id}/add-address', [CustomerController::class, 'addAddress']);
Route::put('/customers/{customerId}/addresses/{addressId}', [CustomerController::class, 'updateAddress']);
Route::delete('/customers/{customerId}/addresses/{addressId}', [CustomerController::class, 'deleteAddress']);
Route::get('/customers/{customerId}/addresses', action: [CustomerController::class, 'getAllAddresses']);
// Route::post('/customers/schedule-order', [CustomerController::class, 'scheduleOrder']);
// Route::post('/customers/create-menu', [CustomerController::class, 'createMenu']);
Route::get('/shopcategories', [CustomerController::class, 'getAllShopCategories']);
Route::get('customer/main-screen/{customerID}', [CustomerController::class, 'getCustomerMainScreenInformation']);
Route::get('vendor/{vendorId}/shop/{shopId}/branch/{branchId}/menu', [CustomerController::class, 'getVendorShopBranchMenu']);
Route::get('/suborders/{suborderId}/live-tracking', [CustomerController::class, 'getLiveTracking']);

Route::post('/customers/place-order', [CustomerController::class, 'placeOrder']);
Route::POST('/customer/place-order', [CustomerController::class, 'placeOrder']);
Route::put('/customers/orders/{orderId}/cancel', [CustomerController::class, 'cancelOrder']);
Route::patch('/customer/order/{suborderId}/confirm-delivery', [CustomerController::class, 'confirmOrderDelivery']);
Route::post('/customer/confirm-payment/{suborderId}', [CustomerController::class, 'confirmPaymentByCustomer']);
Route::get('/statuses', [CustomerController::class, 'getStatuses']);
Route::get('/suborders/{id}/route-info', [CustomerController::class, 'getDeliveryRouteInfo']);
Route::get('/suborder/{suborderId}/payment-status', [CustomerController::class, 'getPaymentStatus']);

Route::get('customers/{customerId}/orders/previous-week', [CustomerController::class, 'getPreviousWeekOrders']);
Route::get('customers/{customerId}/orders/previous-month', [CustomerController::class, 'getPreviousMonthOrders']);
Route::post('/orders/{orderId}/reorder', [CustomerController::class, 'reorder']);
// COURIER ORDER
Route::get('/customer/itemcategories', [CustomerController::class, 'getAllItemCategories']);
Route::get('/customer/items/{categoryId}', [CustomerController::class, 'getItemsByCategory']);
Route::post('/customer/placecourierorder', [CustomerController::class, 'placeCourierOrder']);
Route::get('/courier/orders/customer/{customers_ID}', [CustomerController::class, 'getCustomerCourierOrders']);
Route::post('/courier-order/{courierOrderId}/confirm-delivery/{customerId}', [CustomerController::class, 'confirmDelivery']);
Route::get('/customer/live-tracking/{courierOrderId}', [CustomerController::class, 'getLiveTrackingData']);

Route::get('/lmd/menu/{branchId}', [CustomerController::class, 'getMenuFromVendor']);



Route::post('/cart/create', [CustomerController::class, 'createOrUpdateCart']);
Route::post('/cart/add-item', [CustomerController::class, 'addItemToCart']);
Route::post('/cart/remove-item', [CustomerController::class, 'removeItemFromCart']);
Route::post('/cart/clear', [CustomerController::class, 'clearCart']);
Route::get('/cart/details', [CustomerController::class, 'getCartDetails']);





//DeliveryBoy
Route::post('deliveryboys/signup', [DeliveryBoyController::class, 'signup']);
Route::put('/deliveryboys/status/{id}', [DeliveryBoyController::class, 'updateDeliveryBoyStatus']);
Route::put('deliveryboy/update/{deliveryBoyId}', [DeliveryBoyController::class, 'updateDeliveryBoy']);
Route::post('/deliveryboys/{id}/add-address', [DeliveryBoyController::class, 'addAddress']);
Route::put('/deliveryboys/{deliveryBoyId}/addresses/{addressId}', [DeliveryBoyController::class, 'updateAddress']);
Route::delete('/deliveryboys/{deliveryBoyId}/addresses/{addressId}', [DeliveryBoyController::class, 'deleteAddress']);
Route::get('/deliveryboys/{deliveryBoyId}/addresses', [DeliveryBoyController::class, 'getAllAddresses']);
Route::post('/deliveryboys/{deliveryBoyId}/vehicle', [DeliveryBoyController::class, 'addVehicle']);
Route::get('/deliveryboy/{deliveryBoyId}/vehicles', [DeliveryBoyController::class, 'getVehiclesByDeliveryBoy']);

Route::put('/deliveryboys/{deliveryBoyId}/vehicle/{vehicleId}', [DeliveryBoyController::class, 'updateVehicle']);
Route::delete('/deliveryboys/{deliveryBoyId}/vehicle/{vehicleId}', [DeliveryBoyController::class, 'deleteVehicle']);
///Courier
Route::get('/deliveryboy/pending-courier-orders', [DeliveryBoyController::class, 'getPendingCourierOrders']);
Route::post('/deliveryboy/assign-order/{courierOrderId}/{deliveryboys_ID}', [DeliveryBoyController::class, 'assignCourierOrderToDeliveryBoy']);
Route::post('/courier-order/{courierOrderId}/mark-picked/{deliveryBoyId}', [DeliveryBoyController::class, 'markOrderAsPicked']);
Route::post('/courier-order/{courierOrderId}/mark-delivered/{deliveryBoyId}', [DeliveryBoyController::class, 'markOrderAsDelivered']);
Route::post('/deliveryboy/update-live-tracking', [DeliveryBoyController::class, 'updateLiveTracking']);
//Order
Route::get('/deliveryboy/ready-suborders/{id}', [DeliveryBoyController::class, 'getReadySubordersForDeliveryBoy']);

Route::get('/deliveryboy/ready-suborders', [DeliveryBoyController::class, 'getReadySubordersForDeliveryBoy']);
//Route::post('/suborders/update-status', [DeliveryBoyController::class, 'updateSuborderStatus']);
Route::post('/deliveryboy/{deliveryBoyId}/accept-order/{suborderId}', [DeliveryBoyController::class, 'acceptOrder']);
Route::get('/deliveryboy/{id}/assigned-suborders', [DeliveryBoyController::class, 'getAssignedSubordersForDeliveryBoy']);

Route::patch('/deliveryboy/order/{suborderId}/pickup', [DeliveryBoyController::class, 'confirmPickup']);
Route::put('/deliveryboy/order/{suborderId}/location', [DeliveryBoyController::class, 'updateLocation']);
Route::post('/deliveryboy/reach-destination/{deliveryBoyId}/{suborderId}', [DeliveryBoyController::class, 'reachDestination']);
Route::post('/deliveryboy/confirm-payment/{suborderId}', [DeliveryBoyController::class, 'confirmPaymentByDeliveryBoy']);

Route::get('/getLatestLocationBySuborderId/{suborderId}', [DeliveryBoyController::class, 'getLatestLocationBySuborderId']);



//Vendor (our vendor)
Route::post('/vendor/signup', [VendorController::class, 'vendorSignup']);
Route::get('/vendor/{id}', [VendorController::class, 'getVendorData']); // Get Vendor Data
Route::put('/vendor/{id}', [VendorController::class, 'updateVendor']); // Update Vendor
Route::post('/vendor/shop', [VendorController::class, 'createShop']); // Create shop
Route::get('/vendor/{vendorId}/shops', [VendorController::class, 'getVendorShops']); // View all shops of a vendor
Route::put('/vendor/shop/{shopId}', [VendorController::class, 'updateShop']); // Update shop
Route::delete('/vendor/shop/{shopId}', [VendorController::class, 'deactivateShop']); // Deactivate shop
Route::put('/vendor/shop/{shopId}', [VendorController::class, 'activateShop']);
Route::post('/branches', [VendorController::class, 'createBranch']);
Route::post('/branches/{id}', [VendorController::class, 'updateBranch']);
Route::delete('/branches/{id}', [VendorController::class, 'deleteBranch']);
Route::get('/branches', [VendorController::class, 'getBranches']);
Route::get('/shop/{shopId}/branches', [VendorController::class, 'getBranchesByShopId']);
Route::put('/Vendor/branches/{id}/togglestatus', [VendorController::class, 'toggleBranchStatus']);
//vendor order and statuses...
Route::get('/vendors/{vendorId}/shops/{shopId}/branches/{branchId}/suborders', [VendorController::class, 'getSubOrders']);
Route::get('/suborders/{suborderId}/details', [VendorController::class, 'getOrderedItemInformation']);
Route::get('/vendor/{vendorId}/suborders', [VendorController::class, 'getSubOrdersByVendor']);

//Route::put('/suborders/status', [VendorController::class, 'updateSuborderStatus']);
Route::patch('/vendor/order/{suborderId}/in-progress', [VendorController::class, 'markInProgress']);
Route::patch('/vendor/order/{suborderId}/ready', [VendorController::class, 'markReady']);
Route::patch('/vendor/order/{suborderId}/handover', [VendorController::class, 'confirmHandover']);
Route::post('/vendor/confirm-payment/{suborderId}', [VendorController::class, 'confirmPaymentByVendor']);




Route::get('/PredefinedAttributes/{itemCategoryId}', [VendorController::class, 'getPredefinedAttributes']);
Route::get('/itemcategories/{shopCategoryId}', [VendorController::class, 'getItemCategories']);
Route::get('/item-variations/{itemCategoryId}', [VendorController::class, 'getItemVariations']);
Route::post('/vendor/{vendorId}/shop/{shopId}/branch/{branchId}/item', [VendorController::class, 'addItem']);
Route::post('/items/{itemId}/variations', [VendorController::class, 'addVariation']); // Add variation
Route::post('/categories', [VendorController::class, 'addCategory']); // Add category
Route::patch('/items/{itemId}/toggle-status', [VendorController::class, 'toggleItemStatus']); // Toggle item status
Route::get('/vendor/{vendorId}/shop/{shopId}/branch/{branchId}/items', [VendorController::class, 'getItemsWithDetails']);
Route::get('/vendor/{vendorId}/shop/{shopId}/branch/{branchId}/categories', [VendorController::class, 'getCategories']);


//VendorApi





//Admin
Route::post('/login', [AdminController::class, 'login']);
Route::get('/admin/customers', [AdminController::class, 'getAllCustomers']);
Route::get('/admin/deliveryboys', [AdminController::class, 'getAllDeliveryBoys']);
Route::get('/admin/orders', [AdminController::class, 'getAllOrders']);
Route::get('/admin/vendors', [AdminController::class, 'getAllVendors']);
Route::get('/admin/vehicles', [AdminController::class, 'getAllVehicles']);
Route::post('/admin/deliveryboys/{id}/reject', [AdminController::class, 'rejectDeliveryBoy']);
Route::post('/admin/deliveryboys/{id}/accept', [AdminController::class, 'acceptDeliveryBoy']);
Route::post('/admin/deliveryboys/{id}/correct-rejection', [AdminController::class, 'correctRejectionReason']);
Route::get('/admin/deliveryboys/{id}/rejection-reasons', [AdminController::class, 'getRejectionReasonsForDeliveryBoy']);
Route::post('/admin/shopcategory', [AdminController::class, 'addShopCategory']); // Add a new shop category

//accept and reject delivery boy....
Route::post('/admin/vendors/{id}/reject', [AdminController::class, 'rejectVendor']);
//Route::post('/admin/vendors/{id}/accept', [AdminController::class, 'acceptVendor']);
Route::put('/vendors/{id}/approve', [AdminController::class, 'acceptVendor']);
Route::post('/admin/vendors/{id}/correct-rejection', [AdminController::class, 'correctVendorRejectionReason']);
Route::get('/admin/vendors/{id}/rejection-reasons', [AdminController::class, 'getRejectionReasonsForVendor']);
////////
// Admin Routes

Route::get('/cities', [AdminController::class, 'getAllCities']);
Route::post('/admin/cities/add', [AdminController::class, 'addCity']);
Route::delete('/admin/cities/delete/{id}', [AdminController::class, 'deleteCity']);
Route::get('/admin/branches/pendingBranches', [AdminController::class, 'getPendingBranches']);
Route::put('/admin/branches/{id}/approve', [AdminController::class, 'approveBranch']);
Route::put('/admin/branches/{id}/reject', [AdminController::class, 'rejectBranch']);
Route::get('/admin/branches/{id}/rejection-reasons', [AdminController::class, 'getRejectionReasons']);
Route::post('/admin/branches/{branch_id}/correct-rejection-reason', [AdminController::class, 'correctBranchRejectionReason']);

Route::get('/admin/shops', [AdminController::class, 'getAllShops']);
Route::get('/admin/branches', [AdminController::class, 'getAllBranches']);

//for api vendors.....
Route::get('/admin/api-vendors', [AdminController::class, 'getApiVendorsWithUsers']);
Route::get('/admin/vendor/{vendorId}/shops', [AdminController::class, 'getShopsAndCategories']);
Route::get('/admin/vendor/{vendorId}/shop/{shopId}/branches', [AdminController::class, 'getBranches']);

Route::post('/admin/branch/add-api-vendor', [AdminController::class, 'addApiVendor']);
Route::post('/admin/vendor/add-api-method', [AdminController::class, 'addApiMethod']);
Route::get('/admin/apivendor/{apivendor_id}/methods', [AdminController::class, 'getMethodsByApiVendorId']);
Route::post('/admin/add-variable', [AdminController::class, 'addVariable']);
Route::post('/admin/branch/add-mapping', [AdminController::class, 'addMapping']);
//ADMINROUTES FOR COURIERITEM
Route::post('/admin/addcourieritemcategory', [AdminController::class, 'addCourierItemCategory']);
Route::post('/admin/addcourieritem', [AdminController::class, 'addCourierItem']);











Route::post('/organization/signup', [OrganizationController::class, 'signup']);
Route::post('/organization/connect-vendor', [OrganizationController::class, 'connectVendorToOrganization']);
Route::post('/organization/signup', [OrganizationController::class, 'signup']);
Route::get('/organizations/{id}', [OrganizationController::class, 'getOrganizationData']);
Route::get('/organizations/{organization_id}/deliveryboys', [OrganizationController::class, 'getDeliveryBoysByOrganization']);
Route::post('/organization/connect-vendor', [VendorController::class, 'connectVendorToOrganization']);
Route::get('/pending-vendor-requests/{organizationId}', [OrganizationController::class, 'getPendingVendorRequests']);
Route::post('/accept-vendor-request/{requestId}', [OrganizationController::class, 'acceptVendorRequest']);
Route::post('/reject-vendor-request/{requestId}', [OrganizationController::class, 'rejectVendorRequest']);
Route::get('/rejection-reasons/{organizationId}', [OrganizationController::class, 'getRejectionReasons']);
Route::post('/correct-rejection-reason/{reasonId}', [OrganizationController::class, 'correctRejectionReason']);
Route::get('deliveryboy/{id}', [DeliveryBoyController::class, 'getDeliveryBoyData']);
Route::get('/vendor/{vendorId}/suborders', [VendorController::class, 'getSubOrdersByVendor']);
Route::post('/suborders/{suborderId}/status', [VendorController::class, 'updateSuborderStatus']);
Route::get('/vendor/{vendorId}/available-organizations', [VendorController::class, 'getAvailableOrganizationsForVendor']);




