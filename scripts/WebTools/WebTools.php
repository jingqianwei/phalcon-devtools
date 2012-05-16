<?php

/*
  +------------------------------------------------------------------------+
  | Phalcon Framework                                                      |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2012 Phalcon Team (http://www.phalconphp.com)       |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file docs/LICENSE.txt.                        |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
  |          Eduar Carvajal <eduar@phalconphp.com>                         |
  +------------------------------------------------------------------------+
*/

/**
 * Phalcon_WebTools
 *
 * Allows to use Phalcon Developer Tools with a web interface
 *
 * @category 	Phalcon
 * @package 	Scripts
 * @copyright   Copyright (c) 2011-2012 Phalcon Team (team@phalconphp.com)
 * @license 	New BSD License
 */
class Phalcon_WebTools {

	private $_path;

	private $_uri;

	private $_settings;

	private $_posibleConfig = array(
		'phalcon' => array(
			'controllersDir' => 'string',
			'modelsDir' => 'string',
			'viewsDir' => 'string',
			'baseUri' => 'string',
			'basePath' => 'string',
		),
		'database' => array(
			'adapter' => 'string',
			'host' => 'string',
			'name' => 'string',
			'username' => 'string',
			'password' => 'string',
		)
	);

	/**
	 * Load the default config in the project
	 */
	public function __construct($uri, $path){
		$this->_path = $path;
		$this->_uri = $uri;
		$this->readConfig();
	}

	public function readConfig(){
		$configPath = $this->_path."/app/config/config.ini";
		if(file_exists($configPath)){
			$this->_settings = new Phalcon_Config_Adapter_Ini($configPath);
		} else {
			throw new Phalcon_Exception('Configuration file could not be loaded');
		}
	}

	/**
	 * Starts connection to DB by config.ini
	 */
	public function getConnection(){
		$connection = Phalcon_Db::factory($this->_settings->database->adapter, $this->_settings->database, true);
		$connection->setFetchMode(Phalcon_Db::DB_NUM);
		return $connection;
	}

	/**
	 * Makes HTML view to Controllers
	 */
	public function getControllers(){

		$html = '';
		$request = Phalcon_Request::getInstance();

		if($request->getQuery('subaction')=='list'){

		}

		if($request->isPost()){

			if($request->getQuery('subaction')=='create'){

				$name = $request->getPost('name', 'string');
				$force = $request->getPost('force', 'int');

				try {

					$modelBuilder = Phalcon_Builder::factory('Controller', array(
						'name' => $name,
						'directory' => $this->_path,
						'force' => $force
					));

					$modelBuilder->build();

					$html = '<div class="alert alert-success">The controller "'.$name.'" was created successfully</div>';
				}
				catch(Phalcon_BuilderException $e){
					$html = '<div class="alert alert-error">'.$e->getMessage().'</div>';
				}

			}

		}

		$html .= '<div class="span9">

			<p><h1>Create Controller</h1></p>

			<form method="POST" class="forma-horizontal" action="'.$this->_uri.'/webtools.php?action=controllers&subaction=create">
				<fieldset>
					<div class="control-group">
						<label class="control-label" for="name">Controller name</label>
						<div class="controls">
							'.Phalcon_Tag::textField(array('name', 'placeholder' => 'Name ...')).'
						</div>
					</div>
					<label class="checkbox">
						<input type="checkbox" name="force" value="1">Force
					</label>
					<div align="right">
						<input type="submit" class="btn btn-primary" value="Generate"/>
					</div>
				</fieldset>
			</form>
		</div>';

		return $html;
	}

