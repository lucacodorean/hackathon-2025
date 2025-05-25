# Budget Tracker – Evozon PHP Internship Hackathon 2025

## Starting from the skeleton

Prerequisites:

- PHP >= 8.1 with the usual extension installed, including PDO.
- [Composer](https://getcomposer.org/download)
- Sqlite3 (or another database tool that allows handling SQLite databases)
- Git
- A good PHP editor: PHPStorm or something similar

About the skeleton:

- The skeleton is built on Slim (`slim/slim : ^4.0`)
- The templating engine of choice is Twig (`slim/twig-view`)
- The dependency injection container of choice is `php-di/php-di`
- The database access layer of choice is plain PDO
- The configuration should be provided in a .env file (`vlucas/phpdotenv`)
- There is logging support by using `monolog/monolog`
- Input validation should be simply done using `webmozart/assert` and throwing Slim dedicated HTTP exceptions

## Step-by-step set-up

Install dependencies:

```
composer install
```

Set up the database:

```
cd database
./apply_migrations.sh
```

Note: be aware that, if you are using WSL2 (Windows Subsystem for Linux), you'll have trouble opening SQLite databases
with a DB management app (PHPStorm, for example) in Windows **when they are stored within the virtualized WSL2 drive**.
The solution is to store the `db.sqlite` file on the Windows drive (`/mnt/c`) and configure the path to the file in the
application config (`.env`):

```
cd database
./apply_migrations.sh /mnt/c/Users/<user>/AppData/Local/Temp/db.sqlite
```

Copy `.env.example` to `.env` and configure as necessary:

```
cp .env.example .env
```

Run the built-in server on http://localhost:8000

```
composer start
```

## Features

## Tasks

### Before you start coding

Make sure you inspect the skeleton and identify the important parts:

- `public/index.php` - the web entry point
- `app/Kernel.php` - DI container and application setup
- classes under `app` - this is where most of your code will go
- templates under `templates` are almost complete, at least in terms of static mark-up; all you need is to make use of
  the Twig syntax to make them dynamic.

### Main tasks — for having a functional application

Start coding: search for `// TODO: ...` and fill in the necessary logic. Don't limit yourself to that; you can do
whatever you want, design it the way you see fit. The TODOs are a starting point that you may choose to use.

### Extra tasks — for extra points

Solve extra requirements for extra points. Some of them you can implement from the start, others we prefer you to attack
after you have a fully functional application, should you have time left. More instructions on this in the assignment.

### Deliver well designed quality code

Before delivering your solution, make sure to:

- format every file and make sure there is no commented code left, and code looks spotless

- run static analysis tools to check for code issues:

```
composer analyze
```

- run unit tests (in case you added any):

```
composer test
```

A solution with passing analysis and unit tests will receive extra points.

## Delivery details

Participant:
- Full name: Luca-Andrei Codorean
- Email address: codoreanluca@gmail.com

Features fully implemented:

- Authentication with both Login and Register protected by CSRF.
- The register process also includes the password confirmation field.
- Authentication forms were adjusted to accommodate the final version of the authentication process.
- Log out.
- --
- Expenses with their entire requirements as follows:
  - Expenses list, including custom pagination and a user-friendly option to navigate through the pages.
  - Expenses-creation process as requested in the business requirements document.
  - Expenses-edit process as requested in the business requirements document.
  - Expenses-deletion process with the flash message placed. To be noted that no external package has been used so the actual version may be improved.
  - Expenses mass import using the csv files accompanied by the flash messages.
- --
- Dashboard requirements including alerts, and the monthly expenses summary.
- Categories information is stored in the ``.env``. An example of the implementation is presented in ``.env.example``.
- --
- Extra requirements that were implemented are:
  - Prepared statements, checks for the user not being able to operate over another user's expenses.
  - Composer analyze frequent fixes.
  - Migration system that enables the maintainers to keep track of the migrations that were run in the past.
  - Pagination that respects the 1...N format alongside the next and previous buttons.
  - CSV mass import failures protected by a transaction.
- -- 
- Extra requirements after a fully working application:
  - Unit tests for the CRUD operations made over the Expense Entity.
  - Soft deletion over the Expense Entity
  - amountCents column update from strings to real numbers. To be noted that the solution doesn't keep the previous amount_cents column, but the migration transfers the data to the new column as expected. More information can be found in ``migration1.sql``.

Other instructions about setting up the application (if any): ...
