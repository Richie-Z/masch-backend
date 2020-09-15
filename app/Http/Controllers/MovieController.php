<?php

namespace App\Http\Controllers;

use App\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MovieController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Movie::paginate(5);
    }
    public function all()
    {
        return Movie::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:999',
            'minute_length' => 'required',
            'picture_url' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        } else {
            Movie::create([
                'name' => $request->name,
                'minute_length' => $request->minute_length,
                'picture_url' => $request->picture_url
            ]);
            return "create movie success";
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Movie  $movie
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Movie::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Movie  $movie
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:999',
            'minute_length' => 'required',
            'picture_url' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        } else {
            $movie = Movie::findOrFail($id);
            $movie->update([
                'name' => $request->name,
                'minute_length' => $request->minute_length,
                'picture_url' => $request->picture_url
            ]);
            return "update movie success";
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Movie  $movie
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $movie = Movie::findOrFail($id);
        $movie->delete();
        if ($movie) {
            return "delete movie success";
        }
    }
}