	/**
	* Make HTML view to Models
	*/
	public function getModels(){

		$connection = $this->getConnection();

		$tables = array();
		$result = $connection->query("SHOW TABLES");
		while($table = $connection->fetchArray($result)){
			$tables[$table[0]]=$table[0];
		}

		$html = '<div class="span9">

			<p><h1>Generate Models</h1></p>

			<form method="post" class="forma-horizontal" action="'.$this->_uri.'/webtools.php?action=createModel">
				<table class="table table-striped table-bordered table-condensed">
					<tr>
						<td><b>Schema</b></td>
						<td>'.Phalcon_Tag::textField(array('schema', 'value' => $this->_settings->database->name)).'</td>
					</tr>
					<!--<tr>
						<td><b>All models</b></td>
						<td><i><input type="checkbox" name="allModels" value="1" /></i></td>
					</tr>-->
					<tr>
						<td><b>Table name</b></td>
						<td><i>'.Phalcon_Tag::selectStatic('table-name', $tables).'</i></td>
					</tr>
					<tr>
						<td><b>Add setters and getters</b></td>
						<td><i><input type="checkbox" name="gen-setters-getters" checked="checked" value="1"/></i></td>
					</tr>
					<tr>
						<td><b>Force</b></td>
						<td><i><input type="checkbox" name="force" value="1"/></i></td>
					</tr>
					<tr>
						<td colspan="2">
							<div align="right">
								<input type="submit" class="btn btn-primary" value="Generate"/>
							</div>
						</td>
					</tr>
				</table>
			</form>
		</div>';

		return $html;
	}

	public function createModel(){

		$html = '';
		$request = Phalcon_Request::getInstance();

		$force = $request->getPost('force', 'int');
		$schema = $request->getPost('schema');
		$tableName = $request->getPost('table-name');
		$genSettersGetters = $request->getPost('gen-setters-getters', 'int');

		try {

			$modelBuilder = Phalcon_Builder::factory('Model', array(
				'name' => $tableName,
				'genSettersGetters' => $genSettersGetters,
				'directory' => $this->_path,
				'force' => $force
			));

			$html = $modelBuilder->build();

			$html = '<div class="alert alert-success">The model "'.$tableName.'" was created successfully</div>';
		}
		catch(Phalcon_BuilderException $e){
			$html = '<div class="alert alert-error">'.$e->getMessage().'</div>';
		}

		$html .= $this->getModels();

		return $html;
	}

	/**
	 * Makes HTML view to Scaffold
	 */
	public function getScaffold()	{

		$connection = $this->getConnection();

		$tables = array();
		$result = $connection->query("SHOW TABLES");
		while($table = $connection->fetchArray($result)){
			$tables[$table[0]]=$table[0];
		}

		$html = '<div class="span9">

			<p><h1>Generate Scaffold</h1></p>

			<form class="forma-horizontal" action="'.$this->_uri.'/webtools.php?action=generateScaffold">
				<table class="table table-striped table-bordered table-condensed">
					<tr>
						<td><b>Schema</b></td>
						<td><i>'.$this->_settings->database->name.'</i></td>
					</tr>
					<tr>
						<td><b>Table name</b></td>
						<td><i>'.Phalcon_Tag::selectStatic('table-name', $tables).'</i></td>
					</tr>
					<tr>
						<td><b>Force</b></td>
						<td><i><input type="checkbox" name="force" /></i></td>
					</tr>
					<tr>
						<td colspan="2">
							<div align="right">
								<input type="submit" class="btn btn-primary" value="Generate"/>
							</div>
						</td>
					</tr>
				</table>
			</form>
		</div>';

		return $html;
	}

