@extends('layouts.app')
@section('title','Field Operations | Arewa Civic Tracker')
@section('section_heading','Field Operations')
@section('content')
<section class="dash-heading act-ops-heading">
  <div>
    <span class="eyebrow">ADMINISTRATIVE OVERSIGHT</span>
    <h1>Field Operations</h1>
    <p class="muted">Monitoring coverage, field officer activity, review workloads, and deadlines.</p>
  </div>
  <a class="btn-outline" href="{{ route('admin.operations.audit') }}"><i class="fa-solid fa-list-check" aria-hidden="true"></i> Activity Audit</a>
</section>

<div class="act-ops-workspace">
  <section class="dash-panel act-ops-panel" aria-labelledby="ops-coverage-title">
    <div class="dash-panel-heading act-ops-panel-head">
      <div>
        <h2 id="ops-coverage-title">LGA Coverage</h2>
        <p>Active focal person assignments across local government areas.</p>
      </div>
      <a href="{{ route('admin.users.index') }}" class="dash-text-link">Manage Assignments</a>
    </div>
    <div class="dash-table-wrap">
      <table class="act-ops-table">
        <thead><tr><th scope="col">Local Government</th><th scope="col">Active Focal Persons</th><th scope="col">Coverage</th></tr></thead>
        <tbody>
          @forelse($lgas as $lga)
            <tr>
              <td><strong>{{ $lga->name }}</strong></td>
              <td>{{ $lga->focal_count }}</td>
              <td>
                <span class="act-ops-status {{ $lga->focal_count>0?'is-assigned':'is-unassigned' }}">
                  {{ $lga->focal_count>0?'Assigned':'Unassigned' }}
                </span>
              </td>
            </tr>
          @empty
            <tr><td colspan="3" class="act-table-empty">No LGAs configured.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <div class="act-ops-paired">
    <section class="dash-panel act-ops-panel" aria-labelledby="ops-focals-title">
      <div class="dash-panel-heading act-ops-panel-head">
        <div><h2 id="ops-focals-title">Focal Person Performance</h2><p>Field reports and clarification requests by officer.</p></div>
      </div>
      <div class="dash-table-wrap">
        <table class="act-ops-table">
          <thead><tr><th scope="col">Officer</th><th scope="col">Assigned LGAs</th><th scope="col">Reports</th><th scope="col">Approved</th><th scope="col">Clarifications</th></tr></thead>
          <tbody>
            @forelse($focals as $officer)
              <tr>
                <td><strong>{{ $officer->name }}</strong>@unless($officer->is_active)<small class="act-ops-inactive">Inactive</small>@endunless</td>
                <td>{{ $officer->assignedLgas->pluck('name')->implode(', ') ?: 'Not Assigned' }}</td>
                <td>{{ $officer->reports_count }}</td>
                <td>{{ $officer->approved_reports_count }}</td>
                <td>{{ $officer->clarification_reports_count }}</td>
              </tr>
            @empty
              <tr><td colspan="5" class="act-table-empty">No focal persons yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    <section class="dash-panel act-ops-panel" aria-labelledby="ops-reviewers-title">
      <div class="dash-panel-heading act-ops-panel-head">
        <div><h2 id="ops-reviewers-title">MEAL Reviewer Workload</h2><p>Review allocation, active tasks, and overdue cases.</p></div>
      </div>
      <div class="dash-table-wrap">
        <table class="act-ops-table">
          <thead><tr><th scope="col">Reviewer</th><th scope="col">Assigned Total</th><th scope="col">Active</th><th scope="col">Overdue</th></tr></thead>
          <tbody>
            @forelse($reviewers as $reviewer)
              <tr>
                <td><strong>{{ $reviewer->name }}</strong>@unless($reviewer->is_active)<small class="act-ops-inactive">Inactive</small>@endunless</td>
                <td>{{ $reviewer->assigned_reviews_count }}</td>
                <td>{{ $reviewer->pending_reviews_count }}</td>
                <td>{{ $reviewer->overdue_reviews_count }}</td>
              </tr>
            @empty
              <tr><td colspan="4" class="act-table-empty">No MEAL officers yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <section class="dash-panel act-ops-panel" aria-labelledby="ops-overdue-title">
    <div class="dash-panel-heading act-ops-panel-head">
      <div><h2 id="ops-overdue-title">Overdue Reviews</h2><p>Up to 30 oldest overdue reviews needing attention.</p></div>
      <a href="{{ route('cases.queue') }}" class="dash-text-link">Verification Queue</a>
    </div>
    <div class="dash-table-wrap">
      <table class="act-ops-table">
        <thead><tr><th scope="col">Reference</th><th scope="col">LGA</th><th scope="col">Reviewer</th><th scope="col">Due Date</th><th scope="col">Action</th></tr></thead>
        <tbody>
          @forelse($overdue as $report)
            <tr>
              <td><strong>{{ $report->reference }}</strong></td>
              <td>{{ $report->lga?->name }}</td>
              <td>{{ $report->reviewer?->name??'Unassigned' }}</td>
              <td>{{ $report->review_due_at?->format('d M Y') }}</td>
              <td><a class="act-table-icon-link" href="{{ route('cases.show',$report) }}" aria-label="View {{ $report->reference }}" title="View Report"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a></td>
            </tr>
          @empty
            <tr><td colspan="5" class="act-table-empty">No overdue assigned reviews.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <section class="dash-panel act-ops-panel act-ops-policy" aria-labelledby="ops-policy-title">
    <div class="dash-panel-heading act-ops-panel-head">
      <div>
        <h2 id="ops-policy-title">Review Deadline Policy</h2>
        <p>Set review deadlines and account security requirements.</p>
      </div>
    </div>
    <form method="POST" action="{{ route('admin.operations.settings') }}" class="act-ops-policy-form" data-no-ajax>
      @csrf
      <div class="act-ops-policy-grid">
        <div class="act-ops-policy-field">
          <label for="review-sla">Default Review Period (Days)</label>
          <input id="review-sla" name="review_sla_days" type="number" min="1" max="30" required value="{{ old('review_sla_days',$settings?->review_sla_days??7) }}">
          <small>Applies only to newly assigned reviewers. Existing deadlines stay unchanged.</small>
        </div>
        <fieldset class="act-ops-security-fieldset">
          <legend>Two-Factor Authentication</legend>
          <label class="act-ops-check">
            <input type="hidden" name="require_mfa_admin" value="0">
            <input type="checkbox" name="require_mfa_admin" value="1" @checked(old('require_mfa_admin',$settings?->require_mfa_admin??false))>
            <span>Require 2FA for RAWYOD Administrators</span>
          </label>
          <label class="act-ops-check">
            <input type="hidden" name="require_mfa_meal" value="0">
            <input type="checkbox" name="require_mfa_meal" value="1" @checked(old('require_mfa_meal',$settings?->require_mfa_meal??false))>
            <span>Require 2FA for MEAL Reviewers</span>
          </label>
        </fieldset>
      </div>
      <p class="act-ops-policy-note">When enforced, users must activate an authenticator in Security Settings. At least one active administrator must enroll before administrator enforcement can be enabled.</p>
      <div class="act-ops-policy-actions"><button type="submit" class="btn"><i class="fa-solid fa-check" aria-hidden="true"></i> Save Policy</button></div>
    </form>
  </section>
</div>
@endsection
