<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Worker;
use App\Models\Location;
use App\Models\Schedule;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        /*
        |----------------------------------------------------------------------
        | PANEL KIRI — WORKER BY MONTH
        |----------------------------------------------------------------------
        */

        $month = $request->get('month', now()->month);
        $year  = $request->get('year', now()->year);

        $startMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endMonth   = Carbon::create($year, $month, 1)->endOfMonth();

        $monthName = $startMonth->locale('id')->translatedFormat('F');

        $workers = Worker::where('role_id', 2)
            ->withCount(['schedules as schedule_count' => function ($q) use ($startMonth, $endMonth) {
                $q->whereBetween('waktu_mulai', [$startMonth->timestamp, $endMonth->timestamp]);
            }])
            ->orderBy('name')
            ->get();



        /*
        |----------------------------------------------------------------------
        | PANEL KANAN — FILTER JADWAL
        |----------------------------------------------------------------------
        */

        $locationId  = $request->get('location_id');
        $rightStart = Carbon::create($year, $month, 1)->startOfMonth()->timestamp;
        $rightEnd   = Carbon::create($year, $month, 1)->endOfMonth()->timestamp;

        $locations = Location::orderBy('name')->get();

        $schedulesByLocation = Schedule::with(['worker', 'jobdesc'])
            ->whereBetween('waktu_mulai', [$rightStart, $rightEnd])
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->orderBy('superfisor_id')
            ->orderBy('waktu_mulai')
            ->get()
            ->map(function ($sch) {

                // Convert timestamp → Carbon
                $start = Carbon::createFromTimestamp($sch->waktu_mulai);

                // Kalau ada waktu_selesai
                $end   = $sch->waktu_selesai
                        ? Carbon::createFromTimestamp($sch->waktu_selesai)
                        : null;

                // Tanggal & hari
                $sch->tanggal = $start->format('Y-m-d');
                $sch->hari    = $start->locale('id')->translatedFormat('l');

                // Jam mulai – jam selesai
                $sch->jam_mulai  = $start->format('H:i');
                $sch->jam_selesai = $end ? $end->format('H:i') : null;

                return $sch;
            })
            ->groupBy('superfisor_id')
            ->map(fn($g) => $g->groupBy('tanggal'));


        /*
        |----------------------------------------------------------------------
        | RETURN VIEW
        |----------------------------------------------------------------------
        */

        return view('dashboard', compact(
            'workers', 'month', 'year', 'monthName',
            'locations', 'schedulesByLocation'
        ));
    }
}