	/**
	 * Makes HTML view to Migration
	 */
	public function getMigration()	{

		$html = '';
		$migrationsDir = $this->_path.'/app/migrations';

		if(!file_exists($migrationsDir)){
			mkdir($migrationsDir);
		}

		$request = Phalcon_Request::getInstance();

		$folders = array();
		foreach(scandir($migrationsDir) as $item){
			if (is_file($item) || $item=='.' || $item=='..') {
				continue;
			}
			$folders[$item]= $item;
		}
		natsort($folders);
		$folders = array_reverse($folders);
		$foldersKeys = array_keys($folders);

		$connection = $this->getConnection();
		$tables = array('all' => 'All');
		$result = $connection->query("SHOW TABLES");
		while($table = $connection->fetchArray($result)){
			$tables[$table[0]] = $table[0];
		}

		if($request->isPost()){

			require_once 'scripts/Migrations/Migrations.php';
			require_once 'scripts/Version/Version.php';
			require_once 'scripts/Model/Migration.php';
			require_once 'scripts/Model/Migration/Profiler.php';
			require_once 'scripts/Script/ScriptException.php';

			if($request->getQuery('subaction')=='create'){

				$tableName = $request->getPost('table-name', 'string');
				$version = $request->getPost('version', 'string');
				$force = $request->getPost('force', 'int');
				$exportData = '';

				try {

					ob_start();
					Phalcon_Migrations::generate(array(
						'config' => $this->_settings,
						'directory' => $this->_path,
						'tableName' => $tableName,
						'exportData' => $exportData,
						'migrationsDir' => $migrationsDir,
						'originalVersion' => $version,
						'force' => $force
					));
					$html = ob_get_contents();
					ob_end_clean();

					$_GET['subaction'] = '';
					if(!$version){
						$version = $foldersKeys[0];
					}

					$html .= '<div class="alert alert-success">The migration was created successfully</div>';
				}
				catch(Phalcon_BuilderException $e){
					$html .= '<div class="alert alert-error">'.$e->getMessage().'</div>';
				}

			} else {

				if($request->getQuery('subaction')=='run'){
					echo "a";
					$version = '';
					$force = '';
					$exportData = '';

					try {

						ob_start();
						$migrationOut = Phalcon_Migrations::run(array(
							'config' => $this->_settings,
							'directory' => $this->_path,
							'tableName' => 'all',
							'migrationsDir' => $migrationDir
						));
						$html = ob_get_contents();
						ob_end_clean();

						$_GET['subaction'] = 'list';
						if(!$version){
							$version = $foldersKeys[0];
						}

						$html .= '<div class="alert alert-success">The migration "'.$version.'" was executed successfully</div>';
					}
					catch(Phalcon_BuilderException $e){
						$html .= '<div class="alert alert-error">'.$e->getMessage().'</div>';
					}

				}
			}

		}

		$html .= '<div class="span9">
				<p><h1>Generate  Migration</h1></p>';

		if(!$request->getQuery('subaction')){

			if(!isset($foldersKeys[0])){
				$version = 'None';
			} else {
				$version = $foldersKeys[0];
			}

			//Generate
			$html .= '
				<form method="POST" class="forma-horizontal" action="'.$this->_uri.'/webtools.php?action=migration&subaction=create">
					<table class="table table-striped table-bordered table-condensed">
						<tr>
							<td><b>Current Version</b></td>
							<td><i>'.$version.'</i></td>
						</tr>
						<tr>
							<td><b>New Version</b></td>
							<td>'.Phalcon_Tag::textField(array('version', 'value' => '', 'placeholder' => 'Let empty to auto new version')).'</td>
						</tr>
						<tr>
							<td><b>Table name</b></td>
							<td><i>'.Phalcon_Tag::selectStatic('table-name', $tables).'</i></td>
						</tr>
						<tr>
							<td><b>Force</b></td>
							<td><i><input type="checkbox" name="force" /></i></td>
						</tr>
						<tr>
							<td colspan="2">
								<div align="right">
									<input type="submit" class="btn btn-primary" value="Generate"/>
								</div>
							</td>
						</tr>
					</table>
				</form>';
		} else {

			//List
			$html .= '
				<form method="POST" class="forma-horizontal" action="'.$this->_uri.'/webtools.php?action=migration&subaction=run">
					<table class="table table-striped table-bordered table-condensed">
						<tr>
							<td><b>Current Version</b></td>
							<td><i>'.$foldersKeys[0].'</i></td>
						</tr>
						<tr>
							<td colspan="2">
								<div align="right">
									<input type="submit" class="btn btn-primary" value="Generate"/>
								</div>
							</td>
						</tr>
					</table>
				</form>';
		}

		$html .= '</div>';

		return $html;
	}

