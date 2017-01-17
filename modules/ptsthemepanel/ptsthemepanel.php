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

if (!defined('_PS_VERSION_'))
	exit;

define('LIVE_GUIDE_URL', 'http://www.prestabrain.com/guides/');

require_once( _PS_MODULE_DIR_.'ptsthemepanel/classes/helper.php' );
require_once( _PS_MODULE_DIR_.'ptsthemepanel/classes/sample.php' );

class PtsthemePanel extends Module {

	protected $max_image_size = 1048576;
	protected $default_language;
	protected $languages;
	protected $theme_name;
	protected $themes = array();
	protected $theme_customize_path = '';
	protected $tokens = array();
	protected $prefix = 'PTS_CP_';
	protected $is_showed = false;

	public function __construct()
	{
		$this->name = 'ptsthemepanel';
		$this->tab = 'front_office_features';
		$this->version = '1.6.0';
		$this->bootstrap = true;
		$this->secure_key = Tools::encrypt($this->name);
		$this->default_language = Language::getLanguage(Configuration::get('PS_LANG_DEFAULT'));
		$this->languages = Language::getLanguages();
		$this->author = 'PrestaBrain';
		parent::__construct();
		$this->displayName = $this->l('PTS Theme Control Panel');
		$this->description = $this->l('Configure the main elements of your theme.');
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
		$this->module_path = _PS_MODULE_DIR_.$this->name.'/';
		$this->uploads_path = _PS_MODULE_DIR_.$this->name.'/img/';
		$this->admin_tpl_path = _PS_MODULE_DIR_.$this->name.'/views/templates/admin/';
		$this->hooks_tpl_path = _PS_MODULE_DIR_.$this->name.'/views/templates/hooks/';

		$this->theme_name = Context::getContext()->shop->getTheme();

		$this->themes = $this->getThemes();
		$this->is_showed = $this->isOwnTheme();
	}

	public function getConfigName($name)
	{
		return $this->prefix.Tools::strtoupper($name);
	}

	public function saveConfigValue($name, $value)
	{
		Configuration::updateValue($this->getConfigName($name), $value, true);
	}

	public function getConfigValue($name, $value = '')
	{
		return Configuration::get($this->getConfigName($name), $value);
	}

	public static function getConfig($name, $value)
	{
		Configuration::get('PTS_CP_'.Tools::strtoupper($name), $value);
	}

	public function getThemes()
	{
		if ($this->themes)
			return $this->themes;
		$themes = array();
		$directories = glob(_PS_ALL_THEMES_DIR_.$this->theme_name.'/css/themes/*.css');
		$themes[] = array('skin' => 'default',
			'rehook' => PtsThemeSample::isReHookBySkin($this->theme_name, 'default'),
			'name' => $this->l('Default'));
		if ($directories)
		{
			foreach ($directories as $dir)
			{
				$skin = str_replace('.css', '', basename($dir));
				$rehook = PtsThemeSample::isReHookBySkin($this->theme_name, $skin);
				$themes[] = array('skin' => $skin, 'rehook' => $rehook, 'name' => $this->l(Tools::ucfirst($skin)));
			}
		}
		return $themes;
	}

	public function install()
	{
		$this->installDB();
		if (!parent::install() || !$this->installFixtures()
			|| !$this->registerHook('displayHeader')
			|| !$this->registerHook('displayFooter')
			|| !$this->registerHook('displayBackOfficeHeader')
			|| !$this->registerHook('displayBackOfficeTop')
			// || !$this->registerHook('actionModuleInstallBefore')
			// || !$this->registerHook('actionModuleInstallAfter')
			|| !$this->registerHook('actionProductListModifier')
			|| !$this->registerHook('displayProductListSwap')
			|| !$this->registerHook('displayProductListGallery')
			|| !Configuration::updateValue('PTS_CP_ACTIVE', 1))
			return false;
		return true;
	}

	private function installDB()
	{
		$hookspos = PtsThemePanelHelper::getHookPositions();
		foreach ($hookspos as $hook)
			if (!Hook::getIdByName($hook))
			{
				$new_hook = new Hook();
				$new_hook->name = pSQL($hook);
				$new_hook->title = pSQL($hook);
				$new_hook->add();
			}
		return true;
	}

	public function installFixtures()
	{
		$result = true;
		return $result;
	}

	public function uninstall()
	{
		if (parent::uninstall())
		{
			$res = true;
			return $res;
		}
		return false;
	}

	public function hookDisplayBackOfficeHeader()
	{
		if (Tools::getValue('configure') == $this->name)
			$this->context->controller->addJquery();
		$this->context->controller->addCSS($this->_path.'views/css/admin.css');
		$this->context->controller->addJS($this->_path.'views/js/admin.js');
	}

	/**
	 *
	 */
	public function isOwnTheme()
	{
		return is_file(_PS_ALL_THEMES_DIR_.$this->theme_name.'/features/layout.xml');
	}

	/**
	 *
	 */
	public function storeActivedThemeLog()
	{
		Configuration::updateValue('PTS_ACTIVEDTHEME', $this->theme_name);
	}

