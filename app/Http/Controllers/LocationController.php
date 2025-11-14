<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of locations
     */
    public function index()
    {
        $locations = Location::withCount('schedules')
            ->orderBy('name')
            ->get();

        return view('location-management', compact('locations'));
    }

    /**
     * Store a newly created location
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:locations,name',
        ]);

        try {
            Location::create(['name' => $request->name]);
            return redirect()->route('location.index')->with('success', 'Location added successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to add location: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified location
     */
    public function update(Request $request, $id)
    {
        $location = Location::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:locations,name,' . $id,
        ]);

        try {
            $location->update(['name' => $request->name]);
            return redirect()->route('location.index')->with('success', 'Location updated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update location: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified location
     */
    public function destroy($id)
    {
        $location = Location::findOrFail($id);

        // Check if location is being used in any schedules
        $scheduleCount = $location->schedules()->count();

        if ($scheduleCount > 0) {
            return back()->with('error', "Cannot delete this location. It is being used in {$scheduleCount} schedule(s).");
        }

        try {
            $location->delete();
            return redirect()->route('location.index')->with('success', 'Location deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete location: ' . $e->getMessage());
        }
    }
}
