# Laravel Easy Generator

[![Latest Stable Version](https://poser.pugx.org/hcomg/laravel-easy-generator/v/stable)](https://packagist.org/packages/hcomg/laravel-easy-generator) [![Total Downloads](https://poser.pugx.org/hcomg/laravel-easy-generator/downloads)](https://packagist.org/packages/hcomg/laravel-easy-generator) [![Latest Unstable Version](https://poser.pugx.org/hcomg/laravel-easy-generator/v/unstable)](https://packagist.org/packages/hcomg/laravel-easy-generator) [![License](https://poser.pugx.org/hcomg/laravel-easy-generator/license)](https://packagist.org/packages/hcomg/laravel-easy-generator)

php artisan command to generate fully working crud with api resource by having database tables

## Features

    1. Add api to routes.
    2. Create resource controller with Dingo API.
    3. Create model with Validator rules from Database.
    4. ...

### Requirements
    Laravel >=5.1
    PHP >= 5.5.9
    dingo/api: 1.0.x@dev

## Installation
Open your terminal(CLI), go to the root directory of your Laravel project, then follow the following procedure.
1. Install Through Composer
    ```bash
    composer require hcomg/laravel-easy-generator --dev
    ```

2. Add the Service Provider

    Open `/app/Providers/AppServiceProvider.php` and, to your `register` function, add:

    ```php
    public function register()
    {
        if ($this->app->environment() !== 'production') {
            $this->app->register('EasyGenerator\EasyGeneratorServiceProvider::class');
        }
    }
    ```
3. Run `php artisan help hcomg:gen` to see all parameters

##Examples

```bash
php artisan hcomg:gen --model=User --table=users --controller=User

Creating catalogue for table: users
Model Name: User
Created Controller: ./app/Models/User.php
Created Controller: ./app/Http/Controllers/UserController.php
Created Controller: ./app/Transformers/UserTransformer.php
Adding Route:     $api->resource('users', '\App\Http\Controllers\UserController');
```
Then run `php artisan api:routes` to see the api list.
