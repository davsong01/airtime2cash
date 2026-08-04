@section('page-script')
    <script src="{{ asset('app-assets/vendors/js/editors/quill/highlight.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/editors/quill/quill.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('airtime2cash-product-form');
            var editorElement = document.getElementById('instruction-editor');
            var imageInput = document.getElementById('image');
            var imagePreview = document.getElementById('product-image-preview');
            var nameInput = document.getElementById('name');
            var namePreview = document.getElementById('product-name-preview');

            if (!form || !editorElement) {
                return;
            }

            var quill = new Quill(editorElement, {
                theme: 'snow',
                placeholder: 'Enter the steps customers should follow...',
                modules: { toolbar: '#toolbar-container' }
            });

            form.addEventListener('submit', function () {
                document.getElementById('instruction-content').value = quill.root.innerHTML;
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
        });
    </script>
@endsection
