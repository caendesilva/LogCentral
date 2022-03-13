<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;

class LogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Team $team)
    {
        // Authorize the team, user, and token

        $validated = $request->validate([
            'level' => [
                'required',
                Rule::in(['EMERGENCY','ALERT','CRITICAL','ERROR','WARNING','NOTICE','INFO','DEBUG']),
            ],
            'timestamp' => 'nullable|numeric|integer',
            'label' => 'nullable|string|max:64',
            'message' => 'required|string',
            'context' => 'nullable|json',
        ]);

        // When we get to this point we already assume the token is valid authorized and belongs to a user so we don't need to validate it.
        $token = PersonalAccessToken::findToken($request->bearerToken());
        $user = $token->tokenable;

        $log = new Log($validated);
        $log->user_id = $user->id;
        $log->team_id = $team->id;
        if ($log->save()) {
            return response('Log Created Successfully', 201);
        } else {
            return response('Something went wrong on our end. If this persists, please contact our support.', 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Log  $log
     * @return \Illuminate\Http\Response
     */
    public function show(Log $log)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Log  $log
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Log $log)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Log  $log
     * @return \Illuminate\Http\Response
     */
    public function destroy(Log $log)
    {
        //
    }
}
