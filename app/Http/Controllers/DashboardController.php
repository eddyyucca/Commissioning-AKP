<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Services\AlertService;
use App\Services\DashboardData;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function home(Request $request, DashboardData $data, AlertService $alerts)
    {
        $alerts->scanIfStale();

        $days = in_array((int) $request->query('hari'), [90, 180, 365], true) ? (int) $request->query('hari') : 180;
        $category = array_key_exists((string) $request->query('kategori'), config('commissioning.categories')) ? (string) $request->query('kategori') : null;
        $area = trim((string) $request->query('area')) ?: null;

        $user = portal_user();

        return view('cms.home', $data->build($category, $area, $days, $user) + [
            'days' => $days,
            'category' => $category,
            'area' => $area,
            'areas' => Unit::query()->active()->whereNotNull('area')->distinct()->orderBy('area')->pluck('area'),
            'canStart' => (bool) $user->hasRole('admin', 'inspector', 'safety', 'workshop'),
        ]);
    }
}
