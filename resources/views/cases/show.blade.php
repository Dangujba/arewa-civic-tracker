@extends('layouts.app')
@section('title','Report Details | Arewa Civic Tracker')
@section('section_heading','Report details')
@push('head')<link rel="stylesheet" href="{{ asset('css/act-case-detail.css') }}?v=20261103-evidence1">@endpush
@section('content')
<div class="act-case-report" id="act-case-report">
  <nav class="act-case-breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('cases.index') }}"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Monitoring reports</a>
    <span aria-hidden="true">/</span><span>{{ $case->reference }}</span>
  </nav>

  <header class="act-case-header">
    <div class="act-case-heading">
      <p class="act-case-overline"><i class="fa-regular fa-file-lines" aria-hidden="true"></i> {{ $case->type==='public_project'?'Public Project Monitoring':'Governance Issue Report' }}</p>
      <h1>{{ $case->title }}</h1>
      <div class="act-case-metadata">
        <span><i class="fa-solid fa-hashtag" aria-hidden="true"></i> {{ $case->reference }}</span>
        <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ $case->lga->name }}@if($case->ward), {{ $case->ward }}@endif · {{ $case->community }}</span>
      </div>
      <div class="act-case-statuses">
        <span class="act-case-status act-case-status-{{ $case->verification_status }}">
          <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
          {{ \App\Models\WorkflowStatusLabel::label('verification',$case->verification_status) }}
        </span>
        <span class="act-case-status act-case-progress">
          <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
          {{ \App\Models\WorkflowStatusLabel::label('progress',$case->progress_status) }}
        </span>
        @if($case->closure_status==='closed')<span class="act-case-status act-case-closed">Closed</span>@endif
      </div>
    </div>
    <div class="act-case-actions">
      @can('update',$case)
      <a class="btn" href="{{ route('cases.edit',$case) }}"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Edit report</a>
      @endcan
      <a class="btn-outline act-case-document-link" href="{{ route('cases.print',$case) }}" target="_blank" rel="noopener"><i class="fa-solid fa-print" aria-hidden="true"></i> Print View</a>
      <a class="btn act-case-document-link" href="{{ route('cases.pdf',$case) }}" data-report-export data-export-name="Arewa-Report-{{ $case->reference }}.pdf"><i class="fa-regular fa-file-pdf" aria-hidden="true"></i> Export PDF</a>
    </div>
  </header>

  <section class="act-case-overview" aria-label="Report summary">
    <div class="act-case-overview-item"><span class="act-case-label"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> Sector</span><strong>{{ $case->sector?->name ?? 'Not assigned' }}</strong></div>
    <div class="act-case-overview-item"><span class="act-case-label"><i class="fa-solid fa-calendar-day" aria-hidden="true"></i> Date observed</span><strong>{{ $case->observation_date?->format('d M Y') ?? 'Not recorded' }}<small class="act-observed-time">{{ $case->observation_time ? \Illuminate\Support\Carbon::parse($case->observation_time)->format('h:i A') : 'Time not recorded' }}</small></strong></div>
    <div class="act-case-overview-item"><span class="act-case-label"><i class="fa-regular fa-user" aria-hidden="true"></i> Submitted by</span><strong>{{ $case->reporter?->name ?? 'Field officer' }}</strong></div>
    <div class="act-case-overview-item"><span class="act-case-label"><i class="fa-regular fa-clock" aria-hidden="true"></i> Last updated</span><strong>{{ $case->updated_at?->format('d M Y, h:i A') ?? 'Not recorded' }}</strong></div>
  </section>

  <div class="act-case-content-grid">
    <article class="act-case-content-main">
      <section class="act-case-section">
        <div class="act-case-section-title"><i class="fa-regular fa-file-lines" aria-hidden="true"></i><h2>Description</h2></div>
        <div class="act-case-rich-content">{!! \App\Support\CaseRichText::render($case->description) !!}</div>
      </section>
      <section class="act-case-section">
        <div class="act-case-section-title"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i><h2>Field observations</h2></div>
        <div class="act-case-paragraph">@if($case->observations){{ $case->observations }}@else<span class="act-case-empty">No observations recorded.</span>@endif</div>
      </section>
      <section class="act-case-section">
        <div class="act-case-section-title"><i class="fa-solid fa-people-group" aria-hidden="true"></i><h2>Stakeholders and duty bearers</h2></div>
        <div class="act-case-paragraph">@if($case->duty_bearers){{ $case->duty_bearers }}@else<span class="act-case-empty">No duty bearers recorded.</span>@endif</div>
      </section>
    </article>

    <aside class="act-case-content-side">
      <section class="act-case-facts" aria-label="Report information">
        <div class="act-case-section-title"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><h2>Report information</h2></div>
        <dl>
          <div><dt>Local government</dt><dd>{{ $case->lga->name }}</dd></div>
          <div><dt>Ward</dt><dd>{{ $case->ward ?: 'Not specified' }}</dd></div>
          <div><dt>Community</dt><dd>{{ $case->community }}</dd></div>
          <div><dt>Category</dt><dd>{{ $case->category?->name ?? 'Not specified' }}</dd></div>
          <div><dt>Verification</dt><dd>{{ \App\Models\WorkflowStatusLabel::label('verification',$case->verification_status) }}</dd></div>
          <div><dt>Report lifecycle</dt><dd>{{ $case->closure_status==='closed'?'Closed':'Open' }}</dd></div>
          @if($case->latitude!==null&&$case->longitude!==null)
          <div><dt>GPS coordinates</dt><dd>{{ $case->latitude }}, {{ $case->longitude }}</dd></div>
          @endif
        </dl>
      </section>
    </aside>
  </div>
