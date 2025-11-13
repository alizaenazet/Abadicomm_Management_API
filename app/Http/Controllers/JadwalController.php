<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Worker;
use App\Models\Jobdesc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class JadwalController extends Controller
{
    public function index(Request $request)
    {
        // Get date range from request or use current week
        if ($request->has('startDate') && $request->startDate) {
            $startDate = $request->startDate;
            $endDate = Carbon::parse($startDate)->addDays(6)->format('Y-m-d');
        } else {
            $today = Carbon::now('Asia/Jakarta');
            $startDate = $today->copy()->format('Y-m-d');
            $endDate = $today->copy()->addDays(6)->format('Y-m-d');
        }

        $displayedDates = $this->generateDateRange($startDate, $endDate);

        // Convert to timestamps
        $weekStart = Carbon::parse($startDate)->startOfDay()->timestamp;
        $weekEnd = Carbon::parse($endDate)->endOfDay()->timestamp;

        // Fetch raw schedule data
        $rawSchedule = DB::table('schedules as s')
            ->join('workers as sup', 's.superfisor_id', '=', 'sup.id')
            ->join('workers as w', 's.worker_id', '=', 'w.id')
            ->join('jobdescs as j', 's.jobdesc_id', '=', 'j.id')
            ->select(
                's.id',
                's.waktu_mulai',
                's.waktu_selesai',
                'sup.name as supervisor_name',
                'w.name as worker_name',
                'w.id as worker_id',
                'j.name as jobdesc_name',
                'j.id as jobdesc_id',
                's.tempat',
                's.superfisor_id'
            )
            ->whereBetween('s.waktu_mulai', [$weekStart, $weekEnd])
            ->orderBy('s.waktu_mulai')
            ->get()
            ->map(function($item) {
                $start = Carbon::createFromTimestamp($item->waktu_mulai, 'Asia/Jakarta');
                $end = Carbon::createFromTimestamp($item->waktu_selesai, 'Asia/Jakarta');

                return (object)[
                    'id' => $item->id,
                    'date' => $start->format('Y-m-d'),
                    'start_time' => $start->format('H:i'),
                    'end_time' => $end->format('H:i'),
                    'supervisor_name' => $item->supervisor_name,
                    'supervisor_id' => $item->superfisor_id,
                    'worker_name' => $item->worker_name,
                    'worker_id' => $item->worker_id,
                    'jobdesc_name' => $item->jobdesc_name,
                    'jobdesc_id' => $item->jobdesc_id,
                    'tempat' => $item->tempat,
                ];
            });

        // Parse and group schedule
        $scheduleData = $this->parseSchedule($rawSchedule);

        return view('jadwal', [
            'displayedDates' => $displayedDates,
            'scheduleMap' => $scheduleData['scheduleMap'],
            'supervisorMap' => $scheduleData['supervisorMap'],
            'timeSlots' => $this->getTimeSlots(),
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function edit($dateKey, $supervisor, $start)
    {
        // Decode URL parameters
        $dateKey = urldecode($dateKey);
        $supervisor = urldecode($supervisor);
        $start = urldecode($start);

        // Find schedules matching this group
        $startTimeParts = explode(':', $start);
        $dateParts = explode('-', $dateKey);
        $WIB_OFFSET = 7 * 60 * 60;

        $searchStartTimestamp = mktime(
            (int)$startTimeParts[0],
            (int)$startTimeParts[1],
            0,
            (int)$dateParts[1],
            (int)$dateParts[2],
            (int)$dateParts[0]
        ) - $WIB_OFFSET;

        $schedules = Schedule::with(['worker', 'jobdesc', 'supervisor'])
            ->where('superfisor_id', function($query) use ($supervisor) {
                $query->select('id')
                    ->from('workers')
                    ->where('name', $supervisor)
                    ->limit(1);
            })
            ->where('waktu_mulai', $searchStartTimestamp)
            ->get();

        if ($schedules->isEmpty()) {
            return redirect()->route('jadwal')->with('error', 'Schedule not found');
        }

        $firstSchedule = $schedules->first();
        $startTime = Carbon::createFromTimestamp($firstSchedule->waktu_mulai, 'Asia/Jakarta');
        $endTime = Carbon::createFromTimestamp($firstSchedule->waktu_selesai, 'Asia/Jakarta');

        $workers = Worker::where('role_id', 2)->get(['id', 'name']);
        $jobdescs = Jobdesc::all(['id', 'name']);
        $supervisors = Worker::where('role_id', 1)->get(['id', 'name']);

        $assignments = $schedules->map(function($schedule) {
            return [
                'id' => $schedule->id,
                'workerId' => $schedule->worker_id,
                'workerName' => $schedule->worker->name,
                'jobdescId' => $schedule->jobdesc_id,
                'jobdescName' => $schedule->jobdesc->name,
                'supervisorId' => $schedule->superfisor_id,
                'supervisorName' => $schedule->supervisor->name,
                'tempat' => $schedule->tempat,
            ];
        })->toArray();

        return view('edit-schedule', compact(
            'schedules',
            'assignments',
            'dateKey',
            'startTime',
            'endTime',
            'workers',
            'jobdescs',
            'supervisors'
        ));
    }

    public function update(Request $request)
    {
        $request->validate([
            'scheduleIds' => 'required|array|min:1',
            'date' => 'required|date',
            'location' => 'required|string',
            'startTime' => 'required',
            'endTime' => 'required',
            'assignments' => 'required|array|min:1',
            'assignments.*.workerId' => 'required|exists:workers,id',
            'assignments.*.jobdescId' => 'required|exists:jobdescs,id',
            'assignments.*.supervisorId' => 'required|exists:workers,id',
        ]);

        // Check for duplicate workers
        $workerIds = collect($request->assignments)->pluck('workerId');
        if ($workerIds->count() !== $workerIds->unique()->count()) {
            return back()->with('error', 'Duplicate workers detected in assignments')->withInput();
        }

        // Validate time
        if ($request->startTime >= $request->endTime) {
            return back()->with('error', 'End time must be after start time')->withInput();
        }

        // Parse timestamps
        $dateArray = explode('-', $request->date);
        $startArray = explode(':', $request->startTime);
        $endArray = explode(':', $request->endTime);
        $WIB_OFFSET = 7 * 60 * 60;

        $startTimestamp = mktime(
            (int)$startArray[0], (int)$startArray[1], 0,
            (int)$dateArray[1], (int)$dateArray[2], (int)$dateArray[0]
        ) - $WIB_OFFSET;

        $endTimestamp = mktime(
            (int)$endArray[0], (int)$endArray[1], 0,
            (int)$dateArray[1], (int)$dateArray[2], (int)$dateArray[0]
        ) - $WIB_OFFSET;

        DB::beginTransaction();
        try {
            // Delete old schedules
            Schedule::whereIn('id', $request->scheduleIds)->delete();

            // Create new schedules
            foreach ($request->assignments as $assignment) {
                Schedule::create([
                    'waktu_mulai' => $startTimestamp,
                    'waktu_selesai' => $endTimestamp,
                    'worker_id' => $assignment['workerId'],
                    'jobdesc_id' => $assignment['jobdescId'],
                    'superfisor_id' => $assignment['supervisorId'],
                    'tempat' => $request->location,
                ]);
            }

            DB::commit();
            return redirect()->route('jadwal')->with('success', 'Schedule updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update schedule: ' . $e->getMessage())->withInput();
        }
    }

    private function generateDateRange($start, $end)
    {
        $startD = Carbon::parse($start);
        $endD = Carbon::parse($end);
        $range = [];

        for ($d = $startD->copy(); $d <= $endD; $d->addDay()) {
            $range[] = [
                'day' => $d->translatedFormat('l'),
                'date' => $d->format('d M'),
                'formatted' => $d->format('Y-m-d'),
            ];
        }

        return $range;
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
                    'supervisor_id' => $item->supervisor_id,
                    'workers' => [],
                    'start' => $item->start_time,
                    'end' => $item->end_time,
                    'scheduleIds' => [],
                ];
            }

            $grouped[$key]['workers'][] = [
                'worker_name' => $item->worker_name,
                'worker_id' => $item->worker_id,
                'jobdesc_name' => $item->jobdesc_name,
                'jobdesc_id' => $item->jobdesc_id,
                'tempat' => $item->tempat,
            ];

            $grouped[$key]['scheduleIds'][] = $item->id;
        }

        $scheduleMap = [];

        foreach ($grouped as $ev) {
            $start = $this->parseTime($ev['start']);
            $end = $this->parseTime($ev['end']);

            if (!isset($start['h']) || !isset($end['h'])) continue;

            $startHour = $start['h'];
            $endHour = $end['h'];

            if ($end['m'] > 0) {
                $endHour += 1;
            }

            $gridRowStart = $startHour - 7 + 1;
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
