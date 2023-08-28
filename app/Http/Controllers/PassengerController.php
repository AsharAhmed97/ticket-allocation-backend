<?php

namespace App\Http\Controllers;
use App\Models\Passenger;
use \Carbon\Carbon;
use Illuminate\Http\Request;
use Validator;
use DB;

class PassengerController extends Controller
{
    public function addPassenger(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'dateOfBirth'=>'required|date|date_format:d-m-Y',
            'idNumber'=>'required',
        ]);
        if ($validator->fails()) {
            return response(['status'=>'failed','code'=>422,'messages' =>$validator->errors()->all()],422);
        }
        try 
        {
            $passenger= new Passenger;
            $passenger->name=$request->name;  
            $passenger->dateOfBirth=Carbon::createFromFormat('d-m-Y',$request->dateOfBirth)->format('Y-m-d');  
            $passenger->idNumber=$request->idNumber;  
            $passenger->save();
            return response(['status' =>'success','code'=> 200,'messages'=>['Passenger added successfully.']],200);
        } catch (\Throwable $th) {
            $th->getMessage();
            return response(['status' =>'failed','code'=> 500 ,'messages'=>['something went wrong.']],500);
        }
    }

    public function viewPassenger(Request $request)
    {    
        $validator = Validator::make($request->all(), [
            'passengerId' => 'required|exists:passengers,id'
        ]);

        if ($validator->fails()) {
            return response(['status'=>'failed','code'=>422,'messages' =>$validator->errors()->all()],422);
        }
            
        try 
        {
        $passenger = Passenger::find($request->passengerId);
        $data= $this->getPassengerShortDetail($passenger);
            return response(['status' =>'success','code'=> 200,'messages'=>['Passenger details has been retereived successfully.'],'data'=>$data],200);
        } catch (\Throwable $th) {
            $th->getMessage();
            return response(['status' =>'failed','code'=> 500 ,'messages'=>['something went wrong.']],500);
        }
    }

    public function updatePassenger(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'passengerId' => 'required|exists:passengers,id'
        ]);

        if ($validator->fails()) {
            return response(['status'=>'failed','code'=>422,'messages' =>$validator->errors()->all()],422);
        }
        try 
        {
            $passenger= Passenger::find($request->passengerId);
            if($request->has('name') && !empty($request->name))
            {
                $passenger->name=$request->name;
            }
            if($request->has('dateOfBirth') && !empty($request->dateOfBirth))
            {
                $passenger->dateOfBirth=Carbon::createFromFormat('d-m-Y',$request->dateOfBirth)->format('Y-m-d');
            }
            if($request->has('idNumber') && !empty($request->idNumber))
            {
                $passenger->idNumber=$request->idNumber;
            }
            $passenger->save();
            return response(['status' =>'success','code'=> 200,'messages'=>['Passenger updated successfully.']],200);
        } catch (\Throwable $th) {
            $th->getMessage();
            return response(['status' =>'failed','code'=> 500 ,'messages'=>['something went wrong.']],500);
        }
    }

    public function deletePassenger(Request $request)
    {    
        $validator = Validator::make($request->all(), [
            'passengerId' => 'required|exists:passengers,id'
        ]);

        if ($validator->fails()) {
            return response(['status'=>'failed','code'=>422,'messages' =>$validator->errors()->all()],422);
            }
            
        try {
            $passenger = Passenger::find($request->passengerId);
            $passenger->status = 'deleted';
            $passenger->save();
            return response(['status' =>'success','code'=> 200,'messages'=>['passenger has been deleted successfully.']],200);
        } catch (\Throwable $th) {
            $th->getMessage();
            return response(['status' =>'failed','code'=> 500 ,'messages'=>['something went wrong.']],500);
        }
    }
    protected function getPassengerShortDetail($passenger)
    {
        return[
            'name'=>$passenger->name,
            'dateOfBirth'=>\Carbon\Carbon::parse($passenger->dateOfBirth)->format('d-m-Y'),
            'idNumber'=>$passenger->idNumber
        ];
    }

    //view All passengers
    public function viewAllpassengers(Request $request)
    {
       try 
       {
            $passengers = Passenger::select('passengers.*')->where('passengers.status','=','active');
            if(($request->has('searchKey')) && !empty($request->searchKey))
            {
                $searchTerm = $request->searchKey;
                $reservedSymbols = ['-', '+', '<', '>', '@', '(', ')', '~'];
                $searchTerm = str_replace($reservedSymbols, ' ', $searchTerm);
                $searchValues = preg_split('/\s+/', $searchTerm, -1, PREG_SPLIT_NO_EMPTY);
                $passengers = $passengers->where(function ($q) use ($searchValues) {
                    foreach ($searchValues as $value) {
                    $q->orWhere('name', 'like', "%{$value}%")
                    ->select('*', DB::raw("(s/me - $value) AS column_to_be_order"))
                    ->orderBy('column_to_be_order', 'desc');
                    }
                });
            }    
            if($request->has('perPage'))
            {
                $passengers =  $passengers->orderBy('passengers.created_at', 'desc')->paginate( $request->perPage );
            }
            else{
                $perPage    =   $passengers->count();
                $passengers =  $passengers->orderBy('passengers.created_at', 'desc')->paginate( $perPage );
            }

            if( $passengers->isNotEmpty())
            {
                foreach ($passengers->items() as $passenger) {
                    $passengersData[] = $this->getPassengerShortDetail($passenger);
                }

                $data = [
                    'current_page' => $passengers->currentPage(),
                    'first_page_url' => $passengers->url(1),
                    'last_page' => $passengers->lastPage(),
                    'next_page_url' => $passengers->nextPageUrl(),
                    'prev_page_url' => $passengers->previousPageUrl(),
                    'last_page_url' => $passengers->url($passengers->lastPage()),
                    'from' => $passengers->firstItem(),
                    'to' => $passengers->lastItem(),
                    'per_page' => $passengers->perPage(),
                    'total' =>$passengers->total(),
                    'passengers' => $passengersData,
                ];
                return response(['status' =>'success','code'=> 200,'messages'=>['passengers has been retrieved successfully.'],'data'=>$data],200);
            }
            else{
                return response(['status' =>'success','code'=> 404,'messages'=>['No passenger found!']],404);
            }
        } catch (\Throwable $th) {
            return response(['status' =>'failed','code'=> 500 ,'messages'=>['something went wrong.']],500);
        }
    }

    //view notAllocated Passengers
    public function viewNotAllocatedpassengers(Request $request)
    {
       try 
       {
            $passengers = Passenger::select('passengers.*')->where('passengers.status','=','active')->where('passengers.isAllocated','=','no');
            if(($request->has('searchKey')) && !empty($request->searchKey))
            {
                $searchTerm = $request->searchKey;
                $reservedSymbols = ['-', '+', '<', '>', '@', '(', ')', '~'];
                $searchTerm = str_replace($reservedSymbols, ' ', $searchTerm);
                $searchValues = preg_split('/\s+/', $searchTerm, -1, PREG_SPLIT_NO_EMPTY);
                $passengers = $passengers->where(function ($q) use ($searchValues) {
                    foreach ($searchValues as $value) {
                    $q->orWhere('name', 'like', "%{$value}%")
                    ->select('*', DB::raw("(s/me - $value) AS column_to_be_order"))
                    ->orderBy('column_to_be_order', 'desc');
                    }
                });
            }    
            if($request->has('perPage'))
            {
                $passengers =  $passengers->orderBy('passengers.created_at', 'desc')->paginate( $request->perPage );
            }
            else{
                $perPage    =   $passengers->count();
                $passengers =  $passengers->orderBy('passengers.created_at', 'desc')->paginate( $perPage );
            }

            if( $passengers->isNotEmpty())
            {
                foreach ($passengers->items() as $passenger) {
                    $passengersData[] = $this->getPassengerShortDetail($passenger);
                }

                $data = [
                    'current_page' => $passengers->currentPage(),
                    'first_page_url' => $passengers->url(1),
                    'last_page' => $passengers->lastPage(),
                    'next_page_url' => $passengers->nextPageUrl(),
                    'prev_page_url' => $passengers->previousPageUrl(),
                    'last_page_url' => $passengers->url($passengers->lastPage()),
                    'from' => $passengers->firstItem(),
                    'to' => $passengers->lastItem(),
                    'per_page' => $passengers->perPage(),
                    'total' =>$passengers->total(),
                    'passengers' => $passengersData,
                ];
                return response(['status' =>'success','code'=> 200,'messages'=>['passengers has been retrieved successfully.'],'data'=>$data],200);
            }
            else{
                return response(['status' =>'success','code'=> 404,'messages'=>['No passenger found!']],404);
            }
        } catch (\Throwable $th) {
            return response(['status' =>'failed','code'=> 500 ,'messages'=>['something went wrong.']],500);
        }
    }
}
