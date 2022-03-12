# Creating an API with Laravel - Part 3: Thinking about the migrations and learning how Monolog works

## The base migration 
**I want to start simple and expand outwards from there. I prefer stable, clean and testable code over being overwhelmed with features.**

So, let's iron out the main database model.

In no particular order, these are the basic fields/columns I think we will need.

- id: The primary index
- user_id: For the user it belongs to. If the log belongs to a team this would be the team admin's ID.
- team_id: I also want the team_id set up for when I add proper support for teams.
- level: The log level as per [rfc5424](https://datatracker.ietf.org/doc/html/rfc5424). Here I think I will use Enums.
- timestamp: I don't need updated_at times, and I don't think I want created_at either. I think this is one of those places a Unix Epoch timestamp is more suited. Note that we will use a 32 bit integer as that is the standard for Unix Time. This is susceptible to the [Y2K38](https://en.wikipedia.org/wiki/Year_2038_problem) problem though I am making the tradeoff here between future proofing and data size as there will be a lot of log entries.
- label: The server or app name. Used so the user can keep track of what the log is for. I'll set this to be 64 chars long and nullable. I think it makes sense for the label to default to the App Name unless overridden in an environment variable/the config.

## Looking into Monolog

At this point you are probably thinking, "he forgot the log messages", and that is a fair assumption. To make sure I don't miss any important columns I decided to take a dive into the Monolog source code to see what fields we need and how long the messages may be.

In case you didn't know, Monolog is what Laravel uses under the hood.
> @see https://laravel.com/docs/9.x/logging and https://github.com/Seldaek/monolog

I got lucky and the first class I found was `vendor\monolog\monolog\src\Monolog\Logger.php` which contains a great listing of the various log levels.
```php
/**
 * Action must be taken immediately
 *
 * Example: Entire website down, database unavailable, etc.
 * This should trigger the SMS alerts and wake you up.
 */
public const ALERT = 550;
```

I also found the Logger::addRecord() method which contains the following array
```php
$record = [
	'message' => $message,
	'context' => $context,
	'level' => $level,
	'level_name' => $levelName,
	'channel' => $this->name,
	'datetime' => new DateTimeImmutable($this->microsecondTimestamps, $this->timezone),
	'extra' => [],
];
```

Out of these I think we just need `$message`and `$context`. I looked into the `$extra` property but it does not seem to be used in Laravel (though I did not look very deep) so I won't be adding that now.

From the Laravel/Framework source I saw that the log message is a string, and the context is an array. So I will be storing the latter in a JSON column and a string column type should work for the former.

I feel ready to create the Model and will be using the Artisan command to scaffold the files we need.
```bash
php artisan make:model Log --migration --controller --api
```

Let's define the migration schema in the generated migration file!
```php
Schema::create('logs', function (Blueprint $table) {
	$table->id();
	$table->foreignIdFor(User::class); // The user who owns the team
	$table->foreignIdFor(Team::class); // The team the Log is associated with
	$table->enum('level', [
		'EMERGENCY',
		'ALERT',
		'CRITICAL',
		'ERROR',
		'WARNING',
		'NOTICE',
		'INFO',
		'DEBUG'
	]); // Uses log levels from RFC 5424, @see \Psr\Log\LogLevel::class
	$table->integer('timestamp'); // A 4-byte/32bit integer since Unix Time is 32bit
	$table->string('label', 64)->nullable();
	$table->string('message');
	$table->json('context')->nullable();
});
```

And run the migrations
```bash
$ php artisan migrate
Migrating: 2022_03_12_171329_create_logs_table
Migrated:  2022_03_12_171329_create_logs_table (20.37ms)
```

Next, let's configure the model file. Since we don't use the default Laravel timestamps we will disable them with
```php
public $timestamps = false;
```

and of course, add the relationships
```php
return $this->belongsTo(User::class);
return $this->belongsTo(Team::class);
```

I have here removed the bulk of the class to not clutter the post. Here is a GitHub Gist with the current state.
https://gist.github.com/caendesilva/7ec39b453196d7e2bcdadacf7c439246
<script src="https://gist.github.com/caendesilva/7ec39b453196d7e2bcdadacf7c439246.js"></script>

