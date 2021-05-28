<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\User;
use App\Models\Aircraft;
use App\Models\Flight;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use DateTime;
use Carbon\Carbon;

class FlightController extends Controller
{
    //
    public function __construct() {
        $this->middleware('auth:api', ['except' => []]);
    }
    /**
     * Response all data
     *
     * @return \Illuminate\Http\Response
     */
    public function getAll()
    {
        $flights = Flight::all();
        foreach ($flights as $flight) {
            $flight->aircraft;
        }
        return response()->json([
            'message' => 'success',
            'flights' => $flights
        ], 200);
    }

    /**
     * Response one data by id
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getById(Request $request, $flightId)
    {
        $flight = Flight::find($flightId);
        // $temp = Carbon::parse($flight->departure_time)->format('Y-m-d H:m');
        // $flight->departure_time = $temp;
        $flight->aircraft;
        return response()->json([
            'message' => 'success',
            'flight' => $flight,
        ], 200);
    }

    /**
     * Create new data
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request) {
        $validator = Validator::make($request->all(), [
            'airline_code' => 'required',
            'flight_number' => 'required',
            'aircraft' => 'required',
            'origin_airport_name' => 'required',
            'origin_airport_code' => 'required',
            'destination_airport_name' => 'required',
            'destination_airport_code' => 'required',
            'departure_time' => 'required',
            'arrival_time' => 'required',
            'type' => 'required',
            'operation_days' => 'required',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $aircraft = Aircraft::where('registration', $request->aircraft)->get();
        $departure_time = new DateTime($request->departure_time);
        $flight_time_object = $departure_time->diff(new DateTime($request->arrival_time));
        if ($flight_time_object->h > 0) {
            if ($flight_time_object->i > 0) {
                $flight_time = $flight_time_object->h." hours ".$flight_time_object->i." minutes";
            } else {
                $flight_time = $flight_time_object->h." hours";
            }
        } else {
            $flight_time = $flight_time_object->i." minutes";
        }
        $operation_days = $request->operation_days;
        sort($operation_days);

        $flight = new Flight;
        $flight->airline_code = $request->airline_code;
        $flight->flight_number = $request->flight_number;
        $flight->aircraft_id = $aircraft[0]->id;
        $flight->origin_airport_name = $request->origin_airport_name;
        $flight->origin_airport_code = $request->origin_airport_code;
        $flight->destination_airport_name = $request->destination_airport_name;
        $flight->destination_airport_code = $request->destination_airport_code;
        $flight->departure_time = $request->departure_time;
        $flight->arrival_time = $request->arrival_time;
        $flight->flight_time = $flight_time;
        $flight->type = $request->type;
        $flight->operation_days = $operation_days;
        $flight->save();

        return response()->json([
            'message' => 'flight successfully registered',
            'flight' => $flight
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        // Update flight
        $validator = Validator::make($request->all(), [
            'airline_code' => 'required',
            'flight_number' => 'required',
            'aircraft' => 'required',
            'origin_airport_name' => 'required',
            'origin_airport_code' => 'required',
            'destination_airport_name' => 'required',
            'destination_airport_code' => 'required',
            'departure_time' => 'required',
            'arrival_time' => 'required',
            'type' => 'required',
            'operation_days' => 'required',
            'status' => 'required',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $aircraft = Aircraft::where('registration', $request->aircraft)->get();
        $departure_time = new DateTime($request->departure_time);
        $flight_time_object = $departure_time->diff(new DateTime($request->arrival_time));
        if ($flight_time_object->h > 0) {
            if ($flight_time_object->i > 0) {
                $flight_time = $flight_time_object->h." hours ".$flight_time_object->i." minutes";
            } else {
                $flight_time = $flight_time_object->h." hours";
            }
        } else {
            $flight_time = $flight_time_object->i." minutes";
        }
        $operation_days = $request->operation_days;
        sort($operation_days);

        $flight = Flight::find($request->id);
        $flight -> update([
            'airline_code' => $request->airline_code,
            'flight_number' => $request->flight_number,
            'aircraft_id' => $aircraft[0]->id,
            'origin_airport_name' => $request->origin_airport_name,
            'origin_airport_code' => $request->origin_airport_code,
            'destination_airport_name' => $request->destination_airport_name,
            'destination_airport_code' => $request->destination_airport_code,
            'departure_time' => $request->departure_time,
            'arrival_time' => $request->arrival_time,
            'flight_time' => $flight_time,
            'type' => $request->type,
            'operation_days' => $operation_days,
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'flight successfully updated',
            'flight' => $flight_time
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request, $flightId)
    {
        //delete flight
        $flight = Flight::find($flightId);
        $flight -> delete();
        $flights = Flight::all();
        foreach ($flights as $flight) {
            $flight->aircraft;
        }
        return response()->json([
            'message' => 'successfully deleted',
            'flights' => $flights
        ], 200);
    }
}
