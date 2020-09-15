<?php

namespace App\Http\Controllers;

use App\Studio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
//use App\Http\Resources\Studio as StudioColection;
use Illuminate\Support\Facades\Validator;

class StudioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return DB::table('studios')
            ->leftJoin('branches', 'studios.branch_id', '=', 'branches.id')
            ->select('studios.*', 'branches.name as branch_name')
            ->paginate(5);
        // return StudioColection::collection(Studio::all());
    }
    public function all()
    {
        return Studio::all();
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
            'name' => 'required',
            'branch_id' => 'required',
            'basic_price' => 'required',
            'aditional_friday_price' => 'required',
            'aditional_saturday_price' => 'required',
            'aditional_sunday_price' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        } else {
            Studio::create([
                'name' => $request->name,
                'branch_id' => $request->branch_id,
                'basic_price' => $request->basic_price,
                'aditional_friday_price' => $request->aditional_friday_price,
                'aditional_saturday_price' => $request->aditional_saturday_price,
                'aditional_sunday_price' => $request->aditional_sunday_price
            ]);
            return "create studio success";
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Studio  $studio
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Studio::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Studio  $studio
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'branch_id' => 'required',
            'basic_price' => 'required',
            'aditional_friday_price' => 'required',
            'aditional_saturday_price' => 'required',
            'aditional_sunday_price' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        } else {
            $studio = Studio::findOrFail($id);
            $studio->update([
                'name' => $request->name,
                'branch_id' => $request->branch_id,
                'basic_price' => $request->basic_price,
                'aditional_friday_price' => $request->aditional_friday_price,
                'aditional_saturday_price' => $request->aditional_saturday_price,
                'aditional_sunday_price' => $request->aditional_sunday_price
            ]);
            return "update studio success";
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Studio  $studio
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $studio = Studio::findOrFail($id);
        $studio->delete();
        if ($studio) {
            return "delete studio success";
        }
    }
}
