<?php
namespace App\Http\Controllers;

use App\Models\CivicCase;
use App\Support\CaseRichText;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CaseDocumentController extends Controller
{
    private function document(Request $request, CivicCase $case, bool $print): array
    {
        abort_unless($request->user()?->can('view', $case),403);
        $case->load(['lga','sector','category','reporter','reviewer','evidence','followups','events.actor']);
        $logo=public_path('icons/arewa-approved-logo.png');
        $logoData=is_file($logo)
            ?'data:image/png;base64,'.base64_encode(file_get_contents($logo)):null;
        $evidence=[];
        foreach ($case->evidence->take(5) as $item) {
            $thumb=null;
            try {
                if ($item->preview_path) {
                    $disk=Storage::disk($item->disk);
                    if($disk->exists($item->preview_path) &&
                        ($item->preview_size_bytes??0) < 1200000) {
                        $binary=$disk->get($item->preview_path);
                        if (strlen($binary)<1200000 &&
                            function_exists('imagecreatefromstring') &&
                            ($image=@imagecreatefromstring($binary))!==false) {
                            ob_start();
                            imagejpeg($image,null,72);
                            $jpg=ob_get_clean();
                            imagedestroy($image);
                            if(is_string($jpg) && strlen($jpg)<1500000)
                                $thumb='data:image/jpeg;base64,'.base64_encode($jpg);
                        }
                    }
                }
            } catch (\Throwable $error) {
                // Evidence is never leaked or exposed as a remote URL in exports.
                Log::warning('Case PDF preview unavailable', [
                    'case_id'=>$case->id,'evidence_id'=>$item->id,
                    'error_class'=>get_class($error),
                ]);
            }
            $evidence[]=['name'=>$item->original_name,'size'=>$item->size_bytes,
                'mime'=>$item->mime_type,'image'=>$thumb];
        }
        return [
            'case'=>$case,'logo'=>$logoData,'attachments'=>$evidence,
            'printMode'=>$print,'renderedDescription'=>CaseRichText::render($case->description),
            'generatedAt'=>now('Africa/Lagos'),
        ];
    }

    public function print(Request $request,CivicCase $case)
    {
        return response()->view('cases.document',$this->document($request,$case,true))
            ->header('Cache-Control','private, no-store');
    }

    public function pdf(Request $request,CivicCase $case)
    {
        $html=view('cases.document',$this->document($request,$case,false))->render();
        $options=new Options();
        $options->set('isRemoteEnabled',false);
        $options->set('isPhpEnabled',false);
        $options->set('defaultFont','DejaVu Sans');
        $options->set('chroot',public_path());
        $pdf=new Dompdf($options);
        $pdf->loadHtml($html);
        $pdf->setPaper('a4','portrait');
        $pdf->render();
        $font=$pdf->getFontMetrics()->getFont('DejaVu Sans');
        $pdf->getCanvas()->page_text(470,817,'Page {PAGE_NUM} of {PAGE_COUNT}',$font,8,[.35,.43,.37]);
        $filename='Arewa-Report-'.preg_replace('/[^A-Za-z0-9-]/','',$case->reference).'.pdf';
        return response($pdf->output(),200,[
            'Content-Type'=>'application/pdf',
            'Content-Disposition'=>'attachment; filename="'.$filename.'"',
            'Cache-Control'=>'private, no-store',
            'X-Content-Type-Options'=>'nosniff',
        ]);
    }
}
