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
                                    <li class="breadcrumb-item"><a href="{{ route('announcement.index') }}">Announcement</a>
                                    </li>
                                    <li class="breadcrumb-item active">Add new Announcement
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
                                    <h4 class="card-title">Add new Announcement</h4>
                                    <p>Add new pop-up announcement</p>
                                    @include('layouts.alerts')
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <form action="{{ route('announcement.store') }}" method="POST"
                                            enctype="multipart/form-data">
                                            @csrf
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <fieldset class="form-group">
                                                        <label for="title">Title</label>
                                                        <input type="text" class="form-control" id="title"
                                                            name="title" value="{{ old('title') }}"
                                                            placeholder="Enter announcement title" required>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="status">Status</label>
                                                        <select class="form-control" name="status" id="status" required>
                                                            <option value="">Select</option>
                                                            <option value="active"
                                                                {{ old('status') == 'active' ? 'selected' : '' }}>
                                                                Active</option>
                                                            <option value="inactive"
                                                                {{ old('status') == 'inactive' ? 'selected' : '' }}>
                                                                InActive</option>
                                                        </select>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="type">Announcement Type</label>
                                                        <select class="form-control" name="type" id="type" required>
                                                            <option value="">Select Type</option>
                                                            <option value="scroll"
                                                                {{ old('type') == 'scroll' ? 'selected' : '' }}>Scroll
                                                            </option>
                                                            <option value="popup"
                                                                {{ old('type') == 'popup' ? 'selected' : '' }}>
                                                                Popup</option>
                                                        </select>
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <label for="type">Announcement Content</label>
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
                                                        <div class="editor" style="min-height: 240px">
                                                            {!! old('message') !!}
                                                        </div>
                                                        <input name="message" type="hidden" id="content" />
                                                    </fieldset>
                                                    <fieldset class="form-group">
                                                        <button class="btn btn-primary" type="submit">Submit</button>
                                                    </fieldset>
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
    <link href="/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.0-rc.2/dist/quill.snow.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.0-rc.2/dist/quill.js"></script>
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css" />
    <script>
        let quill = new Quill('.editor', {
            theme: 'snow',
            toolbar: true,
            placeholder: 'Enter announcement content...',
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