	public function getConfig()	{

		$html = '<div class="span7"><p><h1>Edit Configuration</h1></p>';
		$html .= '<form method="post" action="'.$this->_uri.'/webtools.php?action=saveConfig">';
		$html .= '<div align="right"><input type="submit" class="btn btn-success" value="Save"/></div>';
		foreach($this->_posibleConfig as $section => $config){
			$html.= '<p><h3>'.$section.'</h3></p>';
			$html.= '<table class="table table-striped table-bordered table-condensed">';
			foreach($config as $name => $type){
				if(isset($this->_settings->$section->$name)){
					$value = $this->_settings->$section->$name;
				} else {
					$value = '';
				}
				$html.='<tr>
					<td><b>'.$name.'</b></td>
					<td>'.Phalcon_Tag::textField(array($name, 'value' => $value)).'</i></td>
				</tr>';
			}
			$html.= '</table>';
		}
		$html.= '</form></div>';

		return $html;
	}

	public function saveConfig(){

		$newConfig = array();
		$request = Phalcon_Request::getInstance();
		foreach($this->_posibleConfig as $section => $config){
			foreach($config as $name => $type){
				if(isset($_POST[$name])){
					$newConfig[$section][$name] = $request->getPost($name, $type);
				}
			}
		}

		$ini = '';
		foreach($newConfig as $section => $settings){
			$ini.='['.$section.']'.PHP_EOL;
			foreach($settings as $name => $value){
				$ini.=$name.' = '.$value.PHP_EOL;
			}
			$ini.=PHP_EOL;
		}

		$configPath = $this->_path."/app/config/config.ini";
		if(is_writable($configPath)){
			file_put_contents($configPath, $ini);
			$html = '<div class="alert alert-success">Configuration was successfully updated</div>';
		} else {
			$html = '<div class="alert alert-error">Sorry, configuration file is not writable</div>';
		}

		$this->readConfig();

		$html.= $this->getConfig();

		return $html;
	}

	/**
	 * Checks remote address ip to disable remote activity
	 */
	public static function checkIp(){
		if(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR']=='127.0.0.1'){
			return false;
		} else {
			throw new Phalcon_Exception('WebDeveloperTools can only be used on the local machine');
		}
	}

	/**
	 * Applies main template to
	 */
	public static function applyTemplate($uri, $body){
		return '<!DOCTYPE html>
		<html>
			<head>
				<title>Phalcon PHP Framework - Web DevTools.</title>
				<link rel="stylesheet" type="text/css" href="'.$uri.'/css/bootstrap/bootstrap.min.css">
			</head>
			<body>
				<div class="navbar">
					<div class="navbar-inner">
						<div class="container">
							<a data-target=".nav-collapse" data-toggle="collapse" class="btn btn-navbar">
								<span class="icon-bar"></span>
	          					<span class="icon-bar"></span>
	          					<span class="icon-bar"></span>
        					</a>
        					<a href="#" class="brand">Phalcon Web Tools</a>
        					<div class="nav-collapse">
          						<ul class="nav">'.self::getNavMenu($uri).'</ul>
        					</div>
      					</div>
    				</div>
  				</div>
				<div class="container-fluid">
				    <div class="row-fluid">
					    <div class="span2">
					    	<!--Sidebar content-->
					    	<div style="padding: 8px 0;" class="well">
							    <ul class="nav nav-list">
							      '.self::getMenu($uri).'
							    </ul>
							</div>
					    </div>
					    <div class="span9 well">
					    	<!--Body content-->
					    	'.$body.'
					    </div>
				    </div>
			    </div>
			    <script type="text/javascript" href="'.$uri.'/javascript/bootstrap/bootstrap.min.js"></script>
			</body>
		</html>';
	}

