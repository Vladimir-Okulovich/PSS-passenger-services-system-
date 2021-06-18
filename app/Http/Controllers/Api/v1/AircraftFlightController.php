<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\User;
use App\Models\Aircraft;
use App\Models\Flight;
use App\Models\AircraftFlight;
use App\Models\FlightPassenger;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use DateTime;
use Carbon\Carbon;
use Twilio\Rest\Client;
use App\Http\Helpers\Common;

class AircraftFlightController extends Controller
{
    //
    public function __construct() {
        $this->middleware('auth:api', ['except' => []]);

        $this->helper = new Common();
    }
    /**
     * Response all data
     *
     * @return \Illuminate\Http\Response
     */

    public function getAircraftFlightsByDate(Request $request) {
        $current_date = Carbon::now()->timezone('Australia/Sydney')->format('Y-m-d');
        // $current_day = Carbon::now()->timezone('Australia/Sydney')->dayOfWeek;
        // $current_day = Carbon::now()->format('l');
        // if ($current_day == 0) {
        //     $current_day = 7;
        // }
        $date = Carbon::create($request->date)->format('Y-m-d');
        $aircraft_flights = AircraftFlight::where('date', $date)->get();
        if (count($aircraft_flights) > 0) {
            foreach ($aircraft_flights as $aircraft_flight) {
                $aircraft_flight->aircraft;
                $aircraft_flight->flight;
            }
        }
        return response()->json([
            'message' => 'success',
            'aircraft_flights' => $aircraft_flights,
        ], 200);
    }

    public function saveAircraftFlight(Request $request) {
        $current_date = Carbon::now()->timezone('Australia/Sydney')->format('Y-m-d');
        $date = Carbon::create($request->date)->format('Y-m-d');
        $aircraft_flights = AircraftFlight::where('date', $date)
                                        ->where('flight_id', $request->flight)->get();
        if (count($aircraft_flights) > 0) {
            $aircraft_flight = $aircraft_flights[0];
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
            $aircraft_flight -> update([
                'aircraft_id' => $request->aircraft,
                'departure_time' => $request->departure_time,
                'arrival_time' => $request->arrival_time,
                'flight_time' => $flight_time,
                'status' => 'CONFIRMED',
                'phase' => 'OPEN',
            ]);
            $aircraft_flights = AircraftFlight::where('date', $date)->get();
            foreach ($aircraft_flights as $aircraft_flight) {
                $aircraft_flight->aircraft;
                $aircraft_flight->flight;
            }
            return response()->json([
                'message' => 'success',
                'aircraft_flights' => $aircraft_flights
            ], 200);
        }
    }
    public function getFlightPassengers(Request $request, $flightId)
    {
        $aircraft_flight = AircraftFlight::find($flightId);
        $aircraft_flight->flight;
        $aircraft_flight->aircraft;
        
        $flight_passengers = FlightPassenger::where('aircraft_flight_id', $flightId)->get();
        foreach ($flight_passengers as $flight_passenger) {
            $flight_passenger->passenger;
            $flight_passenger->aircraftFlight->flight;
            $flight_passenger->aircraftFlight->aircraft;
        }
        return response()->json([
            'message' => 'success',
            'aircraft_flight' => $aircraft_flight,
            'flight_passengers' => $flight_passengers,
        ], 200);
    }
    public function indivisualTicketing(Request $request)
    {
        $flight_passenger = FlightPassenger::find($request->id);
        if ($flight_passenger->book_reference == null || $flight_passenger->book_reference == '') {
            $book_reference = $this->generateRandomString(6);
            $flight_passenger -> update([
                'book_reference' => $book_reference,
            ]);
        }
        if ($flight_passenger->status == 'CLOSED') {
            $flight = $flight_passenger->aircraftFlight->flight;
            $receiver = str_replace("+", "", $flight_passenger->passenger->phone);
            $sender = $this->helper->twillo_number;
            $sid = $this->helper->twillo_sid;
            $token = $this->helper->twillo_token;
            $client = new Client($sid, $token);
            try {
                $message = $client->messages->create(
                    $receiver, // Text this number
                    [
                        'from' => $sender, // From a valid Twilio number
                        'body' => 'You are booked on '.$flight->airline_code.$flight->flight_number.' '.$flight_passenger->aircraftFlight->date->format('Y-m-d').' from '.$flight->origin_airport_code.' to '.$flight->destination_airport_code.'. Your booking reference is: '.$flight_passenger->book_reference.'. Web check-in is available via https://flightres.tech'
                    ]
                );
            } catch (Exception $e) {
                return response()->json([
                    'message' => 'twillo sms failed',
                ], 300);
            }
            $flight_passenger -> update([
                'status' => 'SENDED',
            ]);
        } else {
            return response()->json([
                'message' => 'already sended',
            ], 200);
        }

        return response()->json([
            'message' => 'success',
            'data' => $sid
        ], 200);
    }
    public function bulkTicketing(Request $request)
    {
        $flight_passengers = FlightPassenger::where('aircraft_flight_id', $request->flightId)
                                            ->where('status', 'CLOSED')->get();
        if (count($flight_passengers) > 0) {
            foreach ($flight_passengers as $flight_passenger) {
                if ($flight_passenger->book_reference == null || $flight_passenger->book_reference == '') {
                    $book_reference = $this->generateRandomString(6);
                    $flight_passenger -> update([
                        'book_reference' => $book_reference,
                    ]);
                }
                $flight = $flight_passenger->aircraftFlight->flight;
                $receiver = str_replace("+", "", $flight_passenger->passenger->phone);
                $sender = $this->helper->twillo_number;
                $sid = $this->helper->twillo_sid;
                $token = $this->helper->twillo_token;
                $client = new Client($sid, $token);
                try {
                    $message = $client->messages->create(
                        $receiver, // Text this number
                        [
                            'from' => $sender, // From a valid Twilio number
                            'body' => 'You are booked on '.$flight->airline_code.$flight->flight_number.' '.$flight_passenger->aircraftFlight->date->format('Y-m-d').' from '.$flight->origin_airport_code.' to '.$flight->destination_airport_code.'. Your booking reference is: '.$flight_passenger->book_reference.'. Web check-in is available via https://flightres.tech'
                        ]
                    );
                } catch (Exception $e) {
                    return response()->json([
                        'message' => 'twillo sms failed',
                    ], 300);
                }
                $flight_passenger -> update([
                    'status' => 'SENDED',
                ]);
            }
        } else {
            return response()->json([
                'message' => 'already sended',
            ], 200);
        }

        return response()->json([
            'message' => 'success',
        ], 200);
    }
    public function generateRandomString($length) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        do {
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            $flight_passengers = FlightPassenger::where('book_reference', $randomString)->get();
        } while (count($flight_passengers) > 0);
        return $randomString;
    }
}
           