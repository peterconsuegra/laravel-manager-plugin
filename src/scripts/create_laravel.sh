#!/bin/bash

while getopts p:r:m:z:a:b:g:u:j:v:n:l:e:t:w:c:o:h:x:k:q:y:i:s:d: option 
do 
case "${option}" 
	in 
	p) db_root_pass=${OPTARG};;
	r) route=${OPTARG};;
	m) logs_route=${OPTARG};;
	z) os_distribution=${OPTARG};;
	a) server_conf=${OPTARG};;
	b) wordpress_laravel_git_branch=${OPTARG};;
	g) wordpress_laravel_git=${OPTARG};;
	u) wordpress_laravel_url=${OPTARG};;
	j) os_version=${OPTARG};;
	v) os=${OPTARG};;
	n) project_name=${OPTARG};;
	t) server=${OPTARG};;
	w) server_version=${OPTARG};;
	c) action_name=${OPTARG};;
	o) laravel_version=${OPTARG};;
	h) composer_bin=${OPTARG};;
	x) db_host=${OPTARG};;
	k) debug=${OPTARG};;
	q) odb=${OPTARG};;
	y) udb=${OPTARG};;
	i) pdb=${OPTARG};;
esac 
done

#DEBUG
if [[ $debug == "active" ]]; then
	
echo "db_root_pass: $db_root_pass"
echo "app_root: $route" 
echo "logs_route: $logs_route"
echo "logs_route: $logs_route"
echo "wordpress_laravel_name: $wordpress_laravel_name"
echo "server_conf: $server_conf"
echo "wordpress_laravel_git_branch: $wordpress_laravel_git_branch"
echo "wordpress_laravel_git: $wordpress_laravel_git"
echo "wordpress_laravel_url: $wordpress_laravel_url"
echo "os_version: $os_version"
echo "os: $os"
echo "project_name: $project_name"
echo "debug: $debug"
echo "DB: "$odb
echo "UDB: "$udb
echo "PDB: "$pdb
echo "composer_bin var: $composer_bin"
echo "db_host: $db_host"
echo "action_name: $action_name"
fi

project_route=$route/$project_name

if [[ $action_name == "new_laravel" ]]; then
	echo "check2"
	mkdir $project_route
	echo "cd $project_route && $composer_bin create-project laravel/laravel="$laravel_version" . --prefer-dist 2>&1"
	cd $project_route && $composer_bin create-project laravel/laravel="$laravel_version" . --prefer-dist 2>&1

elif [[ $action_name == "import_laravel" ]]; then
	echo "check3"
	mkdir $project_route
	echo "cd $project_route && git clone -b $wordpress_laravel_git_branch $wordpress_laravel_git ."
	cd $project_route && git clone -b $wordpress_laravel_git_branch $wordpress_laravel_git .
	echo "cd $project_route && $composer_bin install --ignore-platform-reqs 2>&1"
	cd $project_route && $composer_bin install --ignore-platform-reqs 2>&1
	
	#wpml error patch
	echo "rm $project_route/vendor/laravel/framework/src/illuminate/Foundation/helpers.php"
	rm $project_route/vendor/laravel/framework/src/illuminate/Foundation/helpers.php
	echo "cp $route/Pete/templates/helpers.php $project_route/vendor/laravel/framework/src/illuminate/Foundation/helpers.php"
	cp $route/Pete/templates/helpers.php $project_route/vendor/laravel/framework/src/illuminate/Foundation/helpers.php
	
fi

#APACHE OPTIONS#############
############################


rm -rf $project_route/.env
	
echo "
APP_ENV=local
APP_DEBUG=true

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_DRIVER=sync

MAIL_DRIVER=smtp
MAIL_HOST=mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null

DB_CONNECTION=mysql
DB_HOST=$db_host
DB_PORT=3306
DB_DATABASE=$odb
DB_USERNAME=$udb
DB_PASSWORD=$pdb

" > $project_route/.env
	
mkdir $logs_route/$project_name
touch $logs_route/$project_name/error.log
touch $logs_route/$project_name/access.log
	
	
find $project_route -type f -exec chmod 644 {} \;    
find $project_route -type d -exec chmod 755 {} \;
chmod -R ug+rwx storage $project_route/bootstrap/cache

echo "Running the following commands..."
echo "cd $route/$project_name && php artisan key:generate"
echo "cd $route/$project_name && php artisan migrate"

#HACK TO GENERATE APP_KEY
echo "`cd $project_route && php artisan key:generate --show`"
laravel_key=`cd $project_route && php artisan key:generate --show`
echo "APP_KEY=$laravel_key" >> $project_route/.env
cd $project_route && php artisan migrate