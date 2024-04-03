@extends('layouts.app')
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
                                    <li class="breadcrumb-item active">Add Product
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
                                                        <h4 class="card-title">Add Product</h4>
                                                        @include('layouts.alerts')
                                                    </div>
                                                    <div class="card-content">
                                                        <div class="card-body">
                                                            <p>
                                                                
                                                            </p>
                                                            <!-- Nav tabs -->
                                                            <ul class="nav nav-tabs nav-fill" id="myTab" role="tablist">
                                                                <li class="nav-item">
                                                                    <a class="nav-link active" id="home-tab-fill" data-toggle="tab" href="#product-details" role="tab" aria-controls="product-details" aria-selected="true">
                                                                        Product Details
                                                                    </a>
                                                                </li>
                                                                <li class="nav-item">
                                                                    <a class="nav-link" id="profile-tab-fill" data-toggle="tab" href="#variations" role="tab" aria-controls="variations" aria-selected="false">
                                                                        Variations
                                                                    </a>
                                                                </li>
                                                               
                                                            </ul>

                                                            <!-- Tab panes -->
                                                            <div class="tab-content pt-1">
                                                                <div class="tab-pane active" id="product-details" role="tabpanel" aria-labelledby="home-tab-fill">
                                                                    <form action="{{route('product.store')}}" method="POST" enctype="multipart/form-data">
                                                                        @csrf
                                                                        <div class="row">
                                                                            <div class="col-md-6">
                                                                                <fieldset class="form-group">
                                                                                    <label for="name">Name</label>
                                                                                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter name" value="{{ old('name')}}" required>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="display_name">Display Name</label>
                                                                                    <input type="text" class="form-control" id="display_name" name="display_name" placeholder="Enter display name" value="{{ old('display_name')}}" required>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="slug">Slug</label>
                                                                                    <input type="text" class="form-control" name="slug" placeholder="Enter slug" id="slug" value="{{ old('slug')}}" required>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="basicInputFile">Display Image</label>
                                                                                    <div class="custom-file">
                                                                                        <input type="file" accept="image/*" class="custom-file-input" id="image" name="image" required>
                                                                                        <label class="custom-file-label" for="image">Choose file</label>
                                                                                    </div>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="category">Category</label>
                                                                                    <select class="form-control" name="category" id="category" required>
                                                                                        <option value="">Select</option>
                                                                                        @foreach ($categories as $category)
                                                                                            <option value="{{ $category->id  }}" {{ old('category') == $category->id ? 'selected' : ''}}>{{ $category->name }}</option>
                                                                                        @endforeach
                                                                                    </select>
                                                                                </fieldset>
                                                                                 <fieldset class="form-group">
                                                                                    <label for="name">Allow Quantity</label>
                                                                                    <select class="form-control" name="allow_quantity" id="allow_quantity">
                                                                                        <option value="">Select</option>
                                                                                        <option value="yes" {{ old('allow_quantity') == 'yes' ? 'selected' : ''}}>Yes</option>
                                                                                        <option value="no" {{ old('allow_quantity') == 'no' ? 'selected' : ''}}>No</option>
                                                                                    </select>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="name">Quantity Graduation</label>
                                                                                    <input type="text" class="form-control tiny" placeholder="Please enter each value seperated with a comma" id="quantity_graduation" name="quantity_graduation"  value="{{ old('quantity_graduation') }}">
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="name">Allow Subscription Type</label>
                                                                                    <select class="form-control" name="allow_subscription_type" id="allow_subscription_type">
                                                                                        <option value="">Select</option>
                                                                                        <option value="yes" {{ old('allow_subscription_type') == 'yes' ? 'selected' : ''}}>Yes</option>
                                                                                        <option value="no" {{ old('allow_subscription_type') == 'no' ? 'selected' : ''}}>No</option>
                                                                                    </select>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="description">Description</label>
                                                                                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Description" value="{{ old('description')}}"></textarea>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="seo_title">SEO Title</label>
                                                                                    <input type="text" class="form-control" id="seo_title"  name="seo_title" placeholder="Enter SEO Title" value="{{ old('seo_title')}}">
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="seo_keywords">SEO Keywords</label>
                                                                                    <input type="text" class="form-control"  name="seo_keywords" placeholder="Enter SEO Keywords" id="seo_keywords" value="{{ old('seo_keywords')}}">
                                                                                </fieldset>
                                                                                 <fieldset class="form-group">
                                                                                    <label for="seo_description">SEO Description</label>
                                                                                    <textarea class="form-control" id="seo_description" rows="3" name="seo_description" placeholder="SEO Description">{{ old('seo_description') }}</textarea>
                                                                                </fieldset>
                                                                               
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <fieldset class="form-group">
                                                                                    <label for="helperText">API to use</label>
                                                                                    <select class="form-control" name="api" id="api" required>
                                                                                        @foreach ($apis as $item)
                                                                                            <option value="{{ $item->id  }}" {{ old('api') == $item->id ? 'selected' : ''}}>{{ $item->name }}</option>
                                                                                        @endforeach
                                                                                    </select>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="servercode">Server Code/Server Token</label>
                                                                                    <input type="text" class="form-control" name="servercode" placeholder="Enter servercode" id="servercode" value="{{ old('servercode')}}">
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="multistep">Use Multistep</label>
                                                                                    <select class="form-control tiny" name="multistep" id="multistep">
                                                                                        <option value="">Select</option>
                                                                                        <option value="yes" {{ old('multistep') == 'yes' ? 'selected' : ''}}>Yes</option>
                                                                                        <option value="no" {{ old('multistep') == 'no' ? 'selected' : ''}}>No</option> 
                                                                                    </select>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="ussd_string">USSD String</label>
                                                                                    <input type="text" class="form-control tiny" id="ussd_string" name="ussd_string"  value="{{ old('ussd_string') }}">
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="status">Status</label>
                                                                                    <select class="form-control" name="status" id="status" required>
                                                                                        <option value="">Select</option>
                                                                                        <option value="active" {{ old('status') == 'active' ? 'selected' : ''}}>Active</option>
                                                                                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : ''}}>InActive</option>
                                                                                    </select>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="has_variations">Has Variations</label>
                                                                                    <select class="form-control" name="has_variations" id="has_variations" required>
                                                                                        <option value="">Select</option>
                                                                                        <option value="yes" {{ old('has_variations') == 'active' ? 'selected' : ''}}>Yes</option>
                                                                                        <option value="no" {{ old('has_variations') == 'inactive' ? 'selected' : ''}}>No</option>
                                                                                    </select>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="fixed_price">Fixed Price</label>
                                                                                    <select class="form-control tiny" name="fixed_price" id="fixed_price" required>
                                                                                        <option value="">Select</option>
                                                                                        <option value="yes" {{ old('fixed_price') == 'yes' ? 'selected' : ''}}>Yes</option>
                                                                                        <option value="no" {{ old('fixed_price') == 'no' ? 'selected' : ''}}>No</option>
                                                                                    </select>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="name">System Price</label>
                                                                                    <input type="number" class="form-control tiny" id="system_price" name="system_price"  value="{{ old('system_price') }}">
                                                                                </fieldset>
                                                                                @foreach($customerlevel as $level)
                                                                                <fieldset class="form-group">
                                                                                    <label for="name">{{ $level->name }} Price ({!! getSettings()['currency']!!})</label>
                                                                                    <input type="number" class="form-control tiny" id="productlevel" name="productlevel[{{ $level->id }}]"  value="">
                                                                                </fieldset>
                                                                                @endforeach
                                                                                <fieldset class="form-group">
                                                                                    <label for="min">Minimun Amount</label>
                                                                                    <input type="number" class="form-control tiny" id="min" name="min"  value="{{ old('min') }}">
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="max">Maimum Amount</label>
                                                                                    <input type="number" class="form-control tiny" id="max" name="max"  value="{{ old('max') }}">
                                                                                </fieldset>
                                                                                
                                                                                <input type="hidden" value="product.store" name="route">
                                                                            </div>
                                                                            <div class="col-md-12">
                                                                            <button class="btn btn-primary" type="submit">Submit</button>
                                
                                                                            </div>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                                <div class="tab-pane" id="variations" role="tabpanel" aria-labelledby="profile-tab-fill">
                                                                    <p>
                                                                        Tootsie roll oat cake I love bear claw I love caramels caramels halvah chocolate bar. Cotton candy
                                                                        gummi
                                                                        bears pudding pie apple pie cookie. Cheesecake jujubes lemon drops danish dessert I love caramels
                                                                        powder.
                                                                    </p>
                                                                </div>
                                                                
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

@endsection