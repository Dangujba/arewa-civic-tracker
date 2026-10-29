@extends('layouts.app')
@section('title','My Notifications | Arewa Civic Tracker')
@section('section_heading','Notifications')
@section('content')
<section class="dash-heading"><div><span class="eyebrow">ACTION CENTER</span><h1>Notifications</h1>
<p class="muted">{{ $unreadCount }} unread notices. Assignment and deadline alerts are only shown to authorized staff.</p></div></section>
<section class="dash-panel"><div class="dash-table-wrap">
<table><thead><tr><th>Received</th><th>Notification</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($notifications as $note)
<tr><td>{{ $note->created_at->format('d M Y H:i') }}</td>
<td><strong>{{ $note->data['title']??'Review notice' }}</strong>
<p class="muted">{{ $note->data['detail']??'' }} · {{ $note->data['reference']??'' }}</p></td>
<td>{{ $note->read_at?'Read':'Unread' }}</td>
<td><form method="POST" action="{{ route('notifications.read',$note->id) }}">@csrf
<button type="submit">{{ $note->read_at?'Open report':'Mark read & open' }}</button></form></td></tr>
@empty<tr><td colspan="4">No notifications yet.</td></tr>@endforelse
</tbody></table></div>{{ $notifications->links() }}</section>
@endsection
