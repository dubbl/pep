<?php

	class Load
	{
		public function model($name)
		{
			$file = APP_DIR . 'models/' . strtolower($name) . '.php';
			
			if(file_exists($file))
			{
				require_once($file);
				$model = new $name;
				return $model;
			}
			else
			{
				Pep::show_error(sprintf('The model file %s.php failed to load.', $name));
			}
		}
		
		public function view($name)
		{
			$view = new View($name);
			return $view;
		}
		
		public function lang($name)
		{
			$file = APP_DIR . 'languages/' . strtolower($name) . '.php';
			
			if(file_exists($file))
			{
				include($file);
				return $lang;
			}
			else
			{
				Pep::show_error(sprintf('The language file %s.php failed to load.', $name));
			}
		}
		
		public function helper($name)
		{
			$file = APP_DIR . 'helpers/' . strtolower($name) . '.php';
			
			if(file_exists($file))
			{
				require_once($file);
				$helper = new $name;
				return $helper;
			}
			else
			{
				Pep::show_error(sprintf('The helper file %s.php failed to load.', $name));
			}
		}
	}
	
?>