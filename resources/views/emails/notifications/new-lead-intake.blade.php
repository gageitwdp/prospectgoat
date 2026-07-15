@component('mail::message')
# New Lead Inquiry Received

Hello {{ $notifiable->name }},

A new lead has been submitted through the intake form.

@component('mail::panel')
Name: {{ $lead->name }}

Email: {{ $lead->email }}

Phone: {{ $lead->phone }}

Lead Type: {{ ucwords($leadType) }}

Source: {{ ucwords($source) }}
@endcomponent

@component('mail::button', ['url' => $leadUrl, 'color' => 'primary'])
Open Lead
@endcomponent

Thanks,
{{ config('app.name') }}
@endcomponent
