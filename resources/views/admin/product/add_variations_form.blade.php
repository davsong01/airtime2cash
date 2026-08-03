<div class="modal fade text-left" id="primary" tabindex="-1" role="dialog" aria-labelledby="myModalLabel160" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title white" id="myModalLabel160">Add Variations for {{ $product->name }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="bx bx-x"></i>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{route('manual.variations.add', $product->id)}}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <section id="mode-holder">
                        <div class="row" id="mode-0">
                            <div class="col-md-3">
                                <fieldset class="form-group">
                                    <label for="name">System Name</label>
                                    <input type="text" class="form-control tiny" id="system_name" name="system_name[]"  value="" placeholder="Variation name" required>
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
                                    {{-- {{dd($product->category->discount_type== 'flat')}} --}}
                                    <label for="name">{{ $level->name }} @if($product->category->discount_type == 'flat') Discounted Price ({!! getSettings()['currency']!!}) @else Discounted Percentage (%) @endif</label>
                                    <input type="number" class="form-control tiny" id="level" step=".01" name="level[{{ $level->id }}][]" value="" required>
                                </fieldset>
                            </div>
                            @endforeach

                            <div class="col-md-2">
                                <fieldset class="form-group">
                                    <label style="color:white">S</label>
                                    <button style="color: white;" class="btn btn-sm btn-success form-control" style="padding: 8px;" type="button" id="add-mode"><i class="fa fa-plus"></i> Add More</button>
                                </fieldset>
                            </div>
                            <div class="col-md-12">
                            <hr style="height: 0px;border-color: #00cfdd;">
                            </div>
                        </div>
                    </section>
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-secondary" data-dismiss="modal">
                    <i class="bx bx-x d-block d-sm-none"></i>
                    <span class="d-none d-sm-block">Close</span>
                </button>
                <button type="submit" class="btn btn-primary ml-1"><span class="d-none d-sm-block">Submit</span>
                </button>
            </div>
            </form>
        </div>
    </div>
</div>