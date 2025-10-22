<?php

namespace App\Http\Controllers\API;

use App\Models\Tenant;
use App\Models\Room;
use App\Models\Property;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function getDashboardStats()
    {
        $landlordId = Auth::id();
        
        $currentMonth = Carbon::now()->format('Y-m');
        $lastMonth = Carbon::now()->subMonth()->format('Y-m');
        
        $propertyIds = Property::where('landlord_id', $landlordId)->pluck('id');
        
        $totalBuildings = Property::where('landlord_id', $landlordId)->count();
        
        $totalRooms = Room::whereHas('floor.property', function ($q) use ($landlordId) {
            $q->where('landlord_id', $landlordId);
        })->count();
        
        $totalTenants = Tenant::whereHas('room.floor.property', function ($q) use ($landlordId) {
            $q->where('landlord_id', $landlordId);
        })->count();


        $basePaymentQuery = Payment::whereHas('tenant.room.floor.property', function ($q) use ($landlordId) {
            $q->where('landlord_id', $landlordId);
        })
        ->where('status', 'paid'); 

        $currentMonthTotal = (clone $basePaymentQuery)
            ->where('month_years', $currentMonth)
            ->sum('total_amount');
            
        $lastMonthTotal = (clone $basePaymentQuery)
            ->where('month_years', $lastMonth)
            ->sum('total_amount');


        $paymentMomChange = 0;
        $paymentMomPercentage = 0;

        if ($lastMonthTotal > 0) {
            $paymentMomChange = $currentMonthTotal - $lastMonthTotal;
            $paymentMomPercentage = (($currentMonthTotal - $lastMonthTotal) / $lastMonthTotal) * 100;
        } elseif ($currentMonthTotal > 0) {
            $paymentMomPercentage = 100;
        }

        
        $paymentTrend = $basePaymentQuery
            ->select(
                DB::raw('SUBSTR(month_years, 1, 7) as month'), // Extract YYYY-MM
                DB::raw('SUM(total_amount) as total')
            )
            ->where('month_years', '>=', Carbon::now()->subMonths(5)->format('Y-m')) // Look back 5 months + current
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();


        
        return response()->json([
            'status' => 'success',
            'data' => [
                'static_assets' => [
                    'buildings' => $totalBuildings,
                    'rooms' => $totalRooms,
                    'tenants' => $totalTenants,
                ],
                'payments' => [
                    'current_month_key' => $currentMonth,
                    'current_month_total' => (float) $currentMonthTotal,
                    'last_month_total' => (float) $lastMonthTotal,
                    'mom_change_amount' => (float) $paymentMomChange,
                    'mom_change_percentage' => round($paymentMomPercentage, 2),
                    'trend_data' => $paymentTrend, 
                ]
            ]
        ]);
    }
}