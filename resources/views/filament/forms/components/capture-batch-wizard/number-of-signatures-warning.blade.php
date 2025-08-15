@if (!$this->checkSignatureCount())
<div style="border: red 2px solid; padding: 10px; border-radius: 5px;">
    <p>
        <b>Achtung:</b> Die Anzahl der gültigen und ungültigen Unterschriften stimmt nicht mit dem erfassten Total für die ausgewählten Bögen überein. Du kannst das trotzdem so speichern, aber ev. solltest du noch einmal nachzählen.
    </p>
</div>
@endif
