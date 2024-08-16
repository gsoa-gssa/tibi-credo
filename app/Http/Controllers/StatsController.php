<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function signatureCount()
    {
        $count = [
            "total" => \App\Models\Sheet::all()->sum("signatureCount"),
            "today" => \App\Models\Sheet::where("created_at", ">", now()->subDay())->sum("signatureCount"),
            "thirtyMinutes" => \App\Models\Sheet::where("created_at", ">", now()->subMinutes(30))->sum("signatureCount"),
        ];
        return response()->json($count);
    }

    public function viewSignatureCount()
    {
        $count = [
            "total" => \App\Models\Sheet::all()->sum("signatureCount"),
            "today" => \App\Models\Sheet::where("created_at", ">", now()->subDay())->sum("signatureCount"),
            "thirtyMinutes" => \App\Models\Sheet::where("created_at", ">", now()->subMinutes(30))->sum("signatureCount"),
        ];
        return view("stats.signatureCount", ["count" => $count]);
    }
}
