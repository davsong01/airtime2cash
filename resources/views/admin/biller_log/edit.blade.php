<?php 
    use Illuminate\Support\Facades\Session;
    $page = Session::get('page') ?? 1;
?>
@extends('layouts.app')
@section('page-css')
<style>
    /* .tiny{ */
        /* padding: 1.5px !important;
        font-size: 11px !important;
    } */
    
</style>
@endsection
@section('content')
<!-- Content wrapper -->
<div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <div class="content-header row">
                <div class="content-header-left col-12 mb-2 mt-1">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <div class="breadcrumb-wrapper col-12">
                                <ol class="breadcrumb p-0 mb-0">
                                    <li class="breadcrumb-item"><a href="/"><i class="bx bx-home-alt"></i></a>
                                    </li>
                                    <li class="breadcrumb-item"><a href="{{ route('product.index') }}">Products</a>
                                    </li>
                                    <li class="breadcrumb-item active">Edit {{$product->name}}
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="content-body">
                <!-- Basic Inputs start -->
                <section id="basic-input">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="content-body">
                                    <!-- Nav Filled Starts -->
                                    <section id="nav-filled">
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <div class="card">
                                                    <div class="card-header">
                                                        @include('layouts.alerts')
                                                        <h4 class="card-title">Edit {{$product->name}}</h4>
                                                        <img src="{{asset($product->image)}}" alt="" style="width: 70px;">
                                                    </div>
                                                    <div class="card-content">
                                                        <div class="card-body">
                                                            <p></p>
                                                            <!-- Nav tabs -->
                                                            @if($product->has_variations == 'yes')
                                                                <ul class="nav nav-tabs nav-fill" id="myTab" role="tablist">
                                                                    <li class="nav-item">
                                                                        <a class="nav-link {{ $page == 1 ? 'active' : ''}}" id="home-tab-fill" data-toggle="tab" href="#product-details" role="tab" aria-controls="product-details" aria-selected="true">
                                                                            Product Details
                                                                        </a>
                                                                    </li>
                                                                    <li class="nav-item">
                                                                        <a class="nav-link {{ $page == 2 ? 'active' : ''}}" id="profile-tab-fill" data-toggle="tab" href="#variations" role="tab" aria-controls="variations" aria-selected="false">
                                                                            Variations ({{$product->variations->count()}})
                                                                        </a>
                                                                    </li>
                                                                    {{-- <li class="nav-item">
                                                                        <a class="nav-link" id="messages-tab-fill" data-toggle="tab" href="#settings" role="tab" aria-controls="settings" aria-selected="false">
                                                                            General Settings
                                                                        </a>
                                                                    </li> --}}
                                                                </ul>
                                                            @endif
                                                            <!-- Tab panes -->
                                                            <div class="tab-content pt-1">
                                                                <div class="tab-pane {{ $page == 1 ? 'active' : ''}}" id="product-details" role="tabpanel" aria-labelledby="home-tab-fill">
                                                                    <form action="{{route('product.update', $product->id)}}" method="POST" enctype="multipart/form-data">
                                                                        @csrf
                                                                        @method('PATCH')
                                                                        <div class="row">
                                                                            <div class="col-md-6">
                                                                                <fieldset class="form-group">
                                                                                    <label for="name">Name</label>
                                                                                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter name" value="{{ $product->name ?? old('name')}}" required>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="display_name">Display Name</label>
                                                                                    <input type="text" class="form-control" id="display_name" name="display_name" placeholder="Enter display name" value="{{ $product->display_name ?? old('display_name')}}" required>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="slug">Slug</label>
                                                                                    <input type="text" class="form-control" name="slug" placeholder="Enter slug" id="slug" value="{{ $product->slug ?? old('slug')}}" required>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="basicInputFile">Display Image</label>
                                                                                    <div class="custom-file">
                                                                                        <input type="file" accept="image/*" class="custom-file-input" id="image" name="image">
                                                                                        <label class="custom-file-label" for="image">Replace file</label>
                                                                                    </div>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="category">Category</label>
                                                                                    <select class="form-control" name="category" id="category" required>
                                                                                        <option value="">Select</option>
                                                                                        @foreach ($categories as $category)
                                                                                            <option value="{{ $category->id  }}" {{ $product->category_id == $category->id ? 'selected' : ''}}>{{ $category->name }}</option>
                                                                                        @endforeach
                                                                                    </select>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="name">Allow Quantity</label>
                                                                                    <select class="form-control" name="allow_quantity" id="allow_quantity">
                                                                                        <option value="">Select</option>
                                                                                        <option value="yes" {{ $product->allow_quantity == 'yes' ? 'selected' : ''}}>Yes</option>
                                                                                        <option value="no" {{ $product->allow_quantity == 'no' ? 'selected' : ''}}>No</option>
                                                                                    </select>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="name">Quantity Graduation</label>
                                                                                    <input type="text" class="form-control tiny" placeholder="Please enter each value seperated with a comma" id="quantity_graduation" name="quantity_graduation"  value="{{ $product->quantity_graduation }}">
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="has_variations">Has Variations</label>
                                                                                    <select class="form-control" name="has_variations" id="has_variations" required>
                                                                                        <option value="">Select</option>
                                                                                        <option value="yes" {{ $product->has_variations == 'yes' ? 'selected' : ''}}>Yes</option>
                                                                                        <option value="no" {{ $product->has_variations == 'no' ? 'selected' : ''}}>No</option>
                                                                                    </select>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="name">Allow Subscription Type (For DSTV and GOTV purchses)</label>
                                                                                    <select class="form-control" name="allow_subscription_type" id="allow_subscription_type">
                                                                                        <option value="">Select</option>
                                                                                        <option value="yes" {{ $product->allow_subscription_type == 'yes' ? 'selected' : ''}}>Yes</option>
                                                                                        <option value="no" {{ $product->allow_subscription_type == 'no' ? 'selected' : ''}}>No</option>
                                                                                    </select>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="description">Description</label>
                                                                                    <textarea style="height: 117px !important" class="form-control" id="description" name="description" rows="3" placeholder="Description" value="{{ $product->description ??  old('description')}}"></textarea>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="seo_title">SEO Title</label>
                                                                                    <input type="text" class="form-control" id="seo_title"  name="seo_title" placeholder="Enter SEO Title" value="{{ $product->seo_title ?? old('seo_title')}}">
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="seo_keywords">SEO Keywords</label>
                                                                                    <input type="text" class="form-control"  name="seo_keywords" placeholder="Enter SEO Keywords" id="seo_keywords" value="{{ $product->seo_keywords ?? old('seo_keywords')}}">
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="seo_description">SEO Description</label>
                                                                                    <textarea class="form-control" id="seo_description" rows="3" name="seo_description" value="{{ $product->seo_description ?? old('seo_description') }}" placeholder="SEO Description">{{ $product->seo_description ?? old('seo_description') }}</textarea>
                                                                                </fieldset>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <fieldset class="form-group">
                                                                                    <label for="api">API to use</label>
                                                                                    <select class="form-control" name="api" id="api" required>
                                                                                        @foreach ($apis as $item)
                                                                                            <option value="{{ $item->id  }}" {{ $product->api_id == $item->id ? 'selected' : ''}}>{{ $item->name }}</option>
                                                                                        @endforeach
                                                                                    </select>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="servercode">Server Code/Server Token</label>
                                                                                    <input type="text" class="form-control" name="servercode" placeholder="Enter servercode" id="servercode" value="{{ $product->servercode ?? old('servercode')}}">
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="multistep">Use Multistep</label>
                                                                                    <select class="form-control tiny" name="multistep" id="multistep">
                                                                                        <option value="">Select</option>
                                                                                        <option value="yes" {{ $product->multistep == 'yes' ? 'selected' : ''}}>Yes</option>
                                                                                        <option value="no" {{ $product->multistep == 'no' ? 'selected' : ''}}>No</option> 
                                                                                    </select>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="ussd_string">USSD String</label>
                                                                                    <input type="text" class="form-control tiny" id="ussd_string" name="ussd_string"  value="{{ $product->ussd_string }}">
                                                                                </fieldset>
                                                                                
                                                                                <fieldset class="form-group">
                                                                                    <label for="status">Status</label>
                                                                                    <select class="form-control" name="status" id="status" required>
                                                                                        <option value="">Select</option>
                                                                                        <option value="active" {{ $product->status == 'active' ? 'selected' : ''}}>Active</option>
                                                                                        <option value="inactive" {{ $product->status == 'inactive' ? 'selected' : ''}}>InActive</option>
                                                                                    </select>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="fixed_price">Fixed Price</label>
                                                                                    <select class="form-control tiny" name="fixed_price" id="fixed_price">
                                                                                        <option value="">Select</option>
                                                                                        <option value="yes" {{ $product->fixed_price == 'yes' ? 'selected' : ''}}>Yes</option>
                                                                                        <option value="no" {{ $product->fixed_price == 'no' ? 'selected' : ''}}>No</option>
                                                                                    </select>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="name">System Price ({!! getSettings()['currency']!!})</label>
                                                                                    <input type="number" class="form-control tiny" id="system_price" name="system_price"  value="{{ $product->system_price }}">
                                                                                </fieldset>
                                                                                @foreach($customerlevel as $level)
                                                                                {{-- {{dd($product->category->discount_type )}} --}}
                                                                                <fieldset class="form-group">
                                                                                    <label for="name">{{ $level->name }} @if($product->category->discount_type == 'flat') Discounted Price ({!! getSettings()['currency']!!}) @else Discounted Percentage (%) @endif</label>
                                                                                    <input type="number" class="form-control tiny" id="productlevel" name="productlevel[{{ $level->id }}]" step=".01" value="{{ $product->customer_level_price($level->id) }}">
                                                                                </fieldset>
                                                                                @endforeach
                                                                                <fieldset class="form-group">
                                                                                    <label for="min">Minimun Amount ({!! getSettings()['currency']!!})</label>
                                                                                    <input type="number" class="form-control tiny" id="min" name="min"  value="{{ $product->min }}">
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="max">Maimum Amount ({!! getSettings()['currency']!!})</label>
                                                                                    <input type="number" class="form-control tiny" id="max" name="max"  value="{{ $product->max }}">
                                                                                </fieldset>
                                                                                
                                                                                @if($product->has_variations == 'yes')
                                                                                @endif
                                                                                <input type="hidden" value="page1" name="route">
                                                                            </div>
                                                                            <div class="col-md-12">
                                                                                <button class="btn btn-primary" type="submit">Update</button>
                                                                            </div>
                                                                        </div>
                                                                    </form>
                                                                </div>

                                                                @if($product->has_variations == 'yes')
                                                                    <div class="tab-pane {{ isset($page) && $page == 2 ? 'active' : ''}}" id="variations" role="tabpanel" aria-labelledby="profile-tab-fill">
                                                                        {{-- Manual ADD Variations --}}
                                                                        <div class="modal-primary mr-1 mb-1 d-inline-block">
                                                                        <!-- Button trigger for primary themes modal -->
                                                                        <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#primary">
                                                                            Add Variations
                                                                        </button>

                                                                        @include('admin.product.add_variations_form')
                                                                    </div>

                                                                        @if($product->variations->count() < 1)
                                                                            <a href="{{ route('variations.pull', $product->id) }}" class="btn btn-primary mb-2 d-flex align-items-center"><i class="bx bx-plus"></i>&nbsp; Pull Variations</a>
                                                                        @else
                                                                            <a style="width:fit-content;" href="{{ route('variations.pull', $product->id) }}" class="btn btn-info mb-2 mt-1 d-flex align-items-center"><i class="bx bx-plus"></i>&nbsp; Re Pull Variations</a>

                                                                            <form action="{{route('variations.update', $product->id)}}" method="POST" enctype="multipart/form-data">
                                                                                @csrf
                                                                               
                                                                                @foreach($product->variations as $variation)
                                                                                <div class="row" style="margin-bottom:10px">
                                                                                    <div class="col-md-3">
                                                                                        <fieldset class="form-group">
                                                                                            <label for="api_name">API Name</label>
                                                                                            <input type="text" class="form-control tiny" id="api_name" name="api_name[{{ $variation->id }}]"  value="{{ $variation->api_name }}">
                                                                                        </fieldset>
                                                                                    </div>
                                                                                    <div class="col-md-2">
                                                                                        <fieldset class="form-group">
                                                                                            <label for="name">API Price ({!! getSettings()['currency']!!})</label>
                                                                                            <input type="text" class="form-control tiny" id="api_price" name="api_price[{{ $variation->id }}]"  value="{{ $variation->api_price }}">
                                                                                        </fieldset>
                                                                                    </div>
                                                                                    <div class="col-md-2">
                                                                                        <fieldset class="form-group">
                                                                                            <label for="name">System Name</label>
                                                                                            <input type="text" class="form-control tiny" id="system_name" name="system_name[{{ $variation->id }}]"  value="{{ $variation->system_name }}">
                                                                                        </fieldset>
                                                                                    </div>
                                                                                    
                                                                                    <div class="col-md-3">
                                                                                        <fieldset class="form-group">
                                                                                            <label for="name">Slug</label>
                                                                                            <input type="text" class="form-control tiny" id="slug" name="slug[{{ $variation->id }}]"  value="{{ $variation->slug }}">
                                                                                        </fieldset>
                                                                                    </div>
                                                                                    <div class="col-md-2">
                                                                                        <fieldset class="form-group">
                                                                                            <label for="fixed_price">Fixed Price</label>
                                                                                            <select class="form-control tiny" name="fixed_price[{{ $variation->id }}]" id="fixed_price" required>
                                                                                                <option value="">Select</option>
                                                                                                <option value="Yes" {{ $variation->fixed_price == 'Yes' ? 'selected' : ''}}>Yes</option>
                                                                                                <option value="No" {{ $variation->fixed_price == 'No' ? 'selected' : ''}}>No</option> 
                                                                                            </select>
                                                                                        </fieldset>
                                                                                    </div>
                                                                                    
                                                                                    <div class="col-md-2">
                                                                                        <fieldset class="form-group">
                                                                                            <label for="status">Status</label>
                                                                                            <select class="form-control tiny" name="status[{{ $variation->id }}]" id="status" required>
                                                                                                <option value="">Select</option>
                                                                                                <option value="active" {{ $variation->status == 'active' ? 'selected' : ''}}>Active</option>
                                                                                                <option value="inactive" {{ $variation->status == 'inactive' ? 'selected' : ''}}>InActive</option>
                                                                                            </select>
                                                                                        </fieldset>
                                                                                    </div>
                                                                                    <div class="col-md-2">
                                                                                        <fieldset class="form-group">
                                                                                            <label for="multistep">Use Multistep</label>
                                                                                            <select class="form-control tiny" name="multistep[{{ $variation->id }}]" id="multistep">
                                                                                                <option value="">Select</option>
                                                                                                <option value="yes" {{ $variation->multistep == 'yes' ? 'selected' : ''}}>Yes</option>
                                                                                                <option value="no" {{ $variation->multistep == 'no' ? 'selected' : ''}}>No</option> 
                                                                                            </select>
                                                                                        </fieldset>
                                                                                    </div>
                                                                                    <div class="col-md-4">
                                                                                        <fieldset class="form-group">
                                                                                            <label for="ussd_string">USSD String</label>
                                                                                            <input type="text" class="form-control tiny" id="ussd_string" name="ussd_string[{{ $variation->id }}]"  value="{{ $variation->ussd_string }}">
                                                                                        </fieldset>
                                                                                    </div>
                                                                                    <div class="col-md-2">
                                                                                        <fieldset class="form-group">
                                                                                            <label for="min">Min Amount</label>
                                                                                            <input type="number" class="form-control tiny" id="min" name="min[{{ $variation->id }}]"  value="{{ $variation->min }}">
                                                                                        </fieldset>
                                                                                    </div>
                                                                                    <div class="col-md-2">
                                                                                        <fieldset class="form-group">
                                                                                            <label for="max">Max Amount</label>
                                                                                            <input type="number" class="form-control tiny" id="max" name="max[{{ $variation->id }}]"  value="{{ $variation->max }}">
                                                                                        </fieldset>
                                                                                    </div>
                                                                                    
                                                                                    <div class="col-md-2">
                                                                                        <fieldset class="form-group">
                                                                                            <label for="name">System Price ({!! getSettings()['currency']!!})</label>
                                                                                            <input type="number" class="form-control tiny" id="system_price" name="system_price[{{ $variation->id }}]"  value="{{ $variation->system_price }}">
                                                                                        </fieldset>
                                                                                    </div>
                                                                                   
                                                                                    @foreach($customerlevel as $level)
                                                                                    <div class="col-md-3">
                                                                                        <fieldset class="form-group">
                                                                                            <label for="name">{{ $level->name }} @if($variation->category->discount_type == 'flat') Discounted Price ({!! getSettings()['currency']!!}) @else Discounted Percentage (%) @endif</label>
                                                                                            <input type="number" step=".01" class="form-control tiny" id="level" name="level[{{ $level->id }}][{{ $variation->id }}]"  value="{{ $variation->customer_level_price($level->id) }}">
                                                                                        </fieldset>
                                                                                    </div>
                                                                                    @endforeach
                                                                                    @if($variation->transaction->count() < 1)
                                                                                    <div class="col-md-1">
                                                                                        <fieldset class="form-group">
                                                                                            <label style="color:white">S</label>
                                                                                            <a onclick="return confirm('You are about to delete a variation')" href="{{ route('variation.delete', $variation->id) }}"><button style="color: white;" class="btn btn-sm btn-danger form-control" style="padding: 8px;" type="button"><i class="fa fa-trash"></i></button></a>
                                                                                        </fieldset>
                                                                                    </div>
                                                                                    @endif
                                                                                </div>
                                                                                <input type="hidden" name="variation_id[{{$variation->id}}]" value="{{$variation->id}}">
                                                                                 <hr style="height: 0px;border-color: #00cfdd;">
                                                                                @endforeach
                                                                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                                                <div class="row">
                                                                                    <div class="col-md-12">
                                                                                        <button class="btn btn-primary" type="submit">Submit</button>
                                                                                    </div>
                                                                                </div>
                                                                            </form>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                    <!-- Nav Filled Ends -->
                                </div>
                               
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
@section('page-script')
<script src="{{ asset('app-assets/js/scripts/pages/dashboard-analytics.js') }}"></script>
<script>
    $("#add-mode").on('click', function () {
        //get last ID
        var lastChild = $("#mode-holder").children().last();
        var countChildren = $("#mode-holder").children().length;
        
        var lastId = $(lastChild).attr('id').split('-');

        var id = lastId[1] + 1;
    
        var child = `<div class="row" id="mode-`+id+`">
                <div class="col-md-3">
                <fieldset class="form-group">
                    <label for="name">System Name</label>
                    <input type="text" class="form-control tiny" id="system_name" name="system_name[]"  value="" placeholder="Variation name">
                </fieldset>
            </div>
            
            <div class="col-md-3">
                <fieldset class="form-group">
                    <label for="name">Slug</label>
                    <input type="text" class="form-control tiny" id="slug" name="slug[]"  value="" placeholder="Variation slug" required>
                </fieldset>
            </div>
            <div class="col-md-2">
                <fieldset class="form-group">
                    <label for="fixed_price">Fixed Price</label>
                    <select class="form-control tiny" name="fixed_price[]" id="fixed_price" required>
                        <option value="">Select</option>
                        <option value="Yes" {{ old('fixed_price') == 'Yes' ? 'selected' : ''}}>Yes</option>
                        <option value="No" {{ old('fixed_price') == 'No' ? 'selected' : ''}}>No</option> 
                    </select>
                </fieldset>
            </div>
            
            <div class="col-md-2">
                <fieldset class="form-group">
                    <label for="status">Status</label>
                    <select class="form-control tiny" name="status[]" id="status" required>
                        <option value="">Select</option>
                        <option value="active" {{ old('status') == 'active' ? 'selected' : ''}}>Active</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : ''}}>InActive</option>
                    </select>
                </fieldset>
            </div>
            <div class="col-md-2">
                <fieldset class="form-group">
                    <label for="multistep">Use Multistep</label>
                    <select class="form-control tiny" name="multistep[]" id="multistep">
                        <option value="">Select</option>
                        <option value="yes" {{ old('multistep') == 'yes' ? 'selected' : ''}}>Yes</option>
                        <option value="no" {{ old('multistep') == 'no' ? 'selected' : ''}}>No</option> 
                    </select>
                </fieldset>
            </div>
            <div class="col-md-4">
                <fieldset class="form-group">
                    <label for="ussd_string">USSD String</label>
                    <input type="text" class="form-control tiny" id="ussd_string" name="ussd_string[]"  value="{{ old('ussd_string') }}">
                </fieldset>
            </div>
            <div class="col-md-2">
                <fieldset class="form-group">
                    <label for="min">Min Amount</label>
                    <input type="number" class="form-control tiny" id="min" name="min[]"  value="{{ old('min') }}">
                </fieldset>
            </div>
            <div class="col-md-2">
                <fieldset class="form-group">
                    <label for="max">Max Amount</label>
                    <input type="number" class="form-control tiny" id="max" name="max[]"  value="{{ old('max') }}">
                </fieldset>
            </div>
            
            <div class="col-md-2">
                <fieldset class="form-group">
                    <label for="name">System Price ({!! getSettings()['currency']!!})</label>
                    <input type="number" class="form-control tiny" id="system_price" name="system_price[]"  value="" placeholder="Variation price" required>
                </fieldset>
            </div>
            @foreach($customerlevel as $level)
            <div class="col-md-3">
                <fieldset class="form-group">
                    <label for="name">{{ $level->name }} @if($product->category->discount_type == 'flat') Discounted Price ({!! getSettings()['currency']!!}) @else Discounted Percentage (%) @endif</label>
                    <input type="number" class="form-control tiny" step=".01" id="level" name="level[{{ $level->id }}][]" value="" required>
                </fieldset>
            </div>
            @endforeach
            <div class="col-md-2">
                <fieldset class="form-group">
                    <label for="mark" style="color:white">sdsdsddsdssd</label>
                    <button class="btn btn-danger remove-mode" id="remove-mode-`+id+`" type="button" style="min-width: unset;"> <i class="fa fa-minus"></i> Remove</button>
                </fieldset>
            </div>
            <div class="col-md-12">
            <hr style="height: 0px;border-color: #00cfdd;">
            </div>
        </div>`
        $("#mode-holder").append(child);      
    });

    $("#mode-holder").on('click','.remove-mode', function(e) {
        var removeId = $(e.target).attr('id').split('-');
        var id = removeId[2];
        $("#mode-"+id).remove();
    });

</script>
@endsection