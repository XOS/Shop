<?php
namespace App\Http\Controllers\Merchant; use App\Library\Response; use App\System; use Illuminate\Http\Request; use App\Http\Controllers\Controller; use Illuminate\Support\Facades\Auth; use Illuminate\Support\Facades\DB; use Illuminate\Support\Facades\Storage; class Card extends Controller { function get(Request $sp054aa0, $sp28a33a = false, $sp2e3036 = false, $sp79d754 = false) { $sp0964e2 = $this->authQuery($sp054aa0, \App\Card::class)->with(array('product' => function ($sp0964e2) { $sp0964e2->select(array('id', 'name')); })); $spcb6e4b = $sp054aa0->input('search', false); $spe07b43 = $sp054aa0->input('val', false); if ($spcb6e4b && $spe07b43) { if ($spcb6e4b == 'id') { $sp0964e2->where('id', $spe07b43); } else { $sp0964e2->where($spcb6e4b, 'like', '%' . $spe07b43 . '%'); } } $spc3ee02 = (int) $sp054aa0->input('category_id'); $sp107f34 = $sp054aa0->input('product_id', -1); if ($spc3ee02 > 0) { if ($sp107f34 > 0) { $sp0964e2->where('product_id', $sp107f34); } else { $sp0964e2->whereHas('product', function ($sp0964e2) use($spc3ee02) { $sp0964e2->where('category_id', $spc3ee02); }); } } $sp4e5656 = $sp054aa0->input('status'); if (strlen($sp4e5656)) { $sp0964e2->whereIn('status', explode(',', $sp4e5656)); } $spd16511 = (int) $sp054aa0->input('onlyCanSell'); if ($spd16511) { $sp0964e2->whereRaw('`count_all`>`count_sold`'); } $sp8beb2b = $sp054aa0->input('type'); if (strlen($sp8beb2b)) { $sp0964e2->whereIn('type', explode(',', $sp8beb2b)); } $sp13c483 = $sp054aa0->input('trashed') === 'true'; if ($sp13c483) { $sp0964e2->onlyTrashed(); } if ($sp2e3036 === true) { if ($sp13c483) { $sp0964e2->forceDelete(); } else { \App\Card::_trash($sp0964e2); } return Response::success(); } else { if ($sp13c483 && $sp79d754 === true) { \App\Card::_restore($sp0964e2); return Response::success(); } else { $sp0964e2->orderByRaw('`product_id`,`type`,`status`,`id`'); if ($sp28a33a === true) { $spf9090b = ''; $sp0964e2->chunk(100, function ($spe6d16e) use(&$spf9090b) { foreach ($spe6d16e as $sp33a701) { $spf9090b .= $sp33a701->card . '
'; } }); $sp3703ac = 'export_cards_' . $this->getUserIdOrFail($sp054aa0) . '_' . date('YmdHis') . '.txt'; $sp4977e0 = array('Content-type' => 'text/plain', 'Content-Disposition' => sprintf('attachment; filename="%s"', $sp3703ac), 'Content-Length' => strlen($spf9090b)); return response()->make($spf9090b, 200, $sp4977e0); } $sp1d90fd = $sp054aa0->input('current_page', 1); $sp21d879 = $sp054aa0->input('per_page', 20); $sp03b529 = $sp0964e2->paginate($sp21d879, array('*'), 'page', $sp1d90fd); return Response::success($sp03b529); } } } function export(Request $sp054aa0) { return self::get($sp054aa0, true); } function trash(Request $sp054aa0) { $this->validate($sp054aa0, array('ids' => 'required|string')); $sp4f46fd = $sp054aa0->post('ids'); $sp0964e2 = $this->authQuery($sp054aa0, \App\Card::class)->whereIn('id', explode(',', $sp4f46fd)); \App\Card::_trash($sp0964e2); return Response::success(); } function restoreTrashed(Request $sp054aa0) { $this->validate($sp054aa0, array('ids' => 'required|string')); $sp4f46fd = $sp054aa0->post('ids'); $sp0964e2 = $this->authQuery($sp054aa0, \App\Card::class)->whereIn('id', explode(',', $sp4f46fd)); \App\Card::_restore($sp0964e2); return Response::success(); } function deleteTrashed(Request $sp054aa0) { $this->validate($sp054aa0, array('ids' => 'required|string')); $sp4f46fd = $sp054aa0->post('ids'); $this->authQuery($sp054aa0, \App\Card::class)->whereIn('id', explode(',', $sp4f46fd))->forceDelete(); return Response::success(); } function deleteAll(Request $sp054aa0) { return $this->get($sp054aa0, false, true); } function restoreAll(Request $sp054aa0) { return $this->get($sp054aa0, false, false, true); } function add(Request $sp054aa0) { $sp107f34 = (int) $sp054aa0->post('product_id'); $spe6d16e = $sp054aa0->post('card'); $sp8beb2b = (int) $sp054aa0->post('type', \App\Card::TYPE_ONETIME); $sp156e9e = $sp054aa0->post('is_check') === 'true'; if (str_contains($spe6d16e, '<') || str_contains($spe6d16e, '>')) { return Response::fail('卡密不能包含 < 或 > 符号'); } $sp47762c = $this->getUserIdOrFail($sp054aa0); $sp41a20f = $this->authQuery($sp054aa0, \App\Product::class)->where('id', $sp107f34); $sp41a20f->firstOrFail(array('id')); if ($sp8beb2b === \App\Card::TYPE_REPEAT) { if ($sp156e9e) { if (\App\Card::where('product_id', $sp107f34)->where('card', $spe6d16e)->exists()) { return Response::fail('该卡密已经存在，添加失败'); } } $sp33a701 = new \App\Card(array('user_id' => $sp47762c, 'product_id' => $sp107f34, 'card' => $spe6d16e, 'type' => \App\Card::TYPE_REPEAT, 'count_sold' => 0, 'count_all' => (int) $sp054aa0->post('count_all', 1))); if ($sp33a701->count_all < 1 || $sp33a701->count_all > 10000000) { return Response::forbidden('可售总次数不能超过10000000'); } return DB::transaction(function () use($sp41a20f, $sp33a701) { $sp33a701->saveOrFail(); $sp648779 = $sp41a20f->lockForUpdate()->firstOrFail(); $sp648779->buy_max = 1; $sp648779->count_all += $sp33a701->count_all; $sp648779->saveOrFail(); return Response::success(); }); } else { $sp61a326 = explode('
', $spe6d16e); $spb41ca6 = count($sp61a326); $sp809310 = 500; if ($spb41ca6 > $sp809310) { return Response::fail('每次添加不能超过 ' . $sp809310 . ' 张'); } $sp94143b = array(); if ($sp156e9e) { $sp6172d6 = \App\Card::where('user_id', $sp47762c)->where('product_id', $sp107f34)->get(array('card'))->all(); foreach ($sp6172d6 as $sp1de26b) { $sp94143b[] = $sp1de26b['card']; } } $spa8bded = array(); $spd9ec4a = 0; for ($spbc3a4f = 0; $spbc3a4f < $spb41ca6; $spbc3a4f++) { $sp33a701 = trim($sp61a326[$spbc3a4f]); if (strlen($sp33a701) < 1) { continue; } if (strlen($sp33a701) > 255) { return Response::fail('第 ' . $spbc3a4f . ' 张卡密 ' . $sp33a701 . ' 长度错误<br>卡密最大长度为255'); } if ($sp156e9e) { if (in_array($sp33a701, $sp94143b)) { continue; } $sp94143b[] = $sp33a701; } $spa8bded[] = array('user_id' => $sp47762c, 'product_id' => $sp107f34, 'card' => $sp33a701, 'type' => \App\Card::TYPE_ONETIME); $spd9ec4a++; } if ($spd9ec4a === 0) { return Response::success(); } return DB::transaction(function () use($sp41a20f, $spa8bded, $spd9ec4a) { \App\Card::insert($spa8bded); $sp648779 = $sp41a20f->lockForUpdate()->firstOrFail(); $sp648779->count_all += $spd9ec4a; $sp648779->saveOrFail(); return Response::success(); }); } } function edit(Request $sp054aa0) { $spde29a5 = (int) $sp054aa0->post('id'); $sp33a701 = $this->authQuery($sp054aa0, \App\Card::class)->findOrFail($spde29a5); if ($sp33a701) { $spc47d9c = $sp054aa0->post('card'); $sp8beb2b = (int) $sp054aa0->post('type', \App\Card::TYPE_ONETIME); $sp45ea04 = (int) $sp054aa0->post('count_all', 1); return DB::transaction(function () use($sp33a701, $spc47d9c, $sp8beb2b, $sp45ea04) { $sp33a701 = \App\Card::where('id', $sp33a701->id)->lockForUpdate()->firstOrFail(); $sp33a701->card = $spc47d9c; $sp33a701->type = $sp8beb2b; if ($sp33a701->type === \App\Card::TYPE_REPEAT) { if ($sp45ea04 < $sp33a701->count_sold) { return Response::forbidden('可售总次数不能低于当前已售次数'); } if ($sp45ea04 < 1 || $sp45ea04 > 10000000) { return Response::forbidden('可售总次数不能超过10000000'); } $sp33a701->count_all = $sp45ea04; } else { $sp33a701->count_all = 1; } $sp33a701->saveOrFail(); $sp648779 = $sp33a701->product()->lockForUpdate()->firstOrFail(); if ($sp33a701->type === \App\Card::TYPE_REPEAT) { $sp648779->buy_max = 1; } $sp648779->count_all -= $sp33a701->count_all; $sp648779->count_all += $sp45ea04; $sp648779->saveOrFail(); return Response::success(); }); } return Response::success(); } }