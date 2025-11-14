<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Worker;
use App\Models\Jobdesc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ScheduleViewController extends Controller
{
    /**
     * Constructor - Apply auth middleware
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the schedule view page with session data
     */
    public function showSchedulePage(Request $request)
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

        // Fetch raw schedule data for current week
        $rawSchedule = DB::table('schedules as s')
            ->join('workers as sup', 's.superfisor_id', '=', 'sup.id')
            ->join('workers as w', 's.worker_id', '=', 'w.id')
            ->join('jobdescs as j', 's.jobdesc_id', '=', 'j.id')
            ->select(
                's.id',
                's.waktu_mulai',
                's.waktu_selesai',
                's.tempat',
                'sup.id as supervisor_id',
                'sup.name as supervisor_name',
                'w.id as worker_id',
                'w.name as worker_name',
                'j.id as jobdesc_id',
                'j.name as jobdesc_name'
            )
            ->whereBetween('s.waktu_mulai', [$weekStart, $weekEnd])
            ->orderBy('s.waktu_mulai')
            ->get()
            ->map(function ($item) {
                $start = Carbon::createFromTimestamp($item->waktu_mulai, 'Asia/Jakarta');
                $end = Carbon::createFromTimestamp($item->waktu_selesai, 'Asia/Jakarta');

                return [
                    'id' => (string)$item->id,
                    'date' => $start->format('Y-m-d'),
                    'start_time' => $start->format('H:i'),
                    'end_time' => $end->format('H:i'),
                    'supervisor_id' => (string)$item->supervisor_id,
                    'supervisor_name' => $item->supervisor_name,
                    'worker_id' => (string)$item->worker_id,
                    'worker_name' => $item->worker_name,
                    'jobdesc_id' => (string)$item->jobdesc_id,
                    'jobdesc_name' => $item->jobdesc_name,
                    'tempat' => $item->tempat,
                ];
            });

        // Parse and group schedule
        $scheduleData = $this->parseSchedule($rawSchedule);

        // Fetch workers and jobdescs for form
        $workers = DB::table('workers')->select('id', 'name', 'role_id')->get();
        $jobdescs = DB::table('jobdescs')->select('id', 'name')->get();
        $workerCounts = Schedule::join('workers', 'schedules.worker_id', '=', 'workers.id')
            ->whereBetween('schedules.waktu_mulai', [$weekStart, $weekEnd])
            ->select('workers.name', DB::raw('count(workers.name) as count'))
            ->groupBy('workers.name')
            ->orderBy('count', 'desc')
            ->orderBy('workers.name', 'asc')
            ->get()
            ->map(function($item) {
                return ['name' => $item->name, 'count' => $item->count];
            })
            ->all();

        return view('schedule', [
            'displayedDates' => $displayedDates,
            'scheduleMap' => $scheduleData['scheduleMap'],
            'supervisorMap' => $scheduleData['supervisorMap'],
            'timeSlots' => $this->getTimeSlots(),
            'workers' => $workers,
            'jobdescs' => $jobdescs,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'workerCounts' => $workerCounts,
        ]);
    }

    /**
     * Show edit schedule form
     */
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
            return redirect()->route('schedule.page')->with('error', 'Schedule not found');
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

    /**
     * Update schedule
     */
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
            return redirect()->route('schedule.page')->with('success', 'Schedule updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update schedule: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Get all schedules (API endpoint for AJAX)
     */
    public function index(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Schedule::with(['worker', 'jobdesc', 'supervisor'])
            ->orderBy('waktu_mulai', 'asc');

        // Filter by date range if provided
        if ($startDate && $endDate) {
            $weekStart = Carbon::parse($startDate)->startOfDay()->timestamp;
            $weekEnd = Carbon::parse($endDate)->endOfDay()->timestamp;
            $query->whereBetween('waktu_mulai', [$weekStart, $weekEnd]);
        }

        $schedules = $query->get()->map(function ($schedule) {
            $start = $this->formatTimestampParts($schedule->waktu_mulai);
            $end = $this->formatTimestampParts($schedule->waktu_selesai);

            return [
                'id' => (string)$schedule->id,
                'worker_id' => (string)$schedule->worker_id,
                'worker_name' => $schedule->worker->name ?? 'Unknown',
                'jobdesc_id' => (string)$schedule->jobdesc_id,
                'jobdesc_name' => $schedule->jobdesc->name ?? 'Unknown',
                'supervisor_id' => (string)$schedule->superfisor_id,
                'supervisor_name' => $schedule->supervisor->name ?? 'Unknown',
                'tempat' => $schedule->tempat,
                'date' => $start['date'],
                'start_time' => $start['time'],
                'end_time' => $end['time'],
                'raw_start_timestamp' => (string)$schedule->waktu_mulai,
                'raw_end_timestamp' => (string)$schedule->waktu_selesai,
            ];
        });

        return response()->json($schedules);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'workerId' => 'required|exists:workers,id',
            'jobdescId' => 'required|exists:jobdescs,id',
            'supervisorId' => 'required|exists:workers,id',
            'date' => 'required|date',
            'startTime' => 'required',
            'endTime' => 'required',
            'location' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'error' => $validator->errors()->first()
            ], 400);
        }

        // Parse timestamps (WIB timezone)
        $dateArray = explode('-', $request->date);
        $startArray = explode(':', $request->startTime);
        $endArray = explode(':', $request->endTime);

        $WIB_OFFSET = 7 * 60 * 60;

        $startTimestamp = mktime(
            (int)$startArray[0],
            (int)$startArray[1],
            0,
            (int)$dateArray[1],
            (int)$dateArray[2],
            (int)$dateArray[0]
        ) - $WIB_OFFSET;

        $endTimestamp = mktime(
            (int)$endArray[0],
            (int)$endArray[1],
            0,
            (int)$dateArray[1],
            (int)$dateArray[2],
            (int)$dateArray[0]
        ) - $WIB_OFFSET;

        // Check conflicts
        $conflicts = Schedule::where(function ($query) use ($request) {
            $query->where('worker_id', $request->workerId)
                ->orWhere('superfisor_id', $request->supervisorId);
        })
            ->where(function ($query) use ($startTimestamp, $endTimestamp) {
                $query->where(function ($q) use ($startTimestamp, $endTimestamp) {
                    $q->where('waktu_mulai', '<', $endTimestamp)
                        ->where('waktu_selesai', '>', $startTimestamp);
                });
            })
            ->with(['worker', 'supervisor'])
            ->first();

        if ($conflicts) {
            $conflictPerson = $conflicts->worker_id == $request->workerId ? 'Worker' : 'Supervisor';
            $conflictStart = date('d M Y H:i', $conflicts->waktu_mulai);
            $conflictEnd = date('H:i', $conflicts->waktu_selesai);

            return response()->json([
                'ok' => false,
                'error' => "Konflik waktu! {$conflictPerson} sudah ada jadwal pada {$conflictStart} sampai {$conflictEnd} WIB"
            ], 409);
        }

        $schedule = Schedule::create([
            'waktu_mulai' => $startTimestamp,
            'waktu_selesai' => $endTimestamp,
            'worker_id' => $request->workerId,
            'jobdesc_id' => $request->jobdescId,
            'superfisor_id' => $request->supervisorId,
            'tempat' => $request->location,
        ]);

        return response()->json([
            'ok' => true,
            'id' => $schedule->id,
        ]);
    }

    public function bulkStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'schedules' => 'required|array',
            'schedules.*.workerId' => 'required|exists:workers,id',
            'schedules.*.jobdescId' => 'required|exists:jobdescs,id',
            'schedules.*.supervisorId' => 'required|exists:workers,id',
            'date' => 'required|date',
            'startTime' => 'required',
            'endTime' => 'required',
            'location' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'error' => $validator->errors()->first()
            ], 400);
        }

        // Parse timestamps
        $dateArray = explode('-', $request->date);
        $startArray = explode(':', $request->startTime);
        $endArray = explode(':', $request->endTime);
        $WIB_OFFSET = 7 * 60 * 60;

        $startTimestamp = mktime(
            (int)$startArray[0],
            (int)$startArray[1],
            0,
            (int)$dateArray[1],
            (int)$dateArray[2],
            (int)$dateArray[0]
        ) - $WIB_OFFSET;

        $endTimestamp = mktime(
            (int)$endArray[0],
            (int)$endArray[1],
            0,
            (int)$dateArray[1],
            (int)$dateArray[2],
            (int)$dateArray[0]
        ) - $WIB_OFFSET;

        // Check conflicts for ALL workers
        $workerIds = collect($request->schedules)->pluck('workerId')->toArray();
        $supervisorIds = collect($request->schedules)->pluck('supervisorId')->unique()->toArray();

        $conflicts = Schedule::where(function ($query) use ($workerIds, $supervisorIds) {
            $query->whereIn('worker_id', $workerIds)
                ->orWhereIn('superfisor_id', $supervisorIds);
        })
            ->where(function ($query) use ($startTimestamp, $endTimestamp) {
                $query->where('waktu_mulai', '<', $endTimestamp)
                    ->where('waktu_selesai', '>', $startTimestamp);
            })
            ->with(['worker', 'supervisor'])
            ->first();

        if ($conflicts) {
            $conflictPerson = in_array($conflicts->worker_id, $workerIds) ? 'Worker' : 'Supervisor';
            $conflictStart = date('d M Y H:i', $conflicts->waktu_mulai);
            $conflictEnd = date('H:i', $conflicts->waktu_selesai);

            return response()->json([
                'ok' => false,
                'error' => "Konflik waktu! {$conflictPerson} sudah ada jadwal pada {$conflictStart} sampai {$conflictEnd} WIB"
            ], 409);
        }

        DB::beginTransaction();
        try {
            foreach ($request->schedules as $schedule) {
                Schedule::create([
                    'waktu_mulai' => $startTimestamp,
                    'waktu_selesai' => $endTimestamp,
                    'worker_id' => $schedule['workerId'],
                    'jobdesc_id' => $schedule['jobdescId'],
                    'superfisor_id' => $schedule['supervisorId'],
                    'tempat' => $request->location,
                ]);
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Schedules created successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'scheduleIdsToDelete' => 'required|array',
            'schedules' => 'required|array',
            'schedules.*.workerId' => 'required|exists:workers,id',
            'schedules.*.jobdescId' => 'required|exists:jobdescs,id',
            'schedules.*.supervisorId' => 'required|exists:workers,id',
            'date' => 'required|date',
            'startTime' => 'required',
            'endTime' => 'required',
            'location' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'error' => $validator->errors()->first()
            ], 400);
        }

        // Parse timestamps
        $dateArray = explode('-', $request->date);
        $startArray = explode(':', $request->startTime);
        $endArray = explode(':', $request->endTime);
        $WIB_OFFSET = 7 * 60 * 60;

        $startTimestamp = mktime(
            (int)$startArray[0],
            (int)$startArray[1],
            0,
            (int)$dateArray[1],
            (int)$dateArray[2],
            (int)$dateArray[0]
        ) - $WIB_OFFSET;

        $endTimestamp = mktime(
            (int)$endArray[0],
            (int)$endArray[1],
            0,
            (int)$dateArray[1],
            (int)$dateArray[2],
            (int)$dateArray[0]
        ) - $WIB_OFFSET;

        DB::beginTransaction();
        try {
            // Delete old schedules ONCE
            Schedule::whereIn('id', $request->scheduleIdsToDelete)->delete();

            // Create new schedules for all workers
            foreach ($request->schedules as $schedule) {
                Schedule::create([
                    'waktu_mulai' => $startTimestamp,
                    'waktu_selesai' => $endTimestamp,
                    'worker_id' => $schedule['workerId'],
                    'jobdesc_id' => $schedule['jobdescId'],
                    'superfisor_id' => $schedule['supervisorId'],
                    'tempat' => $request->location,
                ]);
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Schedules updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $schedule = Schedule::find($id);

        if (!$schedule) {
            return response()->json([
                'ok' => false,
                'error' => 'Schedule not found'
            ], 404);
        }

        $schedule->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Schedule deleted successfully'
        ]);
    }

    // Private helper methods

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
            if (!isset($item['date'])) continue;

            $dateKey = $item['date'];
            $key = "{$dateKey}_{$item['supervisor_name']}_{$item['start_time']}";

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'id' => $key,
                    'dateKey' => $dateKey,
                    'supervisor_name' => $item['supervisor_name'],
                    'supervisor_id' => $item['supervisor_id'],
                    'workers' => [],
                    'start' => $item['start_time'],
                    'end' => $item['end_time'],
                    'scheduleIds' => [],
                ];
            }

            $grouped[$key]['workers'][] = [
                'worker_name' => $item['worker_name'],
                'worker_id' => $item['worker_id'],
                'jobdesc_name' => $item['jobdesc_name'],
                'jobdesc_id' => $item['jobdesc_id'],
                'tempat' => $item['tempat'],
            ];

            $grouped[$key]['scheduleIds'][] = $item['id'];
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
                usort($supervEvents, function ($a, $b) {
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
            $supervisors = array_unique(array_map(function ($e) {
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

    private function formatTimestampParts($timestamp)
    {
        if (!$timestamp) return ['date' => '', 'time' => ''];

        date_default_timezone_set('Asia/Jakarta');
        $date = date('l, d M Y', $timestamp);
        $time = date('H:i', $timestamp);

        return ['date' => $date, 'time' => $time];
    }
}
