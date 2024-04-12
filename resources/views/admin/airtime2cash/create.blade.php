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
                                    <li class="breadcrumb-item"><a href="{{ route('airtime2cash.index') }}">Airtime to cash products</a>
                                    </li>
                                    <li class="breadcrumb-item active">Add Airtime to cash product
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
                                                        <h4 class="card-title">Add Airtime to cash Product</h4>
                                                        @include('layouts.alerts')
                                                    </div>
                                                    <div class="card-content">
                                                        <div class="card-body">
                                                            <p>
                                                                
                                                            </p>
                                                            
                                                            <!-- Tab panes -->
                                                            <div class="tab-content pt-1">
                                                                <div class="tab-pane active" id="product-details" role="tabpanel" aria-labelledby="home-tab-fill">
                                                                    <form action="{{route('airtime2cash.store')}}" method="POST" enctype="multipart/form-data">
                                                                        @csrf
                                                                        <div class="row">
                                                                            <div class="col-md-6">
                                                                                <fieldset class="form-group">
                                                                                    <label for="name">Name</label>
                                                                                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter name" value="{{ old('name')}}" required>
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
                                                                                        {{-- <option value="">Select</option> --}}
                                                                                        @foreach ($categories as $category)
                                                                                            <option value="{{ $category->id  }}" {{ old('category') == $category->id ? 'selected' : ''}}>{{ $category->name }}</option>
                                                                                        @endforeach
                                                                                    </select>
                                                                                </fieldset>
                                                                                
                                                                                {{-- <fieldset class="form-group" id="referral_percentage">
                                                                                    <label for="referral_percentage">Referral Percentage(%)</label>
                                                                                    <input type="number" class="form-control" id="referral_percentage" step="0.01" name="referral_percentage" value="{{ old('referral_percentage') }}" placeholder="Enter percentage for referral earnings">
                                                                                </fieldset> --}}
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

                                                                                    </div>
                                                                                    <input name="instruction" type="hidden" id="content" />
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
                                                                                        <option value="active" {{ old('status') == 'active' ? 'selected' : ''}}>Active</option>
                                                                                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : ''}}>InActive</option>
                                                                                    </select>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="fixed_price">Fixed Price</label>
                                                                                    <select class="form-control tiny" name="fixed_price" id="fixed_price" required>
                                                                                        {{-- <option value="">Select</option> --}}
                                                                                        {{-- <option value="yes" {{ old('fixed_price') == 'yes' ? 'selected' : ''}}>Yes</option> --}}
                                                                                        <option value="no" {{ old('fixed_price') == 'no' ? 'selected' : ''}}>No</option>
                                                                                    </select>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="name">Charge Rate (%)</label>
                                                                                    <input type="number" class="form-control tiny" id="rate" name="rate"  value="{{ old('rate') }}">
                                                                                </fieldset>
                                                                                @foreach($customerlevel as $level)
                                                                                <fieldset class="form-group">
                                                                                    <label for="name">{{ $level->name }} Charge Rate (%)</label>
                                                                                    <input type="number" class="form-control tiny" id="productlevel" name="productlevel[{{ $level->id }}]"  value="">
                                                                                </fieldset>
                                                                                @endforeach
                                                                                <fieldset class="form-group">
                                                                                    <label for="min">Minimun Amount</label>
                                                                                    <input type="number" class="form-control tiny" id="min" name="min"  value="{{ old('min') }}" required>
                                                                                </fieldset>
                                                                                <fieldset class="form-group">
                                                                                    <label for="max">Maximum Amount</label>
                                                                                    <input type="number" class="form-control tiny" id="max" name="max"  value="{{ old('max') }}" required>
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
    let quill = new Quill('.editor', {
        theme: 'snow',
        toolbar: true,
        placeholder: 'Enter instruction...',
        modules: {
            syntax: true,
            toolbar: '#toolbar-container',
        },
    });
    
    $('form').on('submit', () => {
        var myEditor = document.querySelector('.editor')
        var html = myEditor.children[0].innerHTML;
        $('#content').val(html);
    });
</script>
@endsection