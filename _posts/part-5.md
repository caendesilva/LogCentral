# Creating an API with Laravel - Part 5: Learning how to use Laravel Sanctum

## Introduction
Since we are using Jetstream which uses Sanctum for API authentication, thus it only makes sense that we use it for our API tokens to authorize requests. But since I've never used Sanctum directly before I first need to learn how it works. Good thing this whole project is about learning!

This post is going to be a bit more focused than the previous ones. I won't be posting as many snippets and going into detail. Instead I will focus on the problems and solutions I come across as I think that is more interesting. You can always check out the source code repo to follow along! If you are curious about a specific thing, you can check the diff between releases, or post a comment!

## Learning from the Docs
Every time I want to learn about something the first step is always the documentation. In the [Sanctum docs](https://laravel.com/docs/9.x/sanctum)
I learned that the API tokens are inspired by GitHubs Personal Access Tokens. This is a useful comparison for me to understand how the feature works by referencing something I am already familiar with.

I also learned that Sanctum authenticates incoming HTTP requests via the Authorization header which should then contain a valid API token. This sounds similar to bearer tokens which I have also used before when making API requests.

I went ahead and added the auth:sanctum middleware to the API route and reran the test which of course failed as the request was not authorized -- which is good! I learned two new things from the docs as well, that there is a method for posting JSON responses (since currently a redirect was returned on failure), and also how to create a factory user with a token to use for the request.

```php
// @see https://laravel.com/docs/9.x/sanctum#testing
public function test_logs_can_be_posted_as_authenticated_user()
{
    Sanctum::actingAs(
        User::factory()->create(),
        ['*']
    );
    
    $response = $this->postJson('/api/logs', [
        //
    ]);

    $response->assertCreated();
}
```

## Finding the user with Sanctum
I previously mentioned that I want the log entries to be POSTed to a endpoint for the team using its UUID. I know how to do that, but I also want to store the user ID who owns the token used in the log entry, so I need to figure out how to get the user.

After creating a user and test token I using HeidiSQL took a look at the database record. 

The token is stored in the `personal_access_tokens` table and looks like this
---
|  id | tokenable_type  | tokenable_id | name | token   | abilities | 
|----:|-----------------|-------------:|------|---------|-----------|
|   1 | App\Models\User |           42 | Test | SHA256* | ["read"]  | 

> *The SHA-256 token has been removed as it takes a lot of space and is not relevant.

The tokenable type and ID is enough to find the model, but since we are using Laravel I am sure there is some kind of helper built in to aid in this.

Through the User model I looked into the Laravel\Sanctum\HasApiTokens trait had some useful information. I also learned that the Str::random() helper is cryptographically secure which may be useful later on. I also found the PersonalAccessToken Eloquent model in Laravel\Sanctum\PersonalAccessToken. The model has a method to get the model the token belongs to, namely `tokenable` though I'll admit I glossed over it until I found this Laracast thread on Google: https://laracasts.com/discuss/channels/laravel/get-user-by-token where [@luisangeldev](https://laracasts.com/@luisangeldev) shows us we can use the following:
```php
use Laravel\Sanctum\PersonalAccessToken;

$token = PersonalAccessToken::findToken($hashedToken);
$user = $token->tokenable;
```
> Update: The findToken method expects the actual plain text token and not the hash.

So, we need to retrieve the SHA-256 hashed token. I *think* that the token is passed as is in the header, but I want to look at how the Sanctum middleware verifies it to see what format it is in.

Okay so in the Sanctum Guard gets the token using `$request->bearerToken()` so I'm going to make a request and capture that property and see what it looks like.

First I thought getting the token using that method was a bust as it returns `null` from the testing request. I suspected this may be because the test does not actually include a bearer token so I did a quick dump of the headers and confirmed that no token was sent -- at least not one I could see. So I'm setting up a quick API route to return the request bearer and making a Curl request with a test token.

```php
Route::post('token-test', function (Request $request) {
    return $request->bearerToken();
});
```

```curl
curl http://localhost:8000/api/token-test
-X POST
-H "Accept: application/json"
-H "Authorization: Bearer TestToken"
```

which returned `TestToken` which confirms that the bearer token method works and indeed returns it un-hashed, so we will need to hash it. First we need to figure out a way to actually send the bearer token with the test. We know (from the source) that the bearerToken method expects a header named Authorization and that it strips out the "Bearer" part of it so we should just be able to add the headers to the test request.

As hoped, adding
```php
withHeaders([
	'Authorization' => 'Bearer Test',
])
```
to the test request returns "Test" when getting the bearer token! Now we need to swap out the word test with the token belonging to the factory user.

From the API Token Tests shipped with Jetstream we can just reuse the code for creating a token in the test:
```php
// ⚠ Note that this snipped does not work as we need the plain text token and this returns the hash.
//   The actual working implementation uses session()->get('flash.token') which you can learn see down.
$token = $user->tokens()->create([
	'name' => 'Test Token',
	'token' => Str::random(40),
	'abilities' => ['create', 'read'],
]);

$response = $this->withHeaders([
	'Authorization' => 'Bearer ' . $token->token,
```


This works, but it seems like the token is stored in plain text instead of the SHA256 hash in the database which is weird and may cause issues when retrieving the user. So I need to look into this since Sanctum should automatically hash it.

For now, I will create the token using the POST request used in the `CreateApiTokenTest` as I just confirmed that uses the SHA256 hash.
```php
$response = $this->post('/user/api-tokens', [
	'name' => 'Test Token',
	'permissions' => [
		'read',
		'update',
	],
]);

$this->assertCount(1, $user->fresh()->tokens);

$response = $this->withHeaders([
	'Authorization' => 'Bearer ' . $user->tokens()->first()->token,
```

## Setting up the relationships in the resource route

I want to use the Laravel route model bindings, so I'll be overriding the store method for the resource route. I'm putting the custom route before the resource one since Laravel uses the first matching route.
```php
// routes/api.php
Route::post('/logs/{team}/store', [LogController::class, 'store'])->middleware('auth:sanctum');
Route::apiResource('/logs', LogController::class)->except('store')->middleware('auth:sanctum');

// The controller:
public function store(Request $request, Team $team)
```

Now we need to update our test to use the user's personal team. Once again I'm referring to the great Jetstream tests to find we can use the `withPersonalTeam()` method on our factory and update our endpoint.
```php
->postJson('/api/logs/'.$user->currentTeam->id.'/store',
```

Since we have added a few new features I'm going to add a few more tests which you can take a look at in the GitHub repo. I still need to add tests for the authorization when I define those policies.

Now that we know how to get both the user and team we can add them to the created Log model.

Adding the team is easy using `$log->team_id = $team->id;`, though I want to open up Tinkerwell again and test the findToken method, so I'm making an account and creating a new API token. Here found that the findToken method expects the actual plain text token and not the hash so we'll pass the request bearer directly.

Our code now looks like this
```php
$token = PersonalAccessToken::findToken($request->bearerToken());
$user = $token->tokenable;

$log = new Log($validated);
$log->user_id = $user->id;
$log->team_id = $team->id;
```

Let's run the test again and try it out!
And we got a 500 error:
```bash
> Attempt to read property "tokenable" on null
```
which means that we could not find the token. Which makes sense since we are actually retrieving the hashed token when we need the plaintext one from the NewAccessToken instance so we need to inspect how the Jetstream route returns the response and if we can get it in a JSON format.

I found the controller through the Jetstream Inertia routes
```php
// laravel\jetstream\src\Http\Controllers\Inertia\ApiTokenController.php
return back()->with('flash', [
    'token' => explode('|', $token->plainTextToken, 2)[1],
]);
```
So seems like I just need to figure out how to get the session flashes in the test request. Thankfully I yesterday discovered the `$response->dumpSession();` helper so let's try that!

Perfect! The session array has a `flash` array which contains the new token!
```php
array:4 [
  "flash" => array:1 [
    "token" => "BY0BffIp9c1kuw6bsBLQp0tvfZJwk22UbPOsU9uk"
  ]
]
```

So now we need to access the session data. The TestResponse class (which the $response in our tests are an instance of) has a protected method called `session()` which returns `app('session.store');` so I'm going to try that to see what we get which indeed returns the session.

However, I am just going to use `session()->get('flash.token')` as that feels more "Laravel".

And rerunning the test with the session token works and a Log entry was indeed created and contains the user and team IDs! Awesome!

I think I am happy with where we are now. Next I want to add some authorization policies. See you in the next post!