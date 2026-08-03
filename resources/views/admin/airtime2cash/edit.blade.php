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
                                    <li class="breadcrumb-item"><a href="{{ route('airtime2cash.index') }}">Products</a>
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
                                                            <!-- Tab panes -->
                                                            <div class="tab-content pt-1">
                                                                <div class="tab-pane {{ $page == 1 ? 'active' : ''}}" id="product-details" role="tabpanel" aria-labelledby="home-tab-fill">
                                                                    <form action="{{route('airtime2cash.update', $product->id)}}" method="POST" enctype="multipart/form-data">
                                                                        @csrf
                                                                        @method('PATCH')
                                                                        <div class="row">
                                                                            <div class="col-md-6">
                                                                                <fieldset class="form-group">
                                                                                    <label for="name">Name</label>
                                                                                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter name" value="{{ $product->name ?? old('name')}}" required>
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
                                                                                        {{-- <option value="">Select</option> --}}
                                                                                        @foreach ($categories as $category)
                                                                                            <option value="{{ $category->id  }}" {{ $product->category_id == $category->id ? 'selected' : ''}}>{{ $category->name }}</option>
                                                                                        @endforeach
                                                                                    </select>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="instruction">Instruction</label>

                                                                                    <div id="toolbar-container">
                                                                                        <span class="ql-formats">
                                                                                            <select class="ql-font"></select>
                                                                                            <select class="ql-size"></select>
                                                                                        </span>
                                                                                        <span class="ql-formats">
                                                                                            <button class="ql-bold"></button>
                                                                                            <button class="ql-italic"></button>
                                                                                            <button class="ql-underline"></button>
                                                                                            <button class="ql-strike"></button>
                                                                                        </span>
                                                                                        <span class="ql-formats">
                                                                                            <select class="ql-color"></select>
                                                                                            <select class="ql-background"></select>
                                                                                        </span>
                                                                                        <span class="ql-formats">
                                                                                            <button class="ql-script" value="sub"></button>
                                                                                            <button class="ql-script" value="super"></button>
                                                                                        </span>
                                                                                        <span class="ql-formats">
                                                                                            <button class="ql-header" value="1"></button>
                                                                                            <button class="ql-header" value="2"></button>
                                                                                            <button class="ql-blockquote"></button>
                                                                                            <button class="ql-code-block"></button>
                                                                                        </span>
                                                                                        <span class="ql-formats">
                                                                                            <button class="ql-list" value="ordered"></button>
                                                                                            <button class="ql-list" value="bullet"></button>
                                                                                            <button class="ql-indent" value="-1"></button>
                                                                                            <button class="ql-indent" value="+1"></button>
                                                                                        </span>
                                                                                        <span class="ql-formats">
                                                                                            <button class="ql-direction" value="rtl"></button>
                                                                                            <select class="ql-align"></select>
                                                                                        </span>
                                                                                        <span class="ql-formats">
                                                                                            <button class="ql-link"></button>
                                                                                            <button class="ql-image"></button>
                                                                                            <button class="ql-video"></button>
                                                                                            <button class="ql-formula"></button>
                                                                                        </span>
                                                                                        <span class="ql-formats">
                                                                                            <button class="ql-clean"></button>
                                                                                        </span>
                                                                                    </div>
                                                                                    
                                                                                    <div class="editor">
                                                                                        {!! $product->instruction !!}
                                                                                    </div>
                                                                                    <input name="instruction" type="hidden" id="content" value=""/>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="description">Description</label>
                                                                                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Description" value="{{ old('description')}}"></textarea>
                                                                                </fieldset>
                                                                            </div>
                                                                            <div class="col-md-6">
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
                                                                                        <option value="no" {{ $product->fixed_price == 'no' ? 'selected' : ''}}>No</option>
                                                                                    </select>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="name">@if($product->category->discount_type == 'flat') Charge Rate ({!! getSettings()['currency']!!}) @else Charge Rate (%) @endif</label>
                                                                                    <input type="number" class="form-control tiny" id="rate" name="rate"  value="{{ $product->rate }}" required>
                                                                                </fieldset>
                                                                                @foreach($customerlevel as $level)
                                                                                <fieldset class="form-group">
                                                                                    <label for="name">{{ $level->name }} @if($product->category->discount_type == 'flat') Charge Rate ({!! getSettings()['currency']!!}) @else Charge Rate (%) @endif</label>
                                                                                    <input type="number" class="form-control tiny" id="productlevel" name="productlevel[{{ $level->id }}]" step=".01" value="{{ $product->customer_level_price($level->id) }}">
                                                                                </fieldset>
                                                                                @endforeach
                                                                                <fieldset class="form-group">
                                                                                    <label for="min">Minimun Amount ({!! getSettings()['currency']!!})</label>
                                                                                    <input type="number" class="form-control tiny" id="min" name="min"  value="{{ $product->min }}" required>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="max">Maimum Amount ({!! getSettings()['currency']!!})</label>
                                                                                    <input type="number" class="form-control tiny" id="max" name="max"  value="{{ $product->max }}" required>
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
                                                                                @if($product->has_variations == 'yes')
                                                                                @endif
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
                                                                        {{-- <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#primary">
                                                                            Add Variations
                                                                        </button> --}}

                                                                        {{-- @include('admin.product.add_variations_form') --}}
                                                                    </div>
                                                                        
                                                                        <a style="width:fit-content;" href="{{ route('variations.pull', $product->id) }}" class="btn btn-info mb-2 mt-1 d-flex align-items-center"><i class="bx bx-plus"></i>&nbsp; Pull Variations</a>
                                                                        @if($product->variations->count() > 0)

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
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.0-rc.2/dist/quill.snow.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.0-rc.2/dist/quill.js"></script>
<script>
        $(document).ready(function() {
            $('.js-example-basic-single').select2();
        });
        let quill = new Quill('.editor', {
            theme: 'snow',
            toolbar: true,
            placeholder: 'Edit instruction...',
            modules: {
                syntax: true,
                toolbar: '#toolbar-container',
            },
        });

        // $('.edit-mail').click(function() {
        //     let btn = $(this);
        //     let id = btn.data('id');
        //     let content = btn.parents('tr').find('.content').data('content');
        //     $('.editor p').html(content)
        //     $('.form-actions').prop('action', id);
        //     if (id == 'view') {
        //         $('.update-btn').hide();
        //     }/*  else {
        //         $('.update-btn').show();
        //     } */
        // });

        $('form').on('submit', (e) => {
            var myEditor = document.querySelector('.editor')
            var html = myEditor.children[0].innerHTML;
            $('#content').val(html);
        });
    </script>
@endsection