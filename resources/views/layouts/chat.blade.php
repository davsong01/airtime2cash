@if(!empty(getSettings()->whatsapp_number))
<a href="https://wa.me/{{getSettings()->whatsapp_number}}" class="whatsapp_float" target="_blank" rel="noopener noreferrer">
    <i class="fa fa-whatsapp whatsapp-icon"></i>
</a>
@endif