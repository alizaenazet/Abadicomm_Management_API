<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use App\Models\Jobdesc;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssignWorkerController extends Controller
{
    public function index()
    {
        $workers = Worker::where('role_id', 2)->get(['id', 'name']);
        $jobdescs = Jobdesc::all(['id', 'name']);
        $supervisors = Worker::where('role_id', 1)->get(['id', 'name']);

        return view('assign-worker', compact('workers', 'jobdescs', 'supervisors'));
    }

    public function store(Request $request)
    {
        // Handle adding new jobdesc
        if ($request->has('addJobdesc')) {
            $request->validate([
                'jobdescName' => 'required|string|max:255|unique:jobdescs,name',
            ]);

            try {
                Jobdesc::create(['name' => $request->jobdescName]);
                return redirect()->route('assign')->with('success', 'Job description added successfully!');
            } catch (\Exception $e) {
                return back()->with('error', 'Failed to add job description: ' . $e->getMessage());
            }
        }

        // Handle worker assignment
        $request->validate([
            'date' => 'required|date',
            'locations' => 'required|array|min:1',
            'locations.*' => 'required|string|max:255',
            'startTime' => 'required',
            'endTime' => 'required',
            'assignments' => 'required|array|min:1',
            'assignments.*.workerId' => 'required|exists:workers,id',
            'assignments.*.jobdescId' => 'required|exists:jobdescs,id',
            'assignments.*.supervisorId' => 'required|exists:workers,id',
        ]);

        // Combine locations with ' || ' separator
        $combinedLocation = implode(' || ', array_filter($request->locations));

        $workerIds = collect($request->assignments)->pluck('workerId');
        if ($workerIds->count() !== $workerIds->unique()->count()) {
            return back()->with('error', 'Duplicate workers detected in assignments')->withInput();
        }

        if ($request->startTime >= $request->endTime) {
            return back()->with('error', 'End time must be after start time')->withInput();
        }

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

        $allWorkerIds = collect($request->assignments)->pluck('workerId')->toArray();
        $allSupervisorIds = collect($request->assignments)->pluck('supervisorId')->unique()->toArray();

        $conflicts = Schedule::where(function ($query) use ($allWorkerIds, $allSupervisorIds) {
            $query->whereIn('worker_id', $allWorkerIds)
                  ->orWhereIn('superfisor_id', $allSupervisorIds);
        })
        ->where(function ($query) use ($startTimestamp, $endTimestamp) {
            $query->where('waktu_mulai', '<', $endTimestamp)
                  ->where('waktu_selesai', '>', $startTimestamp);
        })
        ->with(['worker', 'supervisor'])
        ->first();

        if ($conflicts) {
            $conflictPerson = in_array($conflicts->worker_id, $allWorkerIds) ? 'Worker' : 'Supervisor';
            $conflictStart = date('d M Y H:i', $conflicts->waktu_mulai);
            $conflictEnd = date('H:i', $conflicts->waktu_selesai);

            return back()->with('error', "Konflik waktu! {$conflictPerson} sudah ada jadwal pada {$conflictStart} sampai {$conflictEnd} WIB")->withInput();
        }

        DB::beginTransaction();
        try {
            foreach ($request->assignments as $assignment) {
                Schedule::create([
                    'waktu_mulai' => $startTimestamp,
                    'waktu_selesai' => $endTimestamp,
                    'worker_id' => $assignment['workerId'],
                    'jobdesc_id' => $assignment['jobdescId'],
                    'superfisor_id' => $assignment['supervisorId'],
                    'tempat' => $combinedLocation,
                ]);
            }

            DB::commit();
            return redirect()->route('schedule.page')->with('success', 'Successfully assigned ' . count($request->assignments) . ' worker(s)!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to assign workers: ' . $e->getMessage())->withInput();
        }
    }
}
