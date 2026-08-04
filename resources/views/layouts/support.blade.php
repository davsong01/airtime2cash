@if(!empty(getSettings()?->whatsapp_number))
<div class="text-center"><p> <br>
      <strong>For Support please <a class="btn btn-dark btn-sm" target="_blank" href="https://api.whatsapp.com/send?phone={{ getSettings()?->whatsapp_number }}&text=Hello%20Admin"> contact us</a> on whatsapp.</strong>
</p></div>
@endif