	/**
	 *
	 */
	public function hookDisplayBackOfficeTop()
	{
		if (!$this->is_showed)
			return null;
		$actived_theme = Configuration::get('PTS_ACTIVEDTHEME');
		$themecontrol = 'index.php?controller=AdminModules&token='.Tools::getAdminTokenLite('AdminModules').'
			&tab_module=Home&configure=ptsthemepanel&module_name=ptsthemepanel';
		$livetheme = 'index.php?controller=AdminModules&token='.Tools::getAdminTokenLite('AdminModules').'
			&tab_module=Home&configure=ptsthemepanel&module_name=ptsthemepanel';
		$livemega = 'index.php?controller=AdminModules&token='.Tools::getAdminTokenLite('AdminModules').'&tab_module=Home
			&configure=ptsmegamenu&module_name=ptsmegamenu&liveeditor=1';
		$megamenu = 'index.php?controller=AdminModules&token='.Tools::getAdminTokenLite('AdminModules').'&tab_module=Home
			&configure=ptsmegamenu&module_name=ptsmegamenu';
		if ((int)Validate::isLoadedObject(Module::getInstanceByName('psmegamenu')))
		{
			$livemega = 'index.php?controller=AdminModules&token='.Tools::getAdminTokenLite('AdminModules').'&tab_module=Home
				&configure=psmegamenu&module_name=psmegamenu&liveeditor=1';
			$megamenu = 'index.php?controller=AdminModules&token='.Tools::getAdminTokenLite('AdminModules').'&tab_module=Home
				&configure=psmegamenu&module_name=psmegamenu';
		}
		$livevermega = 'index.php?controller=AdminModules&token='.Tools::getAdminTokenLite('AdminModules').'&tab_module=Home
			&configure=ptsverticalmenu&module_name=ptsverticalmenu&liveeditor=1';
		$megavermenu = 'index.php?controller=AdminModules&token='.Tools::getAdminTokenLite('AdminModules').'&tab_module=Home
			&configure=ptsverticalmenu&module_name=ptsverticalmenu';
		if ((int)Validate::isLoadedObject(Module::getInstanceByName('psverticalmenu')))
		{
			$livevermega = 'index.php?controller=AdminModules&token='.Tools::getAdminTokenLite('AdminModules').'&tab_module=Home
				&configure=psverticalmenu&module_name=psverticalmenu&liveeditor=1';
			$megavermenu = 'index.php?controller=AdminModules&token='.Tools::getAdminTokenLite('AdminModules').'&tab_module=Home
				&configure=psverticalmenu&module_name=psverticalmenu';
		}
		$output = '
			<ul id="pts-dropdown-top">
				<li class="dropdown">
					<a href="" class="dropdown-toggle" data-toggle="dropdown"><i class="icon-gear"></i>
					'.$this->l('Theme Config').' <i class="icon-caret-down"></i></a>
					<ul class="dropdown-menu">';

		$output .= '	<li><a href="'.$themecontrol.'"><i class="icon-gear"></i> '.$this->l('Theme Control Panel').'</a></li>';

		if ((int)Validate::isLoadedObject(Module::getInstanceByName('ptspagebuilder')))
		{
			$pagebuilder = 'index.php?controller=AdminPtspagebuilderProfile&token='.Tools::getAdminTokenLite('AdminPtspagebuilderProfile');
			$footerbuilder = 'index.php?controller=AdminPtspagebuilderFooter&token='.Tools::getAdminTokenLite('AdminPtspagebuilderFooter');
			$output .= ' <li><a href="'.$pagebuilder.'"><i class="icon-gear"></i> '.$this->l('Page Builder Profiles').'</a></li>';
			$output .= ' <li><a href="'.$footerbuilder.'"><i class="icon-gear"></i> '.$this->l('Footer Builder Profiles').'</a></li>';
		}
		elseif ((int)Validate::isLoadedObject(Module::getInstanceByName('pspagebuilder')))
		{
			$pagebuilder = 'index.php?controller=AdminPspagebuilderProfile&token='.Tools::getAdminTokenLite('AdminPspagebuilderProfile');
			$footerbuilder = 'index.php?controller=AdminPspagebuilderFooter&token='.Tools::getAdminTokenLite('AdminPspagebuilderFooter');
			$output .= ' <li><a href="'.$pagebuilder.'"><i class="icon-gear"></i> '.$this->l('Page Builder Profiles').'</a></li>';
			$output .= ' <li><a href="'.$footerbuilder.'"><i class="icon-gear"></i> '.$this->l('Footer Builder Profiles').'</a></li>';
		}
		if ((int)Validate::isLoadedObject(Module::getInstanceByName('ptsmegamenu'))
				|| (int)Validate::isLoadedObject(Module::getInstanceByName('psmegamenu')))
		{
			$output .= '	<li><a href="'.$megamenu.'"><i class="icon-gear"></i> '.$this->l('Megamenu').'</a></li>';
			$output .= '	<li><a href="'.$livemega.'"><i class="icon-gear"></i> '.$this->l('Live Megamenu Editor').'</a></li>';
		}
		if ((int)Validate::isLoadedObject(Module::getInstanceByName('ptsverticalmenu'))
				|| (int)Validate::isLoadedObject(Module::getInstanceByName('psverticalmenu')))
		{
			$output .= '	<li><a href="'.$megavermenu.'"><i class="icon-gear"></i> '.$this->l('Vertical Megamenu').'</a></li>';
			$output .= '	<li><a href="'.$livevermega.'"><i class="icon-gear"></i> '.$this->l('Live Vertical Megamenu Editor').'</a></li>';
		}
		$output .= '	<li><a href="'.$livetheme.'"><i class="icon-gear"></i> '.$this->l('Live Theme Editor').'</a></li>';
		$output .= '</ul>
				</li>
			</ul>';

		$this->smarty->assign(array(
			'content' => 'fsad',
			'url' => $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'
				&module_name='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules')
		));

