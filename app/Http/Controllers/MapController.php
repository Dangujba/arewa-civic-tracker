<?php

namespace App\Http\Controllers;

use App\Models\CivicCase;
use App\Models\Lga;
use App\Models\Sector;
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
        foreach (['lga_id', 'sector_id', 'verification_status', 'type', 'progress_status'] as $field) {
            if (isset($filters[$field])) $query->where($field, $filters[$field]);
        }
        if (isset($filters['date_from'])) $query->whereDate('observation_date', '>=', $filters['date_from']);
        if (isset($filters['date_to'])) $query->whereDate('observation_date', '<=', $filters['date_to']);
        if (!empty($filters['q'])) {
            $term = addcslashes(trim($filters['q']), '%_\\');
            $query->where(fn($q) => $q
                ->where('title', 'like', "%{$term}%")
                ->orWhere('reference', 'like', "%{$term}%")
                ->orWhere('community', 'like', "%{$term}%"));
        }
        $total = (clone $query)->count();
        $limit = 1000;
        $rows = $query->with(['lga:id,name', 'sector:id,name'])->latest('id')->limit($limit)->get();
        $features = $rows->map(static fn($case) => [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [(float) $case->longitude, (float) $case->latitude],
            ],
            'properties' => [
                'id' => $case->id,
                'reference' => $case->reference,
                'title' => $case->title,
                'type' => $case->type,
                'community' => $case->community,
                'verification_status' => $case->verification_status,
                'progress_status' => $case->progress_status,
                'observation_date' => $case->observation_date?->format('Y-m-d'),
                'lga' => $case->lga?->name,
                'sector' => $case->sector?->name,
                'url' => route('cases.show', $case),
            ],
        ])->values();
        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
            'meta' => [
                'total' => $total,
                'returned' => $features->count(),
                'limit' => $limit,
                'truncated' => $total > $limit,
                'visibility' => 'role-restricted',
            ],
        ])->header('Cache-Control', 'private, no-store');
    }
}
