@extends('layouts.app')
@section('title', 'Add Category')
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
                                    <li class="breadcrumb-item"><a href="{{ route('category.index') }}">Categories</a>
                                    </li>
                                    <li class="breadcrumb-item active">Add Category
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
                                <div class="card-header">
                                    <h4 class="card-title">Add Category</h4>
                                    <p>Add new category</p>
                                    @include('layouts.alerts')
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <form action="{{route('category.store')}}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <fieldset class="form-group">
                                                        <label for="name">Name</label>
                                                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" placeholder="Enter name" required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="icon">SVG code</label>
                                                        <input type="text" class="form-control" id="icon" name="icon" value="{{ old('icon') }}" placeholder="Enter icon" required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="display_name">Display Name</label>
                                                        <input type="text" class="form-control" id="display_name" name="display_name" value="{{ old('display_name') }}" placeholder="Enter name" required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="slug">Slug</label>
                                                        <input type="text" class="form-control" name="slug" value="{{ old('slug') }}" placeholder="Enter slug" id="slug" required>
                                                    </fieldset>
                                                   
                                                    <fieldset class="form-group">
                                                        <label for="seo_title">SEO Title</label>
                                                        <input type="text" class="form-control" id="seo_title" name="seo_title" placeholder="Enter SEO Title">
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="seo_keywords">SEO Keywords</label>
                                                        <input type="text" class="form-control" name="seo_keywords" placeholder="Enter SEO Keywords" id="seo_keywords">
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="seo_description">SEO Description</label>
                                                        <textarea class="form-control" id="seo_description" name="seo_description" rows="3" placeholder="SEO Description"></textarea>
                                                    </fieldset>
                                                </div>
                                                <div class="col-md-6">
                                                   
                                                    <fieldset class="form-group">
                                                        <label for="status">Category Status</label>
                                                        <select class="form-control" name="status" id="status" required>
                                                            <option value="">Select</option>
                                                            <option value="active" {{ old('status') == 'active' ? 'selected' : ''}}>Active</option>
                                                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : ''}}>InActive</option>
                                                        </select>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="unique_element">Unique Element</label>
                                                        <select class="form-control" name="unique_element" id="unique_element" required>
                                                            <option value="">Select</option>
                                                            @foreach ( getUniqueElements() as $element )
                                                                <option value="{{ $element }}" {{ old('unique_element') == 'element' ? 'selected' : ''}}>{{ ucfirst(str_replace("_"," ",$element))}}</option>
                                                            @endforeach
                                                        </select>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="discount_type">Discount Type</label>
                                                        <select class="form-control" name="discount_type" id="discount_type" required>
                                                            <option value="">Select...</option>
                                                            <option value="flat" {{ old('discount_type') == 'flat' ? 'selected' : '' }}>Flat</option>
                                                            <option value="percentage" {{ old('discount_type') == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                                        </select>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="order">Order</label>
                                                        <input type="number" class="form-control" name="order" value="{{ old('order') }}" placeholder="Enter order" id="order" required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="description">Description</label>
                                                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Description" required></textarea>
                                                    </fieldset>
                                                    
                                                </div>
                                                <div class="col-md-12">
                                                <button class="btn btn-primary" type="submit">Submit</button>
    
                                                </div>
                                            </div>
                                        </form>
                                    </div>
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

@endsection