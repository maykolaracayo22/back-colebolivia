<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Level;
use Illuminate\Http\Request;

class LevelController extends Controller {
    public function index(Request $request) {
        $q = Level::query()->orderBy('id');
        return response()->json($q->get());
    }
}