</div>

<section class="act-case-evidence-panel" id="case-evidence">
  <div class="act-case-evidence-heading">
    <div><span class="act-case-label">Supporting materials</span><h2><i class="fa-solid fa-images" aria-hidden="true"></i> Evidence &amp; attachments</h2>
    <p>Private to authorized staff. Preview images are optimized; download the original file to examine the unmodified evidence.</p></div>
    <span class="act-evidence-total">{{ $case->evidence->count() }} file(s)</span>
  </div>
  <div class="act-evidence-gallery">
    @forelse($case->evidence as $e)
    <article class="act-evidence-item">
      @if($e->preview_path)
        <a class="act-evidence-thumb" href="{{ route('evidence.download',$e) }}" title="Download original evidence"><img loading="lazy" src="{{ route('evidence.preview',$e) }}" alt="Preview of {{ $e->original_name }}"></a>
      @else
        <a class="act-evidence-thumb act-evidence-file" href="{{ route('evidence.download',$e) }}"><i class="fa-regular fa-file-pdf" aria-hidden="true"></i></a>
      @endif
      <div class="act-evidence-caption">
        <a href="{{ route('evidence.download',$e) }}" download><i class="fa-solid fa-download" aria-hidden="true"></i> {{ $e->original_name }}</a>
        <small>{{ number_format($e->size_bytes/1024) }} KB · Original file</small>
      </div>
    </article>
    @empty
      <p class="act-case-empty">No evidence has been uploaded for this report.</p>
    @endforelse
  </div>
  @if($case->closure_status!=='closed')
    <form class="act-case-evidence-upload act-case-action-form" method="POST" enctype="multipart/form-data" action="{{ route('cases.evidence',$case) }}" data-no-ajax>
      @csrf
      <div class="field"><label for="additional-evidence">Add supporting evidence (JPG, PNG, WebP up to 10 MB; PDF up to 5 MB)</label>
      <input id="additional-evidence" type="file" name="evidence" accept="image/png,image/jpeg,image/webp,application/pdf" required></div>
      <button type="submit" class="act-case-save-btn"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i><span>Upload Evidence</span></button>
    </form>
  @endif
</section>

@if(auth()->user()->isAdmin())
<section class="card">
 <h2>Public transparency</h2>
 <p><strong>{{ $case->published_at?'Published for public viewing':'Not public' }}</strong></p>
 <p class="muted">Public pages display only approved report metadata. Exact GPS, descriptions, evidence, reporter identities, internal notes and follow-ups stay private. Review public title and community names for sensitive information before publishing.</p>
 @if($case->published_at)
 <p class="muted">Published {{ $case->published_at->format('d M Y H:i') }}</p>
 <form method="POST" action="{{ route('admin.cases.publication',$case) }}" data-no-ajax>@csrf
 <input type="hidden" name="action" value="unpublish">
 <button type="submit" class="act-case-save-btn"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i><span>Unpublish Report</span></button></form>
 @elseif($case->verification_status==='approved')
 <form method="POST" action="{{ route('admin.cases.publication',$case) }}" data-no-ajax>@csrf
 <input type="hidden" name="action" value="publish">
 <p><label><input type="checkbox" name="confirm_public" value="1" required> I reviewed the public fields and approve their publication.</label></p>
 <button type="submit" class="act-case-save-btn"><i class="fa-solid fa-globe" aria-hidden="true"></i><span>Publish Report</span></button></form>
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
  <button class="act-case-save-btn" type="submit"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><span>{{ $case->legal_hold?'Release Legal Hold':'Apply Legal Hold' }}</span></button>
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
 <button type="submit" class="act-case-save-btn" @disabled($case->closure_status!=='closed'&&($case->verification_status!=='approved'||!in_array($case->progress_status,['completed','resolved'],true)))>
 <i class="fa-solid fa-lock" aria-hidden="true"></i><span>{{ $case->closure_status==='closed'?'Reopen Case':'Close Case' }}</span></button>
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
<button type="submit" class="act-case-save-btn"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i><span>Save Reviewer Assignment</span></button></form></section>
@elseif($case->reviewer_id)
<p class="muted">Assigned MEAL reviewer: {{ $case->reviewer?->name }} @if($case->review_due_at) | Due {{ $case->review_due_at->format('d M Y') }} @endif</p>
@endif
@if(count($allowedTransitions))<div class="card"><h2>Review decision</h2><form method="post" action="{{ route('cases.review',$case) }}">@csrf<div class="field"><label>New verification state</label><select name="status">@foreach($allowedTransitions as $s)<option value="{{ $s }}">{{ \App\Models\WorkflowStatusLabel::label('verification',$s) }}</option>@endforeach</select></div><div class="field"><label>Review comment / reason</label><textarea name="comment"></textarea></div><button type="submit" class="act-case-save-btn"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i><span>Record Decision</span></button></form></div>@endif

