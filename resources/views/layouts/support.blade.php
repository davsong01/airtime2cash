@if(!empty(getSettings()->whatsapp_number))
<div class="text-center"><p> <br>
      <strong>For Support please contact <a target="_blank" href="https://api.whatsapp.com/send?phone={{getSettings()->whatsapp_number}}&text=Hello%20Admin">{{ getSettings()->whatsapp_number }} on whatsapp.</a></strong>
</p></div>
@endif

