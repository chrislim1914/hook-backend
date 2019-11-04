<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Ranking;
use Illuminate\Support\Facades\DB;

class RankingController extends Controller
{
    public function addData(Request $request) {
        $rank = new Ranking();

        $rank->iduser = $request->iduser;
        $rank->idproduct = $request->idproduct;

        $rank->save();
    }

    public function ranking() {
        $rank = DB::insert(DB::raw( "create temporary table ub_rank as 
        select similar.user_id,count(*) rank
        from ub target
        join ub similar on target.book_id= similar.book_id and target.user_id != similar.user_id
        where target.user_id = 1
        group by similar.user_id"));

        $qwe = DB::raw("select similar.book_id, sum(ub_rank.rank) total_rank
        from ub_rank
        join ub similar on ub_rank.user_id = similar.user_id 
        left join ub target on target.user_id = 1 and target.book_id = similar.book_id
        where target.book_id is null
        group by similar.book_id
        order by total_rank desc;")->get();;

        var_dump($qwe);
    }


}
