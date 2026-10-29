<?php
namespace App\Http\Controllers;

use App\Models\CivicCase;
use App\Models\Lga;
use App\Models\Sector;
use App\Models\WorkflowStatusLabel;
use App\Services\ReportChartRenderer;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class ReportsController extends Controller
{
    private function filtered(Request $request)
    {
        abort_unless($request->user()->canReview(), 403);
        $f=$request->validate([
            'lga_id'=>['nullable','integer','exists:lgas,id'],
            'sector_id'=>['nullable','integer','exists:sectors,id'],
            'ward'=>['nullable','string','max:150'],
            'community'=>['nullable','string','max:150'],
            'verification_status'=>['nullable','in:submitted,under_review,clarification_requested,verified,approved,rejected'],
            'type'=>['nullable','in:public_project,governance_issue'],
            'date_from'=>['nullable','date','before_or_equal:today'],
            'date_to'=>['nullable','date','after_or_equal:date_from','before_or_equal:today'],
            'q'=>['nullable','string','max:150'],
        ]);
        $query=CivicCase::visibleTo($request->user());
        foreach(['lga_id','sector_id','verification_status','type'] as $key)if(isset($f[$key]))$query->where($key,$f[$key]);
        if(!empty($f['ward']))$query->whereRaw('LOWER(TRIM(ward)) = ?', [mb_strtolower(trim($f['ward']))]);
        if(!empty($f['community']))$query->whereRaw('LOWER(TRIM(community)) = ?', [mb_strtolower(trim($f['community']))]);
        if(isset($f['date_from']))$query->whereDate('observation_date','>=',$f['date_from']);
        if(isset($f['date_to']))$query->whereDate('observation_date','<=',$f['date_to']);
        if(!empty($f['q'])){
            $search=addcslashes(trim($f['q']),'%_\\');
            $query->where(fn($q)=>$q->where('title','like',"%{$search}%")->orWhere('reference','like',"%{$search}%")->orWhere('ward','like',"%{$search}%")->orWhere('community','like',"%{$search}%"));
        }
        return $query;
    }

    public function index(Request $request)
    {
        $base=$this->filtered($request);
        $totals=(clone $base)->selectRaw("COUNT(*) AS total, SUM(CASE WHEN type='public_project' THEN 1 ELSE 0 END) AS projects, SUM(CASE WHEN verification_status='approved' THEN 1 ELSE 0 END) AS approved, SUM(CASE WHEN verification_status IN ('submitted','under_review','clarification_requested') THEN 1 ELSE 0 END) AS pending")->first();
        $byLga=(clone $base)->select('lga_id',DB::raw('COUNT(*) AS total'))->with('lga:id,name')->groupBy('lga_id')->orderByDesc('total')->limit(17)->get();
        $bySector=(clone $base)->select('sector_id',DB::raw('COUNT(*) AS total'))->with('sector:id,name')->groupBy('sector_id')->orderByDesc('total')->limit(20)->get();
        $byCommunity=$this->communityRanking($base,20);
        $byStatus=(clone $base)->select('verification_status',DB::raw('COUNT(*) AS total'))->groupBy('verification_status')->orderByDesc('total')->get();
        $byProgress=(clone $base)->select('progress_status',DB::raw('COUNT(*) AS total'))->groupBy('progress_status')->orderByDesc('total')->get();
        $monthLabels=[];
        $monthlyCounts=[];
        for($month=11;$month>=0;$month--){
            $date=now()->subMonthsNoOverflow($month)->startOfMonth();
            $monthLabels[]=$date->format('M Y');
            $monthlyCounts[$date->format('Y-m')]=0;
        }
        // Database-neutral iteration supports both the staging MySQL server and the test suite.
        foreach((clone $base)->whereDate('observation_date','>=',now()->subMonthsNoOverflow(11)->startOfMonth())->select('observation_date')->cursor() as $entry){
            $key=$entry->observation_date?->format('Y-m');
            if(isset($monthlyCounts[$key]))$monthlyCounts[$key]++;
        }
        $recent=(clone $base)->with(['lga:id,name','sector:id,name'])->latest()->paginate(20)->withQueryString();
        $verificationLabels=WorkflowStatusLabel::labels('verification');
        $progressLabels=WorkflowStatusLabel::labels('progress');
        $statusDisplayLabels=$byStatus->map(fn($row)=>$verificationLabels[$row->verification_status]??ucwords(str_replace('_',' ',$row->verification_status)))->values();
        return view('reports.index',[
            'totals'=>$totals,'byLga'=>$byLga,'bySector'=>$bySector,'byCommunity'=>$byCommunity,'byStatus'=>$byStatus,'byProgress'=>$byProgress,'verificationLabels'=>$verificationLabels,'progressLabels'=>$progressLabels,'statusDisplayLabels'=>$statusDisplayLabels,'monthLabels'=>$monthLabels,'monthlyCounts'=>array_values($monthlyCounts),'recent'=>$recent,
            'lgas'=>Lga::orderBy('name')->get(['id','name']),'sectors'=>Sector::orderBy('name')->get(['id','name'])
        ]);
    }

    private function communityRanking($query,int $limit)
    {
        // Community names are scoped by LGA so identical names do not merge across districts.
        return (clone $query)->whereNotNull('community')->whereRaw("TRIM(community) <> ''")
            ->select('lga_id')
            ->selectRaw('MIN(TRIM(community)) AS community, COUNT(*) AS total')
            ->with('lga:id,name')
            ->groupBy('lga_id')->groupByRaw('LOWER(TRIM(community))')
            ->orderByDesc('total')->limit($limit)->get();
    }

    public function excel(Request $request)
    {
        $query=$this->filtered($request)->with(['lga:id,name','sector:id,name'])->orderBy('id');
        abort_if((clone $query)->count()>10000,422,'Please narrow your filters to 10,000 records or fewer before exporting to Excel.');
        $sheetFile=tempnam(sys_get_temp_dir(),'act-xlsx-');
        if($sheetFile===false)abort(500,'Unable to prepare export.');
        $workbook=new Spreadsheet();
        $verificationLabels=WorkflowStatusLabel::labels('verification');
        $progressLabels=WorkflowStatusLabel::labels('progress');
        try {
            $sheet=$workbook->getActiveSheet()->setTitle('Field Reports');
            $headings=['Reference','Type','Title','LGA','Ward','Community','Sector','Observed Date','Verification','Progress','Description','Latitude','Longitude'];
            foreach($headings as $col=>$value)$sheet->setCellValueExplicit([$col+1,1],$value,DataType::TYPE_STRING);
            $row=2;
            foreach($query->cursor() as $case) {
                $values=[
                    $case->reference,$case->type,$case->title,$case->lga?->name,$case->ward,$case->community,$case->sector?->name,
                    $case->observation_date?->format('Y-m-d'),$verificationLabels[$case->verification_status]??$case->verification_status,$progressLabels[$case->progress_status]??$case->progress_status,
                    $case->description,$case->latitude,$case->longitude
                ];
                foreach($values as $col=>$value)$sheet->setCellValueExplicit([$col+1,$row],(string)($value??''),DataType::TYPE_STRING);
                if(++$row>10001)break;
            }
            $sheet->getStyle('A1:M1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle('A1:L1')->getFill()->setFillType('solid')->getStartColor()->setRGB('15563C');
            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:M'.max(1,$row-1));
            foreach(range('A','M') as $c)$sheet->getColumnDimension($c)->setWidth(in_array($c,['C','J'])?45:19);
            (new Xlsx($workbook))->save($sheetFile);
        }catch(\Throwable $e){@unlink($sheetFile);throw $e;}
        finally{$workbook->disconnectWorksheets();}
        return response()->download($sheetFile,'arewa-monitoring-'.now()->format('Ymd').'.xlsx',[
            'Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control'=>'private, no-store',
        ])->deleteFileAfterSend(true);
    }

    public function pdf(Request $request, ReportChartRenderer $charts)
    {
        // All PDF figures and tables derive from the exact same authorized filter scope.
        $query = $this->filtered($request);
        $total = (clone $query)->count();
        $rows = (clone $query)->with(['lga:id,name','sector:id,name'])
            ->orderByDesc('observation_date')->orderByDesc('id')->limit(150)->get();

        $summary = (clone $query)->select('verification_status', DB::raw('COUNT(*) AS total'))
            ->groupBy('verification_status')->pluck('total', 'verification_status');
        $types = (clone $query)->selectRaw(
            "SUM(CASE WHEN type='public_project' THEN 1 ELSE 0 END) AS projects, ".
            "SUM(CASE WHEN verification_status='approved' THEN 1 ELSE 0 END) AS approved, ".
            "SUM(CASE WHEN verification_status IN ('submitted','under_review','clarification_requested') THEN 1 ELSE 0 END) AS pending"
        )->first();

        $byLga = (clone $query)->select('lga_id', DB::raw('COUNT(*) AS total'))
            ->with('lga:id,name')->groupBy('lga_id')->orderByDesc('total')->limit(8)->get();
        $bySector = (clone $query)->select('sector_id', DB::raw('COUNT(*) AS total'))
            ->with('sector:id,name')->groupBy('sector_id')->orderByDesc('total')->limit(8)->get();
        $byCommunity = $this->communityRanking($query,8);

        $monthLabels = [];
        $monthlyCounts = [];
        for ($month = 11; $month >= 0; $month--) {
            $date = now()->subMonthsNoOverflow($month)->startOfMonth();
            $monthLabels[] = $date->format('M y');
            $monthlyCounts[$date->format('Y-m')] = 0;
        }
        foreach ((clone $query)->whereDate('observation_date', '>=', now()->subMonthsNoOverflow(11)->startOfMonth())
            ->select('observation_date')->cursor() as $entry) {
            $key = $entry->observation_date?->format('Y-m');
            if (isset($monthlyCounts[$key])) $monthlyCounts[$key]++;
        }

        $statusNames = array_replace([
            'submitted'=>'Submitted', 'under_review'=>'Under review',
            'clarification_requested'=>'Clarification', 'verified'=>'Verified',
            'approved'=>'Approved', 'rejected'=>'Rejected',
        ], WorkflowStatusLabel::labels('verification'));
        $statusPalette = [
            'submitted'=>'#b38943', 'under_review'=>'#447fa1',
            'clarification_requested'=>'#c47b49', 'verified'=>'#348c72',
            'approved'=>'#206447', 'rejected'=>'#ba605a',
        ];
        $statusKeys = array_keys(array_filter($statusNames,
            fn ($label, $key) => (int)($summary[$key] ?? 0) > 0, ARRAY_FILTER_USE_BOTH));

        $figures = [
            'trend'=>$charts->trend($monthLabels, array_values($monthlyCounts)),
            'status'=>$charts->statuses(
                array_map(fn ($key) => $statusNames[$key], $statusKeys),
                array_map(fn ($key) => (int)$summary[$key], $statusKeys),
                array_map(fn ($key) => $statusPalette[$key], $statusKeys)
            ),
            'lga'=>$charts->ranking(
                $byLga->map(fn ($row) => $row->lga?->name ?? 'Unassigned')->all(),
                $byLga->pluck('total')->map(fn ($n) => (int)$n)->all()
            ),
            'sector'=>$charts->ranking(
                $bySector->map(fn ($row) => $row->sector?->name ?? 'Other')->all(),
                $bySector->pluck('total')->map(fn ($n) => (int)$n)->all(),
                '#b38943'
            ),
            'community'=>$charts->ranking(
                $byCommunity->map(fn ($row) => $row->community.' · '.($row->lga?->name ?? 'Unassigned'))->all(),
                $byCommunity->pluck('total')->map(fn ($n) => (int)$n)->all(),
                '#547963'
            ),
        ];

        $applied = array_filter([
            'Search' => $request->filled('q') ? trim((string)$request->input('q')) : null,
            'LGA' => $request->filled('lga_id') ? Lga::find($request->integer('lga_id'))?->name : null,
            'Sector' => $request->filled('sector_id') ? Sector::find($request->integer('sector_id'))?->name : null,
            'Ward' => $request->filled('ward') ? trim((string)$request->input('ward')) : null,
            'Community' => $request->filled('community') ? trim((string)$request->input('community')) : null,
            'Status' => $request->filled('verification_status')
                ? ($statusNames[$request->input('verification_status')] ?? null) : null,
            'Type' => $request->filled('type') ? ucwords(str_replace('_', ' ', $request->input('type'))) : null,
            'From' => $request->input('date_from'),
            'To' => $request->input('date_to'),
        ], fn ($value) => $value !== null && $value !== '');

        $logoPath = public_path('icons/act-192.png');
        $logo = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;
        $html = view('reports.summary-pdf', [
            'rows'=>$rows, 'total'=>$total, 'summary'=>$summary, 'types'=>$types,
            'byLga'=>$byLga, 'bySector'=>$bySector, 'byCommunity'=>$byCommunity, 'figures'=>$figures, 'verificationLabels'=>$statusNames,
            'applied'=>$applied, 'logo'=>$logo,
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $pdf = new Dompdf($options);
        $pdf->loadHtml($html);
        $pdf->setPaper('a4', 'landscape');
        $pdf->render();
        $font = $pdf->getFontMetrics()->getFont('DejaVu Sans');
        $pdf->getCanvas()->page_text(640, 574, 'RAWYOD  |  Page {PAGE_NUM} of {PAGE_COUNT}', $font, 8, [0.34,0.43,0.38]);

        return response($pdf->output(), 200, [
            'Content-Type'=>'application/pdf',
            'Content-Disposition'=>'attachment; filename="arewa-monitoring-'.now()->format('Ymd').'.pdf"',
            'Cache-Control'=>'private, no-store',
            'X-Content-Type-Options'=>'nosniff',
        ]);
    }
}
