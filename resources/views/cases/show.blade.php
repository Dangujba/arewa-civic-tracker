@extends('layouts.app')
@section('title','Report | Arewa Civic Tracker')
@section('content')
<p><a href="{{ route('cases.index') }}"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Reports</a></p><div class="card"><p class="muted">{{ $case->reference }} · {{ $case->lga->name }} · {{ $case->ward ? $case->ward.' · ' : '' }}{{ $case->community }}</p><h1>{{ $case->title }}</h1><div class="grid"><div><strong>Verification</strong><p>{{ \App\Models\WorkflowStatusLabel::label('verification',$case->verification_status) }}</p></div><div><strong>Progress</strong><p>{{ \App\Models\WorkflowStatusLabel::label('progress',$case->progress_status) }}</p></div><div><strong>Sector</strong><p>{{ $case->sector->name }}</p></div><div><strong>Observed</strong><p>{{ $case->observation_date->format('d M Y') }}</p></div></div>@if($case->category)<p><strong>Category:</strong> {{ $case->category->name }}</p>@endif
<h3>Description</h3><p>{{ $case->description }}</p><h3>Observations</h3><p>{{ $case->observations ?: 'None recorded' }}</p><h3>Duty bearers</h3><p>{{ $case->duty_bearers ?: 'None recorded' }}</p></div>
@if(auth()->user()->isAdmin())
<section class="card">
 <h2>Public transparency</h2>
 <p><strong>{{ $case->published_at?'Published for public viewing':'Not public' }}</strong></p>
 <p class="muted">Public pages display only approved report metadata. Exact GPS, descriptions, evidence, reporter identities, internal notes and follow-ups stay private. Review public title and community names for sensitive information before publishing.</p>
 @if($case->published_at)
 <p class="muted">Published {{ $case->published_at->format('d M Y H:i') }}</p>
 <form method="POST" action="{{ route('admin.cases.publication',$case) }}" data-no-ajax>@csrf
 <input type="hidden" name="action" value="unpublish">
 <button type="submit" class="btn-outline">Unpublish this report</button></form>
 @elseif($case->verification_status==='approved')
 <form method="POST" action="{{ route('admin.cases.publication',$case) }}" data-no-ajax>@csrf
 <input type="hidden" name="action" value="publish">
 <p><label><input type="checkbox" name="confirm_public" value="1" required> I reviewed the public fields and approve their publication.</label></p>
 <button type="submit">Publish on public dashboard</button></form>
 @else
 <p class="muted">Only approved reports may be published.</p>
 @endif
</section>
<section class="card act-legal-hold-panel">
 <h2>Legal Hold</h2>
 <p><strong>{{ $case->legal_hold?'Protection Active':'No Legal Hold' }}</strong></p>
 @if($case->legal_hold)<p class="muted">Reason: {{ $case->legal_hold_reason }}</p>@endif
 <form method="POST" action="{{ route('admin.governance.hold',$case) }}" data-no-ajax>@csrf
  <input type="hidden" name="hold" value="{{ $case->legal_hold?'0':'1' }}">
  <div class="field"><label for="hold-reason">{{ $case->legal_hold?'Reason for Releasing Hold':'Reason for Applying Hold' }}</label><textarea name="reason" id="hold-reason" required minlength="10" maxlength="3000" rows="2"></textarea></div>
  <button class="btn-outline" type="submit">{{ $case->legal_hold?'Release Legal Hold':'Apply Legal Hold' }}</button>
 </form>
</section>
@endif
<section class="card">
 <h2>Case Closure</h2>
 <p><strong>Case status:</strong> {{ $case->closure_status==='closed'?'Closed':'Open' }}</p>
 @if($case->closure_status==='closed')
 <p><strong>Closed:</strong> {{ $case->closed_at?->format('d M Y H:i') }} by {{ $case->closedBy?->name??'Administrator' }}</p>
 <p><strong>Closure reason:</strong> {{ $case->closure_reason }}</p>
 @else
 <p class="muted">Closure requires both administrator approval and progress marked Completed or Resolved.</p>
 @endif
 @if(auth()->user()->isAdmin())
 <form method="POST" action="{{ route('cases.closure',$case) }}" data-no-ajax>@csrf
 <input type="hidden" name="action" value="{{ $case->closure_status==='closed'?'reopen':'close' }}">
 <div class="field"><label for="case-closure-reason">{{ $case->closure_status==='closed'?'Reason for reopening':'Closure reason' }}</label>
 <textarea id="case-closure-reason" name="reason" minlength="10" maxlength="5000" required></textarea></div>
 <button type="submit" class="btn" @disabled($case->closure_status!=='closed'&&($case->verification_status!=='approved'||!in_array($case->progress_status,['completed','resolved'],true)))>
 {{ $case->closure_status==='closed'?'Reopen Case':'Close Case' }}</button>
 </form>
 @endif
