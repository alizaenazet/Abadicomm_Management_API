<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Get current week dates (Monday to Sunday)
        $displayedDates = $this->getCurrentWeekDates();

        // Fetch workers
        $workers = DB::table('workers')
            ->select('name', 'role_id')
            ->get();

        // Get week start and end timestamps
        $weekStart = Carbon::parse($displayedDates[0]['formatted'])->startOfDay()->timestamp;
        $weekEnd = Carbon::parse($displayedDates[6]['formatted'])->endOfDay()->timestamp;

        // Fetch raw schedule data
        $rawSchedule = DB::table('schedules as s')
            ->join('workers as sup', 's.superfisor_id', '=', 'sup.id')
            ->join('workers as w', 's.worker_id', '=', 'w.id')
            ->join('jobdescs as j', 's.jobdesc_id', '=', 'j.id')
            ->select(
                's.waktu_mulai',
                's.waktu_selesai',
                'sup.name as supervisor_name',
                'w.name as worker_name',
                'j.name as jobdesc_name'
            )
            ->whereBetween('s.waktu_mulai', [$weekStart, $weekEnd])
            ->orderBy('s.waktu_mulai')
            ->get()
            ->map(function($item) {
                // Convert timestamps to date and time strings
                // Gunakan timezone Asia/Jakarta
                $start = Carbon::createFromTimestamp($item->waktu_mulai, 'Asia/Jakarta');
                $end = Carbon::createFromTimestamp($item->waktu_selesai, 'Asia/Jakarta');

                return (object)[
                    'date' => $start->format('Y-m-d'),
                    'start_time' => $start->format('H:i'),
                    'end_time' => $end->format('H:i'),
                    'supervisor_name' => $item->supervisor_name,
                    'worker_name' => $item->worker_name,
                    'jobdesc_name' => $item->jobdesc_name,
                ];
            });

        // Parse and group schedule
        $scheduleData = $this->parseSchedule($rawSchedule);

        return view('dashboard', [
            'workers' => $workers,
            'displayedDates' => $displayedDates,
            'scheduleMap' => $scheduleData['scheduleMap'],
            'supervisorMap' => $scheduleData['supervisorMap'],
            'timeSlots' => $this->getTimeSlots(),
        ]);
    }

    private function getCurrentWeekDates()
    {
        $today = Carbon::now();
        $currentDay = $today->dayOfWeek;
        $diffToMonday = $currentDay === 0 ? -6 : 1 - $currentDay;
        $monday = $today->copy()->addDays($diffToMonday);

        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $monday->copy()->addDays($i);
            $dates[] = [
                'formatted' => $date->format('Y-m-d'),
                'day' => $date->translatedFormat('l'),
                'date' => $date->format('d M'),
            ];
        }

        return $dates;
    }

    private function getTimeSlots()
    {
        $slots = [];
        for ($h = 7; $h < 21; $h++) {
            $start = str_pad($h, 2, '0', STR_PAD_LEFT);
            $end = str_pad($h + 1, 2, '0', STR_PAD_LEFT);
            $slots[] = "{$start}:00 - {$end}:00";
        }
        return $slots;
    }

    private function parseSchedule($rawSchedule)
    {
        $grouped = [];

        foreach ($rawSchedule as $item) {
            if (!$item->date) continue;

            $dateKey = $item->date;
            $key = "{$dateKey}_{$item->supervisor_name}_{$item->start_time}";

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'id' => $key,
                    'dateKey' => $dateKey,
                    'supervisor_name' => $item->supervisor_name,
                    'workers' => [],
                    'start' => $item->start_time,
                    'end' => $item->end_time,
                ];
            }

            $grouped[$key]['workers'][] = [
                'worker_name' => $item->worker_name,
                'jobdesc_name' => $item->jobdesc_name,
            ];
        }

        $scheduleMap = [];

        foreach ($grouped as $ev) {
            $start = $this->parseTime($ev['start']);
            $end = $this->parseTime($ev['end']);

            if (!isset($start['h']) || !isset($end['h'])) continue;

            $startHour = $start['h'];
            $endHour = $end['h'];

            // Jika ada menit di end time, tambahkan 1 jam
            if ($end['m'] > 0) {
                $endHour += 1;
            }

            // Grid row start dari jam mulai (dikurangi offset jam 7)
            $gridRowStart = $startHour - 7 + 1;

            // Span berapa row (dari jam mulai ke jam selesai)
            $gridRowSpan = $endHour - $startHour;

            if (!isset($scheduleMap[$ev['dateKey']])) {
                $scheduleMap[$ev['dateKey']] = [];
            }

            $scheduleMap[$ev['dateKey']][] = array_merge($ev, [
                'gridRowStart' => $gridRowStart,
                'gridRowSpan' => $gridRowSpan,
                'startHour' => $startHour,
                'endHour' => $endHour,
            ]);
        }

        // Handle overlaps per supervisor
        foreach ($scheduleMap as $dateKey => $events) {
            $supervisorGroups = [];

            foreach ($events as $key => $ev) {
                $sup = $ev['supervisor_name'];
                if (!isset($supervisorGroups[$sup])) {
                    $supervisorGroups[$sup] = [];
                }
                $supervisorGroups[$sup][$key] = $ev;
            }

            foreach ($supervisorGroups as $sup => $supervEvents) {
                usort($supervEvents, function($a, $b) {
                    return $a['startHour'] - $b['startHour'];
                });

                $columns = [];

                for ($i = 0; $i < count($supervEvents); $i++) {
                    $assigned = false;
                    for ($j = 0; $j < count($columns); $j++) {
                        if ($columns[$j] <= $supervEvents[$i]['startHour']) {
                            $supervEvents[$i]['subColumn'] = $j;
                            $columns[$j] = $supervEvents[$i]['endHour'];
                            $assigned = true;
                            break;
                        }
                    }

                    if (!$assigned) {
                        $supervEvents[$i]['subColumn'] = count($columns);
                        $columns[] = $supervEvents[$i]['endHour'];
                    }
                }

                $totalCols = count($columns) > 0 ? count($columns) : 1;
                for ($i = 0; $i < count($supervEvents); $i++) {
                    $supervEvents[$i]['totalSubColumns'] = $totalCols;
                }

                $supervisorGroups[$sup] = $supervEvents;
            }

            // Merge back
            $scheduleMap[$dateKey] = [];
            foreach ($supervisorGroups as $supervEvents) {
                foreach ($supervEvents as $ev) {
                    $scheduleMap[$dateKey][] = $ev;
                }
            }
        }

        // Build supervisor map
        $supervisorMap = [];
        foreach ($scheduleMap as $date => $events) {
            $supervisors = array_unique(array_map(function($e) {
                return $e['supervisor_name'];
            }, $events));
            sort($supervisors);
            $supervisorMap[$date] = $supervisors;
        }

        return [
            'scheduleMap' => $scheduleMap,
            'supervisorMap' => $supervisorMap,
        ];
    }

    private function parseTime($time)
    {
        $parts = preg_split('/[:\.]/', $time);
        return [
            'h' => (int)$parts[0],
            'm' => isset($parts[1]) ? (int)$parts[1] : 0,
        ];
    }
}
