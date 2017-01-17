<?php
/**
 * Pts Prestashop Theme Framework for Prestashop 1.6.x
 *
 * @package   ptsthemepanel
 * @version   1.6
 * @author    http://www.prestabrain.com
 * @copyright Copyright (C) October 2013 prestabrain.com <@emai:prestabrain@gmail.com>
 *               <info@prestabrain.com>.All rights reserved.
 * @license   GNU General Public License version 2
 */

if (!class_exists('PtsThemeSample'))
{
	/**
	 * PtsFrameworkHelper Class
	 */
	class PtsThemeSample {

		protected $theme = '';
		protected $theme_sample_dir = '';
		protected $backup_dir = '';
		protected $modules_errors = array();

		public function __construct($theme)
		{
			$this->theme = $theme;
			$this->theme_sample_dir = _PS_ALL_THEMES_DIR_.$theme.'/features/sample/';
			$this->backup_dir = _PS_CACHE_DIR_.'samples/'.$theme.'/';

			if (!is_dir(_PS_CACHE_DIR_.'samples/'))
				mkdir(_PS_CACHE_DIR_.'samples/', 0755);
			if (!is_dir(_PS_CACHE_DIR_.'samples/'.$theme))
				mkdir(_PS_CACHE_DIR_.'samples/'.$theme, 0755);
		}

		public function export()
		{
			$modules = self::getModuleSamples($this->theme);
			if (is_array($modules) && !empty($modules))
			{
				$this->exportModulesSkin($modules);
				foreach ($modules as $module)
					if ($module['name'])
					{
						$string = $this->exportModule($module);
						PtsThemePanelHelper::writeToCache($this->theme_sample_dir, $module['name'], $string, 'xml');
						$string = $this->exportModuleQueries($module);
						if ($string)
							PtsThemePanelHelper::writeToCache($this->theme_sample_dir, $module['name'], $string, 'php');
					}
			}
		}

		public function exportModulesSkin($modules)
		{
			if (Tools::getValue('skinname'))
			{
				$xml = '<?xml version="1.0" encoding="UTF-8" ?>'.PHP_EOL.'<modules>';
				foreach ($modules as $module)
				{
					$object = Module::getInstanceByName(urldecode($module['name']));
					if ($object)
					{
						$configs = $this->exportConfig($module);
						$xml .= '<module name="'.urldecode($module['name']).'" action="'.($module->active ? 'enable' : 'disable').'"><config>'.PHP_EOL;
						foreach ($configs as $config => $value)
							$xml .= '<'.trim($config).'>'.(!empty($value) && preg_match('#&#', $value)
								? '<![CDATA[ '.( $value ).' ]]>' : $value).'</'.trim($config).'>'.PHP_EOL;
						$xml .= '</config>';
						$xml .= PHP_EOL.'</module>'.PHP_EOL;
					}
				}
				if (!is_dir($this->theme_sample_dir.'/skins/'))
					mkdir($this->theme_sample_dir.'/skins/', 0777);
				PtsThemePanelHelper::writeToCache($this->theme_sample_dir.'/skins/', 'modules_'.Tools::getValue('skinname'), $xml.'</modules>', 'xml');
			}
		}

		public static function isReHookBySkin($theme, $skin)
		{
			return file_exists(_PS_ALL_THEMES_DIR_.$theme.'/features/sample/skins/configs_'.$skin.'.xml');
		}

		public function reHookBySkin($skin)
		{
			$modules = $this->theme_sample_dir.'skins/modules_'.$skin.'.xml';
			$configs = $this->theme_sample_dir.'skins/configs_'.$skin.'.xml';

			if (is_file($configs))
			{
				$xml = simplexml_load_file($configs);
				self::hooksAllModuleWithXML($xml);
			}
			if (is_file($modules))
			{
				$xml = simplexml_load_file($modules);
				foreach ($xml->module as $module)
				{
					$config = get_object_vars($module->config);
					foreach ($config as $key => $value)
					{
						$value = preg_replace('#!s:(\d+):"(.*?)";!e#', '\'s:\'.strlen(\'$2\').\':"$2";\'', (string)$value);
						Configuration::updateValue((string)trim($key), trim((string)$value), true);
					}
				}
			}
		}

		/*
		 * export db struct to download file
		 */

		public function exportDBStruct()
		{
			$ignore_insert_table = array(_DB_PREFIX_.'sekeyword',
				_DB_PREFIX_.'statssearch', _DB_PREFIX_.'favorite_product',
				_DB_PREFIX_.'pagenotfound');
			$install_folder = $this->theme_sample_dir.'sql';
			if (!is_dir($install_folder))
				mkdir($install_folder, 0755);
			$backupfile = $install_folder.'/db_structure.sql';

			$fp = fopen($backupfile, 'w');
			if ($fp === false)
			{
				$this->_html['error'] = 'Error when export DbStruct! Can not write file in leotemcp/install';
				return false;
			}
			fwrite($fp, 'SET NAMES \'utf8\';'.PHP_EOL);
			// Find all tables
			$tables = Db::getInstance()->executeS('SHOW TABLES');
			//$found = 0;
			$data = '';

			foreach ($tables as $table)
			{
				$table = current($table);
				// Skip tables which do not start with _DB_PREFIX_
				if (Tools::strlen($table) < Tools::strlen(_DB_PREFIX_) || strncmp($table, _DB_PREFIX_, Tools::strlen(_DB_PREFIX_)) != 0)
					continue;
				// Export the table schema
				$schema = Db::getInstance()->executeS('SHOW CREATE TABLE `'.$table.'`');
				if (in_array($schema[0]['Table'], $ignore_insert_table))
					continue;

				$data .= $schema[0]['Create Table'].';'.PHP_EOL.PHP_EOL;
				if (count($schema) != 1 || !isset($schema[0]['Table']) || !isset($schema[0]['Create Table']))
				{
					fclose($fp);
					$this->_html['error'] = 'An error occurred while backing up. Unable to obtain the schema of '.$table;
					return false;
				}
			}

			$data = str_replace('CREATE TABLE `'._DB_PREFIX_, 'CREATE TABLE `PREFIX_', $data);
			//$tableName = str_replace(_DB_PREFIX_, '_DB_PREFIX_', $table);
			fwrite($fp, $data);
			fclose($fp);
			$this->_html['confirm'][] = 'Create datastruct was successful';
		}

		/*
		 * export db data to download file
		 */

		public function exportThemeSql()
		{
			$ignore_insert_table = array(
				_DB_PREFIX_.'connections', _DB_PREFIX_.'connections_page', _DB_PREFIX_.'connections_source',
				_DB_PREFIX_.'guest', _DB_PREFIX_.'statssearch',
				_DB_PREFIX_.'sekeyword', _DB_PREFIX_.'favorite_product',
				_DB_PREFIX_.'pagenotfound', _DB_PREFIX_.'shop_url',
				_DB_PREFIX_.'employee', _DB_PREFIX_.'employee_shop',
				_DB_PREFIX_.'contact', _DB_PREFIX_.'contact_lang',
				_DB_PREFIX_.'contact', _DB_PREFIX_.'contact_shop'
			);
			$install_folder = $this->theme_sample_dir.'sql';
			if (!is_dir($install_folder))
				mkdir($install_folder, 0755);
			$backupfile = $install_folder.'/theme.sql';

			$fp = fopen($backupfile, 'w');
			if ($fp === false)
			{
				$this->_html['error'] = 'Error when export DbStruct! Can not write file in leotemcp/install';
				return false;
			}
			fwrite($fp, 'SET NAMES \'utf8\';'.PHP_EOL.PHP_EOL);
			// Find all tables
			$tables = Db::getInstance()->executeS('SHOW TABLES');
			$found = 0;
			$sql = '';
			foreach ($tables as $table)
			{
				$table = current($table);
				// Skip tables which do not start with _DB_PREFIX_
				if (Tools::strlen($table) < Tools::strlen(_DB_PREFIX_) || strncmp($table, _DB_PREFIX_, Tools::strlen(_DB_PREFIX_)) != 0)
					continue;

				// Export the table schema
				$schema = Db::getInstance()->executeS('SHOW CREATE TABLE `'.$table.'`');

				if (count($schema) != 1 || !isset($schema[0]['Table']) || !isset($schema[0]['Create Table']))
				{
					fclose($fp);
					//$this->delete();
					//echo Tools::displayError('An error occurred while backing up. Unable to obtain the schema of').' "'.$table;
					$this->_html['error'] = 'An error occurred while backing up. Unable to obtain the schema of '.$table;
					return false;
				}

				if (!in_array($schema[0]['Table'], $ignore_insert_table))
				{
					$sql .= PHP_EOL.'TRUNCATE TABLE '.str_replace('`'._DB_PREFIX_, '`PREFIX_', '`'.$schema[0]['Table']).'`;'.PHP_EOL;
					$data = Db::getInstance()->Executes('SELECT * FROM `'.$schema[0]['Table'].'`');
					$sizeof = DB::getInstance()->NumRows();
					$lines = explode("\n", $schema[0]['Create Table']);

					if ($data && $sizeof > 0)
					{
						// Export the table data
						$sql .= 'INSERT INTO '.str_replace('`'._DB_PREFIX_, '`PREFIX_', '`'.$schema[0]['Table']).'` VALUES'.PHP_EOL;
						//fwrite($fp, 'INSERT INTO `'.$schema[0]['Table'].'` VALUES\n');
						$i = 1;
						foreach ($data as $row)
						{
							$s = '(';
							foreach ($row as $field => $value)
							{
								$tmp = '\''.str_replace('\\\'', '\\\\\\\'', pSQL($value, true)).'\',';
								if ($tmp != '\'\',')
									$s .= $tmp;
								else
								{
									foreach ($lines as $line)
										if (strpos($line, '`'.$field.'`') !== false)
										{
											if (preg_match('/(.*NOT NULL.*)/Ui', $line))
												$s .= '\'\',';
											else
												$s .= 'NULL,';
											break;
										}
								}
							}
							$s = rtrim($s, ',');

							if ($i % 200 == 0 && $i < $sizeof)
								$s .= ');'.PHP_EOL.'INSERT INTO '.str_replace('`'._DB_PREFIX_, '`PREFIX_', '`'.$schema[0]['Table']).'` VALUES'.PHP_EOL;
							elseif ($i < $sizeof)
								$s .= '),'.PHP_EOL;
							else
								$s .= ');'.PHP_EOL;
							$sql .= $s;

							//fwrite($fp, $s);
							++$i;
						}
					}
				}
				$found++;
			}
			//table PREFIX_condition
			$sql = str_replace(' '._DB_PREFIX_, ' PREFIX_', $sql);
			//img link
			//$sql = str_replace('src=\"'.__PS_BASE_URI__.'modules/', 'src=\"modules/', $sql);

			fwrite($fp, $sql);
			fclose($fp);
			if ($found == 0)
			{
				//echo Tools::displayError('No valid tables were found to backup.' );
				$this->_html['error'] = 'No valid tables were found to backup.';
				return false;
			}

			$this->_html['confirm'][] .= 'Create theme.sql was successful';
		}

		public function importConfigModules($modules)
		{
			foreach ($modules as $module => $label)
				if (self::isSampleExisted($this->theme, $module))
					$this->importModule($module);
		}

		public function importQueryModules($modules)
		{
			foreach ($modules as $module => $label)
				if (self::isSampleExisted($this->theme, $module))
					$this->importModule($module, 'query');
		}

		public static function isSampleExisted($theme, $module, $ext = 'xml')
		{
			return is_file(_PS_ALL_THEMES_DIR_.$theme.'/features/sample/'.$module.'.'.$ext);
		}

		public static function isBackupExisted($theme, $module)
		{
			return is_file(_PS_CACHE_DIR_.'samples/'.$theme.'/'.$module.'.xml');
		}

		/**
		 *
		 */
		public static function assignHooksModulesInXml($theme, $modules)
		{
			$xml = false;
			$module_hook = array();
			if (file_exists(_PS_ROOT_DIR_.'/config/xml/themes/'.$theme.'.xml'))
				$xml = simplexml_load_file(_PS_ROOT_DIR_.'/config/xml/themes/'.$theme.'.xml');
			elseif (file_exists(_PS_ROOT_DIR_.'/config/xml/themes/default.xml'))
				$xml = simplexml_load_file(_PS_ROOT_DIR_.'/config/xml/themes/default.xml');
			if ($xml)
			{
				$module_hook = array();
				foreach ($xml->modules->hooks->hook as $row)
				{
					$name = (string)$row['module'];
					$exceptions = (isset($row['exceptions']) ? explode(',', (string)$row['exceptions']) : array());
					if (Hook::getIdByName((string)$row['hook']))
						$module_hook[$name]['hook'][] = array(
							'hook' => (string)$row['hook'],
							'position' => (string)$row['position'],
							'exceptions' => $exceptions
						);
				}
			}

			$shops = array(Configuration::get('PS_SHOP_DEFAULT'));
			$modules_errors = array();
			foreach ($shops as $id_shop)
			{
				foreach ($modules as $value => $text)
				{
					$module = Module::getInstanceByName($value);
					if ($module)
					{
						$is_installed_success = true;
						if (!Module::isInstalled($module->name))
							$is_installed_success = $module->install();
						if ($is_installed_success)
						{
							if (!Module::isEnabled($module->name))
								$module->enable();
							if ((int)$module->id > 0 && isset($module_hook[$module->name]))
								self::hookModule($module->id, $module_hook[$module->name], $id_shop);
						}
						else
							$modules_errors[] = array('module_name' => $module->name, 'errors' => $module->getErrors());

						unset($module_hook[$module->name]);
					}
				}
			}
			return $module_hook;
		}

		public static function hookModule($id_module, $module_hooks, $shop)
		{
			Db::getInstance()->execute('INSERT IGNORE INTO '._DB_PREFIX_.'module_shop (id_module, id_shop) VALUES('.$id_module.', '.(int)$shop.')');
			Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'hook_module` WHERE `id_module` = '.pSQL($id_module).' AND id_shop = '.(int)$shop);
			foreach ($module_hooks as $hooks)
				foreach ($hooks as $hook)
				{
					$sql_hook_module = 'INSERT INTO `'._DB_PREFIX_.'hook_module` (`id_module`, `id_shop`, `id_hook`, `position`)
										VALUES ('.(int)$id_module.', '.(int)$shop.', '.(int)Hook::getIdByName($hook['hook']).', '.(int)$hook['position'].')';

					if (count($hook['exceptions']) > 0)
						foreach ($hook['exceptions'] as $exception)
						{
							$sql_hook_module_except = 'INSERT INTO `'._DB_PREFIX_.'hook_module_exceptions` (`id_module`, `id_hook`, `file_name`) 
								VALUES ('.(int)$id_module.', '.(int)Hook::getIdByName($hook['hook']).', "'.pSQL($exception).'")';
							Db::getInstance()->execute($sql_hook_module_except);
						}
					Db::getInstance()->execute($sql_hook_module);
				}
		}

		public static function hooksAllModule($theme)
		{
			$xml = false;
			if (file_exists(_PS_ROOT_DIR_.'/config/xml/themes/'.$theme.'.xml'))
				$xml = simplexml_load_file(_PS_ROOT_DIR_.'/config/xml/themes/'.$theme.'.xml');
			elseif (file_exists(_PS_ROOT_DIR_.'/config/xml/themes/default.xml'))
				$xml = simplexml_load_file(_PS_ROOT_DIR_.'/config/xml/themes/default.xml');

			return self::hooksAllModuleWithXML($xml);
		}

		/**
		 *
		 *
		 */
		public static function hooksAllModuleWithXML($xml)
		{
			if ($xml)
			{
				$module_hook = array();
				foreach ($xml->modules->hooks->hook as $row)
				{
					$name = (string)$row['module'];
					$exceptions = (isset($row['exceptions']) ? explode(',', (string)$row['exceptions']) : array());
					if (Hook::getIdByName((string)$row['hook']))
						$module_hook[$name]['hook'][] = array(
							'hook' => (string)$row['hook'],
							'position' => (string)$row['position'],
							'exceptions' => $exceptions
						);
				}

				$xmodules = array();
				$modules_errors = array();

				foreach ($xml->modules->module as $row)
				{
					$name = (string)$row['name'];
					$xmodules[$name] = 'to_'.(string)$row['action'].'_'.$name;
				}

				$shops = array(Configuration::get('PS_SHOP_DEFAULT'));
				foreach ($shops as $id_shop)
				{
					foreach ($xmodules as $value => $key)
					{
						if (strncmp($key, 'to_install', Tools::strlen('to_install')) == 0)
						{
							$module = Module::getInstanceByName($value);
							if ($module)
							{
								$is_installed_success = true;
								if (!Module::isInstalled($module->name))
									$is_installed_success = $module->install();
								if ($is_installed_success)
								{
									if (!Module::isEnabled($module->name))
										$module->enable();

									if ((int)$module->id > 0 && isset($module_hook[$module->name]))
										self::hookModule($module->id, $module_hook[$module->name], $id_shop);
								}
								else
									$modules_errors[] = array('module_name' => $module->name, 'errors' => $module->getErrors());

								unset($module_hook[$module->name]);
							}
						}
						elseif (strncmp($key, 'to_enable', Tools::strlen('to_enable')) == 0)
						{
							$module = Module::getInstanceByName($value);
							if ($module)
							{
								$is_installed_success = true;
								if (!Module::isInstalled($module->name))
									$is_installed_success = $module->install();

								if ($is_installed_success)
								{
									if (!Module::isEnabled($module->name))
										$module->enable();

									if ((int)$module->id > 0 && isset($module_hook[$module->name]))
										self::hookModule($module->id, $module_hook[$module->name], $id_shop);
								}
								else
									$modules_errors[] = array('module_name' => $module->name, 'errors' => $module->getErrors());

								unset($module_hook[$module->name]);
							}
						}
						elseif (strncmp($key, 'to_disable', Tools::strlen('to_disable')) == 0)
						{
							$key_exploded = explode('_', $key);
							$id_shop_module = (int)Tools::substr($key_exploded[2], 4);

							if ((int)$id_shop_module > 0 && $id_shop_module != (int)$id_shop)
								continue;

							$module_obj = Module::getInstanceByName($value);
							if (Validate::isLoadedObject($module_obj))
							{
								if (Module::isEnabled($module_obj->name))
									$module_obj->disable();

								unset($module_hook[$module_obj->name]);
							}
						}
					}
				}
				// end foreach
				$fill_default_meta = true;
				$metas_xml = array();

				$theme = self::isThemeInstalled(Context::getContext()->shop->getTheme());

				if ($xml->metas->meta)
				{
					foreach ($xml->metas->meta as $meta)
					{
						$meta_id = Db::getInstance()->getValue('SELECT id_meta FROM '._DB_PREFIX_.'meta WHERE page=\''.pSQL($meta['meta_page']).'\'');
						if ((int)$meta_id > 0)
						{
							$tmp_meta = array();
							$tmp_meta['id_meta'] = (int)$meta_id;
							$tmp_meta['left'] = (int)$meta['left'];
							$tmp_meta['right'] = (int)$meta['right'];
							$metas_xml[(int)$meta_id] = $tmp_meta;
						}
					}
					$fill_default_meta = false;
					if (count($xml->metas->meta) < (int)Db::getInstance()->getValue('SELECT count(*) FROM '._DB_PREFIX_.'meta'))
						$fill_default_meta = true;
				}

				if ($fill_default_meta == true)
				{
					$metas = Db::getInstance()->executeS('SELECT id_meta FROM '._DB_PREFIX_.'meta');
					foreach ($metas as $meta)
					{
						if (!isset($metas_xml[(int)$meta['id_meta']]))
						{
							$tmp_meta['id_meta'] = (int)$meta['id_meta'];
							//$tmp_meta['left'] = $new_theme->default_left_column;
							//$tmp_meta['right'] = $new_theme->default_right_column;
							$metas_xml[(int)$meta['id_meta']] = $tmp_meta;
						}
					}
				}
				if ($theme)
					$theme->updateMetas($metas_xml);
			}
		}

		public static function isThemeInstalled($theme_name)
		{
			$themes = Theme::getThemes();
			foreach ($themes as $theme_object)
				if ($theme_object->name == $theme_name)
					return $theme_object;
			return false;
		}

		/**
		 *
		 *
		 */
		public function importModule($module, $mod = 'config')
		{
			$xml_modules = self::getModuleSamples($this->theme);
			/**
			 * check and move images
			 */
			if (isset($xml_modules[$module]['images']) && isset($xml_modules[$module]['imgfolder']))
			{
				$dst = _PS_MODULE_DIR_.$module.'/'.trim($xml_modules[$module]['imgfolder']);
				$src = $this->theme_sample_dir.'images';
				if (!is_array($xml_modules[$module]['images']['image']))
					$xml_modules[$module]['images']['image'] = array($xml_modules[$module]['images']['image']);
				// d($source);
				if (is_dir($dst) && is_dir($src))
					foreach ($xml_modules[$module]['images']['image'] as $file)
						if (!file_exists($dst.'/'.$file))
							if (is_readable($src.'/'.$file) && $file != 'Thumbs.db' && $file != '.DS_Store' && Tools::substr($file, -1) != '~')
								copy($src.'/'.$file, $dst.'/'.$file);
			}
			/**
			 * update module configuration and execute sql queries
			 */
			$moddir = $this->theme_sample_dir.$module.'.xml';
			$info = simplexml_load_string(Tools::file_get_contents($moddir));
			$configs = get_object_vars($info);

			$error = array();

			if ($mod == 'query' && self::isSampleExisted($this->theme, $module) && isset($configs['tables']))
			{
				$ts = get_object_vars($configs['tables']);
				$tables = !is_array($ts['table']) ? array($ts['table']) : $ts['table'];

				$iquery_tables = array();
				$iquery_sql = array();
				$extra_sql = array();
				$query_tables = array();
				$query_sql = array();
				include($this->theme_sample_dir.$module.'.php');
				if (is_file($this->theme_sample_dir.'query.php'))
					include($this->theme_sample_dir.'query.php');

				if (isset($query_tables) && isset($query_tables[$module]))
					$iquery_tables = $query_tables[$module];
				if (isset($query_sql))
					$iquery_sql = $query_sql;

				foreach ($tables as $table)
				{
					$sql = ' SHOW TABLES LIKE \''._DB_PREFIX_.$table.'\'';
					$schema = Db::getInstance()->executeS($sql);
					if (empty($schema))
						if (isset($iquery_tables[$table]))
							if (!Db::getInstance()->Execute($iquery_tables[$table]))
								$error[] = $iquery_tables[$table];

					$sql = 'SELECT count(*) as total FROM '._DB_PREFIX_.$table.'';
					$data = Db::getInstance()->executeS($sql);

					if ($data[0]['total'] <= 0)
					{
						$sqlfile = $this->theme_sample_dir.'sql/'.$table.'.sql';
						$i = 0;
						if (file_exists($sqlfile))
						{
							$queries = preg_split('#;\s*[\r\n]+#', Tools::file_get_contents($sqlfile));
							$errors = array();
							foreach ($queries as $query)
							{
								$query = str_replace('_DB_PREFIX_', _DB_PREFIX_, trim($query));
								if (!$query)
									continue;

								if (!Db::getInstance()->Execute($query))
									$errors[] = array(
										'query' => $query
									);
								else
									if (preg_match('#`id_shop`#', $query))
									{
										$query2 = 'UPDATE '._DB_PREFIX_.$table.' set `id_shop`='.(int)Context::getContext()->shop->id;
										Db::getInstance()->Execute($query2);
									}
								$i++;
							}
						}

						$data[0]['total'] = $i;
					}

					if ($data[0]['total'] <= 0 && isset($iquery_sql[$table]))
					{
						if (preg_match('#_shop$#', $table))
							foreach ($iquery_sql[$table] as $s)
							{
								$s = str_replace('_SHOPID_', (int)Context::getContext()->shop->id, $s);
								if (!Db::getInstance()->Execute($s))
									$error[] = $s;
							}
						else if (preg_match('#_lang$#', $table))
						{
							$languages = Language::getLanguages(true, Context::getContext()->shop->id);
							foreach ($iquery_sql[$table] as $s)
								foreach ($languages as $lang)
								{
									$s = str_replace('_LANGUAGEID_', (int)$lang['id_lang'], $s);
									if (!Db::getInstance()->Execute($s))
										$error[] = $s;
								}
						}
						else
						{
							$languages = Language::getLanguages(true, Context::getContext()->shop->id);
							foreach ($iquery_sql[$table] as $s)
							{
								if (preg_match('#_LANGUAGEID_#', $s))
								{
									$s = str_replace('_SHOPID_', (int)Context::getContext()->shop->id, $s);
									foreach ($languages as $lang)
									{
										$sl = str_replace('_LANGUAGEID_', (int)$lang['id_lang'], $s);
										if (!Db::getInstance()->Execute($sl))
											$error[] = $s;
									}
								}
								else
								{
									$s = str_replace('_SHOPID_', (int)Context::getContext()->shop->id, $s);
									if (!Db::getInstance()->Execute($s))
										$error[] = $s;
								}
							}
						}
					}

					/* execute Extra SQL */
					if (isset($extra_sql) && isset($extra_sql[$table]))
					{
						foreach ($extra_sql[$table] as $key => $sql)
						{
							$sql['sql'] = str_replace('_SHOPID_', (int)Context::getContext()->shop->id, $sql['sql']);
							$sql['sql'] = str_replace('_LANGUAGEID_', (int)$lang['id_lang'], $s);

							if (isset($sql['type']) && $sql['type'] == 'update' && $sql['sql'])
								if (!Db::getInstance()->Execute($sql['sql']))
									$error[] = $sql['sql'];
							else
							{
								$check = true;
								// check record is existed or not?
								if (isset($sql['check']))
								{
									$data = Db::getInstance()->executeS($sql['check']);
									$check = empty($data) ? true : false;
								}

								if ($check && $sql['sql'])
									if (!Db::getInstance()->Execute($sql['sql']))
										$error[] = $s;
							}
						}
					}
				}
			}
			elseif (isset($configs['config']))
			{
				$configs['config'] = get_object_vars($configs['config']);
				foreach ($configs['config'] as $key => $value)
					Configuration::updateValue($key, $value, true);
			}
			return $error;
		}

		public function importSampleQueries()
		{
			return '';
		}

		public function backup()
		{
			$modules = self::getModuleSamples($this->theme);
			//d( $modules );
			if (is_array($modules) && !empty($modules))
				foreach ($modules as $module)
					if (isset($module['name']))
					{
						$string = $this->exportModule($module);
						PtsThemePanelHelper::writeToCache($this->backup_dir, $module['name'], $string, 'xml');
					}
		}

		public function restore($modules, $mod = 'config')
		{
			foreach ($modules as $module => $name)
			{
				$moddir = $this->backup_dir.$module.'.xml';
				$info = simplexml_load_string(Tools::file_get_contents($moddir));
				$configs = get_object_vars($info);

				if ($mod != 'query')
				{
					if (isset($configs['config']))
					{
						$configs['config'] = get_object_vars($configs['config']);
						foreach ($configs['config'] as $key => $value)
							Configuration::updateValue($key, $value, true);
					}
				}
			}
		}

		public function clearCache()
		{
			return true;
		}

		public function exportModuleQueries($module)
		{
			if (isset($module['tables']['table']))
			{
				$output = array();
				$output[] = '$query_tables = array();';
				$output[] = '$query_sql = array();';
				$tables = !is_array($module['tables']['table']) ? array($module['tables']['table']) : $module['tables']['table'];

				$stblang = array();
				// export sql creating tables.
				foreach ($tables as $table)
				{
					$sql = ' SHOW CREATE TABLE   '._DB_PREFIX_.$table;
					$data = Db::getInstance()->query($sql, false);
					$row = DB::getInstance()->nextRow($data);

					if (isset($row['Create Table']))
					{
						$tmp = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS ', $row['Create Table']);
						$tmp = str_replace('\'', '\\\'', $tmp);
						$tmp = str_replace(_DB_PREFIX_, '\'._DB_PREFIX_.\'', $tmp);
						$output[] = '$query_tables[\''.$module['name'].'\'][\''.$table.'\'] = \''.$tmp.'\';'.PHP_EOL.PHP_EOL;
						if (!preg_match('#lang$#', $table) && preg_match('#id_lang#', $tmp))
							$stblang[$table] = true;
					}
				}

				// export queries
				$query = array();

				foreach ($tables as $table)
				{
					$tablename = _DB_PREFIX_.$table;

					$sql = 'SELECT * FROM `'.$tablename.'`';

					if (isset($stblang[$table]))
						$sql .= 'WHERE id_lang='.(int)Context::getContext()->language->id;

					if (preg_match('#_shop$#', $tablename))
						$sql .= ' WHERE id_shop='.(int)Context::getContext()->shop->id;
					elseif (preg_match('#_lang$#', $tablename))
						$sql .= ' WHERE id_lang='.(int)Context::getContext()->language->id;

					$rows = Db::getInstance()->executeS($sql);

					$query[] = '/*DATA FOR TABLE '.$table.'*/'.PHP_EOL;
					$data = array();
					foreach ($rows as $row)
					{
						$fs = array();
						$vs = array();
						foreach ($row as $key => $value)
						{
							$fs[] = $key;
							if ($key == 'id_lang')
								$value = '_LANGUAGEID_';
							elseif ($key == 'id_shop')
								$value = '_SHOPID_';
							$vs[] = '\\\''.str_replace('\\\'', '\\\\\\\'', pSQL($value, true)).'\\\'';
						}
						$query[] = ' $query_sql[\''.$table.'\'][] = \'INSERT INTO \'._DB_PREFIX_.\''.$table.'( `'.implode('`,`', $fs).'` ) 
							VALUES('.implode(', ', $vs).')\'; '.PHP_EOL;
						$data[] = $row;
					}
				}
				$string = '<?php '.PHP_EOL.implode(PHP_EOL, $output).PHP_EOL.implode('', $query).PHP_EOL.' ?>';
				return $string;
				// echo '<pre>'.print_r( $tables ,1 );die;
			}
			return '';
		}

		public function exportModule($module)
		{
			$xml = '<?xml version="1.0" encoding="UTF-8" ?>'.PHP_EOL;
			$configs = $this->exportConfig($module);
			$xml .= '<module><config>'.PHP_EOL;
			foreach ($configs as $config => $value)
				$xml .= '<'.trim($config).'>'.$value.'</'.trim($config).'>'.PHP_EOL;
			$xml .= '</config>';
			if (isset($module['tables']['table']))
			{
				$tables = !is_array($module['tables']['table']) ? array($module['tables']['table']) : $module['tables']['table'];
				$xml .= PHP_EOL.'<tables>'.PHP_EOL;
				foreach ($tables as $table)
					$xml .= '<table>'.$table.'</table>'.PHP_EOL;
				$xml .= '</tables>'.PHP_EOL;
			}

			$xml .= PHP_EOL.'<queries>';
			$xml .= '</queries>'.PHP_EOL;
			// 	echo $tables;die;
			$xml .= PHP_EOL.'</module>';
			return $xml;
		}

		public function exportConfig($config)
		{
			$a = array(
				'prefix' => ''
			);
			$config = array_merge($a, $config);

			$sql = 'SELECT c.`name`, cl.`id_lang`, IF(cl.`id_lang` IS NULL, c.`value`, cl.`value`) AS value, c.id_shop_group, c.id_shop
					FROM `'._DB_PREFIX_.bqSQL(Configuration::$definition['table']).'` c
					LEFT JOIN `'._DB_PREFIX_.bqSQL(Configuration::$definition['table']).'_lang` cl ON 
						(c.`'.bqSQL(Configuration::$definition['primary']).'` = cl.`'.bqSQL(Configuration::$definition['primary']).'`)';
			$sql .= ' WHERE c.`name` LIKE "'.$config['prefix'].'%"';
			// echo $sql;die ;
			$db = Db::getInstance();
			$result = $db->executeS($sql, false);

			$output = array();
			while ($row = $db->nextRow($result))
				$output[$row['name']] = $row['value'];

			return $output;
		}
		public static function getModuleSamples($theme)
		{
			$xml = _PS_ALL_THEMES_DIR_.$theme.'/features/modules.xml';
			$output = array();
			if (file_exists($xml))
			{
				$info = simplexml_load_string(Tools::file_get_contents($xml));
				$output = array();
				if (empty($info))
					return $output;
				foreach ($info->children() as $xml_module)
				{
					$vars = get_object_vars($xml_module);
					$m = array();
					foreach ($vars as $k => $f)
						if (is_object($f))
							$m[$k] = get_object_vars($f);
						else
							$m[$k] = (string)$f;
					$output[$m['name']] = $m;
				}
			}

			return $output;
		}

		private function recurseCopy($src, $dst)
		{
			if (!$dir = opendir($src))
				return;
			if (!file_exists($dst))
				mkdir($dst);
			while (($file = readdir($dir)) !== false)
				if (strncmp($file, '.', 1) != 0)
					if (is_dir($src.'/'.$file))
						self::recurseCopy($src.'/'.$file, $dst.'/'.$file);
					elseif (is_readable($src.'/'.$file) && $file != 'Thumbs.db' && $file != '.DS_Store' && Tools::substr($file, -1) != '~')
						copy($src.'/'.$file, $dst.'/'.$file);
			closedir($dir);
		}

	}

}