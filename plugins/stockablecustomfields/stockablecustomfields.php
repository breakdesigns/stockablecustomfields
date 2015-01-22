<?php

if (!defined('_JEXEC')) die;

/**
 * @version		$Id: stockablecustomfields.php 2014-12-15 20:10 sakis Terz $2
 * @package		stockablecustomfield
 * @copyright	Copyright (C) 2014-2015 breakdesigns.net . All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

if(!class_exists('vmCustomPlugin')) require(JPATH_VM_PLUGINS.DIRECTORY_SEPARATOR.'vmcustomplugin.php');
require_once(JPATH_PLUGINS.DIRECTORY_SEPARATOR.'vmcustom'.DIRECTORY_SEPARATOR.'stockablecustomfields'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'customfield.php');

class plgVmCustomStockablecustomfields extends vmCustomPlugin {
	/**
	 * Constructor class of the custom field
	 *
	 * @param unknown_type $subject
	 * @param array $config
	 */
	function __construct(& $subject, $config) {
		parent::__construct($subject, $config);

		$varsToPush = array(
			'parentOrderable'=>array(0,'int'),	
			'custom_id'=> array('', 'array'),
			'child_product_id'=>array(0,'int'),				
		);


		if(!defined('VM_VERSION'))define('VM_VERSION', '2.0');
		if(version_compare(VM_VERSION, '2.9','lt')){
			$this->setConfigParameterable ('custom_params', $varsToPush);
			$this->_product_paramName = 'plugin_param';
		} else {
			$this->setConfigParameterable ('customfield_params', $varsToPush);
			$this->_product_paramName = 'customfield_params';
		}

	}

	/**
	 * Declares the Parameters of a plugin
	 * @param $data
	 *
	 * @return bool
	 */
	function plgVmDeclarePluginParamsCustomVM3(&$data){

		return $this->declarePluginParams('custom', $data);
	}

	function plgVmGetTablePluginParams($psType, $name, $id, &$xParams, &$varsToPush){
		return $this->getTablePluginParams($psType, $name, $id, $xParams, $varsToPush);
	}

	/**
	 *
	 * Exec when a cf is created/updated (stored) - Customfield view
	 * @param string $psType
	 * @param array  $data All the data of that cf
	 */
	function plgVmOnStoreInstallPluginTable($psType,$data) {
		vmdebug('data:',$data);
	}

	/**
	 * Displays the custom field in the product view of the backend
	 * The custom field should not be displayed before the product being saved. Also should not loaded in the child products
	 * @todo	should not loaded in the child products
	 *
	 * @param 	object	$field - The custom field
	 * @param 	int		$product_id
	 * @param 	int 	$row - The a/a of that field within the product
	 * @param 	string 	$retValue - The html that regards the custom fields of that product
	 *
	 * @return	boolean
	 * @since	1.0
	 */
	function plgVmOnProductEdit($field, $product_id, &$row,&$retValue) {
		if ($field->custom_element != $this->_name) return '';
		if(version_compare(VM_VERSION, '2.9','lt'))$this->parseCustomParams ($field);
		//If the product is not saved do not proceed
		if(empty($product_id)){
			$retValue='<div style="clear:both;" class="alert alert-info"><span class="icon-info">'.JText::_('PLG_STOCKABLECUSTOMFIELDS_PLEASE_SAVE_PRODUCT').'</span></div>';
			return;
		}
		$product=$this->getProduct($product_id);

		//do not display in child products
		if($product->product_parent_id>0){
			$retValue='<div style="clear:both;" class="alert alert-info"><span class="icon-info">'.JText::_('PLG_STOCKABLECUSTOMFIELDS_PLUGIN_ASSIGNED').'</span></div>';
			return;
		}

		//check if there is a child product derived from this customfield
		$child_product_id=0;
		if(!empty($field->child_product_id)){
			$child_product=$this->getProduct($field->child_product_id);
			//it can return an empty product object, even if it does not exist
			if(empty($child_product->product_parent_id))$child_product=false;
		}
		if(!empty($child_product))$child_product_id=$child_product->virtuemart_product_id;

		$html='';
		$parent_custom_id=$field->virtuemart_custom_id;
		$customfield=CustomfieldStockablecustomfields::getInstance($parent_custom_id);
		$custom_params=$customfield->getCustomfieldParams($parent_custom_id);
		//the customs that consists the stockable
		$custom_ids=$custom_params['custom_id'];

		if(!empty($custom_ids) && is_array($custom_ids)){
			$html.='<table class="table">';

			foreach ($custom_ids as $custom_id){
				$subcustomfield=false;
				$custom=CustomfieldStockablecustomfields::getCustom($custom_id);
				if(!empty($child_product)){
					//get the other fields
					$subcustomfields=CustomfieldStockablecustomfields::getCustomfields($child_product_id,$custom_id);
					$subcustomfield=reset($subcustomfields);
				}

				if($custom->field_type!='E'){
					$value='';
					!empty($subcustomfield->customfield_value)?$value=$subcustomfield->customfield_value:$value='';
					$html.='<tr>';
					$html.='<td><label for="'.$row.'_'.$custom_id.'" class="">'.JText::_($custom->custom_title).'</label></td>';
					$html.='<td><input type="text" value="'.$value.'" name="'.$this->_product_paramName.'['.$row.']['.$this->_name.']['.$custom_id.'][value]" id="'.$row.'_'.$custom_id.'"/></td>';
					$html.='</tr>';
				}
			}
			$html.='</table>';

			$html.='<input type="hidden" value="'.$row.'" name="'.$this->_product_paramName.'['.$row.'][row]"/>';
			$html.='<input type="hidden" value="'.$child_product_id.'" name="'.$this->_product_paramName.'['.$row.'][child_product_id]" />';

			//print the child product
			if(!empty($child_product)){
				//set price display
				$this->setPriceDisplay($child_product);

				$html.='
				<table class="table table-bordered"  style="width:100%; min-width:450px;">
				<caption>'.JText::_('COM_VIRTUEMART_CUSTOM_PRODUCT_CHILD').'</caption>
				<thead>
				<tr>
				<th width="60%">'.JText::_('COM_VIRTUEMART_PRODUCT_FORM_NAME').'</th>
				<th width="15%">'.JText::_('COM_VIRTUEMART_SKU').'</th>					
				<th width="20%">'.JText::_('COM_VIRTUEMART_PRODUCT_FORM_PRICE_COST').'</th>
				<th style="min-width:50px;"></th>
				</tr>
				</thead>
				<tbody>
				<tr>
				<td>'.$child_product->product_name.'</td>
				<td>'.$child_product->product_sku.'</td>
				<td>'.$child_product->product_price_display.'</td>
				<td>
				<a class="btn" target="_blank" href="'.JRoute::_('index.php?option=com_virtuemart&view=product&task=edit&virtuemart_product_id='.$child_product->virtuemart_product_id).'">'.
				JText::_('JACTION_EDIT').
				'</td>
				</tr>
				</tbody>
				</table>';				
			}
			//no child product. Print a form
			else{
				$html.='
				<table class="table table-bordered" style="width:100%; min-width:450px;">
				<caption>'.JText::_('COM_VIRTUEMART_CUSTOM_PRODUCT_CHILD').'</caption>
				<thead>
				<tr>
				<th width="60%">'.JText::_('COM_VIRTUEMART_PRODUCT_FORM_NAME').'</th>
				<th width="15%">'.JText::_('COM_VIRTUEMART_SKU').'</th>	
				<th>'.JText::_('COM_VIRTUEMART_PRODUCT_FORM_IN_STOCK').'</th>					
				<th width="20%">'.JText::_('COM_VIRTUEMART_PRODUCT_FORM_PRICE_COST').'</th>				
				</tr>
				</thead>
				<tbody>
				<tr>
				<td><input type="text" value="'.$product->product_name.'" name="'.$this->_product_paramName.'['.$row.'][product_name]"/></td>
				<td><input type="text" value="'.$product->product_sku.'" name="'.$this->_product_paramName.'['.$row.'][product_sku]"/></td>
				<td><input type="text" value="'.$product->product_in_stock.'" name="'.$this->_product_paramName.'['.$row.'][product_in_stock]"/></td>
				<td><input type="text" value="" name="'.$this->_product_paramName.'['.$row.'][cost_price]"/></td>				
				</tr>
				</tbody>
				</table>';		
			}
		}

		$retValue=$html;
		return true;
	}

	/**
	 * Proxy function to get a product
	 *
	 * @param int $id
	 *
	 * @return	JTable	 A database object
	 * @since	1.0
	 * @author	Sakis Terz
	 */
	function getProduct($id){
		$productModel=VmModel::getModel('Product');
		$product = $productModel->getProduct ($id, false, false, false);
		return $product;
	}

	/**
	 * Store the custom fields for a specific product
	 *
	 * @param 	array 	$data
	 * @param 	array 	$plugin_param
	 *
	 * @todo	If there is child id, check if it exists. Maybe the user has deleted that. If that's the case remove the values of the custom fields for that child
	 *
	 * @since	1.0
	 */
	function plgVmOnStoreProduct($data,$plugin_param){
		$plugin_name=key($plugin_param);
		if($plugin_name!= $this->_name)return;
		$is_stockablecustomfield=false;


		if($plugin_name=='stockablecustomfields'){
			$is_stockablecustomfield=true;
			if(!$this->isValidInput($plugin_param['stockablecustomfields']))return false;

			$row=$plugin_param['row'];

			$product_id=(int)$data['virtuemart_product_id'];
			$custom_id=$data['field'][$row]['virtuemart_custom_id'];

			//do not store on child products
			$product=$this->getProduct($product_id);
			if($product->product_parent_id>0)return false;

			$virtuemart_customfield_id=$data['field'][$row]['virtuemart_customfield_id'];
			//new record without customfield id
			if(empty($virtuemart_customfield_id)){
				/*
				 * Get it from the db.
				 * This will not be 100% accurate if the same custom is assigned to the child product more than once
				 */
				$virtuemart_customfield_ids=CustomfieldStockablecustomfields::getCustomfields($product_id);

				//We need the numerical index of the customfield to find it's order. The $row is not reliable for that as it does not decrease when we delete a custom field
				$index=array_search($row, array_keys($data['field']));

				if($virtuemart_customfield_ids[$index]->virtuemart_custom_id==$custom_id){

					$virtuemart_customfield_id=$virtuemart_customfield_ids[$index]->virtuemart_customfield_id;
				}
			}


			$child_product_id=$plugin_param['child_product_id'];
			if(empty($child_product_id)){
				$child_product_id=$this->createChildProduct($data,$plugin_param);
				vmdebug('Stocakbles - $child_product_id:',$child_product_id);
				//could not create child
				if(empty($child_product_id)){
					return false;
				}

				//update the params in the customfield
				$upated=CustomfieldStockablecustomfields::updateCustomfield($virtuemart_customfield_id,'customfield_params',$value='custom_id=""|child_product_id="'.$child_product_id.'"|');
				vmdebug('Stockables - Master Product\'s custom field\'s '.$virtuemart_customfield_id.'  params update status:',$upated);
			}

			//we have child product. Let's give it custom fields or update the existings
			if(!empty($child_product_id)){
				//store the custom fields to the child product
				$result=CustomfieldStockablecustomfields::storeCustomFields($child_product_id,$plugin_param['stockablecustomfields']);
			}
		}
		return $result;
	}

	/**
	 * Check if the user has filled in all the inputs for the custom fields
	 *
	 * @param 	array $input
	 *
	 * @return	boolean
	 * @since	1.0
	 */
	function isValidInput($input){
		foreach ($input as $custom_id=>$inp){
			$value= JString::trim($inp['value']);
			if(empty($value))return false;
		}
		return true;
	}


	/**
	 *
	 * Creates a child product from the main product
	 *
	 * @param 	array 	$data	All the data of the product form
	 * @param 	array	$plugin_param	The data/params of the plugin
	 *
	 * @return	int		The new created product id
	 *
	 * @since	1.0
	 * @author	Sakis Terz
	 */
	function createChildProduct($data,$plugin_param){
		//we do not want to store in child products
		if($data['product_parent_id']>0)return;
		vmdebug('STOCKABLE Parent id ',$data['virtuemart_product_id']);
		//set the parent product and reset the product id
		$data['product_parent_id']=(int)$data['virtuemart_product_id'];
		$data['virtuemart_product_id']=0;
		//$data['isChild']=true;

		if(!empty($plugin_param['product_name']))$data['product_name']=$plugin_param['product_name'];
		if(!empty($plugin_param['product_sku']))$data['product_sku']=$plugin_param['product_sku'];
		if(!empty($plugin_param['product_in_stock']))$data['product_in_stock']=$plugin_param['product_in_stock'];
		if(!empty($plugin_param['cost_price'])){
			$data['mprices']['product_price']=array();
			$data['mprices']['product_price'][0]=$plugin_param['cost_price'];

			$data['mprices']['virtuemart_product_price_id']=array();
			$data['mprices']['virtuemart_product_price_id'][0]=0;
		}
		//vmdebug('Stockable mprices',$data['mprices']);
		//vmdebug('STOCKABLE PRICES',$data['mprices']);
		/*
		 * unset categories and manufacturers
		 * If child products have categories they are displayed in the category pages
		 */
		$data['virtuemart_manufacturer_id']=array();
		$data['categories']=array();

		//call the products model to create a child product
		$productModel=VmModel::getModel('Product');
		$productTable = $productModel->getTable ('products');
		//set a new slug
		$productTable->checkCreateUnique('#__virtuemart_products_' . VmConfig::$vmlang,'slug');
		$data->slug=$productTable->slug;
		$new_product_id=$productModel->store($data);

		return $new_product_id;
	}


	/**
	 * Sets the price display for a product
	 *
	 * @param 	JTable	A database object $product
	 *
	 * @since	1.0
	 * @author	Sakis Terz
	 */
	function setPriceDisplay(&$product){
		$product->product_price_display='';
		if(empty($product->allPrices[$product->selectedPrice]['product_price']))return;
		$vendor_model = VmModel::getModel('vendor');
		$vendor_model->setId($product->virtuemart_vendor_id);
		$vendor = $vendor_model->getVendor();
		$vendor_model = VmModel::getModel('vendor');
		$currencyDisplay = CurrencyDisplay::getInstance($vendor->vendor_currency,$vendor->virtuemart_vendor_id);
		$product->product_price_display = $currencyDisplay->priceDisplay($product->allPrices[$product->selectedPrice]['product_price'],(int)$product->allPrices[$product->selectedPrice]['product_currency'],1,true);
	}


	/**
	 * Display of the Cart Variant/Non cart variants Custom fields - VM3
	 *
	 * @param 	object $product
	 * @param 	object $group
	 * @todo	Check the child products against stock if this is dictated by the VM config
	 *
	 * @since	1.0
	 */
	function plgVmOnDisplayProductFEVM3(&$product,&$group){
		if ($group->custom_element != $this->_name) return '';
		$group->show_title=false;
		//display only in product details
		if(JFactory::getApplication()->input->get('view')!='productdetails'){
			$group->display='';
			$product->orderable=false;
			return false;
		} 		
		$html='';
		//we want this function to run only once. Not for every customfield record of this type
		static $printed=false;
		if($printed==true)return;
		$printed=true;

		$stockable_customfields=array();
		$custom_id=$group->virtuemart_custom_id;
		$customfield=CustomfieldStockablecustomfields::getInstance($custom_id);
		$custom_params=$customfield->getCustomfieldParams($custom_id);


		if(empty($group->pb_group_id))$group->pb_group_id='';
		$group->custom_params=$custom_params;
		//the customs that consists the stockable
		$custom_ids=$custom_params['custom_id'];
		$layout='default';

		//this is the parent
		if($product->product_parent_id==0){
			$product_parent_id=$product->virtuemart_product_id;
			if(empty($custom_params['parentOrderable']))$product->orderable=false;
		}
		else $product_parent_id=$product->product_parent_id;

		/*
		 * we need to get the stockable cuctomfields of the parent, to load the child product ids
		 * We use them to load their custom fields and for the correct order of display of the custom fields
		 */
		$parent_customfields=CustomfieldStockablecustomfields::getCustomfields($product_parent_id,$custom_id);
		$child_product_ids=array();
		foreach ($parent_customfields as $pc){
			$customfield_params=explode('|', $pc->customfield_params);
			foreach ($customfield_params as $cparam){
				$item=explode('=', $cparam);
				if($item[0]=='child_product_id')$child_product_ids[]=json_decode($item[1]);
			}
		}
		$viewdata=$group;
		$viewdata->product=$product;

		if(!empty($custom_ids) && !empty($child_product_ids)){
			$child_product_ids=CustomfieldStockablecustomfields::getOrderableProducts($child_product_ids);
			//wraps all the html generated
			$html.='<div class="stockablecustomfields_fields_wrapper">';
			
			foreach ($custom_ids as $cust_id){
				$custom=CustomfieldStockablecustomfields::getCustom($cust_id);
				$viewdata->custom=$custom;

				if($custom->field_type!='E'){
					//get it from the built in function
					$stockable_customfields_tmp=CustomfieldStockablecustomfields::getCustomfields($child_product_ids,$cust_id);
					$stockable_customfields_display=array();
					if(!empty($stockable_customfields_tmp)){
						//filter to remove duplicates
						$stockable_customfields_display=CustomfieldStockablecustomfields::filterUniqueValues($stockable_customfields_tmp);						
					}					
					$stockable_customfields_display=CustomfieldStockablecustomfields::setSelected($stockable_customfields_display,$product);
					$viewdata->options=$stockable_customfields_display;
					//cart input
					if($group->is_input)$html.= $this->renderByLayout($layout,$viewdata);
				}
				if(!empty($stockable_customfields_tmp))$stockable_customfields=array_merge($stockable_customfields,$stockable_customfields_tmp);
			}
			$html.='</div>';
			
			//print the scripts for the fe
			if(!empty($stockable_customfields)){
				$customfield_product_combinations=CustomfieldStockablecustomfields::getProductCombinations($stockable_customfields);				
				$doc=JFactory::getDocument();
				//generate the array based on which, it will load the chilc products getting into account the selected fields
				$script='var stockableCustomFieldsCombinations=\''.json_encode($customfield_product_combinations).'\';';
				$childproduct_urls=$this->getProductUrls($child_product_ids,$product->virtuemart_category_id);
				$script.='var stockableCustomFieldsProductUrl=\''.json_encode($childproduct_urls).'\';';
				$doc->addScriptDeclaration($script);
				$doc->addScript(JUri::root().'plugins/vmcustom/stockablecustomfields/assets/js/stockables_fe.js');
				//Adds a string as script to the end of your document 
				$script2="var currentProductid=$product->virtuemart_product_id; var stockableAreas=jQuery('.stockablecustomfields_fields_wrapper'); Stockablecustomfields.setEvents(stockableAreas);";
				vmJsApi::addJScript ( 'addStockableEvents', $script2);	
			}
		}

		$group->display = $html;
		return true;
	}

	/**
	 * Generate urls for a set of products
	 *
	 * @param array $product_ids
	 * @param int $category_id
	 *
	 * @return	array
	 * @since	1.0
	 */
	function getProductUrls($product_ids, $category_id){
		$product_urls=array();
		foreach ($product_ids as $pid){
			$product_urls[$pid]=JRoute::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_category_id='.(int)$category_id.'&virtuemart_product_id='.(int)$pid);
		}
		return $product_urls;
	}
}