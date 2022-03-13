<?php

namespace App\Policies;

use App\Models\Log;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Route;

class LogPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models for the team.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user, Team $team)
    {
        return  $user->belongsToTeam($team) &&
                $user->hasTeamPermission($team, 'log:view') &&
                $user->tokenCan('log:view');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Log  $log
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Log $log)
    {
        return  $user->belongsToTeam($log->team) &&
                $user->hasTeamPermission($log->team, 'log:view') &&
                $user->tokenCan('log:view');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Team  $team
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user, Team $team)
    {
	    if (!$user->tokenCan('log:create')) {
            return Response::deny('Supplied token missing permission log:create');
        }

        if (!$user->belongsToTeam($team)) {
            return Response::deny('You don\'t belong to this team!');
        }
        
        if (!$user->hasTeamPermission($team, 'log:create')) {
            return Response::deny('You don\'t have the proper team permission!');
        }
        
        // if team has hit its account limits
        // return Response::deny('Your team has reached its quota. Please contact your account manager.');

        return Response::allow();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Log  $log
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Log $log)
    {
        // Logs should not be updated since that does not make sense.
        // Possibly we include an annotation field that can be updated in the future.
        return false; 
    }

    /**
     * Determine whether the user can delete (archive) the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Log  $log
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Log $log)
    {
        return  $user->belongsToTeam($log->team) &&
                $user->hasTeamPermission($log->team, 'log:delete') &&
                $user->tokenCan('log:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Log  $log
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Log $log)
    {
        return  $user->belongsToTeam($log->team) &&
                $user->hasTeamPermission($log->team, 'log:delete') &&
                $user->tokenCan('log:delete');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Log  $log
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Log $log)
    {
        return  $user->belongsToTeam($log->team) &&
                $user->hasTeamPermission($log->team, 'log:delete') &&
                $user->tokenCan('log:delete');
    }
}
