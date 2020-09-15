<?php

namespace App\Http\Controllers;

use App\Dummy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DummyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $firstname = "Studio 1";
        $start = "2020-09-07 00:04:00";
        $end = "2020-09-07 00:04:12";
        $dbfirst = DB::table('dummies')
            ->where('firstname', 'like', $firstname)
            ->where('start', '<=', $end)
            ->where('end', '>=', $start)
            ->count();
        return $dbfirst;
        // return Dummy::all();
        // $start_one = '2020-09-06 00:04:10';
        // $end_one = '2020-09-10 00:04:12';
        // $start_two = '2020-09-10 00:04:13';
        // $end_two = '2020-09-12 00:04:12';
        // if ($start_one <= $end_two && $end_one >= $start_two) { //If the dates overlap
        //     echo "0"; //return how many days overlap
        // } else {
        //     echo "1"; //Return 0 if there is no overlap
        // }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $firstname = $request->firstname;
        $start = $request->start;
        $end = $request->end;
        $dbfirst = DB::table('dummies')
            ->where('firstname', 'like', $firstname)
            ->where('start', '<=', $end)
            ->where('end', '>=', $start)
            ->count();
        if ($dbfirst > 0) {
            return response()->json("schedule overlapped", 400);
        } else {
            Dummy::create([
                'firstname' => $firstname,
                'start' => $start,
                'end' => $end
            ]);
            return "success";
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Dummy  $dummy
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Dummy::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Dummy  $dummy
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Dummy $dummy)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Dummy  $dummy
     * @return \Illuminate\Http\Response
     */
    public function destroy(Dummy $dummy)
    {
        //
    }
}
