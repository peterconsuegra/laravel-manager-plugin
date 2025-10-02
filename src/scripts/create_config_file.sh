#!/bin/bash

while getopts u:a:n:z:s: option 
do 
case "${option}" 
	in 
	u) project_url=${OPTARG};;
	a) app_root=${OPTARG};;
	n) project_name=${OPTARG};;
	z) logs_route=${OPTARG};;
	s) server_conf=${OPTARG};;
esac 
done


echo "
<VirtualHost *:80>
		
    ServerName $project_url
	ServerAlias www.$project_url
	DocumentRoot $app_root/$project_name/public		
			
	<Directory $app_root/$project_name>
	    SetOutputFilter DEFLATE
		Options FollowSymLinks
		AllowOverride All
		Require all granted
	</Directory>

	<FilesMatch '\.php$'>
   	    # neutralise any other handler that may already be set
    	SetHandler none
    	# now forward to PHP-FPM (the 'php' service from docker-compose)
    	SetHandler 'proxy:fcgi://php:9000'
	</FilesMatch>

	# optional – if you use +MultiViews anywhere
	<IfModule mod_negotiation.c>
        Options -MultiViews
	</IfModule>
			
	ErrorLog $logs_route/$project_name/error.log
	CustomLog $logs_route/$project_name/access.log combined
			
</VirtualHost>" > $server_conf/$project_name.conf

cd /etc/apache2/sites-enabled && ln -s /etc/apache2/sites-available/$project_name.conf $project_name.conf