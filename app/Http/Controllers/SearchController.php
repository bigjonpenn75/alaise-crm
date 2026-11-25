<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $term = trim((string) $request->query('q'));
        $leads = $clients = collect();

        if (mb_strlen($term) >= 2) {
            $businessIds = $request->user()->accessibleBusinesses()->pluck('id');
            $like = '%'.addcslashes($term, '%_\\').'%';
            $fullName = DB::connection()->getDriverName() === 'sqlite'
                ? "coalesce(first_name, '') || ' ' || coalesce(last_name, '')"
                : "concat(coalesce(first_name, ''), ' ', coalesce(last_name, ''))";
            $match = fn ($q) => $q->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)
                ->orWhere('tel', 'like', $like)->orWhere('email', 'like', $like)
                ->orWhereRaw("$fullName like ?", [$like]);

            $leads = Lead::whereIn('business_id', $businessIds)->where($match)->with(['business', 'technician'])->latest('id')->limit(100)->get();
            $clients = Client::whereIn('business_id', $businessIds)->where($match)->with('business')->withCount('appointments')->latest('id')->limit(100)->get();
        }

        return view('search', ['term' => $term, 'leads' => $leads, 'clients' => $clients]);
    }
}
