<div class="contact-page">
    <div class="contact-header">
        <h2>{{ $contact->firstname }} {{ $contact->lastname }}</h2>
        <div class="contact-id">ID: #{{ $contact->id }}</div>
    </div>

    <div class="contact-content">
        <div class="info-section">
            <h3>Personal Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">First Name:</span>
                    <span class="value">{{ $contact->firstname }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Last Name:</span>
                    <span class="value">{{ $contact->lastname }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Birth Date:</span>
                    <span class="value">{{ $contact->birthdate?->format('F j, Y') ?? 'Not provided' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Contact Type:</span>
                    <span class="value">{{ $contact->contactType?->name ?? 'Not specified' }}</span>
                </div>
            </div>
        </div>

        <div class="info-section">
            <h3>Address Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">Street & Number:</span>
                    <span class="value">{{ $contact->street_no }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Postal Code:</span>
                    <span class="value">{{ $contact->zipcode?->code ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">City:</span>
                    <span class="value">{{ $contact->zipcode?->name ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Language:</span>
                    <span class="value">{{ $contact->zipcode?->commune?->lang ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <div class="info-section">
            <h3>Additional Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">Sheet:</span>
                    <span class="value">{{ $contact->sheet?->label ?? 'No sheet assigned' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Letter Status:</span>
                    <span class="value status-{{ $contact->letter_sent ? 'sent' : 'not-sent' }}">
                        {{ $contact->letter_sent ? 'Letter Sent' : 'Letter Not Sent' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="contact-footer">
        <small>Generated on {{ now()->format('F j, Y \a\t g:i A') }}</small>
    </div>
</div>