	public static function getNavMenu($uri){
		$options = array(
			'home' => array(
				'caption' => 'Home',
			),
			'controllers' => array(
				'caption' => 'Controllers',
			),
			'models' => array(
				'caption' => 'Models',
			),
			'scaffold' => array(
				'caption' => 'Scaffold'
			),
			'migration' => array(
				'caption' => 'Migrations'
			),
			'config' => array(
				'caption' => 'Configuration'
			),
		);
		$code = '';
		$activeAction = isset($_GET['action']) ? $_GET['action'] : 'home';
		foreach($options as $action => $option){
			if($activeAction==$action){
				$code.= '<li class="active"><a href="'.$uri.'/webtools.php?action='.$action.'">'.$option['caption'].'</a></li>'.PHP_EOL;
			} else {
				$code.= '<li><a href="'.$uri.'/webtools.php?action='.$action.'">'.$option['caption'].'</a></li>'.PHP_EOL;
			}
		}
		return $code;
	}

	public static function getMenu($uri){

		$activeAction = isset($_GET['action']) ? $_GET['action'] : 'home';
		$activesubaction = isset($_GET['subaction']) ? $_GET['subaction'] : '';

		$options = array(
			'home' => array(
				'' => array(
					'caption' => 'home'
				)
			),
			'controllers' => array(
				'' => array(
					'caption' => 'Generate',
				),
				'list' => array(
					'caption' => 'List',
				)
			),
			'models' => array(
				'' => array(
					'caption' => 'Generate'
				),
				'list' => array(
					'caption' => 'List',
				)
			),
			'scaffold' => array(
				'' => array(
					'caption' => 'Generate'
				)
			),
			'migration' => array(
				'' => array(
					'caption' => 'Generate'
				),
				'run' => array(
					'caption' => 'Run'
				)
			),
			'config' => array(
				'' => array(
					'caption' => 'Edit'
				)
			),
		);

		$code = '';
		foreach($options[$activeAction] as $subaction => $option){
			if($activesubaction==$subaction){
				$code.= '<li class="active"><a href="'.$uri.'/webtools.php?action='.$activeAction.'&subaction='.$subaction.'">'.$option['caption'].'</a></li>'.PHP_EOL;
			} else {
				$code.= '<li><a href="'.$uri.'/webtools.php?action='.$activeAction.'&subaction='.$subaction.'">'.$option['caption'].'</a></li>'.PHP_EOL;
			}
		}
		return $code;
	}

	public function dispatch(){
		switch ($_GET['action']) {

			case 'controllers':
				return $this->getControllers();
				break;

			case 'models':
				return $this->getModels();
				break;

			case 'createModel':
				return $this->createModel();
				break;

			case 'scaffold':
				return $this->getScaffold();
				break;

			case 'generateScaffold':
				return $this->generateScaffold();
				break;

			case 'migration':
				return $this->getMigration();
				break;

			case 'config':
				return $this->getConfig();
				break;

			case 'saveConfig':
				return $this->saveConfig();
				break;

			case 'home':
				break;

			default:
				return '<div class="alert alert-error">Unknown action</div>';
				break;
		}
	}

	public static function main($path){

		if(isset($_SERVER['PHP_SELF'])){
			$uri = '/'.join(array_slice(explode('/' , dirname($_SERVER['PHP_SELF'])), 1, -1), '/');
		} else {
			$uri = '/';
		}

		try {
			$webTools = new self($uri, $path);
			if(isset($_GET['action']) && $_GET['action']){
				$body = $webTools->dispatch();
			} else {
				$body = '<p><h1>Welcome to Web Developer Tools</h1></p>';
    			$body.= '<p>This application allows you to use Phalcon Developer Tools using a web interface.</p>';
			}
		}
		catch(Phalcon_Exception $e){
			$body = '<div class="alert alert-error">'.$e->getMessage().'</div>';
		}

		if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH']!='XMLHttpRequest'){
			$body = self::applyTemplate($uri, $body);
		}

		echo $body;
	}

}
