@extends('layouts.app', ['title' => __('Quotations')])
@section('admin_title')
{{__('Quotations')}}
@endsection
@section('content')
<div class="header bg-gradient-primary pb-8 pt-5 pt-md-8">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <!--<div class="col-lg-6 col-7">
                </div>-->
                <div class="col-lg-12 col-12 text-right">

                    <a class="btn btn-sm btn-warning" href="{{route('quotations.create')}}">
                        <span class="btn-inner--icon"><i class="fa fa-plus"></i> {{ __('Add new Quotation') }}</span>
                    </a>



                </div>
            </div>
        </div>
    </div>
</div>


<div class="container-fluid mt--7">
    <div class="row">
        <div class="col">
            <!-- Order Card -->
            @include('quotations.partials.ordercard')
        </div>
    </div>
    @include('layouts.footers.auth')
    @include('quotations.partials.modals')
</div>
@endsection