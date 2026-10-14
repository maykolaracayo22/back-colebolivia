<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class PlanningController extends Controller
{
    public function index(){ return Plan::latest()->paginate(20); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'teacher_id'=>'required|integer',
            'type'=>'required|in:anual,mensual',
            'path'=>'required|string',
        ]);
        return Plan::create($data);
    }

    public function approve(Request $request, Plan $plan)
    {
        $plan->update([
            'status'=>'aprobado',
            'approved_by'=>$request->user()->id,
            'observations'=>$request->input('observations'),
        ]);
        return $plan->refresh();
    }

    public function observe(Request $request, Plan $plan)
    {
        $plan->update([
            'status'=>'observado',
            'approved_by'=>null,
            'observations'=>$request->input('observations'),
        ]);
        return $plan->refresh();
    }
}
