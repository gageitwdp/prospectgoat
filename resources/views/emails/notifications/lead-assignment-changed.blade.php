@component('mail::message')
# Lead Assigned To You

Hello {{ $notifiable->name }},

A lead has been assigned to you.

@component('mail::panel')
Lead: {{ $lead->name }}

Email: {{ $lead->email }}

Phone: {{ $lead->phone }}

Previous Assignee: {{ $previousAssignee }}
@if ($assignedBy)

Assigned By: {{ $assignedBy }}
@endif
@endcomponent

@component('mail::button', ['url' => $leadUrl, 'color' => 'primary'])
View Lead
@endcomponent

Thanks,
{{ config('app.name') }}
@endcomponent
