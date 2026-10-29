<?php
namespace App\Http\Controllers;

use App\Models\CivicCase;
use App\Models\Lga;
use App\Models\Sector;
use App\Models\WorkflowStatusLabel;
use Illuminate\Http\Request;

class MapController extends Controller
{
    public function page(Request $request)
    {
        abort_unless((bool) $request->user()->is_active, 403);

        return view('map.index', [
            'lgas' => Lga::orderBy('name')->get(['id', 'name']),
            'sectors' => Sector::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function data(Request $request)
    {
        abort_unless((bool) $request->user()->is_active, 403);
        $filters = $request->validate([
            'lga_id' => ['nullable', 'integer', 'exists:lgas,id'],
            'sector_id' => ['nullable', 'integer', 'exists:sectors,id'],
            'verification_status' => ['nullable', 'in:submitted,under_review,clarification_requested,verified,approved,rejected'],
            'type' => ['nullable', 'in:public_project,governance_issue'],
            'progress_status' => ['nullable', 'in:observed,not_started,ongoing,stalled,completed,resolved,other'],
            'date_from' => ['nullable', 'date', 'before_or_equal:today'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $query = CivicCase::visibleTo($request->user())
            ->whereNotNull('latitude')->whereNotNull('longitude');
        foreach (['lga_id', 'sector_id', 'verification_status', 'type', 'progress_status'] as $key) {
            if (isset($filters[$key])) $query->where($key, $filters[$key]);
        }
        if (isset($filters['date_from'])) $query->whereDate('observation_date', '>=', $filters['date_from']);
        if (isset($filters['date_to'])) $query->whereDate('observation_date', '<=', $filters['date_to']);
        if (!empty($filters['q'])) {
            $search = addcslashes(trim($filters['q']), '%_\\');
            $query->where(fn($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('reference', 'like', "%{$search}%")
                ->orWhere('community', 'like', "%{$search}%"));
        }
        $total = (clone $query)->count();
        $max = 1000;
        $rows = $query->with(['lga:id,name', 'sector:id,name'])
            ->orderByDesc('id')->limit($max)->get();
        $verificationLabels = WorkflowStatusLabel::labels('verification');
        $progressLabels = WorkflowStatusLabel::labels('progress');
        $features = $rows->map(static function ($case) use ($verificationLabels,$progressLabels) {
            return [
                'type' => 'Feature',
                'geometry' => ['type' => 'Point', 'coordinates' => [(float) $case->longitude, (float) $case->latitude]],
                'properties' => [
                    'id' => $case->id,
                    'reference' => $case->reference,
                    'title' => $case->title,
                    'type' => $case->type,
                    'verification_status' => $case->verification_status,
                    'progress_status' => $case->progress_status,
                    'verification_label' => $verificationLabels[$case->verification_status] ?? $case->verification_status,
                    'progress_label' => $progressLabels[$case->progress_status] ?? $case->progress_status,
                    'lga' => $case->lga?->name,
                    'sector' => $case->sector?->name,
                    'community' => $case->community,
                    'observation_date' => $case->observation_date?->format('Y-m-d'),
                    'url' => route('cases.show', $case),
                ],
            ];
        })->values();

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
            'meta' => [
                'returned' => $features->count(),
                'total' => $total,
                'limit' => $max,
                'truncated' => $total > $max,
                'visibility' => 'role-restricted',
            ],
        ])->header('Cache-Control', 'private, no-store');
    }
}
