<?php

namespace App\Http\Controllers;

use App\Schedule;
use App\Movie;
use App\Studio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\Schedule as ScheduleCollection;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return DB::table('schedules')
            ->leftJoin('movies', 'movies.id', '=', 'schedules.movie_id')
            ->leftJoin('studios', 'studios.id', '=', 'schedules.studio_id')
            ->leftJoin('branches', 'branches.id', '=', 'studios.branch_id')
            ->select('schedules.id', 'movies.name as movie_name', 'studios.name as studio_name', 'branches.name as branch_name', 'schedules.start', 'schedules.end', 'schedules.price')
            ->paginate(5);
        // return Schedule::paginate(5);
    }
    public function user()
    {
        // $query =  DB::table('schedules')
        //     ->select(DB::raw('movies.name as name , schedules.price as price, CAST(schedules.`start` AS TIME(0)) as start_time'))
        //     ->leftJoin('movies', 'schedules.movie_id', '=', 'movies.id')
        //     ->paginate(5);
        $query = DB::table('schedules')
            ->select(DB::raw('movies.name as name, schedules.price, GROUP_CONCAT(CAST(schedules.start AS TIME(0))) AS start_time'))
            ->leftJoin('movies', 'schedules.movie_id', '=', 'movies.id')
            ->groupBy('movies.name', 'schedules.price')
            ->get();
        return $query;
    }
    public function filter(Request $request, Schedule $schedule)
    {
        $schedule = $schedule->newQuery();
        if (!empty($request->branch)) {
            $schedule->where('studio_id', '=', $request->branch);
        }
        if ($request->has('start')) {
            $schedule->whereDate('start', '=', $request->start);
        }
        return $schedule->select(DB::raw('movies.name as name, schedules.price, GROUP_CONCAT(CAST(schedules.start AS TIME(0))) AS start_time'))
            ->leftJoin('movies', function ($join) {
                $join->on('schedules.movie_id', '=', 'movies.id');
            })
            ->groupBy('movies.name', 'schedules.price')
            ->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $start_time = date('H:i:s', strtotime($request->start));
        $year = date('Y-m-d', strtotime($request->start));
        $movie = $request->movie_id;
        $movies = Movie::findOrFail($movie);
        function convertToHoursMins($time, $format = '%02d:%02d')
        {
            if ($time < 1) {
                return;
            }
            $hours = floor($time / 60);
            $minutes = ($time % 60);
            return sprintf($format, $hours, $minutes);
        }
        $length = convertToHoursMins($movies->minute_length, '%02d:%02d');
        $plus = strtotime($start_time) + strtotime($length);
        $plus = date('H:i:s', $plus);
        $timing = date('Y-m-d', strtotime($year)) . "T" . $plus;
        //price
        $day = date('D', strtotime($year));
        $studio = $request->studio_id;
        $studios = Studio::find($studio);
        if ($day == 'Fri') {
            $price = $studios->basic_price + $studios->aditional_friday_price;
        } elseif ($day == 'Sat') {
            $price = $studios->basic_price + $studios->aditional_saturday_price;
        } elseif ($day == 'Sun') {
            $price = $studios->basic_price + $studios->aditional_sunday_price;
        } else {
            $price = $studios->basic_price;
        }
        $timefilter = DB::table('schedules')
            ->where('studio_id', 'like', $studio)
            ->where('start', '<=', $timing)
            ->where('end', '>=', $request->start)
            ->count();
        if ($timefilter > 0) {
            return response()->json("schedule overlapped", 400);
        } else {
            Schedule::create([
                'movie_id' => $movie,
                'studio_id' => $studio,
                'start' => $request->start,
                'end' => $timing,
                'price' => $price
            ]);
            return "create schedule success";
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Schedule  $schedule
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Schedule::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Schedule  $schedule
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);
        $start_time = date('H:i:s', strtotime($request->start));
        $year = date('Y-m-d', strtotime($request->start));
        $movie = $request->movie_id;
        $movies = Movie::findOrFail($movie);
        function convertToHoursMins($time, $format = '%02d:%02d')
        {
            if ($time < 1) {
                return;
            }
            $hours = floor($time / 60);
            $minutes = ($time % 60);
            return sprintf($format, $hours, $minutes);
        }
        $length = convertToHoursMins($movies->minute_length, '%02d:%02d');
        $plus = strtotime($start_time) + strtotime($length);
        $plus = date('H:i:s', $plus);
        $timing = date('Y-m-d', strtotime($year)) . "T" . $plus;
        //price
        $day = date('D', strtotime($year));
        $studio = $request->studio_id;
        $studios = Studio::find($studio);
        if ($day == 'Fri') {
            $price = $studios->basic_price + $studios->aditional_friday_price;
        } elseif ($day == 'Sat') {
            $price = $studios->basic_price + $studios->aditional_saturday_price;
        } elseif ($day == 'Sun') {
            $price = $studios->basic_price + $studios->aditional_sunday_price;
        } else {
            $price = $studios->basic_price;
        }
        $timefilter = DB::table('schedules')
            ->where('studio_id', 'like', $studio)
            ->where('start', '<=', $timing)
            ->where('end', '>=', $request->start)
            ->count();
        if ($timefilter > 0) {
            return response()->json("schedule overlapped", 400);
        } else {
            $schedule->update([
                'movie_id' => $movie,
                'studio_id' => $studio,
                'start' => $request->start,
                'end' => $timing,
                'price' => $price
            ]);
            return "update schedule success";
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Schedule  $schedule
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();
        if ($schedule) {
            return "delete schedule success";
        }
    }
}
