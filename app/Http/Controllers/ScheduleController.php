<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Worker;
use App\Models\Jobdesc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    public function index()
    {
        $schedules = Schedule::with(['worker', 'jobdesc', 'supervisor'])
            ->orderBy('waktu_mulai', 'asc')
            ->get()
            ->map(function ($schedule) {
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

    // ✅ NEW: Bulk create for multiple workers
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
            (int)$startArray[0], (int)$startArray[1], 0,
            (int)$dateArray[1], (int)$dateArray[2], (int)$dateArray[0]
        ) - $WIB_OFFSET;

        $endTimestamp = mktime(
            (int)$endArray[0], (int)$endArray[1], 0,
            (int)$dateArray[1], (int)$dateArray[2], (int)$dateArray[0]
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

    // ✅ UPDATED: Bulk update for multiple workers
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
            (int)$startArray[0], (int)$startArray[1], 0,
            (int)$dateArray[1], (int)$dateArray[2], (int)$dateArray[0]
        ) - $WIB_OFFSET;

        $endTimestamp = mktime(
            (int)$endArray[0], (int)$endArray[1], 0,
            (int)$dateArray[1], (int)$dateArray[2], (int)$dateArray[0]
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

    private function formatTimestampParts($timestamp)
    {
        if (!$timestamp) return ['date' => '', 'time' => ''];

        // Set timezone to Asia/Jakarta (WIB)
        date_default_timezone_set('Asia/Jakarta');

        $date = date('l, d M Y', $timestamp);
        $time = date('H:i', $timestamp);

        return ['date' => $date, 'time' => $time];
    }
}
