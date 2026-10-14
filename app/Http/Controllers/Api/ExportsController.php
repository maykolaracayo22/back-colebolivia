<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExportsController extends Controller
{
    public function centralizador(Request $req)
    {
        $year  = $req->get('year');
        $nivel = $req->get('nivel');
        $grado = $req->get('grado');
        $secc  = $req->get('seccion');

        $q = DB::table('actas')
            ->join('sections','sections.id','=','actas.section_id')
            ->join('acta_evaluations','acta_evaluations.acta_id','=','actas.id')
            ->leftJoin('acta_notes','acta_notes.evaluation_id','=','acta_evaluations.id')
            ->leftJoin('students','students.id','=','acta_notes.student_id')
            ->when($year,  fn($x)=>$x->where('actas.year',$year))
            ->when($nivel, fn($x)=>$x->where('actas.nivel',$nivel))
            ->when($grado, fn($x)=>$x->where('actas.grado',$grado))
            ->when($secc,  fn($x)=>$x->where('sections.paralelo',$secc))
            ->select(
                'actas.year','actas.nivel','actas.grado','sections.paralelo',
                'actas.subject_nombre',
                'students.id as student_id','students.nombres','students.apellidos',
                'acta_evaluations.nombre as evaluacion','acta_evaluations.peso',
                'acta_notes.tipo','acta_notes.valor','acta_notes.valor_qual'
            )->orderBy('students.apellidos')->orderBy('students.nombres');

        return response()->json(['data'=>$q->get()]);
    }

    public function acta($id)
    {
        $acta = DB::table('actas')->where('id',$id)->first();
        if (!$acta) return response()->json(['message'=>'Not found'],404);

        $rows = DB::table('acta_evaluations')
            ->where('acta_id',$id)
            ->leftJoin('acta_notes','acta_notes.evaluation_id','=','acta_evaluations.id')
            ->leftJoin('students','students.id','=','acta_notes.student_id')
            ->select('acta_evaluations.nombre as evaluacion','acta_evaluations.peso',
                     'students.id as student_id','students.nombres','students.apellidos',
                     'acta_notes.tipo','acta_notes.valor','acta_notes.valor_qual')
            ->orderBy('acta_evaluations.id')->orderBy('students.apellidos')->get();

        return response()->json(['meta'=>$acta, 'data'=>$rows]);
    }
}
