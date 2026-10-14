<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditController extends Controller
{
    public function index(Request $req)
    {
        $q = DB::table('audit_events')->orderByDesc('id');
        if ($term = $req->get('q')) {
            $q->where(function($x) use ($term){
                $x->where('action','like',"%$term%")
                  ->orWhere('target','like',"%$term%")
                  ->orWhere('ref','like',"%$term%");
            });
        }
        $limit = (int)($req->get('limit', 100));
        return response()->json(['data' => $q->limit($limit)->get()]);
    }
}
