@section('page-script')
    <script src="{{ asset('app-assets/vendors/js/editors/quill/highlight.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/editors/quill/quill.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('airtime2cash-product-form');
            var editorElement = document.getElementById('instruction-editor');
            var autoShareEditorElement = document.getElementById('auto-share-instruction-editor');
            var imageInput = document.getElementById('image');
            var imagePreview = document.getElementById('product-image-preview');
            var nameInput = document.getElementById('name');
            var namePreview = document.getElementById('product-name-preview');

            if (!form || !editorElement || !autoShareEditorElement) {
                return;
            }

            var quill = new Quill(editorElement, {
                theme: 'snow',
                placeholder: 'Enter the steps customers should follow...',
                modules: { toolbar: '#manual-instruction-toolbar' }
            });

            var autoShareQuill = new Quill(autoShareEditorElement, {
                theme: 'snow',
                placeholder: 'Enter the steps customers should follow for Auto Transfer...',
                modules: { toolbar: '#auto-share-instruction-toolbar' }
            });

            form.addEventListener('submit', function () {
                document.getElementById('instruction-content').value = quill.root.innerHTML;
                document.getElementById('auto-share-instruction-content').value = autoShareQuill.root.innerHTML;
            });

            nameInput.addEventListener('input', function () {
                namePreview.textContent = nameInput.value.trim() || 'New conversion product';
            });

            imageInput.addEventListener('change', function () {
                var file = imageInput.files && imageInput.files[0];
                if (!file) {
                    return;
                }

                imagePreview.src = URL.createObjectURL(file);
                imagePreview.style.display = 'block';
                imagePreview.nextElementSibling.style.display = 'none';
                imageInput.nextElementSibling.textContent = file.name;
            });

            @error('auto_share_rate')
                $('#auto-share-rate-tab').tab('show');
            @enderror
            @error('auto_share_profit_percentage')
                $('#auto-share-rate-tab').tab('show');
            @enderror
            @error('rate')
                $('#manual-rate-tab').tab('show');
            @enderror
            @error('manual_profit_percentage')
                $('#manual-rate-tab').tab('show');
            @enderror
            @if($errors->has('manual_level_rate.*'))
                $('#manual-rate-tab').tab('show');
            @endif
            @if($errors->has('auto_share_level_rate.*'))
                $('#auto-share-rate-tab').tab('show');
            @endif
            @if($errors->has('auto_share_product_code'))
                $('#auto-share-rate-tab').tab('show');
            @endif
        });
    </script>
@endsection
