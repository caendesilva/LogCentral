# Creating an API with Laravel - Part 4: Setting up the resource controller with validation and feature testing

**A feature I really love in Laravel are resource controllers so of course we are going to use them in this project!
We already generated the scaffolding in the last post so let's dig in.**

## Planning the controller

Before we can do much else we need data so let's start with the store method. I want to begin with laying out the flow of the method as there are a few vital steps in the request cycle.

Step 1: Authenticate and authorize the request using Sanctum - is the API token valid, and is it allowed to make posts to the team?
Step 2: Validate the request - is the request properly formed?
Step 3: Attempt to store the validated data and return the appropriate HTTP response code

> I want to note that I was thinking about how to handle the user and team ID. I think that each team should have an API endpoint which is the team UUID (which I need to add). The user ID will then be of the user who owns the API token that was used.

Until I implement the UUIDs I will just use a single endpoint for testing.

In `routes/api.php` I added the resource route with the handy helper.
```php
use App\Http\Controllers\LogController;
Route::apiResource('/logs', LogController::class);
```

## Validation

I'm going to start with the validation logic in the store method on our controller.

When writing validation rules I usually go back to the migration schema to make sure they are compatible.

I need validation rules for the fillable properties. I'm making them in the same order I made the migrations.

For the `level` I am using the Rule::in method with the same array I used to declare the enums.
```php
Rule::in(['EMERGENCY','ALERT','CRITICAL','ERROR','WARNING','NOTICE','INFO','DEBUG']),
```
While the timestamp is required in the database I'm setting it as nullable here. If it is not set in the request it will default to the current time of creating the model.

Here is the validation rules
```php
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
```

## Testing

To test that the validation works as expected I'm going to create a feature test with
```bash
php artisan make:test StoreLogTest
```

Since I'm using Jetstream I'm borrowing some code from the the build in tests. There is no shame in not remembering the exact syntax as long as you take the time to learn and understand what the code does. I saw a great quote on Twitter a while back that said something like "Only copy and paste code that you were to lazy to write yourself".

So I created the test in the created file
```php
public function test_logs_can_be_posted()
{
	$response = $this->post('/api/logs', [
		'level' => 'INFO',
		'timestamp' => time(),
		'label' => 'Laravel Test',
		'message' => 'Hello World!',
		'context' => '{"foo": "bar"}',
	]);

	$response->assertStatus(200);
}
```

And ran the test with the filter flag set to speed things up by only running this test
```bash
php artisan test --filter="StoreLogTest"
```
and after fixing a typo and importing a class I forgot to import the test passed.

```bash
PASS  Tests\Feature\StoreLogTest
✓ logs can be posted

Tests:  1 passed
Time:   0.20s
```

Though just asserting the 200 status is not very meaningful. We want to make sure that the model is actually stored. But before that we need to implement the storing logic. Which brings us to the next section: implementing the storing logic.

## Implementing the storing logic

I like writing dry code and as such I often use the create method
```php
# Example from the docs
$comment = $post->comments()->create([
    'message' => 'A new comment.',
]);
```
this requires us to add properties as fillable as Laravel protects from mass assignment vulnerabilities out of the box. So let's add some fillable properties to our Log model.

```php
/**
 * The attributes that are mass assignable.
 *
 * @var string[]
 */
protected $fillable = [
	'level', 'timestamp', 'label', 'message', 'context'
];
```

I'm leaving out the user and teams ID's as I want to specify those specifically. I will have to use a slightly different syntax though, since the log belongs to both a user and team. But since I have not implemented that yet I will just set the fields to 1.
```php
$log = new Log($validated);
$log->user_id = 1; // Temporary until the feature is implemented
$log->team_id = 1; // Temporary until the feature is implemented
$log->save();
```

Let's run the test again! It passed, but how do we know that the Log was stored? Before writing the assertions I'm cheating and using **Tinkerwell** to check if the model was created.

```php
# Running
\App\Models\Log::all();

# Returns
=> Illuminate\Database\Eloquent\Collection {
     all: [
		App\Models\Log {
			id: 1,
			user_id: 1,
			team_id: 1,
			level: "INFO",
			timestamp: 1647114238,
			label: "Laravel Test",
			message: "Hello World!",
			context: "{"foo": "bar"}",
		},
	],
}
```

Success!

Let's update our test!

I'm still pretty new to TDD (Test Driven Development), so I was not sure how to best test if a model was persisted to the database -- and unfortunately Stackoverflow is currently down. I realized though that we need to return a response when the model is created, so let's implement that and then add it to our test.

While researching this I learned that the save() method returns a boolean for if the model was saved successfully or not, so let's use that!

```php
// In the controller
if ($log->save()) {
	return response('Log Created Successfully', 201);
} else {
	return response('Something went wrong on our end. If this persists, please contact our support.', 500);
}

// And update the test assertion to check for a 201 Created HTTP status code
$response->assertCreated();
```

I ran the test again, and it works! The model is persisted and the proper response is sent.

The next step now, I think is to start working on how to use the API tokens. But that's for the next post!