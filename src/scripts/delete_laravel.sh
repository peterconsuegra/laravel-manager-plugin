#!/bin/bash

while getopts n:r: option 
do 
case "${option}" 
	in 
	n) project_name=${OPTARG};; 
	r) route=${OPTARG};; 
esac 
done 

project_route=$route/$project_name
rm -rf $project_route

rm /etc/apache2/sites-enabled/$project_name.conf
rm /etc/apache2/sites-available/$project_name.conf

