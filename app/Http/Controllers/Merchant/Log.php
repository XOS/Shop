<?php
namespace App\Http\Controllers\Merchant; use App\Library\Response; use Illuminate\Http\Request; use App\Http\Controllers\Controller; use Illuminate\Support\Facades\Auth; class Log extends Controller { function get(Request $spf09a96) { $sp6738b1 = $spf09a96->input('user_id'); $sp807df0 = $spf09a96->input('action', \App\Log::ACTION_LOGIN); $sp40bc20 = \App\Log::where('action', $sp807df0); $sp40bc20->where('user_id', Auth::id()); $sp791c3e = $spf09a96->input('start_at'); if (strlen($sp791c3e)) { $sp40bc20->where('created_at', '>=', $sp791c3e . ' 00:00:00'); } $spdb32b3 = $spf09a96->input('end_at'); if (strlen($spdb32b3)) { $sp40bc20->where('created_at', '<=', $spdb32b3 . ' 23:59:59'); } $sp5b4065 = (int) $spf09a96->input('current_page', 1); $spe24165 = (int) $spf09a96->input('per_page', 20); $sp3fe1fa = $sp40bc20->orderBy('created_at', 'DESC')->paginate($spe24165, array('*'), 'page', $sp5b4065); return Response::success($sp3fe1fa); } }