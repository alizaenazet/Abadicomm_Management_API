<?php

namespace App\Http\Controllers;

use App\Models\Jobdesc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobdescController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of job descriptions
     */
    public function index()
    {
        $jobdescs = Jobdesc::withCount('schedules')
            ->orderBy('name')
            ->get();

        return view('jobdesc-management', compact('jobdescs'));
    }

    /**
     * Store a newly created job description
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:jobdescs,name',
        ]);

        try {
            Jobdesc::create(['name' => $request->name]);
            return redirect()->route('jobdesc.index')->with('success', 'Job description added successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to add job description: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified job description
     */
    public function update(Request $request, $id)
    {
        $jobdesc = Jobdesc::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:jobdescs,name,' . $id,
        ]);

        try {
            $jobdesc->update(['name' => $request->name]);
            return redirect()->route('jobdesc.index')->with('success', 'Job description updated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update job description: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified job description
     */
    public function destroy($id)
    {
        $jobdesc = Jobdesc::findOrFail($id);

        // Check if jobdesc is being used in any schedules
        $scheduleCount = $jobdesc->schedules()->count();

        if ($scheduleCount > 0) {
            return back()->with('error', "Cannot delete this job description. It is being used in {$scheduleCount} schedule(s).");
        }

        try {
            $jobdesc->delete();
            return redirect()->route('jobdesc.index')->with('success', 'Job description deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete job description: ' . $e->getMessage());
        }
    }
}
