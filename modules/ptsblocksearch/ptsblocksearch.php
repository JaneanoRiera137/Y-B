<?php
/**
 * Pts Prestashop Theme Framework for Prestashop 1.6.x
 *
 * @package   ptsblocksearch
 * @version   2.0.0
 * @author    http://www.prestabrain.com
 * @copyright Copyright (C) October 2013 prestabrain.com <@emai:prestabrain@gmail.com>
 *               <info@prestabrain.com>.All rights reserved.
 * @license   GNU General Public License version 2
 */

if (!defined('_PS_VERSION_'))
	exit;

class PtsBlockSearch extends Module {

	private $prefix;
	private $fields_form = array();

	public function __construct()
	{
		$this->name = 'ptsblocksearch';
		$this->tab = 'search_filter';
		$this->version = '2.0.0';
		$this->author = 'PrestaBrain';
		$this->need_instance = 0;
		$this->prefix = 'ptssearch';
		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('Pts Quick search block');
		$this->description = $this->l('Adds a quick search field to your website.');
	}

	public function install()
	{
		$this->checkOwnerHooks();
		if (!parent::install() || !$this->registerHook('top') || !$this->registerHook('header') || !$this->registerHook('displayMobileTopSiteMap'))
			return false;
		return true;
	}

	private function checkOwnerHooks()
	{
		$hookspos = array(
			'displayTop',
			'displayHeaderRight',
			'displaySlideshow',
			'topNavigation',
			'displayMainmenu',
			'displayPromoteTop',
			'displayRightColumn',
			'displayLeftColumn',
			'displayHome',
			'displayFooter',
			'displayBottom',
			'displayContentBottom',
			'displayFootNav',
			'displayFooterTop',
			'displayFooterBottom'
		);

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

	public function getContent()
	{
		$output = '<h2>'.$this->displayName.'</h2>';
		if (Tools::isSubmit('submitPtsBlockSearch'))
		{
			$this->makeFormConfig();
			$this->batchUpdateConfigs();
			$output .= $this->displayConfirmation($this->l('Settings updated successfully.'));
		}
		$this->context->controller->addJqueryPlugin('tagify');
		return $output.$this->renderForm();
	}

	public function makeFormConfig()
	{
		if ($this->fields_form)
			return;
		$fields = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Settings'),
					'icon' => 'icon-cogs'
				),
				'input' => array(
					array(
						'type' => 'switch',
						'label' => $this->l('Display categories'),
						'name' => $this->renderName('show_cat'),
						'desc' => $this->l('Show list categories to filter.'),
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
						'default' => '1'
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Show tags'),
						'name' => $this->renderName('show_tags'),
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
						'default' => '0'
					),
					array(
						'type' => 'tags',
						'label' => $this->l('Tags'),
						'name' => $this->renderName('tags'),
						'lang' => true,
						'hint' => array(
							$this->l('Invalid characters:').' &lt;&gt;;=#{}',
							$this->l('To add "tags" click in the field, write something, and then press "Enter."')
						),
						'default' => ''
					),
				),
				'submit' => array(
					'title' => $this->l('Save'),
					'class' => 'btn btn-default')
			),
		);

		$this->fields_form[] = $fields;
	}

	public function renderForm()
	{
		$this->makeFormConfig();

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitPtsBlockSearch';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'
			&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(($this->fields_form));
	}

	public function getConfigFieldsValues()
	{
		$fields_values = array();
		foreach ($this->fields_form as $f)
			foreach ($f['form']['input'] as $input)
				if (isset($input['lang']))
				{
					foreach ($this->languages() as $lang)
					{
						$values = Tools::getValue($input['name'].'_'.$lang['id_lang'], (Configuration::hasKey($input['name'], $lang['id_lang'])
										? Configuration::get($input['name'], $lang['id_lang']) : $input['default']));
						$fields_values[$input['name']][$lang['id_lang']] = $values;
					}
				}
				else
				{
					$values = Tools::getValue($input['name'], ( Configuration::hasKey($input['name'])
									? Configuration::get($input['name']) : $input['default']));
					$fields_values[$input['name']] = $values;
				}
		return $fields_values;
	}

	public function batchUpdateConfigs()
	{
		foreach ($this->fields_form as $f)
			foreach ($f['form']['input'] as $input)
				if (isset($input['lang']))
				{
					$data = array();
					foreach ($this->languages() as $lang)
					{
						$val = Tools::getValue($input['name'].'_'.$lang['id_lang'], $input['default']);
						$data[$lang['id_lang']] = $val;
					}
					Configuration::updateValue(trim($input['name']), $data);
				}
				else
				{
					$val = Tools::getValue($input['name'], $input['default']);
					Configuration::updateValue($input['name'], $val);
				}
	}

	public function deleteConfigs()
	{
		foreach ($this->fields_form as $f)
			foreach ($f['form']['input'] as $input)
				if (isset($input['lang']))
					foreach ($this->languages() as $lang)
						Configuration::deleteByName($input['name'].'_'.$lang['id_lang']);
				else
					Configuration::deleteByName($input['name']);
	}

	public function getConfigValue($key, $id_lang = null, $value = null)
	{
		return (Configuration::hasKey($this->renderName($key), $id_lang) ? Configuration::get($this->renderName($key), $id_lang) : $value );
	}

	public function renderName($name)
	{
		return Tools::strtoupper($this->prefix.'_'.$name);
	}

	public function languages()
	{
		return Language::getLanguages(false);
	}

	public function hookdisplayMobileTopSiteMap($params)
	{
		$this->smarty->assign(array('hook_mobile' => true, 'instantsearch' => false));
		$params['hook_mobile'] = true;
		return $this->hookTop($params);
	}

	public function hookHeader()
	{
		$this->context->controller->addJqueryPlugin('autocomplete');
		$this->context->controller->addCSS(($this->_path).'views/css/ptsblocksearch.css', 'all');

		Media::addJsDef(array('search_url' => $this->context->link->getPageLink('search', Tools::usingSecureMode())));
	}

	public function hookLeftColumn($params)
	{
		return $this->hookRightColumn($params);
	}

	public function hookRightColumn($params)
	{
		if (Tools::getValue('search_query') || !$this->isCached('ptsblocksearch.tpl', $this->getCacheId()))
		{
			$this->calculHookCommon($params);
			$this->smarty->assign(array(
				'ptsblocksearch_type' => 'block',
				'search_query' => (string)Tools::getValue('search_query')
			));
		}
		Media::addJsDef(array('ptsblocksearch_type' => 'block'));
		return $this->display(__FILE__, 'ptsblocksearch.tpl', Tools::getValue('search_query') ? null : $this->getCacheId());
	}

	public function hookTop($params)
	{
		$key = $this->getCacheId('ptsblocksearch-top'.((!isset($params['hook_mobile']) || !$params['hook_mobile']) ? '' : '-hook_mobile'));
		if (Tools::getValue('search_query') || !$this->isCached('ptsblocksearch-top.tpl', $key))
		{
			$this->calculHookCommon();
			$this->smarty->assign(array(
				'ptsblocksearch_type' => 'top',
				'search_query' => (string)Tools::getValue('search_query')
			));
		}
		Media::addJsDef(array('ptsblocksearch_type' => 'top'));
		return $this->display(__FILE__, 'ptsblocksearch-top.tpl', Tools::getValue('search_query') ? null : $key);
	}

	public function hookDisplayNav($params)
	{
		return $this->hookTop($params);
	}

	public function hookDisplayMainmenu($params)
	{
		return $this->hookTop($params);
	}

	private function calculHookCommon()
	{
		if (file_exists(_PS_THEME_DIR_.'modules/ptsblocksearch/views/templates/hook/ptsblocksearch-instantsearch.tpl'))
			$this->smarty->assign('instance_tpl_path', _PS_THEME_DIR_.'modules/ptsblocksearch/views/templates/hook/ptsblocksearch-instantsearch.tpl');
		else
			$this->smarty->assign('instance_tpl_path', _PS_MODULE_DIR_.'ptsblocksearch/views/templates/hook/ptsblocksearch-instantsearch.tpl');
		$tags = '';
		$arrtags = array();
		if ($this->getConfigValue('show_tags', null, 1))
		{
			$tags = $this->getConfigValue('tags', $this->context->language->id, '');
			if ($tags)
			{
				$tags = explode(',', $tags);
				foreach ($tags as $value)
				{
					$request = array(
						'orderby' => 'position',
						'orderway' => 'desc',
						'search_query' => urlencode($value)
					);
					$arrtags[] = array('tag' => $value, 'link' => $this->context->link->getPageLink('search', null, null, $request));
				}
			}
		}
		$this->smarty->assign(array(
			'ENT_QUOTES' => ENT_QUOTES,
			'search_ssl' => Tools::usingSecureMode(),
			'instantsearch' => Configuration::get('PS_INSTANT_SEARCH'),
			'form_link' => $this->context->link->getModuleLink('ptsblocksearch', 'search'),
			'self' => dirname(__FILE__),
			'category_html' => $this->getHtmlCategories(),
			'tags' => $arrtags
		));

		return true;
	}

	public function getHtmlCategories()
	{
		if (!$this->getConfigValue('show_cat', null, 1))
			return '';
		$max_depth = Configuration::get('BLOCK_CATEG_MAX_DEPTH');
		// Get all groups for this customer and concatenate them as a string: "1,2,3..."
		$groups = implode(', ', Customer::getGroupsStatic((int)$this->context->customer->id));
		if (!$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT DISTINCT c.id_parent, c.id_category, cl.name, cl.description, cl.link_rewrite
            FROM `'._DB_PREFIX_.'category` c
            '.Shop::addSqlAssociation('category', 'c').'
            LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (c.`id_category` = cl.`id_category` 
							AND cl.`id_lang` = '.(int)$this->context->language->id.Shop::addSqlRestrictionOnLang('cl').')
            LEFT JOIN `'._DB_PREFIX_.'category_group` cg ON (cg.`id_category` = c.`id_category`)
            WHERE (c.`active` = 1 OR c.`id_category` = '.(int)Configuration::get('PS_HOME_CATEGORY').')
			AND c.`id_category` != '.(int)Configuration::get('PS_ROOT_CATEGORY').'
            '.((int)$max_depth != 0 ? ' AND `level_depth` <= '.(int)$max_depth : '').'
            AND cg.`id_group` IN ('.pSQL($groups).')
            ORDER BY `level_depth` ASC, '.(Configuration::get('BLOCK_CATEG_SORT') ? 'cl.`name`'
								: 'category_shop.`position`').' '.(Configuration::get('BLOCK_CATEG_SORT_WAY') ? 'DESC' : 'ASC')))
			return;
		$result_parents = array();
		$result_ids = array();

		foreach ($result as &$row)
		{
			$result_parents[$row['id_parent']][] = &$row;
			$result_ids[$row['id_category']] = &$row;
		}
		//$nbr_columns = Configuration::get('BLOCK_CATEG_NBR_COLUMNS_FOOTER');
		$nbr_columns = (int)Configuration::get('BLOCK_CATEG_NBR_COLUMN_FOOTER');
		if (!$nbr_columns || empty($nbr_columns))
			$nbr_columns = 3;
		$number_column = abs(count($result) / $nbr_columns);
		$width_column = floor(100 / $nbr_columns);
		$this->smarty->assign('numberColumn', $number_column);
		$this->smarty->assign('widthColumn', $width_column);

		$block_categ_tree = $this->getTree($result_parents, $result_ids, Configuration::get('BLOCK_CATEG_MAX_DEPTH'));

		unset($result_parents, $result_ids);

		$this->smarty->assign('ptsblocksearchCategTree', $block_categ_tree);
		$this->smarty->assign('current_category', Tools::getValue('id_category'));
		$this->smarty->assign('home_category', new Category(Context::getContext()->shop->getCategory(), (int)Context::getContext()->language->id));

		if (file_exists(_PS_THEME_DIR_.'modules/ptsblocksearch/views/templates/hook/categories.tpl'))
			$this->smarty->assign('ptsbranche_tpl_path', _PS_THEME_DIR_.'modules/ptsblocksearch/views/templates/hook/category-tree-branch.tpl');
		else
			$this->smarty->assign('ptsbranche_tpl_path', _PS_MODULE_DIR_.'ptsblocksearch/views/templates/hook/category-tree-branch.tpl');
		$display = $this->display(__FILE__, 'categories.tpl');
		return $display;
	}

	public function getTree($result_parents, $result_ids, $max_depth, $id_category = null, $current_depth = 0, $prefix = '')
	{
		if (is_null($id_category))
			$id_category = $this->context->shop->getCategory();
		$prefix .= '--';
		$children = array();
		if (isset($result_parents[$id_category]) && count($result_parents[$id_category]) && ($max_depth == 0 || $current_depth < $max_depth))
			foreach ($result_parents[$id_category] as $subcat)
				$children[] = $this->getTree($result_parents, $result_ids, $max_depth, $subcat['id_category'], $current_depth + 1, $prefix);

		$return = array(
			'id' => $id_category,
			'link' => $this->context->link->getCategoryLink($id_category, $result_ids[$id_category]['link_rewrite']),
			'name' => $result_ids[$id_category]['name'],
			'desc' => $result_ids[$id_category]['description'],
			'children' => $children,
			'prefix' => $prefix
		);

		return $return;
	}

}
