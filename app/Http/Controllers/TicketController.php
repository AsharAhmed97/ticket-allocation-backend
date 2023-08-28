<?php

namespace App\Http\Controllers;
use App\Models\Passenger;
use App\Models\Ticket;
use App\Models\TicketAllocationToPassenger;
use \Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;
use DB;

class TicketController extends Controller
{
    public function addTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pnrNumber' => 'required',
            'flight' => 'required',
            'flightDateTime'=>'required|date_format:Y-m-d H:i:s',
            'numberOfSeats'=>'required|min:1',
            'passengers' => [
                'array',
                'min:1',
                Rule::exists('passengers', 'id'),
                function ($attribute, $value, $fail) {
                    $existingIds = DB::table('passengers')
                        ->where('isAllocated', 'no')
                        ->pluck('id')
                        ->toArray();
    
                    foreach ($value as $passengerId) {
                        if (!in_array($passengerId, $existingIds)) {
                            $passenger = DB::table('passengers')->find($passengerId);
                            if ($passenger && $passenger->isAllocated === 'yes') {
                                $fail("Passenger with ID $passengerId is already allocated.");
                            } else {
                                $fail("$attribute contains an invalid passenger ID.");
                            }
                        }
                    }
                },
            ],
        ]);
        if ($validator->fails()) {
            return response(['status'=>'failed','code'=>422,'messages' =>$validator->errors()->all()],422);
        }
        try 
        {
            if($request->has('passengers'))
            {
                if(count($request->passengers) <= $request->numberOfSeats)
                {
                    $ticket= new Ticket;
                    $ticket->pnrNumber=$request->pnrNumber;   
                    $ticket->flight=$request->flight;
                    $ticket->flightDateTime=$request->flightDateTime;
                    $ticket->numberOfSeats=$request->numberOfSeats;
                    $ticket->save();
                    if($request->has('passengers'))
                    {
                        $passengers=$request->passengers;
                        foreach ($passengers as $key => $passenger) {
                            TicketAllocationToPassenger::create([
                                'ticketId'=>$ticket->id,
                                'passengerId'=>$passenger,
                            ]);
                            $user=Passenger::find($passenger);
                            $user->isAllocated='yes';
                            $user->save();
                        }
                    }
                    return response(['status' =>'success','code'=> 200,'messages'=>['ticket added successfully.']],200);
                }
                else
                {
                    return response(['status' =>'failed','code'=> 422,'messages'=>['number of passengers is exceeding the total seats limit in this ticket']],422);
                }
            }
            else
            {
                $ticket= new Ticket;
                $ticket->pnrNumber=$request->pnrNumber;   
                $ticket->flight=$request->flight;
                $ticket->flightDateTime=$request->flightDateTime;
                $ticket->numberOfSeats=$request->numberOfSeats;
                $ticket->save();
                return response(['status' =>'success','code'=> 200,'messages'=>['ticket added successfully.']],200);
            }
        } catch (\Throwable $th) {
            $th->getMessage();
            return response(['status' =>'failed','code'=> 500 ,'messages'=>['something went wrong.']],500);
        }
    }

    public function viewTicket(Request $request)
    {    
        $validator = Validator::make($request->all(), [
            'ticketId' => 'required|exists:tickets,id'
        ]);

        if ($validator->fails()) {
            return response(['status'=>'failed','code'=>422,'messages' =>$validator->errors()->all()],422);
        }
            
        try 
        {
        $ticket = Ticket::find($request->ticketId);
        $data= $this->getTicketShortDetail($ticket);
            return response(['status' =>'success','code'=> 200,'messages'=>['Ticket details has been retereived successfully.'],'data'=>$data],200);
        } catch (Throwable $th) {
            $th->getMessage();
            return response(['status' =>'failed','code'=> 500 ,'messages'=>['something went wrong.']],500);
        }
    }

    public function updateTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticketId'=>'required|exists:tickets,id',
            'flightDateTime'=>'date_format:Y-m-d H:i:s',
            'numberOfSeats'=>'min:1',
            'passengers' => [
                'array',
                'min:1',
                Rule::exists('passengers', 'id'),
                function ($attribute, $value, $fail) {
                    $existingIds = DB::table('passengers')
                        ->where('isAllocated', 'no')
                        ->pluck('id')
                        ->toArray();
    
                    foreach ($value as $passengerId) {
                        if (!in_array($passengerId, $existingIds)) {
                            $passenger = DB::table('passengers')->find($passengerId);
                            if ($passenger && $passenger->isAllocated === 'yes') {
                                $fail("Passenger with ID $passengerId is already allocated.");
                            } else {
                                $fail("$attribute contains an invalid passenger ID.");
                            }
                        }
                    }
                },
            ],
        ]);
        if ($validator->fails()) {
            return response(['status'=>'failed','code'=>422,'messages' =>$validator->errors()->all()],422);
        }
        try 
        {
            $ticket= Ticket::find($request->ticketId);
            if($request->has('passengers') && $request->has('numberOfSeats'))
            {
                if(count($request->passengers) <= $request->numberOfSeats)
                {
                    if($request->has('pnrNumber') && !empty($request->pnrNumber))
                    {
                        $ticket->pnrNumber=$request->pnrNumber;   
                    }
                    if($request->has('flight') && !empty($request->flight))
                    {
                        $ticket->flight=$request->flight;   
                    }
                    if($request->has('flightDateTime') && !empty($request->flightDateTime))
                    {
                        $ticket->flightDateTime=$request->flightDateTime;   
                    }
                    if($request->has('numberOfSeats') && !empty($request->numberOfSeats))
                    {
                        $ticket->numberOfSeats=$request->numberOfSeats;   
                    }
                    $ticket->save();
                    if($request->has('passengers'))
                    {
                        $ticketPassengers= $ticket->passengers;
                        if(!empty($ticketPassengers))
                        {
                            foreach ($ticketPassengers as $key => $passenger) {
                                $user=Passenger::find($passenger->passengerId);
                                if(!empty($user))
                                {
                                    $user->isAllocated='no';
                                    $user->save();
                                }
                                $passenger->delete();
                            }
                        }
                        $newPassengers=$request->passengers;
                        foreach ($newPassengers as $key => $newPassenger) {
                            TicketAllocationToPassenger::create([
                                'ticketId'=>$ticket->id,
                                'passengerId'=>$newPassenger,
                               ]);
                            $user=Passenger::find($newPassenger);
                            $user->isAllocated='yes';
                            $user->save();
                        }
                    }
                    return response(['status' =>'success','code'=> 200,'messages'=>['ticket updated successfully.']],200);
                } 
                else
                {
                    return response(['status' =>'failed','code'=> 422,'messages'=>['number of passengers is exceeding the total seats limit in this ticket']],422);
                }
            }
            else if( $request->has('passengers'))
            {
                if(count($request->passengers) > $ticket->numberOfSeats)
                {
                    return response(['status' =>'failed','code'=> 422,'messages'=>['number of passengers is exceeding the total seats limit in this ticket']],422);
                }
                else
                {
                    if($request->has('pnrNumber') && !empty($request->pnrNumber))
                    {
                        $ticket->pnrNumber=$request->pnrNumber;   
                    }
                    if($request->has('flight') && !empty($request->flight))
                    {
                        $ticket->flight=$request->flight;   
                    }
                    if($request->has('flightDateTime') && !empty($request->flightDateTime))
                    {
                        $ticket->flightDateTime=$request->flightDateTime;   
                    }
                    if($request->has('numberOfSeats') && !empty($request->numberOfSeats))
                    {
                        $ticket->numberOfSeats=$request->numberOfSeats;   
                    }
                    $ticket->save();
                    if($request->has('passengers'))
                    {
                        $ticketPassengers= $ticket->passengers;
                        if(!empty($ticketPassengers))
                        {
                            foreach ($ticketPassengers as $key => $passenger) {
                                $user=Passenger::find($passenger->passengerId);
                                if(!empty($user))
                                {
                                    $user->isAllocated='no';
                                    $user->save();
                                }
                                $passenger->delete();
                            }
                        }
                        $newPassengers=$request->passengers;
                        foreach ($newPassengers as $key => $newPassenger) {
                            TicketAllocationToPassenger::create([
                                'ticketId'=>$ticket->id,
                                'passengerId'=>$newPassenger,
                            ]);
                            $user=Passenger::find($newPassenger);
                            $user->isAllocated='yes';
                            $user->save();
                        }
                    }
                    return response(['status' =>'success','code'=> 200,'messages'=>['ticket updated successfully.']],200);
                }
            }
            else
            {
                if($request->has('pnrNumber') && !empty($request->pnrNumber))
                {
                    $ticket->pnrNumber=$request->pnrNumber;   
                }
                if($request->has('flight') && !empty($request->flight))
                {
                    $ticket->flight=$request->flight;   
                }
                if($request->has('flightDateTime') && !empty($request->flightDateTime))
                {
                    $ticket->flightDateTime=$request->flightDateTime;   
                }
                if($request->has('numberOfSeats') && !empty($request->numberOfSeats))
                {
                    $ticket->numberOfSeats=$request->numberOfSeats;   
                }
                $ticket->save();
                return response(['status' =>'success','code'=> 200,'messages'=>['ticket updated successfully.']],200);
            }
        } catch (\Throwable $th) {
            $th->getMessage();
            return response(['status' =>'failed','code'=> 500 ,'messages'=>['something went wrong.']],500);
        }
    }

    public function deleteTicket(Request $request)
    {    
        $validator = Validator::make($request->all(), [
            'ticketId' => 'required|exists:tickets,id'
        ]);

        if ($validator->fails()) {
            return response(['status'=>'failed','code'=>422,'messages' =>$validator->errors()->all()],422);
        }
            
        try {
            $ticket = Ticket::find($request->ticketId);
            $ticketPassengers=$ticket->passengers;
            if(!empty($ticketPassengers))
            {
                foreach ($ticketPassengers as $key => $ticketPassenger) {
                    $user=Passenger::find($ticketPassenger->passengerId);
                    $user->isAllocated='no';
                    $user->save();
                    $ticketPassenger->delete();
                }
            }
            $ticket->delete();
            return response(['status' =>'success','code'=> 200,'messages'=>['ticket has been deleted successfully.']],200);
        } catch (\Throwable $th) {
            $th->getMessage();
            return response(['status' =>'failed','code'=> 500 ,'messages'=>['something went wrong.']],500);
        }
    }

    //view All passengers
    public function viewAllTickets(Request $request)
    {
       try 
       {
            $tickets = Ticket::select('tickets.*');
            if(($request->has('searchKey')) && !empty($request->searchKey))
            {
                $searchTerm = $request->searchKey;
                $reservedSymbols = ['-', '+', '<', '>', '@', '(', ')', '~'];
                $searchTerm = str_replace($reservedSymbols, ' ', $searchTerm);
                $searchValues = preg_split('/\s+/', $searchTerm, -1, PREG_SPLIT_NO_EMPTY);
                $tickets = $tickets->where(function ($q) use ($searchValues) {
                    foreach ($searchValues as $value) {
                    $q->orWhere('pnrNumber', 'like', "%{$value}%")
                    ->orWhere('flight', 'like', "%{$value}%")
                    ->select('*', DB::raw("(s/me - $value) AS column_to_be_order"))
                    ->orderBy('column_to_be_order', 'desc');
                    }
                });
            }    
            if($request->has('perPage'))
            {
                $tickets =  $tickets->orderBy('tickets.created_at', 'desc')->paginate( $request->perPage );
            }
            else{
                $perPage    =   $tickets->count();
                $tickets =  $tickets->orderBy('tickets.created_at', 'desc')->paginate( $perPage );
            }

            if( $tickets->isNotEmpty())
            {
                foreach ($tickets->items() as $ticket) {
                    $ticketsData[] = $this->getTicketShortDetail($ticket);
                }

                $data = [
                    'current_page' => $tickets->currentPage(),
                    'first_page_url' => $tickets->url(1),
                    'last_page' => $tickets->lastPage(),
                    'next_page_url' => $tickets->nextPageUrl(),
                    'prev_page_url' => $tickets->previousPageUrl(),
                    'last_page_url' => $tickets->url($tickets->lastPage()),
                    'from' => $tickets->firstItem(),
                    'to' => $tickets->lastItem(),
                    'per_page' => $tickets->perPage(),
                    'total' =>$tickets->total(),
                    'tickets' => $ticketsData,
                ];
                return response(['status' =>'success','code'=> 200,'messages'=>['tickets has been retrieved successfully.'],'data'=>$data],200);
            }
            else{
                return response(['status' =>'success','code'=> 404,'messages'=>['No ticket found!']],404);
            }
        } catch (\Throwable $th) {
            $th->getMessage();
            return response(['status' =>'failed','code'=> 500 ,'messages'=>['something went wrong.']],500);
        }
    }

    protected function getTicketShortDetail($ticket)
    {
        $ticketPassengers= $ticket->passengers;
            $passengerData=[];
            if(!empty($ticketPassengers))
            {
                foreach ($ticketPassengers as $key => $passenger) 
                {
                    $user= Passenger::find($passenger->passengerId);
                    $passengerData[]=[
                        'id'=>$user->id,
                        'name'=>$user->name,
                        'idNumber'=>$user->idNumber,
                    ] ;
                }
            }
            else
            {
                $passengerData=[];
            }
            $carbonDateTime = Carbon::parse($ticket->flightDateTime);
        return[
            'id'=>$ticket->id,
            'pnrNumber'=>$ticket->pnrNumber,
            'flight'=>$ticket->flight,
            'flightDate'=>$carbonDateTime->format('d-m-y'), 
            'flightTime'=>$carbonDateTime->format('h:i A'), 
            'numberOfSeats'=>$ticket->numberOfSeats,
            'passengers'=>$passengerData,
        ];
    }

    //ticket allocation to passenger
    public function ticketAllocationToPassenger(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticketId' => 'required|exists:tickets,id',
            'passengers' => [
                'required',
                'array',
                'min:1',
                Rule::exists('passengers', 'id'),
                function ($attribute, $value, $fail) {
                    $existingIds = DB::table('passengers')
                        ->where('isAllocated', 'no')
                        ->pluck('id')
                        ->toArray();
    
                    foreach ($value as $passengerId) {
                        if (!in_array($passengerId, $existingIds)) {
                            $passenger = DB::table('passengers')->find($passengerId);
                            if ($passenger && $passenger->isAllocated === 'yes') {
                                $fail("Passenger with ID $passengerId is already allocated.");
                            } else {
                                $fail("$attribute contains an invalid passenger ID.");
                            }
                        }
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response(['status'=>'failed','code'=>422,'messages' =>$validator->errors()->all()],422);
        }
        try 
        {
            $ticket=Ticket::find($request->ticketId);
            if(count($request->passengers) <= $ticket->numberOfSeats)
            {
                    $passengers=$request->passengers;
                    foreach ($passengers as $key => $passenger) {
                        TicketAllocationToPassenger::create([
                            'ticketId'=>$ticket->id,
                            'passengerId'=>$passenger,
                        ]);
                        $user=Passenger::find($passenger);
                        $user->isAllocated='yes';
                        $user->save();
                    }
                return response(['status' =>'success','code'=> 200,'messages'=>['ticket allocated successfully.']],200);
            }
            else
            {
                return response(['status' =>'failed','code'=> 422,'messages'=>['number of passengers is exceeding the total seats limit in this ticket']],422);
            }
        } catch (\Throwable $th) {
            $th->getMessage();
            return response(['status' =>'failed','code'=> 500 ,'messages'=>['something went wrong.']],500);
        }
    }
}
