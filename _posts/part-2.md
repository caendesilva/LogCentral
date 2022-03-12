# Creating an API with Laravel - Part 2: Setting up the development environment and installing Jetstream

This post is part 2 in my series on building a Laravel API. If you have not read the first part you should absolutely do that first. It's here 👉 https://blog.desilva.se/posts/practising-making-an-api-service-building-a-central-logging-platform.

This is a bit of a shorter post, talking about the development environment I'm using, and journaling the setup. If you are already a Laravel pro this may not be very interesting to you. Feel free to skip to part 3!

I usually code in VSCode, but I'm learning PHPStorm, so I will be attempting to use more it for this project.

Let's get started making the site! If you want to follow along, here are the commands. I'm using PowerShell in Windows Terminal Preview.

I'm terrible at coming up with app names, so I'm just calling it "Log Central" for now. Do comment with name ideas!
```bash
composer create-project laravel/laravel LogCentral

cd LogCentral

composer require laravel/jetstream

php artisan jetstream:install inertia --teams

npm install
npm run dev
php artisan migrate

php artisan serve
```

While installing the dependencies, I created the database. Remember to do that before running the migrations.

The site is now live at http://127.0.0.1:8000/ and shows us the lovely Laravel 9 welcome page!

![Laravel welcome page](https://cdn.desilva.se/static/media/blog-posts/general/127.0.0.1_8000_(Macbook%20Pro).png)

Let's save our progress with Git and move on to the next post!
```bash
 git init
 git add .
 git commit -m "Initial Commit"
 ```
 
Following along with this series? Here is the commit we just made. https://github.com/caendesilva/LogCentral/releases/tag/follow-along