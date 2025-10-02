#!/bin/bash

while getopts v:n:r:q:a:s:p:o:l:d: option 
do 
case "${option}" 
	in 
	v) os=${OPTARG};;
	n) project_name=${OPTARG};; 
	r) route=${OPTARG};; 
	q) base_path=${OPTARG};; 
	a) server_conf=${OPTARG};;
	s) id=${OPTARG};;
	p) os_distribution=${OPTARG};;
	o) integration_type=${OPTARG};;
	l) wp_load_path=${OPTARG};;
	d) wordpress_laravel_name=${OPTARG};;
esac 
done 


if test "$integration_type" = 'inside_wordpress'; then
	project_route=$wp_load_path/$wordpress_laravel_name
else
	project_route=$route/$project_name
fi

rm -rf $project_route

if test "$integration_type" = 'separate_subdomain'; then
	rm /etc/apache2/sites-enabled/$project_name.conf
	rm /etc/apache2/sites-available/$project_name.conf
fi
