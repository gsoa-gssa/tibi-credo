<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function signatureCount()
    {
        $count = [
            "total" => \App\Models\Batch::sum("signature_count"),
            "today" => \App\Models\Batch::where("created_at", ">", now()->subDay())->sum("signature_count"),
            "thirtyMinutes" => \App\Models\Batch::where("created_at", ">", now()->subMinutes(30))->sum("signature_count"),
        ];
        return response()->json($count);
    }

    public function viewSignatureCount()
    {
        $count = [
            "total" => \App\Models\Batch::sum("signature_count"),
            "today" => \App\Models\Batch::where("created_at", ">", now()->subDay())->sum("signature_count"),
            "thirtyMinutes" => \App\Models\Batch::where("created_at", ">", now()->subMinutes(30))->sum("signature_count"),
        ];
        return view("stats.signatureCount", ["count" => $count]);
    }
}
