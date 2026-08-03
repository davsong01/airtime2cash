
<?php $a = announcements('pop'); ?>
@if ($a?->status === 'active')
<button type="button" class="btn btn-outline-primary block d-none" data-toggle="modal" data-target="#border-less-pop" id="auto-pop-up">
    Launch Modal
</button>

<!--Basic Modal -->
<div class="modal fade text-left modal-borderless" id="border-less-pop" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered" role="announcement">
        <div class="modal-content" style="min-height: 240px">
            <div class="modal-header">
                <div class="d-flex justify-content-center align-items-center" style="flex-grow: 1" >
                    <svg xmlns="http://www.w3.org/2000/svg" height="42" viewBox="0 -960 960 960" width="57"><path style="color: red !important" d="M720-440v-80h160v80H720Zm48 280-128-96 48-64 128 96-48 64Zm-80-480-48-64 128-96 48 64-128 96ZM200-200v-160h-40q-33 0-56.5-23.5T80-440v-80q0-33 23.5-56.5T160-600h160l200-120v480L320-360h-40v160h-80Zm240-182v-196l-98 58H160v80h182l98 58Zm120 36v-268q27 24 43.5 58.5T620-480q0 41-16.5 75.5T560-346ZM300-480Z"/></svg>
                    <h1 class="modal-title d-inline-block" style="font-weight: bold; font-size: 24px">
                        Announcement
                    </h1>
                </div>
                {{-- <button type="button" class="close rounded-pill" data-dismiss="modal" aria-label="Close">
                    <i class="bx bx-x"></i>
                </button> --}}
            </div>
            <div class="modal-body">
                <p class="d-flex justify-content-center align-items-center">
                    {!! $a->message !!}
                </p>
            </div>
            <div class="modal-footer d-flex justify-content-center">
                <button type="button" class="btn btn-light-primary" data-dismiss="modal">
                    <i class="bx bx-x d-block d-sm-none"></i>
                    <span class="d-none d-sm-block">Close</span>
                </button>
            </div>
        </div>
    </div>
</div>

@section('page-script')
    <script>
        setTimeout(() => {
            $('#auto-pop-up').click();
        }, 3000);
    </script>
@endsection
@endif
