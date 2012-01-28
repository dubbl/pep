<?php

	class View 
	{
		private $template;
		private $theme;
		private $view;
		
		public function __construct($view)
		{
			header('Content-type: text/html; charset=utf-8');
			
			$this->theme = Pep::get_setting('theme');
			$this->view = $view;
			
			if(empty($this->theme))
			{
				$this->template = APP_DIR .'views/'. $view .'.php';
			}
			else
			{
				$this->template = THEME_DIR . $this->theme .'/'. $view .'.html';
				require_once(ROOT_DIR.'system/core/parser.php');
			}
		}
		
		public function render($data = null)
		{
			if(empty($this->theme))
			{
				$this->render_view($data);
			}
			else
			{
				if(file_exists($this->template))
				{
					$parser = new Parser();
					echo $parser->parse(file_get_contents($this->template), $data, 'View::parse_callback');
				}
				else
				{
					// Fall back to application view if no theme file exists. 
					$this->template = APP_DIR .'views/'. $this->view .'.php';
					$this->render_view($data);
				}
			}
		}
		
		public static function parse_callback($name, $attributes, $content)
		{
			if($name == 'lang')
			{
				$language = Pep::get_setting('language');
				
				if(!empty($language))
				{
					$file = APP_DIR . 'languages/' .strtolower($language). '.php';
					
					if(file_exists($file))
					{
						require_once($file);
						return $lang[$attributes['name']];
					}
					else
					{
						Pep::show_error(sprintf('The language file %s.php does not exist.', $language));
					}
				}
			}
		}
		
		private function render_view($data = null)
		{
			$language = Pep::get_setting('language');
			
			if(!empty($language))
			{
				$file = APP_DIR . 'languages/' .strtolower($language). '.php';
				
				if(file_exists($file)) require_once($file);
				else Pep::show_error(sprintf('The language file %s.php does not exist.', $language));
			}
			
			if($data) extract($data);
			
			if(file_exists($this->template))
			{
				ob_start();
				require($this->template);
				echo ob_get_clean();
			}
			else
			{
				// No view exists at all.
				Pep::show_error(sprintf('The view file %s.php does not exist.', $this->view));
			}
		}
	}

?>