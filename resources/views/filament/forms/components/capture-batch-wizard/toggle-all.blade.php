@php
    $id = uniqid();
@endphp
<a href="#" class="checkbox-toggle-all" onclick="event.preventDefault(); event.target.closest('.fi-section').querySelectorAll('input[type=checkbox]').forEach(checkbox => checkbox.click());">
    Alle auswählen
</a>


