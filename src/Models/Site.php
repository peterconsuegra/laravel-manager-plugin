<?php

namespace Pete\LaravelManager\Models;

use App\Models\Site as BaseSite;
use App\Services\PeteOption;
use Log;
use Illuminate\Support\Facades\Crypt; 
use App\Services\OMysql;

// Extending Site Model 
class Site extends BaseSite   
{
	public function set_laravel_url($site_id){
		$target_site = Site::findOrFail($site_id);
		$this->url = $this->name . '.' . $target_site->url;
	}

    public function delete_laravel(){
			
		$pete_options = new PeteOption();
	    $app_root = $pete_options->get_meta_value('app_root');
	
		$base_path = base_path();
		$script_path = $base_path."/vendor/peteconsuegra/laravel-manager-plugin/src";
		chdir("$script_path/scripts/");
			
		$command = "./delete_laravel.sh -n {$this->name} -r {$app_root}";
		$output = shell_exec($command);

		$host = env('DB_HOST') ?? 'localhost';
		if($host != "localhost")
			$host=$host.":3306";
		$db_user_pass = env('PETE_ROOT_PASS');

		if(isset($this->db_name))
			OMysql::delete_database($host,$this->db_name,$db_user_pass);
		
	  	if(env('PETE_DEBUG') == "active"){
			Log::info("######DELETE LOGIC DEBUG########");
			Log::info("COMMAND:");
  			Log::info($command);
	  		Log::info("OUTPUT:");
			Log::info($output);
  	  	}
	}

    public function create_laravel() {
		
		$pete_options = new PeteOption();
	    $app_root = $pete_options->get_meta_value('app_root');
        $mysql_bin = $pete_options->get_meta_value('mysql_bin');
	    $server_conf = $pete_options->get_meta_value('server_conf');
		$os = $pete_options->get_meta_value('os');
		$os_version = $pete_options->get_meta_value('os_version');
		$server = $pete_options->get_meta_value('server');
		$server_version = $pete_options->get_meta_value('server_version');
		$apache_version = $pete_options->get_meta_value('apache_version');
		
		$logs_route = $pete_options->get_meta_value('logs_route');
		$os_distribution = $pete_options->get_meta_value('os_distribution');
		
		$db_root_pass = env('PETE_ROOT_PASS');
		$mysqlcommand = $mysql_bin . "mysql";
		$debug = env('PETE_DEBUG');
		
		$base_path = base_path();
		
		//GIT
		$git_branch = isset($this->wordpress_laravel_git_branch) ? $this->wordpress_laravel_git_branch : "none";
		$git_url = isset($this->wordpress_laravel_git) ? $this->wordpress_laravel_git : "none";
		$laravel_version = isset($this->laravel_version) ? $this->laravel_version : "none";
		
		
		//CREATE DATABASE
	    $db_name = "db_" . substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);
		$db_user = "usr_" . substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);
		$db_user_pass = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);
		$db_host = 'localhost';
		OMysql::create_database($db_name);
        OMysql::create_user_and_grant($db_user, $db_user_pass, $db_name);
		
		$composer_bin = "composer";
			
		$command = "./create_laravel.sh -p {$db_root_pass} -r {$app_root} -m {$logs_route} -z {$os_distribution} -a {$server_conf} -b $git_branch -g {$git_url} -n {$this->name} -u {$this->url} -j {$os_version} -v {$os} -t {$server} -w {$server_version} -c {$this->action_name} -o {$laravel_version} -h {$composer_bin} -x {$db_host} -k {$debug} -q {$db_name} -y {$db_user} -i {$db_user_pass}";
		
        $script_path = $base_path."/vendor/peteconsuegra/laravel-manager-plugin/src";
		chdir("$script_path/scripts/");
		
	   	putenv("COMPOSER_HOME=/usr/local/bin/composer");
		putenv("COMPOSER_CACHE_DIR=~/.composer/cache");
	   	
		$output = shell_exec($command);
	  	if($debug == "active"){
			Log::info("Action: create_laravel");
			Log::info($command);
  			Log::info("Output:");
			Log::info($output);
	  	}
		$this->db_name = $db_name;
		$this->output = $this->output . "####### LARAVEL #######\n";	 
		$this->output .= $output;
	   	$this->save();
	  
	}

     public function create_config_file() {

        $debug = env('PETE_DEBUG');
        $base_path = base_path();
        $pete_options = new PeteOption();
	    $app_root = $pete_options->get_meta_value('app_root');
        $logs_route = $pete_options->get_meta_value('logs_route');
        $server_conf = $pete_options->get_meta_value('server_conf');
        $site_url = $this->url;
       
		$command = "./create_config_file.sh -u {$this->url} -a {$app_root} -n {$this->name} -z {$logs_route} -s {$server_conf}";
		
        $script_path = $base_path."/vendor/peteconsuegra/laravel-manager-plugin/src";
		chdir("$script_path/scripts/");

        $output = shell_exec($command);
	  	if($debug == "active"){
			Log::info("Action: create_laravel");
			Log::info($command);
  			Log::info("Output:");
			Log::info($output);
	  	}

        $this->output .= $output;
	   	$this->save();

     }

}