<section class="card act-case-secondary-section">
  <h2><i class="fa-solid fa-comments" aria-hidden="true"></i> Follow-up</h2>
  @forelse($case->followups as $f)
    <p>{{ $f->note }} <small class="muted">{{ $f->created_at->format('d M Y, h:i A') }}</small></p>
  @empty
    <p class="muted">No follow-up yet.</p>
  @endforelse
  @if($case->closure_status!=='closed')
    <form class="act-case-action-form" method="POST" action="{{ route('cases.followups',$case) }}" data-no-ajax>
      @csrf
      <div class="field"><label for="case-followup-note">Follow-up note</label><textarea id="case-followup-note" name="note" minlength="5" required></textarea></div>
      <button type="submit" class="act-case-save-btn"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i><span>Save Follow-up</span></button>
    </form>
  @endif
</section>

@can('update',$case)<div class="card"><div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap"><div><h2 style="margin:0">Report Details</h2><p class="muted">Corrections are saved as a new version with a documented reason.</p></div><a class="btn" href="{{ route('cases.edit',$case) }}"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Edit Report</a></div></div>@endcan
<div class="card"><h2>Revision History</h2><p class="muted">Each version preserves the previous and updated field values.</p>
@forelse($case->revisions as $revision)<details style="padding:12px 0;border-bottom:1px solid #dce5dc"><summary style="cursor:pointer;font-weight:750">Version {{ $revision->version }} · {{ $revision->actor?->name??'Unknown' }} · {{ $revision->created_at?->format('d M Y H:i') }}</summary><p><strong>Reason:</strong> {{ $revision->reason }}</p><div class="dash-table-wrap"><table><thead><tr><th>Field</th><th>Before</th><th>After</th></tr></thead><tbody>@foreach($revision->changes as $field=>$change)<tr><td>{{ ucwords(str_replace('_',' ',$field)) }}</td><td>{{ $change['from']??'Not set' }}</td><td>{{ $change['to']??'Not set' }}</td></tr>@endforeach</tbody></table></div></details>
@empty<p>No revisions recorded.</p>@endforelse</div>
<div class="card"><h2>Audit history</h2><ol>@foreach($case->events as $event)<li>{{ $event->event_type }} by {{ $event->actor?->name ?? 'Unknown user' }} @if($event->to_status) to {{ $event->to_status }} @endif · {{ $event->created_at->format('d M Y H:i') }} @if($event->comment) · {{ $event->comment }} @endif</li>@endforeach</ol></div>
@if(auth()->id()===$case->reporter_id && $case->verification_status==='clarification_requested')
<section class="card"><h2>Respond to clarification</h2><form action="{{ route('cases.clarification',$case) }}" method="post">@csrf<div class="field"><label>Response<textarea name="response" required minlength="5"></textarea></label></div><button>Submit response</button></form></section>@endif
@if(auth()->user()->canReview() && $case->closure_status!=='closed')<section class="card"><h2>Update project / issue progress</h2><form action="{{ route('cases.progress',$case) }}" method="post">@csrf<div class="field"><label>Progress<select name="progress_status">@foreach(['observed','not_started','ongoing','stalled','completed','resolved','other'] as $status)<option value="{{ $status }}" @selected($case->progress_status===$status)>{{ \App\Models\WorkflowStatusLabel::label('progress',$status) }}</option>@endforeach</select></label></div><div class="field"><label>Reason or observation<textarea name="note" required minlength="5"></textarea></label></div><button type="submit" class="act-case-save-btn"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i><span>Save Progress</span></button></form></section>@endif
@endsection
