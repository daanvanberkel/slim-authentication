# PHP Slim authentication library

This library can be used with the [Slim framework](https://www.slimframework.com/). It supported two factor
authentication.

## Installation

Install using [Composer](https://getcomposer.org/)

```text
composer require daanvanberkel/slim-authentication
```

When Composer is finished, use the file `database_structure.sql` to create the right database tables.

## Usage

You can use the following example to use this authentication library with Slim

```php
<?php
// Create Slim instance
$app = new \Slim\App();

// Set config for Slim authentication, for all config variables see 'Config'
$config = array(
    "name" => "Slim authentication example"
);

// Set config and set PDO instance
\DvbSlimAuthentication\DvbSlimAuthentication::getInstance($config)->setPdo(new PDO("mysql:host=localhost;dbname=database", "user", "password"))

// Create new user controller instance
$controller = new \DvbSlimAuthentication\Controllers\UserController();

// Register login, register and two factor setup routes to Slim

$app->get('/login', array($controller, 'login'));
$app->post('/login', array($controller, 'login'));

$app->get('/register', array($controller, 'register'));
$app->post('/register', array($controller, 'register'));

$app->get('/two-factor-setup', array($controller, 'twoFactorSetup'));
$app->post('/two-factor-setup', array($controller, 'twoFactorSetup'));

// Group with protected routes
$app->group('', function() {
    // Protected route
    $this->get('/', 'protectedRoute');
})->add(new \DvbSlimAuthentication\Middleware\UserMiddleware());

// Run Slim instance
$app->run();

```

## Config

### table
Type: `string`  
Default: `users`

The name of the database table you created

### two_factor_enabled
Type: `boolean`  
Default: `true`

Is two factor authentication enabled?

### name
Type: `string`  
Default: `Authentication`

The name of the application, this is shown in the [Google Authenticator](https://en.wikipedia.org/wiki/Google_Authenticator) app.

### enable_registration
Type: `boolean`  
Default: `true`

Is registration enabled?

### login_template
Type: `string`  
Default:
```html
<form method="post">
    <input type="email" name="dvb_email" placeholder="Email" />
    <input type="password" name="dvb_password" placeholder="Password" />
    <input type="text" name="dvb_two_factory" placeholder="Two factory code" />
    <button type="submit" name="dvb_submit" value="dvb_login">Login</button>
</form>
```

This is the HTML template that is used to show the login page, if you override this template make sure all the input 
fields have the same names as shown in the default.

### register_template
Type: `string`  
Default:
```html
<form method="post">
    <input type="email" name="dvb_email" placeholder="Email" />
    <input type="password" name="dvb_password1" placeholder="Password" />
    <input type="password" name="dvb_password2" placeholder="Password verify" />
    <input type="text" name="dvb_firstname" placeholder="Firstname" />
    <input type="text" name="dvb_lastname" placeholder="Lastname" />
    <button type="submit" name="dvb_submit" value="dvb_register">Register</button>
</form>
```

This is the HTML template that is used to show the register page, if you override this template make sure all the input 
fields have the same names as shown in the default.

### two_factor_secret_template
Type: `string`  
Default: 
``` html
<form method="post">
    <p>Scan the QR-code with the Google Authenticator app and enter the verification code below</p>
    <input type="hidden" name="dvb_two_factor_secret" value="%s" />
    <img src="%s" /><input type="text" name="dvb_verify" placeholder="Verification code" />
    <button type="submit" name="dvb_submit" value="dvb_two_factor_verify">Verify</button>
</form>
```

This is the HTML template that is used to show the two factor setup page, if you override this template make sure all 
the input fields have the same names as shown in the default. Make also sure that the order of the `%s`'s are right, 
they are used in a `sprintf` function.

### two_factor_error_message
Type: `string`  
Default: 
```html
<p><a href="%s">Try again</a></p>
```

This is the HTML template that is shown below the error message on the two factor setup page.

### after_login_url
Type: `string`  
Default: `/`

The url to redirect to when an user logged in successfully

### after_register_url
Type: `string`  
Default: `/login`

The url to redirect to when a new user is registered
 
### login_url
Type: `string`  
Default: `/login`

The login page url 

### two_factor_setup_url
Type: `string`  
Default: `/two-factor-setup`

The two factor setup page url

