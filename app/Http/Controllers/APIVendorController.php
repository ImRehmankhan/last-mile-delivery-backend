<?php

namespace App\Http\Controllers;

use App\Models\APIVendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Support\Facades\DB;

class ApiVendorController extends Controller {

    // asdasd

    private function getApiDetails( $methodName, $branchId ) {
        return DB::table( 'apimethods' )
        ->join( 'apivendor', 'apivendor.id', '=', 'apimethods.apivendor_ID' )
        ->where( 'apimethods.method_name', $methodName )
        ->where( 'apivendor.branches_ID', $branchId ) // Use branchId to filter the correct vendor
        ->select( 'apivendor.api_base_url', 'apimethods.endpoint', 'apimethods.http_method', 'apivendor.api_key' )
        ->first();
    }

    private function callKfcApi( $methodName, $data = [], $orderId = null, $branchId ) {
        $apiDetails = $this->getApiDetails( $methodName, $branchId );

        if ( !$apiDetails ) {
            return response()->json( [ 'error' => 'API method not found' ], 404 );
        }

        $url = $apiDetails->api_base_url . $apiDetails->endpoint;
        if ( $orderId ) {
            $url = str_replace( '{orderId}', $orderId, $url );
        }

        $response = Http::withHeaders( [
            'Authorization' => 'Bearer ' . $apiDetails->api_key,
        ] )-> {
            $apiDetails->http_method}
            ( $url, $data );

            return $response->json();
        }

        public function getMenu( $branchId ) {
            $menu = $this->callKfcApi( 'getMenu', [], null, $branchId );
            return response()->json( $menu );
        }

    }