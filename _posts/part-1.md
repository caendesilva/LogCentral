# Practising making an API service: Building a central logging platform

> Follow along this series at https://github.com/caendesilva/LogCentral

**I've been wanting to build an API for a while now as I've mainly just used APIs and not implemented any. Today I figured out what the API should be used for.**

I want to create a central logging platform to send Laravel logs to.

I'm going to document this process with a series of short blog posts. I want to start with the platform requirements.

- It needs to be open source as I think that is vital for mutual learning. It also allows people to self-host it.
- It has to be secure as system logs may contain sensitive data. I am thinking TLS/SSL encryption in transit and public-key cryptography encrypted data at rest. This will also allow me to learn about how to implement encryption within a database and at scale. User's should be able to set their own key pairs as well.
- It needs to be easy to send data to. Since I mainly work with Laravel I should make a Log driver package for it that hooks into the Laravel Monologger. Each request should include the API token, I need to learn how to handle this securely.
	- One should also be able to create logs using POST requests using tools like Curl so logs can be created from the command line. I used something like this to post notifications to Slack when my servers were updating and really liked it.

It also needs a good interface. I think I will use Laravel Jetstream for this. Though I am much more comfortable with Livewire I'm going to use the Vue stack as I need to practice Vue more.

The interface needs to be easy and fast to use and could have a few dashboards, displaying the log levels at a glance. The actual log table interface should include filters and searches so users can deep dive into the data.

I also need to put caps on the usage limits since I'll be self-hosting this as a proof of concept. And also show the usage limits on the dashboard. Limits could be based on storage and requests/min, so I need something to handle that. These caps can of course be disabled for people who self-host.

I also need to include some kind of categorization besides the log levels. I know I will be using this to collect logs for multiple sites. I could overcomplicate this and create models for each site but I think I will just have a database column for the site label. When making the POST request the label is passed as a parameter.

I also want to send notifications when high-level alerts are received. I've only handled email notifications previously, so it would be cool to look into more platforms.

A cool feature for the Laravel package would be to send logs in batches asynchronously. It should probably be disabled for emergency and critical alerts. But logs that don't need to be sent in real-time could be queued and sent as a batch during the night when the site does not have as much traffic to ease the load on both my server and the users. But that's a feature for the future.

I think that concludes this post. Please let me know your feedback and ideas in the comments!