		if (empty($actived_theme) || $actived_theme != $this->theme_name)
		{
			$this->checkInstallation();
			$output .= $this->display(__FILE__, 'views/templates/admin/injecthead.tpl');
		}
		return $output;
	}

	/**
	 *
	 */
	public function checkInstallation()
	{
		$samples = PtsThemeSample::getModuleSamples($this->theme_name);
		if (!empty($samples))
		{
			$sample = new PtsThemeSample($this->theme_name);
			foreach ($samples as $module => $data)
				if (PtsThemeSample::isSampleExisted($this->theme_name, $module))
				{
					$sample->importModule($module, 'config');
					$sample->importModule($module, 'query');
				}
			PtsThemeSample::hooksAllModule($this->theme_name);
			$this->storeActivedThemeLog();
		}
	}

	/**
	 *
	 */
	public function reloadGlobalCssFile($skinfilename)
	{
		$this->context->controller->removeCSS(_THEME_CSS_DIR_.'global.css');
		$rlts = array(_THEME_CSS_DIR_.$skinfilename => 'all');
		$this->context->controller->css_files = array_merge($rlts, $this->context->controller->css_files);
		$this->context->controller->addCss(_THEME_CSS_DIR_.$skinfilename, 'all', 99999);
	}

	public function getCustomConfig($name, $value)
	{
		$kc = $this->theme_name.'_'.$name;
		if ($this->context->cookie->__isset($kc))
			return $this->context->cookie->__get($kc);
		return $value;
	}

	public function saveCustomConfig($name, $value)
	{
		$kc = $this->theme_name.'_'.$name;

		$this->context->cookie->__set($kc, $value);
		return $value;
	}

	/**
	 *
	 */
	public function hookdisplayHeader()
	{
		$this->context->controller->addJqueryPlugin('fancybox');
		$loadrlt = true;

		$cgtheme = _PS_MODE_DEMO_ ? $this->getCustomConfig('themeskin', Configuration::get('PTS_CP_THEME')) : Configuration::get('PTS_CP_THEME');

		if (_PS_MODE_DEMO_)
		{
			if (Tools::getValue('themeskin') && Tools::getValue('themeskinactived'))
			{
				$cgtheme = $this->saveCustomConfig('themeskin', Tools::getValue('themeskin'));
				if (Tools::getIsset('headers'))
				{
					$header = Tools::getValue('headers', 'default');
					$this->saveCustomConfig('header', $header);
				}
			}
			if (Tools::getValue('headers') && Tools::getValue('headerctived'))
			{
				$header = Tools::getValue('headers', 'default');
				$this->saveCustomConfig('header', $header);
			}
			if (Tools::getValue('productsstyle') && Tools::getValue('productsstyleactived'))
			{
				$productsstyle = Tools::getValue('productsstyle', 'style1');
				$this->saveCustomConfig('productsstyle', $productsstyle);
			}
			if (Tools::getValue('productdetail') && Tools::getValue('productdetailactived'))
			{
				$productdetail = Tools::getValue('productdetail', 'default');
				$this->saveCustomConfig('productdetail', $productdetail);
			}
			if (Tools::isSubmit('applyCustomSkinChanger'))
			{
				$cgtheme = $this->saveCustomConfig('themeskin', Tools::getValue('themeskin'));
				if (Tools::getIsset('headers'))
				{
					$header = Tools::getValue('headers', 'default');
					$this->saveCustomConfig('header', $header);
				}
				if (Tools::getIsset('productsstyle'))
				{
					$productsstyle = Tools::getValue('productsstyle', 'style1');
					$this->saveCustomConfig('productsstyle', $productsstyle);
				}
				if (Tools::getValue('productdetail') && Tools::getValue('productdetailactived'))
				{
					$productdetail = Tools::getValue('productdetail', 'default');
					$this->saveCustomConfig('productdetail', $productdetail);
				}
			}
			if (Tools::isSubmit('resetDemoTheme'))
			{
				$cgtheme = $this->saveCustomConfig('themeskin', Configuration::get('PTS_CP_THEME'));
				$this->saveCustomConfig('header', Configuration::get('PTS_CP_HEADER'));
			}
			$this->context->smarty->assign('header_cp', $this->getCustomConfig('header', Tools::getValue('headers', Configuration::get('PTS_CP_HEADER'))));
		}

		if (!isset($productdetail))
			$productdetail = _PS_MODE_DEMO_ ? $this->getCustomConfig('productdetail', Configuration::get('PTS_CP_PRODUCT_DETAIL'))
				: Configuration::get('PTS_CP_PRODUCT_DETAIL');
		$this->context->smarty->assign('productdetail', $productdetail);

		if (!empty($cgtheme))
		{
			$themeskinfile = _PS_ALL_THEMES_DIR_.$this->theme_name.'/css/global-'.$cgtheme.'.css';
			$themeskinfilertl = _PS_ALL_THEMES_DIR_.$this->theme_name.'/css/global-rtl-'.$cgtheme.'.css';
			if ($this->context->language->is_rtl && file_exists($themeskinfilertl))
			{
				$this->reloadGlobalCssFile('global-rtl-'.$cgtheme.'.css');
				$loadrlt = false;
			}
			elseif (file_exists($themeskinfile))
				$this->reloadGlobalCssFile('global-'.$cgtheme.'.css');
		}

		if ($this->context->language->is_rtl && $loadrlt)
			$this->reloadGlobalCssFile('rtl-global.css');

		if (!$this->is_showed)
			return null;

		if ($this->checkVisiable())
		{
			$this->context->controller->addCSS($this->_path.'views/css/panel.css');
			$this->context->controller->addJS($this->_path.'views/js/live_configurator.js');
			$this->context->controller->addCSS($this->_path.'views/css/colorpicker/colorpicker.css', 'all');
			$this->context->controller->addJS($this->_path.'views/js/colorpicker/colorpicker.js');
			$this->theme_customize_path = _PS_ALL_THEMES_DIR_.$this->theme_name.'/css/profiles/';
		}
		if ((int)Configuration::get('PTS_CP_ACTIVE') == 1 && Tools::getValue('live_configurator_token') && $this->checkValidToken())
		{
			if (Tools::isSubmit('submitPtsLiveConfiguratorDelete') && Tools::getValue('saved_file'))
			{
				$file = Tools::getValue('saved_file');
				if (file_exists($this->theme_customize_path.$file.'.css'))
					unlink($this->theme_customize_path.$file.'.css');
				if (file_exists($this->theme_customize_path.$file.'.json'))
					unlink($this->theme_customize_path.$file.'.json');
			}
			if (Tools::isSubmit('submitLiveThemeChanger') && Tools::getValue('themeskin'))
			{
				$themeskin = Tools::getValue('themeskin');
				if (Tools::getValue($themeskin.'_rehook'))
				{
					$sample = new PtsThemeSample($this->theme_name);
					$sample->reHookBySkin($themeskin);
				}
				Configuration::updateValue('PTS_CP_THEME', Tools::getValue('themeskin'));
				if (Tools::getIsset('headers'))
				{
					$header = Tools::getValue('headers', 'default');
					Configuration::updateValue('PTS_CP_HEADER', $header);
				}

				$id_employee = is_object($this->context->employee) ? (int)$this->context->employee->id :
								Tools::getValue('id_employee');
				$url = $this->context->shop->getBaseURL()
								.((Configuration::get('PS_REWRITING_SETTINGS') && count(Language::getLanguages(true)) > 1)
									? Language::getIsoById($this->context->language->id).'/' : '')
								.(Configuration::get('PS_REWRITING_SETTINGS') ? '' : 'index.php')
								.'?live_configurator_token='.$this->getLiveConfiguratorToken()
								.'&id_employee='.(int)$id_employee
								.'&id_shop='.(int)$this->context->shop->id;

				Tools::redirect($url);
			}

			if (Tools::isSubmit('submitPtsLiveConfiguratorActiveProfile') && Tools::getValue('saved_file'))
				Configuration::updateValue('PTS_CP_PROFILE', Tools::getValue('saved_file'));

			if (Tools::isSubmit('submitPtsLiveConfigurator'))
			{
				$data = Tools::getAllValues();
				$selectors = $data['customize'];
				$matches = $data['customize_match'];
				$output = '';

				$cache = array();
				$tmpss = array();
				foreach ($selectors as $match => $customizes)
					foreach ($customizes as $key => $customize)
						if (isset($matches[$match]) && isset($matches[$match][$key]))
						{
							$tmp = explode('|', $matches[$match][$key]);
							if (trim($customize))
								if (Tools::strtolower(trim($tmp[1])) == 'background-image')
									$tmpss[$tmp[0]][] = $tmp[1].':url('.$customize.')';
								else
								{
									$prefix = preg_match('#color#i', $tmp[1]) ? '#' : '';
									$suffix = preg_match('#size#i', $tmp[1]) ? 'px' : '';
									$tmpss[$tmp[0]][] = $tmp[1].':'.$prefix.$customize.$suffix;
								}
							$cache[$match][] = array('val' => $customize, 'selector' => $tmp[0], 'attr' => $tmp[1]);
						}

				$output = '';

				foreach ($tmpss as $key => $values)
				{
					$output .= "\r\n/* customize for $key */ \r\n";
					$output .= $key." { \r\n".implode(";\r\n", $values)." \r\n} \r\n";
				}

				if (!empty($data['saved_file']))
				{
					if ($data['saved_file'] && file_exists($this->theme_customize_path.$data['saved_file'].'.css'))
						unlink($this->theme_customize_path.$data['saved_file'].'.css');
					if ($data['saved_file'] && file_exists($this->theme_customize_path.$data['saved_file'].'.json'))
						unlink($this->theme_customize_path.$data['saved_file'].'.json');
				}

				if (empty($data['newfile']))
					$name_file = $data['saved_file'] ? $data['saved_file'] : 'profile-'.time();
				else
					$name_file = preg_replace('#\s+#', '-', trim($data['newfile']));

				if ($data['action-mode'] != 'save-delete')
				{
					if (!empty($output))
						PtsThemePanelHelper::writeToCache($this->theme_customize_path, $name_file, $output);
					if (!empty($cache))
						PtsThemePanelHelper::writeToCache($this->theme_customize_path, $name_file, Tools::jsonEncode($cache), 'json');
				}
			}
		}

		if ($cgtheme != '')
			$this->context->controller->addCss(_PS_THEME_DIR_.'css/themes/'.$cgtheme.'.css', 'all');

		if (Configuration::get('PTS_CP_PROFILE'))
			$this->context->controller->addCss(_PS_THEME_DIR_.'css/profiles/'.Configuration::get('PTS_CP_PROFILE').'.css', 'all');

		$this->context->smarty->registerPlugin('function', 'plugin', array('PtsthemePanel', 'smartyplugin'));
		$this->context->smarty->assign('LANG_DIRECTION', $this->context->language->is_rtl ? 'rtl' : 'ltr' );

		if (!isset($productsstyle))
			$productsstyle = _PS_MODE_DEMO_ ? $this->getCustomConfig('productsstyle', Configuration::get('PTS_CP_PRODUCT_STYLE'))
				: Configuration::get('PTS_CP_PRODUCT_STYLE');

		$this->context->smarty->assign('product_style', $productsstyle);
		$this->context->smarty->assign(array(
			'DEFAUTL_LAYOUT' => $this->getConfigValue('layout'),
			'PRODUCTS_ITEMSROW' => $this->getConfigValue('products_itemrow'),
			'DEFAUTL_LANGUAGEID' => $this->context->language->id,
			'DEFAULT_THEMESKIN' => $cgtheme,
			'CURRENT_THEMEDIR' => _PS_ALL_THEMES_DIR_.$this->theme_name.'/',
			'THEME_SKIN_DIR' => _PS_ALL_THEMES_DIR_.$this->theme_name.'/sub/themes/'.$cgtheme.'/',
			'THEME_HEADER_FILE' => _PS_ALL_THEMES_DIR_.$this->theme_name.'/sub/themes/'.$cgtheme.'/header.tpl'
		));

		$output = '';

		if ($this->getConfigValue('product_layout') == 'gallery')
			$this->context->controller->addJS($this->_path.'views/js/gallery.js');

		if ($customcss = $this->getConfigValue('customcss'))
			$output .= '<style type="text/css">'.$customcss.'</style>';
		if ($customjs = $this->getConfigValue('customjs'))
			$output .= '\r\n<script type="text/javascript">'.$customjs.'</script>';

		return $output;
	}

	/**
	 *
	 */
	public static function smartyplugin($params)
	{
		$id_module = Module::getModuleIdByName($params['module']);
		$id_hook = Hook::getIdByName($params['hook']);
		$array = array();
		$array['id_hook'] = $id_hook;
		$array['module'] = $params['module'];
		$array['id_module'] = $id_module;
		return PtsThemePanelHelper::hookExec($params['hook'], isset($params['args']) ? $params['args'] : array(), $id_module, $array);
	}

	/**
	 *
	 */
	public function checkVisiable()
	{
		return (int)Configuration::get('PTS_CP_ACTIVE') == 1 && Tools::getValue('live_configurator_token')
			&& $this->checkValidToken() && Tools::getIsset('id_employee') || _PS_MODE_DEMO_;
	}

	/**
	 *
	 */
	public function hookDisplayFooter()
	{
		if (!$this->is_showed)
			return null;

		$html = '';
		if ($this->checkVisiable())
		{
			$profiles = array();
			$files = (array)glob($this->theme_customize_path.'*.css');
			foreach ($files as $dir)
				$profiles[] = str_replace('.css', '', basename($dir));

			$id_employee = is_object($this->context->employee) ? (int)$this->context->employee->id :
							Tools::getValue('id_employee');

			$url = $this->context->shop->getBaseURL()
							.((Configuration::get('PS_REWRITING_SETTINGS') && count(Language::getLanguages(true)) > 1)
								? Language::getIsoById($this->context->language->id).'/' : '')
							.(Configuration::get('PS_REWRITING_SETTINGS') ? '' : 'index.php')
							.'?live_configurator_token='.$this->getLiveConfiguratorToken()
							.'&id_employee='.(int)$id_employee
							.'&id_shop='.(int)$this->context->shop->id;
			$cgtheme = _PS_MODE_DEMO_ ? Tools::getValue('themeskin', $this->getCustomConfig('themeskin', Configuration::get('PTS_CP_THEME')))
				: Configuration::get('PTS_CP_THEME');
			$header = _PS_MODE_DEMO_ ? Tools::getValue('headers', $this->getCustomConfig('header', Configuration::get('PTS_CP_HEADER')))
				: Configuration::get('PTS_CP_HEADER');

			$folders = glob(_PS_ALL_THEMES_DIR_.$this->theme_name.'/sub/headers/*', GLOB_ONLYDIR);
			$headers = array();
			foreach ($folders as $folder)
			{
				$paths = explode('/', $folder);
				$last = array_pop($paths);
				if ($last != '__MACOSX')
					$headers[] = $last;
			}

			$this->smarty->assign(array(
				'themes' => $this->getThemes(),
				'headers' => $headers,
				'header' => $header,
				'profiles' => $profiles,
				'fonts' => PtsThemePanelHelper::getLocalFonts(),
				'actionURL' => $url,
				'themeskin' => $cgtheme,
				'theme_font' => Tools::getValue('theme_font', Configuration::get('PTS_CP_FONT')),
				'live_configurator_token' => $this->getLiveConfiguratorToken(),
				'id_shop' => (int)$this->context->shop->id,
				'id_employee' => $id_employee,
				'isliveeditor' => $this->checkValidToken() ? true : false,
				'selectedprofile' => Configuration::get('PTS_CP_PROFILE'),
				'patterns' => (array)PtsThemePanelHelper::getPattern($this->theme_name),
				'backgroundImageURL' => $this->context->shop->getBaseURL().'themes/'.$this->theme_name.'/img/patterns/'
			));

			$html .= $this->display(__FILE__, 'live_configurator.tpl');
			$xmlselectors = PtsThemePanelHelper::renderEdtiorThemeForm($this->theme_name);
			$this->smarty->assign(array(
				'customizeFolderURL' => $this->context->shop->getBaseURL().'themes/'.$this->theme_name.'/css/profiles/',
				'xmlselectors' => $xmlselectors
			));
			$html .= $this->display(__FILE__, 'themeeditor.tpl');
		}

		$this->context->smarty->assign(array(
			'hook' => 'footer',
			'COPYRIGHT' => Configuration::get('PTS_CP_COPYRIGHT')
		));

		return $html;
	}

	/**
	 *
	 */
	public function hookActionModuleInstallBefore($o)
	{
		return $this->hookActionModuleInstallAfter($o);
	}

	/**
	 *
	 */
	public function hookActionModuleInstallAfter($o)
	{
		if (isset($o['object']) && is_object($o['object']) && $module = $o['object'])
		{
			$sample = new PtsThemeSample($this->theme_name);
			if (isset($module->name) && PtsThemeSample::isSampleExisted($this->theme_name, $module->name))
			{
				$sample->importModule($module->name, 'config');
				$sample->importModule($module->name, 'query');
			}
		}
		return true;
	}

	/**
	 *
	 */
	public function hookActionObjectLanguageAddAfter()
	{
		return $this->installFixtures();
	}

	/**
	 *
	 */
	public function hookActionProductListModifier($products)
	{
		$playout = $this->getConfigValue('product_layout');
		if (($playout == 'swap' || $playout == 'gallery') && $products['cat_products'])
		{
			foreach ($products['cat_products'] as $key => $product)
			{
				$obj = new Product($product['id_product'], true, $this->context->language->id, $this->context->shop->id);
				$images = $obj->getImages((int)$this->context->cookie->id_lang);
				$product['ptsimages'] = $images;
				$product['swapimage'] = count($images) > 1 ? $images[1] : '';
				$products['cat_products'][$key] = $product;
			}
		}
	}

	/**
	 *
	 */
	public function getContent()
	{
		if (Tools::isSubmit('submitModule'))
		{
			Configuration::updateValue('PS_QUICK_VIEW', (int)Tools::getValue('quick_view'));
			Configuration::updateValue('PS_GRID_PRODUCT', (int)Tools::getValue('grid_list'));
			Configuration::updateValue('PTS_CP_ACTIVE', (int)Tools::getValue('live_config'));
			foreach ($this->getConfigurableModules() as $module)
			{
				if (!isset($module['is_module']) || !$module['is_module'] || !Validate::isModuleName($module['name']) || !Tools::isSubmit($module['name']))
					continue;

				$module_instance = Module::getInstanceByName($module['name']);
				if ($module_instance === false || !is_object($module_instance))
					continue;

				$is_installed = (int)Validate::isLoadedObject($module_instance);
				if ($is_installed)
				{
					if (($active = (int)Tools::getValue($module['name'])) == $module_instance->active)
						continue;
					if ($active)
						$module_instance->enable();
					else
						$module_instance->disable();
				}
				elseif ((int)Tools::getValue($module['name']))
					$module_instance->install();
			}
			$this->batchUpdateConfigs($this->getLayoutForm());
			$this->batchUpdateConfigs($this->renderCustomForm());
			if ((int)Validate::isLoadedObject($module = Module::getInstanceByName('ptspagebuilder'))
				|| (int)Validate::isLoadedObject($module = Module::getInstanceByName('pspagebuilder')))
				$this->batchUpdateConfigs($this->renderProfilesForm());
			$themeskin = Configuration::get('PTS_CP_THEME');
			if (Configuration::get('PTS_CP_REHOOK') && $themeskin)
			{
				$sample = new PtsThemeSample($this->theme_name);
				$sample->reHookBySkin($themeskin);
			}
			Configuration::updateValue('PTS_CP_REHOOK', 0);
			$this->storeActivedThemeLog();
			$admin_url = $this->context->link->getAdminLink('AdminModules', true);
			$admin_url .= '&conf=1&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
			Tools::redirectAdmin($admin_url);
		}

		if (Tools::isSubmit('assignHooksAllModulesInXml'))
			PtsThemeSample::hooksAllModule($this->theme_name);

		if (Tools::isSubmit('assignHooksModulesInXml') && $modules = Tools::getValue('modules'))
			PtsThemeSample::assignHooksModulesInXml($this->theme_name, $modules);

		if (Tools::isSubmit('importConfiguration') && $modules = Tools::getValue('modules'))
		{
			$sample = new PtsThemeSample($this->theme_name);
			$sample->importConfigModules($modules);
		}

		if (Tools::isSubmit('importQueries') && $modules = Tools::getValue('modules'))
		{
			$sample = new PtsThemeSample($this->theme_name);
			$sample->importQueryModules($modules);
		}

		if (Tools::isSubmit('backupAll') && $modules = Tools::getValue('modules'))
		{
			$sample = new PtsThemeSample($this->theme_name);
			$sample->backup($this->theme_name);
		}

		if (Tools::isSubmit('restoreAll') && $modules = Tools::getValue('modules'))
		{
			$sample = new PtsThemeSample($this->theme_name);
			$sample->restore($modules);
		}

		if (Tools::getValue('export'))
		{
			$sample = new PtsThemeSample($this->theme_name);
			$sample->export();
			$sample->exportDBStruct();
			$sample->exportThemeSql();
		}

		if (Tools::getValue('import'))
		{
			$sample = new PtsThemeSample($this->theme_name);
			$sample->importModule(Tools::getValue('mod'));
		}

		$config_form = $this->renderConfigurationForm();
		$html_form = $this->renderPtsthemePanelForm();
		// buttons navs
		$btnnavs = array();

		$btnnavs[] = array('icon' => 'icon-cogs', 'title' => $this->l('Theme Setting'));
		if ((int)Validate::isLoadedObject($module = Module::getInstanceByName('ptspagebuilder')))
			$btnnavs[] = array('icon' => 'icon-cogs', 'title' => $this->l('Page, Footer Builder'));
		$btnnavs[] = array('icon' => 'icon-cogs', 'title' => $this->l('Customization'));
		$btnnavs[] = array('icon' => 'icon-cogs', 'title' => $this->l('Modules Setting'));

		$this->context->smarty->assign('configform', $config_form);
		$this->context->smarty->assign('btnnavs', $btnnavs);
		$this->context->smarty->assign('htmlform', $html_form);
		$this->context->smarty->assign('moduleEditURL', 'index.php?controller=adminmodules
				&token='.Tools::getAdminTokenLite('AdminModules').'&tab_module=Home');

		$samples = PtsThemeSample::getModuleSamples($this->theme_name);
		// d($samples );
		foreach ($samples as $key => $sample)
		{
			if (isset($sample['name']))
			{
				$sample['hassample'] = PtsThemeSample::isSampleExisted($this->theme_name, $sample['name']);
				$sample['queries'] = (int)$sample['queries'];
				$sample['backup'] = PtsThemeSample::isBackupExisted($this->theme_name, $sample['name']);
				$samples[$key] = $sample;
			}
			else
				unset($samples[$key]);
		}

		$this->context->smarty->assign('samples', $samples);
		$this->context->smarty->assign('guideurl', LIVE_GUIDE_URL.$this->theme_name);

		$lang = '';
		if (Configuration::get('PS_REWRITING_SETTINGS') && count(Language::getLanguages(true)) > 1)
			$lang = Language::getIsoById($this->context->employee->id_lang).'/';
		$tools_editor = $this->context->shop->getBaseUrl()
						.(Configuration::get('PS_REWRITING_SETTINGS') ? '' : 'index.php')
						.$lang
						.'?live_configurator_token='.$this->getLiveConfiguratorToken()
						.'&id_employee='.(int)$this->context->employee->id
						.'&id_shop='.(int)$this->context->shop->id
						.(Configuration::get('PS_TC_THEME') != '' ? '&theme='.Configuration::get('PS_TC_THEME') : '')
						.(Configuration::get('PS_TC_FONT') != '' ? '&theme_font='.Configuration::get('PS_TC_FONT') : '');
		$this->context->smarty->assign('tools_editor', $tools_editor);
		return $this->display(__FILE__, 'views/templates/admin/form.tpl');
	}

	public function getLayoutForm()
	{
		$layoutform = PtsThemePanelHelper::getLayoutSettingByTheme($this->theme_name);
		$params = isset($layoutform['layout']) ? $layoutform['layout'] : array('layout' => array());

		$soption = array(
			array(
				'id' => 'active_on',
				'value' => 1,
				'label' => $this->l('Enabled')
			),
			array(
				'id' => 'active_off',
				'value' => 0,
				'label' => $this->l('Disabled')
			)
		);

		$inputs = array(
			'theme' => array(
				'type' => 'select',
				'label' => $this->l('Set Default Skin'),
				'name' => 'theme',
				'options' => array('query' => $this->getThemes(),
					'id' => 'skin',
					'name' => 'skin'),
				'default' => 'default',
				'desc' => $this->l('select a skin as default, and you can switch skin in')
				.'<a href="#livethemeconfigurator"> '.$this->l('Live Theme Configurator Panel').'</a>'
			)
		);
		if (PtsThemeSample::isReHookBySkin($this->theme_name, 'default'))
		{
			$inputs['rehook'] = array(
				'type' => 'switch',
				'label' => $this->l('Auto ReHook Wih Actived Skin'),
				'name' => 'rehook',
				'desc' => $this->l('When changing skin to other skin, all modules will be re-hooked on expected 
					positions of that skin. This doest not save value in configuration'),
				'values' => $soption,
				'default' => 0,
			);
		}
		$params = empty($params) ? $inputs : array_merge_recursive($inputs, $params);
		return $params;
	}

	public function renderConfigurationForm()
	{
		$inputs = array();

		foreach ($this->getConfigurableModules() as $module)
		{
			$desc = '';
			if (isset($module['is_module']) && $module['is_module'])
			{
				$module_instance = Module::getInstanceByName($module['name']);
				if (Validate::isLoadedObject($module_instance) && method_exists($module_instance, 'getContent'))
					$desc = '<a class="btn btn-default" href="'.$this->context->link->getAdminLink('AdminModules', true).'
						&configure='.urlencode($module_instance->name).'&tab_module='.$module_instance->tab.'&module_name='.urlencode($module_instance->name).'">
						'.$this->l('Configure').' <i class="icon-external-link"></i></a>';
			}
			if (!$desc && isset($module['desc']) && $module['desc'])
				$desc = $module['desc'];

			$inputs[] = array(
				'type' => 'switch',
				'label' => $module['label'],
				'name' => $module['name'],
				'desc' => $desc,
				'default' => 1,
				'values' => array(
					array(
						'id' => 'active_on',
						'value' => 1,
						'label' => $this->l('Enabled')
					),
					array(
						'id' => 'active_off',
						'value' => 0,
						'label' => $this->l('Disabled')
					)
				),
			);
		}
		$fields_form = array();
		$fields_form[] = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Theme Settings'),
					'icon' => 'icon-cogs'
				),
				'input' => $this->getLayoutForm(),
				'submit' => array(
					'title' => $this->l('Save'),
					'class' => 'btn btn-primary pull-right'
				)
			),
		);
		/// Profiles
		if ((int)Validate::isLoadedObject($module = Module::getInstanceByName('ptspagebuilder'))
			|| (int)Validate::isLoadedObject($module = Module::getInstanceByName('pspagebuilder')))
			$fields_form[] = array(
				'form' => array(
					'legend' => array(
						'title' => $this->l('Footer And Page Builder Profies Assignment'),
						'icon' => 'icon-cogs'
					),
					'input' => $this->renderProfilesForm(),
				),
			);
		$live_theme_url = $this->context->shop->getBaseURL()
						.((Configuration::get('PS_REWRITING_SETTINGS') && count(Language::getLanguages(true)) > 1)
							? Language::getIsoById($this->context->employee->id_lang).'/' : '')
						.(Configuration::get('PS_REWRITING_SETTINGS') ? '' : 'index.php')
						.'?live_configurator_token='.$this->getLiveConfiguratorToken()
						.'&id_employee='.(int)$this->context->employee->id
						.'&id_shop='.(int)$this->context->shop->id
						.(Configuration::get('PTS_CP_THEME') != '' ? '&theme='.Configuration::get('PTS_CP_THEME') : '')
						.(Configuration::get('PTS_CP_FONT') != '' ? '&theme_font='.Configuration::get('PTS_CP_FONT') : '');

		$fields_form[] = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Customizations'),
					'icon' => 'icon-cogs'
				),
				'description' => $this->l('Support Customize Theme Skin Without experting Css Code').' 
				'.$this->l(' The framework supports you two way to put your customization on theme.').'<br />
                <p id="livethemeconfigurator" >'.$this->l('1. You create your css file(s) and put in PTS_YOURTHEME/css/autoload. 
                	And put your js files in PTS_YOURTHEME/js/autoload').'</p>'
				.$this->l('2. All files will be loaded automatic Or use tools at bellow').'</p><hr>'
				.$this->l('Click Live Customizing Theme to create custom-theme-profiles and they will be listed in above dropdown box. 
					You select one profile theme to apply for your site')
				.'<p>'.$this->l('!important: All theme profiles are stored in folder PTS_YOURTHEME/css/customize, 
					it need permission 0755 to put files inside').'</p>
                <br><a href="'.$live_theme_url.'" class="btn-danger btn"><i class="icon-external-link-sign"></i> '.
				$this->l('Live Theme Configurator').'</a> ( '.$this->l('Only you can see this on your Front-Office - 
					your visitors will not see this tool.').' ).',
				'input' => $this->renderCustomForm(),
				'submit' => array(
					'title' => $this->l('Save'),
					'class' => 'btn btn-primary pull-right'
				)
			),
		);

		$fields_form[] = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Module Settings'),
					'icon' => 'icon-cogs'
				),
				'input' => $inputs,
				'submit' => array(
					'title' => $this->l('Save'),
					'class' => 'btn btn-primary pull-right'
				)
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitModule';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'
			&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues($fields_form),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(($fields_form));
	}
	public function getList()
	{
		$context = Context::getContext();
		$id_shop = $context->shop->id;
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT  *, ps.default 
			FROM '._DB_PREFIX_.'pagebuilderprofile  p
			LEFT JOIN '._DB_PREFIX_.'pagebuilderprofile_shop ps ON (p.id_pagebuilderprofile = ps.id_pagebuilderprofile)
			WHERE id_shop = '.(int)$id_shop.' and is_footer !=1 
		');
	}
	public function getListByFooter()
	{
		$context = Context::getContext();
		$id_shop = $context->shop->id;

		return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT  *, ps.default 
			FROM '._DB_PREFIX_.'pagebuilderprofile  p
			LEFT JOIN '._DB_PREFIX_.'pagebuilderprofile_shop ps ON (p.id_pagebuilderprofile = ps.id_pagebuilderprofile)
			WHERE id_shop = '.(int)$id_shop.' AND is_footer=1
		');
	}
	protected function renderProfilesForm()
	{
		$inputs = array();
		$skins = $this->getThemes();
		$pagebuilder = $this->getList();
		$footerbuilder = $this->getListByFooter();
		$inputs['enable_pb'] = array(
			'type' => 'switch',
			'label' => $this->l('Enable Page Builder'),
			'name' => 'enable_pbuilder',
			'desc' => $this->l('When changing skin to other skin, all modules will be re-hooked on expected positions of that skin. 
				This doest not save value in configuration'),
			'default' => 0,
			'values' => array(
				array(
					'id' => 'active_on',
					'value' => 1,
					'label' => $this->l('Enabled')
				),
				array(
					'id' => 'active_off',
					'value' => 0,
					'label' => $this->l('Disabled')
				)
			),
		);
		$inputs['enable_fb'] = array(
			'type' => 'switch',
			'label' => $this->l('Enable Footer Builder'),
			'name' => 'enable_fbuilder',
			'desc' => $this->l('When changing skin to other skin, all modules will be re-hooked on expected positions of that skin. 
				This doest not save value in configuration'),
			'default' => 0,
			'values' => array(
				array(
					'id' => 'active_on',
					'value' => 1,
					'label' => $this->l('Enabled')
				),
				array(
					'id' => 'active_off',
					'value' => 0,
					'label' => $this->l('Disabled')
				)
			),
		);
		foreach ($skins as $skin)
		{
			$inputs[] = array(
				'type' => 'select',
				'label' => $this->l('Page Profiles For Skin').': '.$skin['name'],
				'name' => $skin['skin'].'pbuilder',
				'options' => array('query' => $pagebuilder,
					'id' => 'key',
					'name' => 'name'),
				'default' => '',
				'desc' => ''
			);
			$inputs[] = array(
				'type' => 'select',
				'label' => $this->l('Footer Profiles For Skin').': '.$skin['name'],
				'name' => $skin['skin'].'fbuilder',
				'options' => array('query' => $footerbuilder,
					'id' => 'key',
					'name' => 'name'),
				'default' => 'default',
				'desc' => $this->l('select a skin as default, and you can switch skin in')
			);
		}
		return $inputs;
	}
	/**
	 *
	 */
	protected function renderCustomForm()
	{
		return array(
			array(
				'type' => 'textarea',
				'label' => $this->l('Add Custom Css'),
				'name' => ( 'customcss' ),
				'autoload_rte' => false,
				'rows' => 5,
				'cols' => 40,
				'desc' => 'Please make your code be  careful',
				'default' => '',
				'hint' => $this->l('Invalid characters:').' <>;=#{}'
			),
			array(
				'type' => 'textarea',
				'label' => $this->l('Add Custom JS'),
				'name' => ( 'customjs' ),
				'autoload_rte' => false,
				'rows' => 5,
				'cols' => 40,
				'desc' => 'Please make your code be  careful',
				'default' => '',
				'hint' => $this->l('Invalid characters:').' <>;=#{}'
			)
		);
	}

	/**
	 *
	 */
	protected function renderPtsthemePanelForm()
	{
		return '';
	}

	/**
	 *
	 */
	protected function getConfigurableModules()
	{
		// Construct the description for the 'Enable Live Configurator' switch
		$desc = $this->l('Only you can see this on your Front-Office - your visitors will not see this tool.');

		return array(
			array(
				'label' => $this->l('Display links to your store\'s social accounts (Twitter, Facebook, etc.)'),
				'name' => 'blocksocial',
				'value' => (int)Validate::isLoadedObject($module = Module::getInstanceByName('blocksocial')) && $module->isEnabledForShopContext(),
				'is_module' => true,
			),
			array(
				'label' => $this->l('Display contact information'),
				'name' => 'blockcontactinfos',
				'value' => (int)Validate::isLoadedObject($module = Module::getInstanceByName('blockcontactinfos')) && $module->isEnabledForShopContext(),
				'is_module' => true,
			),
			array(
				'label' => $this->l('Display social sharing buttons on the products page'),
				'name' => 'socialsharing',
				'value' => (int)Validate::isLoadedObject($module = Module::getInstanceByName('socialsharing')) && $module->isEnabledForShopContext(),
				'is_module' => true,
			),
			array(
				'label' => $this->l('Display the Facebook block on the home page'),
				'name' => 'blockfacebook',
				'value' => (int)Validate::isLoadedObject($module = Module::getInstanceByName('blockfacebook')) && $module->isEnabledForShopContext(),
				'is_module' => true,
			),
			array(
				'label' => $this->l('Custom CMS information block'),
				'name' => 'blockcmsinfo',
				'value' => (int)Validate::isLoadedObject($module = Module::getInstanceByName('blockcmsinfo')) && $module->isEnabledForShopContext(),
				'is_module' => true,
			),
			array(
				'label' => $this->l('Enable quick view'),
				'name' => 'quick_view',
				'value' => (int)Tools::getValue('PS_QUICK_VIEW', Configuration::get('PS_QUICK_VIEW'))
			),
			array(
				'label' => $this->l('Display categories as a list of products instead of the default grid-based display'),
				'desc' => $this->l('Works only for first-time users. This setting is overridden by the user\'s choice as soon as the user cookie is set.'),
				'name' => 'grid_list',
				'value' => (int)Tools::getValue('PS_GRID_PRODUCT', Configuration::get('PS_GRID_PRODUCT'))
			),
			array(
				'label' => $this->l('Enable top banner'),
				'name' => 'blockbanner',
				'value' => (int)Validate::isLoadedObject($module = Module::getInstanceByName('blockbanner')) && $module->isEnabledForShopContext(),
				'is_module' => true,
			),
			array(
				'label' => $this->l('Display your product payment logos'),
				'name' => 'productpaymentlogos',
				'value' => (int)Validate::isLoadedObject($module = Module::getInstanceByName('productpaymentlogos')) && $module->isEnabledForShopContext(),
				'is_module' => true,
			),
			array(
				'label' => $this->l('Enable Live Configurator'),
				'name' => 'live_config',
				'value' => (int)Tools::getValue('PTS_CP_ACTIVE', Configuration::get('PTS_CP_ACTIVE')),
				'hint' => $this->l('The customization tool allows you to make color and font changes in your theme.'),
				'desc' => $desc
			)
		);
	}

	/**
	 *
	 */
	public function batchUpdateConfigs($f)
	{
		$languages = Language::getLanguages(false);
		foreach ($f as $input)
			if (isset($input['lang']))
			{
				$data = array();
				foreach ($languages as $lang)
				{
					$val = Tools::getValue($input['name'].'_'.$lang['id_lang'], $input['default']);
					$data[$lang['id_lang']] = $val;
				}
				$this->saveConfigValue($input['name'], $data);
			}
			else
			{

				$val = Tools::getValue($input['name'], $input['default']);
				$this->saveConfigValue($input['name'], $val);
			}
	}

	public function getConfigFieldsValue($f)
	{
		$languages = Language::getLanguages(false);
		$fields_values = array();
		foreach ($f['form']['input'] as $input)
		{
			$field = 'PTS_CP_'.Tools::strtoupper(trim($input['name']));
			if (isset($input['lang']))
				foreach ($languages as $lang)
				{
					$values = Tools::getValue($input['name'].'_'.$lang['id_lang'], (Configuration::hasKey($field, $this->context->language->id)
						? Configuration::get($field, $lang['id_lang']) : $input['default']));
					$fields_values[$input['name']][$lang['id_lang']] = $values;
				}
			else
			{
				$values = Tools::getValue($input['name'], (Configuration::hasKey($field) ? Configuration::get($field)
					: (isset($input['default']) ? $input['default'] : '')));
				$fields_values[$input['name']] = $values;
			}
		}
		return $fields_values;
	}

	/**
	 *
	 */
	public function getConfigFieldsValues($fields_form)
	{
		$values = array();
		if (is_array($fields_form))
			foreach ($fields_form as $fm)
			{
				$aa = $this->getConfigFieldsValue($fm);
				$values = array_merge_recursive($values, $aa);
			}
		foreach ($this->getConfigurableModules() as $module)
			$values[$module['name']] = $module['value'];

		return $values;
	}

	/**
	 *
	 */
	public function checkValidToken()
	{
		$this->getLiveConfiguratorToken();
		return in_array(Tools::getValue('live_configurator_token'), $this->tokens);
	}

	/**
	 *
	 */
	public function getLiveConfiguratorToken()
	{
		$context = Context::getContext();
		$token = $context->cookie->__get('liveecookie');
		$name = 'ptsthemepanel';
		$this->tokens[] = Tools::getAdminToken($name.(int)Tab::getIdFromClassName($name)
										.(is_object(Context::getContext()->employee) ? (int)Context::getContext()->employee->id :
														Tools::getValue('id_employee')));
		if (empty($token))
		{
			$token = Tools::getAdminToken($this->name.(int)Tab::getIdFromClassName($this->name)
											.(is_object(Context::getContext()->employee) ? (int)Context::getContext()->employee->id :
															Tools::getValue('id_employee')));
			$context->cookie->__set('liveecookie', $token);
		}
		$this->tokens[] = $token;

		return $token;
	}

	/* Gallery, Swap */

	public function hookDisplayProductListGallery($params)
	{
		if (Configuration::get('PTS_CP_PRODUCT_LAYOUT') != 'gallery')
			return '';
		$id_product = (int)$params['product']['id_product'];
		if (!$this->isCached('gallery.tpl', $this->getCacheId($id_product)))
		{
			$images = $this->getImages($id_product, (int)$this->context->language->id);
			$ptsimages = $images;

			$this->smarty->assign(array(
				'ptsimages' => $ptsimages,
				'product' => $params['product'],
				'ptsgkey' => rand(100, 99999),
				'link' => $this->context->link
			));
		}
		return $this->display(__FILE__, 'gallery.tpl', $this->getCacheId($id_product));
	}

	public function hookDisplayProductListSwap($params)
	{
		if (Configuration::get('PTS_CP_PRODUCT_LAYOUT') != 'swap')
			return '';
		$id_product = (int)$params['product']['id_product'];

		if (!$this->isCached('swap.tpl', $this->getCacheId($id_product)))
		{
			$images = $this->getImages($id_product, (int)$this->context->language->id);
			$swapimage = count($images) > 1 ? $images[1] : '';
			$this->smarty->assign(array(
				'swapimage' => $swapimage,
				'product' => $params['product'],
				'ptsgkey' => rand(100, 999),
				'link' => $this->context->link
			));
		}
		return $this->display(__FILE__, 'swap.tpl', $this->getCacheId($id_product));
	}

	public function getImages($id_product, $id_lang, Context $context = null)
	{
		if (!$context)
			$context = Context::getContext();

		$sql = 'SELECT image_shop.`cover`, i.`id_image`, il.`legend`, i.`position`
                FROM `'._DB_PREFIX_.'image` i
                '.Shop::addSqlAssociation('image', 'i').'
                LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = '.(int)$id_lang.')
                WHERE i.`id_product` = '.(int)$id_product.'
                ORDER BY `position`';
		return Db::getInstance()->executeS($sql);
	}

	protected function getCacheId($id_product = null, $hook = '')
	{
		$cache_array = array(
			$id_product,
			$hook,
			date('Ymd'),
			(int)Tools::usingSecureMode(),
			(int)$this->context->shop->id,
			(int)Group::getCurrent()->id,
			(int)$this->context->language->id,
			(int)$this->context->currency->id,
			(int)$this->context->country->id
		);
		return implode('|', $cache_array);
	}

	public function clearBLCCache()
	{
		$this->_clearCache('gallery.tpl');
		$this->_clearCache('swap.tpl');
	}

	public function hookAddProduct()
	{
		$this->clearBLCCache();
	}

	public function hookUpdateProduct()
	{
		$this->clearBLCCache();
	}

	public function hookDeleteProduct()
	{
		$this->clearBLCCache();
	}

}
