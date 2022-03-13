# Creating an API with Laravel - Part 6: Reevaluating relationships and thinking about policies

## Rethinking the relationships
I've realized that the way I'm connecting user ID's and team ID's may not work well. I think I instead want to make so the Log belongs to the team directly, and then have a nullable option to add the user ID of who owns the token. That way clients can backtrace which user's token was used where.

I re-read the docs for Jetstream teams and APIs and a few Laracast posts and I think I will use user_id as the token owner.

## Authorization planning

Now that we have the basic backend setup I think it's time to add some authorization policies.

Since we only have the `store` method set up I will focus on that. I like to start with listing the requirements that need to be satisfied to authorize the request.

Since we authenticate requests using bearer tokens that is what we will use to run the authorization rules. I think the following rules needs to be fulfilled. If I missed anything, do let me know!
1. The token has the `log:create` permission.
2. The token user is part of the team the endpoint belongs to and has the proper team permission.
3. The team is allowed to send logs (i.e. they have not reached their quota or rate limits)

We also want to send the proper responses with why a request failed.

## Creating the policy

As usual, my preferred method of scaffolding files is by using the Artisan commands.
```bash
php artisan make:policy LogPolicy --model="Log"
```

> I learned something very interesting here. I assumed that the policy as it is would not work since it expects a user model and that since we use tokens to authenticate the requests there would be no user model, but it turns out that there is! Making a request with Curl using an API token and dumping the request in the controller shows that we actually have access to the `$request->user()` which is awesome! I love Laravel!

So, in the create policy method I broke down the rules defined above into something to work with.
```php
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
```
The test still runs successfully, but that's not very meaningfully unless we also test that we cannot store logs if we have an invalid token or are missing permissions so I created 2 new tests which you can see in the file on GitHub.


Here I also did some refactoring since I know know I don't have to supply the bearer token manually in the tests which allowed me to clean up the code a lot which you can see in the GitHub diff!