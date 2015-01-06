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
		if(!empty($child_product))$child_product_id=$field->child_product_id;

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
				if(!empty($field->child_product_id)){
					//get the other fields
					$subcustomfields=CustomfieldStockablecustomfields::getCustomfields($field->child_product_id,$custom_id);
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
			if(!empty($field->child_product_id) && !empty($child_product)){
				//set price display
				$this->setPriceDisplay($child_product);

				$html.='<table class="table table-bordered" width="100%">
				<caption>'.JText::_('COM_VIRTUEMART_CUSTOM_PRODUCT_CHILD').'</caption>
				<thead>
				<tr>
				<th>'.JText::_('COM_VIRTUEMART_PRODUCT_FORM_NAME').'</th>
				<th>'.JText::_('COM_VIRTUEMART_SKU').'</th>					
				<th>'.JText::_('COM_VIRTUEMART_PRODUCT_FORM_PRICE_COST').'</th>
				<th></th>
				</tr>
				</thead>
				<tbody>
				<tr>
				<td>'.$child_product->product_name.'</td>
				<td>'.$child_product->product_sku.'</td>
				<td>'.$child_product->product_price_display.'</td>
				<td>
				<a class="modal btn" role="modal" data-toggle="modal" href="'.JRoute::_('index.php?option=com_virtuemart&view=product&task=edit&tmpl=component&virtuemart_product_id='.$child_product->virtuemart_product_id).'">'.
				JText::_('JACTION_EDIT').
				'</td>
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
			if($product->product_parent_id>0)return;
				
			$virtuemart_customfield_id=$data['field'][$row]['virtuemart_customfield_id'];
			//new record without customfield id
			if(empty($virtuemart_customfield_id)){
				/*
				 * Get it from the db.
				 * This will not be 100% accurate if the same custom is assigned to the child product more than once
				 */
				$virtuemart_customfield_ids=CustomfieldStockablecustomfields::getCustomfields($product_id);
				//vmdebug('Stockables - virtuemart_customfield_ids',$virtuemart_customfield_ids);

				if($virtuemart_customfield_ids[$row]->virtuemart_custom_id==$custom_id){
					$virtuemart_customfield_id=$virtuemart_customfield_ids[$row]->virtuemart_customfield_id;
				}
				//vmdebug('Stockables - customfield_id for custom_id '.$custom_id.' and position '.$position[$custom_id].': ',$customfield_id);
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
		//vmdebug('Stockables - plugin params:', $plugin_param['stockablecustomfields']);
		//set the parent product and reset the product id
		$data['product_parent_id']=(int)$data['virtuemart_product_id'];
		unset($data['virtuemart_product_id']);
		$data['isChild']=true;
		/*
		 * unset categories and manufacturers
		 * If child products have categories they are displaying in the category pages
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

}