</section>
@if(auth()->user()->isAdmin() && $case->closure_status!=='closed')
<section class="card"><h2>MEAL reviewer assignment</h2>
<p class="muted">Assign one active MEAL reviewer and a deadline. Only the assigned reviewer and RAWYOD administrators may change the review status; unassigned cases remain available in the MEAL queue.</p>
<form method="POST" action="{{ route('admin.reviews.assign',$case) }}">@csrf
<div class="grid"><div class="field"><label for="reviewer-assigned">Reviewer</label>
<select id="reviewer-assigned" name="reviewer_id"><option value="">Unassigned</option>
@foreach($reviewers as $reviewer)<option value="{{ $reviewer->id }}" @selected($case->reviewer_id==$reviewer->id)>{{ $reviewer->name }}</option>@endforeach
</select></div><div class="field"><label for="reviewer-due">Review due date</label><input id="reviewer-due" type="date" name="review_due_at" value="{{ $case->review_due_at?->format('Y-m-d') }}"></div></div>
<button type="submit">Save reviewer assignment</button></form></section>
@elseif($case->reviewer_id)
<p class="muted">Assigned MEAL reviewer: {{ $case->reviewer?->name }} @if($case->review_due_at) | Due {{ $case->review_due_at->format('d M Y') }} @endif</p>
@endif
@if(count($allowedTransitions))<div class="card"><h2>Review decision</h2><form method="post" action="{{ route('cases.review',$case) }}">@csrf<div class="field"><label>New verification state</label><select name="status">@foreach($allowedTransitions as $s)<option value="{{ $s }}">{{ \App\Models\WorkflowStatusLabel::label('verification',$s) }}</option>@endforeach</select></div><div class="field"><label>Review comment / reason</label><textarea name="comment"></textarea></div><button type="submit"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> Record decision</button></form></div>@endif
<div class="grid"><div class="card"><h2>Evidence</h2><p class="muted">Previews are optimized for fast viewing. Download original to inspect unmodified photographic detail.</p>@forelse($case->evidence as $e)
@if($e->preview_path)<div><a href="{{ route('evidence.download',$e) }}" title="Download original unmodified evidence"><img loading="lazy" src="{{ route('evidence.preview',$e) }}" alt="Optimized preview of {{ $e->original_name }}" style="display:block;max-width:100%;max-height:255px;object-fit:contain;margin-bottom:8px"></a></div>@endif
<p><a href="{{ route('evidence.download',$e) }}">{{ $e->original_name }}</a> <small class="muted">Original · {{ number_format($e->size_bytes/1024) }} KB</small></p>@empty<p>No evidence uploaded.</p>@endforelse
@if($case->closure_status!=='closed')<form method="post" enctype="multipart/form-data" action="{{ route('cases.evidence',$case) }}">@csrf<div class="field"><label>Add file (JPEG, PNG, WebP up to 10 MB; PDF up to 5 MB)</label><input type="file" name="evidence" accept="image/png,image/jpeg,image/webp,application/pdf" required></div><button>Upload evidence</button></form>@endif</div><div class="card"><h2>Follow-up</h2>@forelse($case->followups as $f)<p>{{ $f->note }} <small class="muted">{{ $f->created_at->format('d M Y') }}</small></p>@empty<p>No follow-up yet.</p>@endforelse
@if($case->closure_status!=='closed')<form method="post" action="{{ route('cases.followups',$case) }}">@csrf<div class="field"><label>Follow-up note</label><textarea name="note" required></textarea></div><button>Save follow-up</button></form>@endif</div></div>
@can('update',$case)<div class="card"><div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap"><div><h2 style="margin:0">Report Details</h2><p class="muted">Corrections are saved as a new version with a documented reason.</p></div><a class="btn" href="{{ route('cases.edit',$case) }}"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Edit Report</a></div></div>@endcan
<div class="card"><h2>Revision History</h2><p class="muted">Each version preserves the previous and updated field values.</p>
@forelse($case->revisions as $revision)<details style="padding:12px 0;border-bottom:1px solid #dce5dc"><summary style="cursor:pointer;font-weight:750">Version {{ $revision->version }} · {{ $revision->actor?->name??'Unknown' }} · {{ $revision->created_at?->format('d M Y H:i') }}</summary><p><strong>Reason:</strong> {{ $revision->reason }}</p><div class="dash-table-wrap"><table><thead><tr><th>Field</th><th>Before</th><th>After</th></tr></thead><tbody>@foreach($revision->changes as $field=>$change)<tr><td>{{ ucwords(str_replace('_',' ',$field)) }}</td><td>{{ $change['from']??'Not set' }}</td><td>{{ $change['to']??'Not set' }}</td></tr>@endforeach</tbody></table></div></details>
@empty<p>No revisions recorded.</p>@endforelse</div>
<div class="card"><h2>Audit history</h2><ol>@foreach($case->events as $event)<li>{{ $event->event_type }} by {{ $event->actor?->name ?? 'Unknown user' }} @if($event->to_status) to {{ $event->to_status }} @endif · {{ $event->created_at->format('d M Y H:i') }} @if($event->comment) · {{ $event->comment }} @endif</li>@endforeach</ol></div>
@if(auth()->id()===$case->reporter_id && $case->verification_status==='clarification_requested')
<section class="card"><h2>Respond to clarification</h2><form action="{{ route('cases.clarification',$case) }}" method="post">@csrf<div class="field"><label>Response<textarea name="response" required minlength="5"></textarea></label></div><button>Submit response</button></form></section>@endif
@if(auth()->user()->canReview() && $case->closure_status!=='closed')<section class="card"><h2>Update project / issue progress</h2><form action="{{ route('cases.progress',$case) }}" method="post">@csrf<div class="field"><label>Progress<select name="progress_status">@foreach(['observed','not_started','ongoing','stalled','completed','resolved','other'] as $status)<option value="{{ $status }}" @selected($case->progress_status===$status)>{{ \App\Models\WorkflowStatusLabel::label('progress',$status) }}</option>@endforeach</select></label></div><div class="field"><label>Reason or observation<textarea name="note" required minlength="5"></textarea></label></div><button>Save progress</button></form></section>@endif
@endsection
