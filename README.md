# tibi-credo
Application to manage signature sheets for direct democratic votes in Switzerland.

# Installation

* Install docker
* install ddev (https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/) using e.g. apt
* start containers using ddev start
* run composer install in the container (enter container using ddev ssh)
* copy .env.example to .env and set APP_ENV=development (also other  changes)
* In folder database touch database.sqlite.
* php artisan migrate
* create admin user with php artisan shield:super-admin
* php artisan db:seed --class=ShieldSeeder creates something necessary in the db, otherwise half the app is missing