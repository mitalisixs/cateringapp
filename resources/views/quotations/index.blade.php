@extends('layouts.app', ['title' => __('Quotations')])
@section('admin_title')
    {{__('Quotations')}}
@endsection
@section('content')
    <div class="header bg-gradient-primary pb-8 pt-5 pt-md-8">
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


