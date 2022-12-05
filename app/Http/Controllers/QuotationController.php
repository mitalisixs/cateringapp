<?php

namespace App\Http\Controllers;

use App\Repositories\Quotations\QuotationRepoGenerator;
use App\Exports\QuotationsExport;
use App\Notifications\QuotationNotification;
use App\Quotation;
use App\Restorant;
use App\Status;
use App\User;
use Carbon\Carbon;
use Cart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use willvincent\Rateable\Rating;
use App\Services\ConfChanger;
use Akaunting\Module\Facade as Module;
use App\Events\QuotationAcceptedByAdmin;
use App\Events\QuotationAcceptedByVendor;
use App\Models\SimpleDelivery;

class QuotationController extends Controller
{

   
    public function migrateStatuses()
    {
        if (Status::count() < 13) {
            $statuses = ['Just created', 'Accepted by admin', 'Accepted by restaurant', 'Assigned to driver', 'Prepared', 'Picked up', 'Delivered', 'Rejected by admin', 'Rejected by restaurant', 'Updated', 'Closed', 'Rejected by driver', 'Accepted by driver'];
            foreach ($statuses as $key => $status) {
                Status::updateOrCreate(['name' => $status], ['alias' =>  str_replace(' ', '_', strtolower($status))]);
            }
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->migrateStatuses();

        $restorants = Restorant::where(['active'=>1])->get();
        $drivers = User::role('driver')->where(['active'=>1])->get();
        $clients = User::role('client')->where(['active'=>1])->get();

        $driversData = [];
        foreach ($drivers as $key => $driver) {
            $driversData[$driver->id] = $driver->name;
        }

        $quotations = Quotation::orderBy('created_at', 'desc');

        //Get client's quotations
        if (auth()->user()->hasRole('client')) {
            $quotations = $quotations->where(['client_id'=>auth()->user()->id]);
        ////Get driver's quotations
        } elseif (auth()->user()->hasRole('driver')) {
            $quotations = $quotations->where(['driver_id'=>auth()->user()->id]);
        //Get owner's restorant quotations
        } elseif (auth()->user()->hasRole('owner')) {
             
            //Change currency
            ConfChanger::switchCurrency(auth()->user()->restorant);

            $quotations = $quotations->where(['restorant_id'=>auth()->user()->restorant->id]);
        }elseif (auth()->user()->hasRole('staff')) {
             
            //Change currency
            ConfChanger::switchCurrency(auth()->user()->restaurant);

            $quotations = $quotations->where(['restorant_id'=>auth()->user()->restaurant_id]);
        }

        //FILTER BT RESTORANT
        if (isset($_GET['restorant_id'])) {
            $quotations = $quotations->where(['restorant_id'=>$_GET['restorant_id']]);
        }
        //If restorant owner, get his restorant quotations only
        if (auth()->user()->hasRole('owner')) {
            //Current restorant id
            $restorant_id = auth()->user()->restorant->id;
            $quotations = $quotations->where(['restorant_id'=>$restorant_id]);
        }

        //BY CLIENT
        if (isset($_GET['client_id'])) {
            $quotations = $quotations->where(['client_id'=>$_GET['client_id']]);
        }

        //BY DRIVER
        if (isset($_GET['driver_id'])) {
            $quotations = $quotations->where(['driver_id'=>$_GET['driver_id']]);
        }

        //BY DATE FROM
        if (isset($_GET['fromDate']) && strlen($_GET['fromDate']) > 3) {
            //$start = Carbon::parse($_GET['fromDate']);
            $quotations = $quotations->whereDate('created_at', '>=', $_GET['fromDate']);
        }

        //BY DATE TO
        if (isset($_GET['toDate']) && strlen($_GET['toDate']) > 3) {
            //$end = Carbon::parse($_GET['toDate']);
            $quotations = $quotations->whereDate('created_at', '<=', $_GET['toDate']);
        }

        //With downloaod
        if (isset($_GET['report'])) {
            $items = [];
            foreach ($quotations->get() as $key => $quotation) {
                $item = [
                    'quotation_id'=>$quotation->id,
                    'restaurant_name'=>$quotation->restorant->name,
                    'restaurant_id'=>$quotation->restorant_id,
                    'created'=>$quotation->created_at,
                    'last_status'=>$quotation->status->pluck('alias')->last(),
                    'client_name'=>$quotation->client ? $quotation->client->name : '',
                    'client_id'=>$quotation->client ? $quotation->client_id : null,
                    'table_name'=>$quotation->table ? $quotation->table->name : '',
                    'table_id'=>$quotation->table ? $quotation->table_id : null,
                    'area_name'=>$quotation->table && $quotation->table->restoarea ? $quotation->table->restoarea->name : '',
                    'area_id'=>$quotation->table && $quotation->table->restoarea ? $quotation->table->restoarea->id : null,
                    'address'=>$quotation->address ? $quotation->address->address : '',
                    'address_id'=>$quotation->address_id,
                    'driver_name'=>$quotation->driver ? $quotation->driver->name : '',
                    'driver_id'=>$quotation->driver_id,
                    'quotation_value'=>$quotation->quotation_price,
                    'quotation_delivery'=>$quotation->delivery_price,
                    'quotation_total'=>$quotation->delivery_price + $quotation->quotation_price,
                    'payment_method'=>$quotation->payment_method,
                    'srtipe_payment_id'=>$quotation->srtipe_payment_id,
                    'quotation_fee'=>$quotation->fee_value,
                    'restaurant_fee'=>$quotation->fee,
                    'restaurant_static_fee'=>$quotation->static_fee,
                    'vat'=>$quotation->vatvalue,
                  ];
                array_push($items, $item);
            }

            return Excel::download(new QuotationsExport($items), 'quotations_'.time().'.xlsx');
        }

        $quotations = $quotations->paginate(10);

        return view('quotations.index', [
            'quotations' => $quotations,
            'restorants'=>$restorants,
            'drivers'=>$drivers,
            'fields'=>[['class'=>'col-12', 'classselect'=>'noselecttwo', 'ftype'=>'select', 'name'=>'Driver', 'id'=>'driver', 'placeholder'=>'Assign Driver', 'data'=>$driversData, 'required'=>true]],
            'clients'=>$clients,
            'parameters'=>count($_GET) != 0,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    

    private function toMobileLike(Request $request){
        /*{
            "restaurant_id":1,
            "delivery_method":"delivery", //delivery, pickup, dinein
            "payment_method":"cod" ,
            "address_id":1,
            "platform":"WebService",
            "items":[{
                "id":1,
                "qty":2,
                "extrasSelected":[{"id":1},{"id":2}],
                "variant":1
              }],
            "quotation_price":72,
            "comment":"",
            "timeslot":"1320_1350",
            "stripe_token":null
        }*/


        //Find vendor id
        $vendor_id = null;
        foreach (Cart::getContent() as $key => $item) {
            $vendor_id = $item->attributes->restorant_id;
        }
        $restorant = Restorant::findOrFail($vendor_id);

        //Organize the item
        $items=[];
        foreach (Cart::getContent() as $key => $item) {
            $extras=[];
            foreach ($item->attributes->extras as $keyExtra => $extra_id) {
                array_push($extras,array('id'=>$extra_id));
            }
            array_push($items,array(
                "id"=>$item->attributes->id,
                "qty"=>$item->quantity,
                "variant"=>$item->attributes->variant,
                "extrasSelected"=>$extras
            ));
        }


        //stripe token
        $stripe_token=null;
        if($request->has('stripePaymentId')){
            $stripe_token=$request->stripePaymentId;
        }

        //Custom fields
        $customFields=[];
        if($request->has('custom')){
            $customFields=$request->custom;
        }

        
       

        //DELIVERY METHOD
        //Default - pickup - since available everywhere
        $delivery_method="pickup";
        
        //Delivery method - deliveryType - ft
        if($request->has('deliveryType')){
            $delivery_method=$request->deliveryType;
        }else if($restorant->can_pickup == 0 && $restorant->can_deliver == 1){
            $delivery_method="delivery";
        }

        //Delivery method  - dineType - qr
        if($request->has('dineType')){
            $delivery_method=$request->dineType;
        }



        //In case it is QR, and there is no dineInType, and pickup is diabled, it is dine in
        if(config('app.isqrsaas')&&!$request->has('dineType')&&!config('settings.is_whatsapp_quotationing_mode')){
            $delivery_method='dinein';
        }
        //takeaway is pickup
        if($delivery_method=="takeaway"){
            $delivery_method="pickup";
        }

        //Table id
        $table_id=null;
        if($request->has('table_id')){
            $table_id=$request->table_id;
        }

         //Phone 
         $phone=null;
         if($request->has('phone')){
             $phone=$request->phone;
         }

        //Delivery area
        $deliveryAreaId=$request->has('delivery_area')?$request->delivery_area:null;
        if($deliveryAreaId){
            //Set this in custom field
            $deliveryAreaName="";
            $deliveryAreaElement=SimpleDelivery::find($request->delivery_area);
            if($deliveryAreaElement){
                $deliveryAreaName=$deliveryAreaElement->name;
            }
            $customFields['delivery_area_name']=$deliveryAreaName;
        }

        $requestData=[
            'vendor_id'   => $vendor_id,
            'delivery_method'=> $delivery_method,
            'payment_method'=> $request->paymentType?$request->paymentType:"cod",
            'address_id'=>$request->addressID,
            "timeslot"=>$request->timeslot,
            "items"=>$items,
            "comment"=>$request->comment,
            "stripe_token"=>$stripe_token,
            "dinein_table_id"=>$table_id,
            "phone"=>$phone,
            "customFields"=>$customFields,
            "deliveryAreaId"=> $deliveryAreaId
        ];

        

        return new Request($requestData);
    }

    public function store(Request $request){

        //Convert web request to mobile like request
        $mobileLikeRequest=$this->toMobileLike($request);

        //Data
        $vendor_id =  $mobileLikeRequest->vendor_id;
        $expedition= $mobileLikeRequest->delivery_method;
        $hasPayment= $mobileLikeRequest->payment_method!="cod";
        $isStripe= $mobileLikeRequest->payment_method=="stripe";

        $vendorHasOwnPayment=null;
        if(config('settings.social_mode')){
            //Find the vendor, and check if he has payment
        
            $vendor=Restorant::findOrFail($mobileLikeRequest->vendor_id);

            //Payment methods
            foreach (Module::all() as $key => $module) {
                if($module->get('isPaymentModule')){
                    if($vendor->getConfig($module->get('alias')."_enable","false")=="true"){
                        $vendorHasOwnPayment=$module->get('alias');
                    }
                }
            }

            if($vendorHasOwnPayment==null){
                $hasPayment=false;
            }
        }

        //Repo Holder
        $quotationRepo=QuotationRepoGenerator::makeQuotationRepo($vendor_id,$mobileLikeRequest,$expedition,$hasPayment,$isStripe,false,$vendorHasOwnPayment);

        //Proceed with validating the data
        $validator=$quotationRepo->validateData();
        if ($validator->fails()) { 
            notify()->error($validator->errors()->first());
            return $quotationRepo->redirectOrInform(); 
        }

        //Proceed with making the quotation
        $validatorOnMaking=$quotationRepo->makeQuotation();
        if ($validatorOnMaking->fails()) { 
            notify()->error($validatorOnMaking->errors()->first()); 
            return $quotationRepo->redirectOrInform(); 
        }

        return $quotationRepo->redirectOrInform();
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Quotation $quotation)
    {
        //Do we have pdf invoice
        $pdFInvoice=Module::has('pdf-invoice');

        //Change currency
        ConfChanger::switchCurrency($quotation->restorant);

        //Change language
        ConfChanger::switchLanguage($quotation->restorant);

        $drivers = User::role('driver')->get();
        $driversData = [];
        foreach ($drivers as $key => $driver) {
            $driversData[$driver->id] = $driver->name;
        }

        if (auth()->user()->hasRole('client') && auth()->user()->id == $quotation->client_id ||
            auth()->user()->hasRole('owner') && auth()->user()->id == $quotation->restorant->user->id ||
            auth()->user()->hasRole('staff') && auth()->user()->restaurant_id == $quotation->restorant->id ||
                auth()->user()->hasRole('driver') && auth()->user()->id == $quotation->driver_id || auth()->user()->hasRole('admin')
            ) {
            return view('quotations.show', [
                'quotation'=>$quotation,
                'pdFInvoice'=>$pdFInvoice,
                'custom_data'=>$quotation->getAllConfigs(),
                'statuses'=>Status::pluck('name', 'id'), 
                'drivers'=>$drivers,
                'fields'=>[['class'=>'col-12', 'classselect'=>'noselecttwo', 'ftype'=>'select', 'name'=>'Driver', 'id'=>'driver', 'placeholder'=>'Assign Driver', 'data'=>$driversData, 'required'=>true]],
            ]);
        } else {
            return redirect()->route('quotations.index')->withStatus(__('No Access.'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function liveapi()
    {

        //TODO - Method not allowed for client or driver
        if (auth()->user()->hasRole('client')) {
            dd('Not allowed as client');
        }

        //Today only
        $quotations = Quotation::where('created_at', '>=', Carbon::today())->quotationBy('created_at', 'desc');

        //If owner, only from his restorant
        if (auth()->user()->hasRole('owner')) {
            $quotations = $quotations->where(['restorant_id'=>auth()->user()->restorant->id]);
            
            //Change currency
            ConfChanger::switchCurrency(auth()->user()->restorant);

            //Change language
            //ConfChanger::switchLanguage($quotation->restorant);
        }
        $quotations = $quotations->with(['status', 'client', 'restorant', 'table.restoarea'])->get()->toArray();

        

        $newQuotations = [];
        $acceptedQuotations = [];
        $doneQuotations = [];

        $items = [];
        foreach ($quotations as $key => $quotation) {
            $client="";
            if(config('app.isft')){
                $client=$quotation['client']['name'];
            }else{
                if(!config('settings.is_whatsapp_quotationing_mode')){
                    //QR
                    if($quotation['table']&&$quotation['table']['restoarea']&&$quotation['table']['restoarea']['name']&&$quotation['table']['name']){
                        $client=$quotation['table']['restoarea']['name'].' - '.$quotation['table']['name'];
                    }else if($quotation['table']&&$quotation['table']['name']){
                        $client=$quotation['table']['name'];
                    }
                }else{
                    //WhatsApp
                    $client=$quotation['phone'];
                }
            }
            array_push($items, [
                'id'=>$quotation['id'],
                'restaurant_name'=>$quotation['restorant']['name'],
                'last_status'=>count($quotation['status']) > 0 ? __($quotation['status'][count($quotation['status']) - 1]['name']) : 'Just created',
                'last_status_id'=>count($quotation['status']) > 0 ? $quotation['status'][count($quotation['status']) - 1]['pivot']['status_id'] : 1,
                'time'=>$quotation['updated_at'],
                'client'=>$client,
                'link'=>'/quotations/'.$quotation['id'],
                'price'=>money($quotation['quotation_price'], config('settings.cashier_currency'), config('settings.do_convertion')).'',
            ]);
        }

        //dd($items);

        /**

         */

        //----- ADMIN ------
        if (auth()->user()->hasRole('admin')) {
            foreach ($items as $key => $item) {
                //Box 1 - New Quotations
                //Today quotations that are just created ( Needs approvment or rejection )
                //Box 2 - Accepted
                //Today quotations approved by Restaurant , or by admin( Needs assign to driver )
                //Box 3 - Done
                //Today quotations assigned with driver, or rejected
                if ($item['last_status_id'] == 1) {
                    $item['pulse'] = 'blob green';
                    array_push($newQuotations, $item);
                } elseif ($item['last_status_id'] == 2 || $item['last_status_id'] == 3) {
                    $item['pulse'] = 'blob orangestatic';
                    if ($item['last_status_id'] == 3) {
                        $item['pulse'] = 'blob orange';
                    }
                    array_push($acceptedQuotations, $item);
                } elseif ($item['last_status_id'] > 3) {
                    $item['pulse'] = 'blob greenstatic';
                    if ($item['last_status_id'] == 9 || $item['last_status_id'] == 8) {
                        $item['pulse'] = 'blob redstatic';
                    }
                    array_push($doneQuotations, $item);
                }
            }
        }

        //----- Restaurant ------
        if (auth()->user()->hasRole('owner')) {
            foreach ($items as $key => $item) {

                
                //Box 1 - New Quotations
                //Today quotations that are approved by admin ( Needs approvment or rejection )
                //Box 2 - Accepted
                //Today quotations approved by Restaurant ( Needs change of status to done )
                //Box 3 - Done
                //Today completed or rejected
                $last_status = $item['last_status_id'];
                if ($last_status == 2 || $last_status == 10 || ($item['last_status_id'] == 1 && config('app.isqrsaas'))) {
                    $item['pulse'] = 'blob green';
                    array_push($newQuotations, $item);
                } elseif ($last_status == 3 || $last_status == 4 || $last_status == 5) {
                    $item['pulse'] = 'blob orangestatic';
                    if ($last_status == 3) {
                        $item['pulse'] = 'blob orange';
                    }
                    array_push($acceptedQuotations, $item);
                } elseif ($last_status > 5 && $last_status != 8) {
                    $item['pulse'] = 'blob greenstatic';
                    if ($last_status == 9 || $last_status == 8) {
                        $item['pulse'] = 'blob redstatic';
                    }
                    array_push($doneQuotations, $item);
                }
            }
        }

        $toRespond = [
                'newquotations'=>$newQuotations,
                'accepted'=>$acceptedQuotations,
                'done'=>$doneQuotations,
            ];

        return response()->json($toRespond);
    }

    public function live()
    {
        return view('quotations.live');
    }

    public function autoAssignToDriver(Quotation $quotation)
    {
        //The restaurant id
        $restaurant_id = $quotation->restorant_id;

        //1. Get all the working drivers, where active and working
        $theQuery = User::role('driver')->where(['active'=>1, 'working'=>1]);

        //2. Get Drivers with their assigned quotation, where payment_status is unpaid yet, this quotation is still not delivered and not more than 1
        $theQuery = $theQuery->whereHas('driverquotations', function (Builder $query) {
            $query->where('payment_status', '!=', 'paid')->where('created_at', '>=', Carbon::today());
        }, '<=', 1);

        //Get Restaurant lat / lng
        $restaurant = Restorant::findOrFail($restaurant_id);
        $lat = $restaurant->lat;
        $lng = $restaurant->lng;

        //3. Sort drivers by distance from the restaurant
        $driversWithGeoIDS = $this->scopeIsWithinMaxDistance($theQuery, $lat, $lng, config('settings.driver_search_radius'), 'users')->pluck('id')->toArray();

        //4. The top driver gets the quotation
        if (count($driversWithGeoIDS) == 0) {
            //No driver found -- this will appear in  the admin list also in the list of free quotation so driver can get an quotation
            //dd('no driver found');
        } else {
            //Driver found
            ///dd('driver found: '.$driversWithGeoIDS[0]);
            $quotation->driver_id = $driversWithGeoIDS[0];
            $quotation->update();
            $quotation->status()->attach([4 => ['comment'=>'System', 'user_id' => $driversWithGeoIDS[0]]]);

            //Now increment the driver quotations
            $theDriver = User::findOrFail($quotation->driver_id);
            $theDriver->numquotations = $theDriver->numquotations + 1;
            $theDriver->update();
        }
    }

    public function updateStatus($alias, Quotation $quotation)
    {
        if (isset($_GET['driver'])) {
            $quotation->driver_id = $_GET['driver'];
            $quotation->update();

            //Now increment the driver quotations
            $theDriver = User::findOrFail($quotation->driver_id);
            $theDriver->numquotations = $theDriver->numquotations + 1;
            $theDriver->update();
        }

        if (isset($_GET['time_to_prepare'])) {
            $quotation->time_to_prepare = $_GET['time_to_prepare'];
            $quotation->update();
        }

        $status_id_to_attach = Status::where('alias', $alias)->value('id');

        //Check access before updating
        /**
         * 1 - Super Admin
         * accepted_by_admin
         * assigned_to_driver
         * rejected_by_admin.
         *
         * 2 - Restaurant
         * accepted_by_restaurant - 3
         * prepared
         * rejected_by_restaurant
         * picked_up
         * delivered
         *
         * 3 - Driver
         * picked_up
         * delivered
         */
        //

        $rolesNeeded = [
            'accepted_by_admin'=>'admin',
            'assigned_to_driver'=>'admin',
            'rejected_by_admin'=>'admin',
            'accepted_by_restaurant'=>['owner', 'staff'],
            'prepared'=>['owner', 'staff'],
            'rejected_by_restaurant'=>['owner', 'staff'],
            'picked_up'=>['driver', 'owner', 'staff'],
            'delivered'=>['driver', 'owner', 'staff'],
            'closed'=>['owner', 'staff'],
            'accepted_by_driver'=>['driver'],
            'rejected_by_driver'=>['driver']
        ];

        if (! auth()->user()->hasRole($rolesNeeded[$alias])) {
            abort(403, 'Unauthorized action. You do not have the appropriate role');
        }

        //For owner - make sure this is his quotation
        if (auth()->user()->hasRole('owner')) {
            //This user is owner, but we must check if this is quotation from his restaurant
            if (auth()->user()->id != $quotation->restorant->user_id) {
                abort(403, 'Unauthorized action. You are not owner of this quotation restaurant');
            }
        }

        if (auth()->user()->hasRole('sstaff')) {
            //This user is owner, but we must check if this is quotation from his restaurant
            if (auth()->user()->restaurant_id != $quotation->restorant->id) {
                abort(403, 'Unauthorized action. You are not owner of this quotation restaurant');
            }
        }

        //For driver - make sure he is assigned to this quotation
        if (auth()->user()->hasRole('driver')) {
            //This user is owner, but we must check if this is quotation from his restaurant
            if (auth()->user()->id != $quotation->driver->id) {
                abort(403, 'Unauthorized action. You are not driver of this quotation');
            }
        }

        /**
         * IF status
         * Accept  - 3
         * Prepared  - 5
         * Rejected - 9.
         */
        // dd($status_id_to_attach."");

        if (config('app.isft')) {
            if ($status_id_to_attach.'' == '3' || $status_id_to_attach.'' == '5' || $status_id_to_attach.'' == '9') {
                $quotation->client->notify(new QuotationNotification($quotation, $status_id_to_attach));
            }

            if ($status_id_to_attach.'' == '4') {
                $quotation->driver->notify(new QuotationNotification($quotation, $status_id_to_attach));
            }
        }

        //Picked up - start tracing
        if ($status_id_to_attach.'' == '6') {
            $quotation->lat = $quotation->restorant->lat;
            $quotation->lng = $quotation->restorant->lng;
            $quotation->update();
        }

        if (config('app.isft') && $alias.'' == 'delivered') {
            $quotation->payment_status = 'paid';
            $quotation->update();
        }

        if (config('app.isqrsaas') && $alias.'' == 'closed') {
            $quotation->payment_status = 'paid';
            $quotation->update();
        }

        if (config('app.isft')) {
            //When quotations is accepted by restaurant, auto assign to driver
            if ($status_id_to_attach.'' == '3') {
                if (config('settings.allow_automated_assign_to_driver')) {
                    $this->autoAssignToDriver($quotation);
                }
            }
        }

        //$quotation->status()->attach([$status->id => ['comment'=>"",'user_id' => auth()->user()->id]]);
        $quotation->status()->attach([$status_id_to_attach => ['comment'=>'', 'user_id' => auth()->user()->id]]);


        //Dispatch event
        if($alias=="accepted_by_restaurant"){
            QuotationAcceptedByVendor::dispatch($quotation);
        }
        if($alias=="accepted_by_admin"){
            //IN FT send email
            if (config('app.isft')) {
                $quotation->restorant->user->notify((new QuotationNotification($quotation))->locale(strtolower(config('settings.app_locale'))));
            }
            
            QuotationAcceptedByAdmin::dispatch($quotation);
        }


        return redirect()->route('quotations.index')->withStatus(__('Quotation status succesfully changed.'));
    }

    public function rateQuotation(Request $request, Quotation $quotation)
    {
        $restorant = $quotation->restorant;

        $rating = new Rating;
        $rating->rating = $request->ratingValue;
        $rating->user_id = auth()->user()->id;
        $rating->quotation_id = $quotation->id;
        $rating->comment = $request->comment;

        $restorant->ratings()->save($rating);

        return redirect()->route('quotations.show', ['quotation'=>$quotation])->withStatus(__('Quotation succesfully rated!'));
    }

    public function checkQuotationRating(Quotation $quotation)
    {
        $rating = DB::table('ratings')->select('rating')->where(['quotation_id' => $quotation->id])->get()->first();
        $is_rated = false;

        if (! empty($rating)) {
            $is_rated = true;
        }

        return response()->json(
            [
                'rating' => $rating->rating,
                'is_rated' => $is_rated,
                ]
        );
    }

    public function guestQuotations()
    {
        $previousQuotations = Cookie::get('quotations') ? Cookie::get('quotations') : '';
        $previousQuotationArray = array_filter(explode(',', $previousQuotations));

        //Find the quotations
        $quotations = Quotation::whereIn('id', $previousQuotationArray)->quotationBy('id', 'desc')->get();
        $backUrl = url()->previous();
        foreach ($quotations as $key => $quotation) {
            $backUrl = route('vendor', $quotation->restorant->subdomain);
        }

        return view('quotations.guestquotations', ['backUrl'=>$backUrl, 'quotations'=>$quotations, 'statuses'=>Status::pluck('name', 'id')]);
    }


    public function generateQuotationMsg($address, $comment, $price)
    {
        $title = 'New quotation #'.strtoupper(Str::random(5))."\n\n";

        $price = '*Price*: '.$price.' '.config('settings.cashier_currency')."\n\n";

        $items = '*Quotation:*'."\n";
        foreach (Cart::getContent() as $key => $item) {
            $items .= strval($item->quantity).' x '.$item->name."\n";
        }
        $items .= "\n";
        $final = $title.$price.$items;

        if ($address != null) {
            $final .= '*Address*:'."\n".$address."\n\n";
        }

        if ($comment != null) {
            $final .= '*Comment:*'."\n".$comment."\n\n";
        }

        return urlencode($final);
    }

    public function fbQuotationMsg(Request $request)
    {
        $quotationPrice = Cart::getSubTotal();

        $title = 'New quotation #'.strtoupper(Str::random(5))."\n\n";

        $price = '*Price*: '.$quotationPrice.' '.config('settings.cashier_currency')."\n\n";

        $items = '*Quotation:*'."\n";
        foreach (Cart::getContent() as $key => $item) {
            $items .= strval($item->quantity).' x '.$item->name."\n";
        }
        $items .= "\n";
        $final = $title.$price.$items;

        if ($request->address != null) {
            $final .= '*Address*:'."\n".$request->address."\n\n";
        }

        if ($request->comment != null) {
            $final .= '*Comment:*'."\n".$request->comment."\n\n";
        }

        return response()->json(
            [
                'status' => true,
                'msg' => $final,
            ]
        );
    }

    public function storeWhatsappQuotation(Request $request)
    {
        $restorant_id = null;
        foreach (Cart::getContent() as $key => $item) {
            $restorant_id = $item->attributes->restorant_id;
        }

        $restorant = Restorant::findOrFail($restorant_id);

        $quotationPrice = Cart::getSubTotal();

        if ($request->exists('deliveryType')) {
            $isDelivery = $request->deliveryType == 'delivery';
        }

        $text = $this->generateWhatsappQuotation($request->exists('addressID') ? $request->addressID : null, $request->exists('comment') ? $request->comment : null, $quotationPrice);

        $url = 'https://wa.me/'.$restorant->whatsapp_phone.'?text='.$text;

        Cart::clear();

        return Redirect::to($url);
    }

    public function cancel(Request $request)
    {   
        $quotation = Quotation::findOrFail($request->quotation);
        return view('quotations.cancel', ['quotation' => $quotation]);
    }

    public function success(Request $request)
    {   
        $quotation = Quotation::findOrFail($request->quotation);

        //If quotation is not paid - redirect to payment
        if($request->redirectToPayment.""=="1"&&$quotation->payment_status != 'paid'&&strlen($quotation->payment_link)>5){
            //Redirect to payment
            return redirect($quotation->payment_link);
        } 

        //If we have whatsapp send
        if($request->has('whatsapp')){
            $message=$quotation->getSocialMessageAttribute(true);
            $url = 'https://api.whatsapp.com/send?phone='.$quotation->restorant->whatsapp_phone.'&text='.$message;
            return Redirect::to($url);
        }

        //Should we show whatsapp send quotation
        $showWhatsApp=config('settings.whatsapp_quotationing_enabled');

        if($showWhatsApp){
            //Disable when WhatsApp Mode
            if(config('settings.is_whatsapp_quotationing_mode')){
                $showWhatsApp=false;
            }

            //In QR, if owner phone is not set, hide the button
            //In FT, we use owner phone to have the number
            if(strlen($quotation->restorant->whatsapp_phone)<3){
                $showWhatsApp=false;
            }
        }

        
        return view('quotations.success', ['quotation' => $quotation,'showWhatsApp'=>$showWhatsApp]);
    }
}
