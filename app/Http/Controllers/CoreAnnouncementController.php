<?php

namespace App\Http\Controllers;

use Notification;
use App\Models\MobileUser;
use Illuminate\Http\Request;
use App\Models\CoreAnouncement;
use Illuminate\Support\Facades\DB;
use App\Notifications\MobileAnouncement;
use App\DataTables\CoreAnouncementDataTable;

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
        // dd($request->all());
        $request->validate([
            'title' => 'required',
            'message' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
        ]);
        try {
            DB::beginTransaction();
            // content
            $announcement = CoreAnouncement::create([
                'title' => $request->title,
                'message' => $request->message,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'image' => $request->image->store('announcement/images'),
            ]);
            $mobileUser = MobileUser::all();
            Notification::send($mobileUser, new MobileAnouncement($announcement));
            DB::commit();
        return redirect()->route('android.anouncement.index')->success("Pengumuman berhasil dibuat");
        } catch (\Exception $e) {
        DB::rollBack();
        report($e);
        return redirect()->route('android.anouncement.index')->error("Pengumuman gagal dibuat, Silahkan coba lagi");
        }
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
