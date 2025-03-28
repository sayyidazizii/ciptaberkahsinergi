<?php

namespace App\Http\Controllers;

use App\DataTables\CoreAnouncementDataTable;
use App\Models\CoreAnouncement;
use Illuminate\Http\Request;

class CoreAnnouncementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(CoreAnouncementDataTable $table)
    {
        // dd("DOO11");
        return $table->render('content.CoreAnnouncement.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('content.CoreAnnouncement.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CoreAnouncement $coreAnouncement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CoreAnouncement $coreAnouncement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CoreAnouncement $coreAnouncement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CoreAnouncement $coreAnouncement)
    {
        //
    }
}
