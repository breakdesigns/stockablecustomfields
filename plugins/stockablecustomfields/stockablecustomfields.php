<?php
/**
 * @package stockablecustomfield
 * @copyright Copyright (C) 2014-2021 breakdesigns.net . All rights reserved.
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

if (!defined('_JEXEC')) die;

require_once __DIR__.DIRECTORY_SEPARATOR.'bootstrap.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\String\StringHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Multilanguage;


/**
 * Class plgVmCustomStockablecustomfields
 */
class plgVmCustomStockablecustomfields extends vmCustomPlugin
{
    /**
     * Stores the parent product's custom field id, in case it is used as variation
     *
     * @var int
     * @since 1.5.1
     */
    protected static $parentProductCustomfieldId;

    /**
     * Constructor class of the custom field
     *
     * plgVmCustomStockablecustomfields constructor.
     * @param $subject
     * @param $config
     * @throws Exception
     * @since 1.0
     */
    public function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);

        $varsToPush = array(
            'parentOrderable' => array(0, 'int'),
            'custom_id' => array('', 'array'),
            'outofstockcombinations' => array('enabled', 'string'),
            'child_product_id' => array(0, 'int'),
        );
        $release = \VmConfig::getInstalledVersion();
        if (!defined('VM_RELEASE')) {
            define('VM_RELEASE', $release);
        }
        if (!defined('VM_VERSION')) {
            define('VM_VERSION', '3.0');
        }
        $this->setConfigParameterable('customfield_params', $varsToPush);
        $this->_product_paramName = 'customfield_params';
        $this->_init();
    }

    /**
     * Runs on the initialization of the plugin
     *
     * @throws Exception
     * @since 1.5.1
     */
    protected function _init()
    {
        if (Factory::getApplication()->isClient('administrator')) {
            HTMLHelper::_('behavior.framework', true);
            HTMLHelper::_('behavior.modal');
        }
    }

    /**
     * Declares the Parameters of a plugin
     *
     * @param array $data
     * @return bool
     * @since 1.0
     */
    public function plgVmDeclarePluginParamsCustomVM3(&$data)
    {
        return $this->declarePluginParams('custom', $data);
    }

    /**
     * @param $psType
     * @param $name
     * @param $id
     * @param $xParams
     * @param $varsToPush
     * @return bool
     * @since 1.0
     */
	public function plgVmGetTablePluginParams($psType, $name, $id, &$xParams, &$varsToPush)
	{
		return $this->getTablePluginParams($psType, $name, $id, $xParams, $varsToPush);
	}

	/**
	 * Exec when a cf is created/updated (stored) - Customfield view
	 *
	 * @param string $psType
	 * @param array  $data All the data of that cf
     * @since 1.0
	 */
	public function plgVmOnStoreInstallPluginTable($psType,$data)
	{
		\vmdebug('data:',$data);
	}

    /**
     * Displays the custom field in the product view of the backend
     * The custom field should not be displayed before the product being saved. Also should not loaded in the child products
     *
     * @param \stdClass $field
     * @param int $product_id
     * @param int $row
     * @param string $retValue
     * @return bool
     * @throws Exception
     * @since 1.0
     */
    public function plgVmOnProductEdit($field, $product_id, &$row, &$retValue)
    {
        if ($field->custom_element != $this->_name) {
            return false;
        }
        $derived_product = new \stdClass();
        $derived_product->virtuemart_product_id = 0;

        //If the product is not saved do not proceed
        if (empty($product_id)) {
            $retValue = '
<div style="clear:both;" class="alert alert-info">
<span class="icon-info"></span>
<span>' . Text::_('PLG_STOCKABLECUSTOMFIELDS_PLEASE_SAVE_PRODUCT') . '</span>
</div>';
            return false;
        }
        $product = $this->getProduct($product_id);

        //do not display in child products
        if ($product->product_parent_id > 0) {
            $retValue = '
<div style="clear:both;" class="alert alert-info">
<span class="icon-info"></span>
<span>' . Text::_('PLG_STOCKABLECUSTOMFIELDS_PLUGIN_ASSIGNED') . '</span>
</div>';
            return false;
        }

        //check if there is a child product derived from this customfield
        $derived_product_id = 0;
        if (!empty($field->child_product_id)) {
            if ($field->child_product_id != $product_id) {
                $derived_product = $this->getProduct($field->child_product_id);
                if (!empty($derived_product)) {
                    $derived_product_id = $derived_product->virtuemart_product_id;
                }
            } else {
                $derived_product->virtuemart_product_id = $product_id;
                $derived_product_id = $product_id;
            }
        }

        $html = '';
        $parent_custom_id = $field->virtuemart_custom_id;
        $customfield = CustomfieldStockablecustomfield::getInstance($parent_custom_id);
        $custom_params = $customfield->getCustomfieldParams();
        //the customs that consists the stockable
        $custom_ids = $custom_params['custom_id'];

        if (!empty($custom_ids) && is_array($custom_ids)) {

            //print the variation markup
            $html .= '<h4>' . Text::_('PLG_STOCKABLECUSTOMFIELDS_VARIATION') . '</h4>';

            //get the markup that prints the variations
            $html .= $this->getVariationMarkup($row, $custom_ids, $derived_product);
            $html .= '<input type="hidden" value="' . $row . '" name="' . $this->_product_paramName . '[' . $row . '][row]"/>';
            $html .= '<input type="hidden" id="derived_product_id' . $row . '" value="' . $derived_product_id . '" name="' . $this->_product_paramName . '[' . $row . '][child_product_id]" />';

            //print the child product
            if (!empty($derived_product->virtuemart_product_id)) {

                //if the parent product is orderable, it can be variant as well
                if ($field->child_product_id == $product_id) {
                    $parent_derived = true;
                    $html .= '<div style="clear:both;" class="alert alert-info"><span class="icon-info"></span><span>' . Text::_('PLG_STOCKABLECUSTOMFIELDS_USE_PARENT') . '</span></div>';
                    $html .= '<script>parent_derived=true;</script>';
                } else {
                    //set price display
                    $this->setPriceDisplay($derived_product);

                    //existing derived
                    $html .= '<h4>' . Text::_('PLG_STOCKABLECUSTOMFIELDS_DERIVED_PRODUCT') . '</h4>';

                    //get the markup for existing derived products
                    $html .= $this->getDerivedProductMarkup($row, $derived_product);

                }
            }

			/* No derived product - parent product orderable*/
			else{
			    $html.='<h4>'.Text::_('PLG_STOCKABLECUSTOMFIELDS_DERIVED_PRODUCT').'</h4>';

				//if the parent product is orderable, it can be a variant as well
				if($custom_params['parentOrderable']){
					$html.='
					<div class="controls" id="parent_derived_wrapper'.$row.'">
					   <input type="checkbox" id="use_parent_'.$row.'" onclick="if(jQuery(this).attr(\'checked\')==\'checked\' && (typeof parent_derived==\'undefined\' || parent_derived==false)){jQuery(\'#derived_product_wrapper_'.$row.'\').hide(); parent_derived=true;} else{jQuery(\'#derived_product_wrapper_'.$row.'\').show(); parent_derived=false;}"
					   name="'.$this->_product_paramName.'['.$row.'][parent_product_as_derived]" value="1"/>
					   <label for="use_parent_'.$row.'">'.Text::_('PLG_STOCKABLECUSTOMFIELDS_USE_PARENT').'</label>
					</div>';
					$html.='<script>if(typeof parent_derived!="undefined" && parent_derived==true)jQuery("#parent_derived_wrapper'.$row.'").hide();</script>';
				}

				//Print a form to create
				$html.=$this->getDerivedProductFormMarkup($row, $product, $field);

			}

    		//add toolbar at the end (adding a new variation)
    		$html.='
    		    <div class="btn-toolbar">
        		    <a class="btn stcoakbles_add_customfield" id="stcoakbles_add_customfield'.$row.'" onclick="return false;">
        		      <i class="icon-plus"></i><span>'.Text::_('PLG_STOCKABLECUSTOMFIELDS_NEW_VARIATION').'</span>
        		    </a>
    		    </div>';
    		$html.='
    		<script type="text/javascript">
    		    //hide all but the last
    		    jQuery(\'.stcoakbles_add_customfield\').hide();
    		    jQuery(\'#stcoakbles_add_customfield'.$row.'\').show();
        		jQuery(\'#stcoakbles_add_customfield'.$row.'\').on(\'click\',function(){
    		        if(typeof nextCustom !=\'undefined\') {
    		              var counter=nextCustom;
    		              nextCustom++
		              }
    		        else {
                          var counter=Virtuemart.nextCustom;
    		              Virtuemart.nextCustom++;
		              }
    		       	jQuery.getJSON(\''.Uri::root(false).'administrator/index.php?option=com_virtuemart&view=product&task=getData&format=json&virtuemart_product_id='.$product_id.'&type=fields&id='.$parent_custom_id.'&row=\'+counter,
            		function(data) {
            			jQuery.each(data.value, function(index, value){
            				jQuery(\'#custom_field\').append(value);
            				jQuery(\'#custom_field\').trigger(\'sortupdate\');
            			});
            		});
        		 });
    		 </script>';
		}
		$retValue=$html;
		return true;
	}

    /**
     * Generates and return the markup related with the variation
     *
     * @param int $row
     * @param array $custom_ids
     * @param mixed $derived_product
     * @return string
     * @throws Exception
     * @since  1.4.1
     */
    public function getVariationMarkup($row, $custom_ids, $derived_product)
    {
        $html = '<table class="table">';
        $i = 0;
        foreach ($custom_ids as $custom_id) {
            $subcustomfield = false;
            $custom = CustomfieldStockablecustomfield::getCustom($custom_id);
            if (!empty($derived_product->virtuemart_product_id)) {
                //get the other fields
                $subcustomfields = CustomfieldStockablecustomfield::getCustomfields($derived_product->virtuemart_product_id, $custom_id, $limit = false, 'disabler', '=', 0);
                $subcustomfield = reset($subcustomfields);
            } else {
                $subcustomfield = $custom;
                $subcustomfield->virtuemart_customfield_id = 0;
            }
            $html .= '<tr>';
            $html .= '<td><label for="' . $row . '_' . $custom_id . '" class="">' . Text::_($custom->custom_title) . '</label></td>';

            //native types
            if ($custom->field_type != 'E') {
                $value = '';
                !empty($subcustomfield->customfield_value) ? $value = $subcustomfield->customfield_value : $value = '';
                $html .= '<td><input type="text" value="' . $value . '" name="' . $this->_product_paramName . '[' . $row . '][' . $this->_name . '][' . $custom_id . '][value]" id="' . $row . '_' . $custom_id . '"/></td>';

            } //plugins
            else {
                $input_pefix = '[' . $this->_name . '][' . $custom_id . ']';
                $inner_row = $row;
                $retValue = '';
                PluginHelper::importPlugin('vmcustom');
                Factory::getApplication()->triggerEvent('plgVmOnStockableDisplayBE', array($subcustomfield, $derived_product->virtuemart_product_id, &$inner_row, &$retValue, $input_pefix));
                if (!empty($retValue)) $html .= '<td>' . $retValue . '</td>';
            }
            $html .= '</tr>';
            $i++;
        }
        $html .= '</table>';
        return $html;
    }

    /**
     * Generates and return the markup related with the existing child products
     *
     * @param Table $derived_product
     * @return string
     * @since  1.4.1
     */
	public function getDerivedProductMarkup($row,$derived_product)
	{
	    $html='
	        <table class="table table-bordered" style="width:100%; min-width:450px;">
	           <tr>
	               <td style="width:90px; height:100px; background:#ffffff;">';
    		        //image
    		        if(!empty($derived_product->images[0]))$html.=$derived_product->images[0]->displayMediaThumb('class="vm_mini_image"',false );
    		        else $html.=$this->getImageLoaderMarkup($row);

			        $html.=
			        '</td>
			        <td>
				        <table>
    				        <thead>
        				        <tr>
        				        <th width="60%">'.Text::_('COM_VIRTUEMART_PRODUCT_FORM_NAME').'</th>
        				        <th width="15%">'.Text::_('COM_VIRTUEMART_SKU').'</th>
        				        <th width="20%">'.Text::_('COM_VIRTUEMART_PRODUCT_FORM_PRICE_COST').'</th>
        				        <th style="min-width:50px;"></th>
        				        </tr>
    				        </thead>
    				        <tbody>
        				        <tr>
            				        <td>'.$derived_product->product_name.'</td>
            				        <td>'.$derived_product->product_sku.'</td>
            				        <td>'.$derived_product->product_price_display.'</td>
            				        <td>
            				        <a class="btn" target="_blank" href="'.Route::_('index.php?option=com_virtuemart&view=product&task=edit&virtuemart_product_id='.$derived_product->virtuemart_product_id).'">'.
                    					Text::_('JACTION_EDIT').
                    				'</td>
                				</tr>
            				</tbody>
        				</table>
        	        </td>
               </tr>
           </table>';
		return $html;
	}

    /**
     * Generates and return the markup for creating new products
     *
     * @param int $row
     * @param Table $product
     * @param Table $field
     * @return string
     * @since  1.4.1
     */
	public function getDerivedProductFormMarkup($row, $product, $field)
	{
	    $loadProductsURL = 'index.php?option=com_virtuemart&view=product&custom_id='.$field->virtuemart_custom_id.'&row='.$row.'&product_id='.$product->virtuemart_product_id.'&layout=simple2&tmpl=component&function=jSelectProduct';

	    $html='
                <div id="derived_product_wrapper_'.$row.'">
                    <div class="btn-toolbar">
                       <div class="btn-group">
                           <a class="btn btn-success" id="stcoakbles_createnew_'.$row.'" href="#" onclick="return false;"><i class="icon-plus"></i><span>'.Text::_('PLG_STOCKABLECUSTOMFIELDS_CREATE_NEW').'</span></a>
                           <a class="btn modal" id="stcoakbles_loadproduct_'.$row.'"
                            href="'.$loadProductsURL.'"
                                rel="{handler: \'iframe\', size: {x: 850, y: 550}}"
                            ><i class="icon-briefcase"></i><span>'.Text::_('PLG_STOCKABLECUSTOMFIELDS_SELECT_EXISTING').'</span></a>
                       </div>
                    </div>';

	    $html.='<script type="text/javascript">
    				    if(typeof SqueezeBox.initialize =="function") {
    				        SqueezeBox.initialize({});
    					    SqueezeBox.assign($$(\'a.modal\'), {
    						parse: \'rel\'
    					    });
    					}

    					// Load existing
    				    jQuery(\'#stcoakbles_loadproduct_'.$row.'\').on("click",function(e){
    				        e.preventDefault();
    				        if(typeof SqueezeBox.initialize !="function") {
    				            alert("You need to save the product with a stockable variation, to use that feature");
    				            return false;
    				        }
    			             jQuery(\'#derived_product_new'.$row.'\').hide();
    			             jQuery(\'#derived_product_existing'.$row.'\').show();
    			             jQuery(\'#derived_product_image_loader'.$row.'\').hide();
    			             jQuery(\'#stcoakbles_createnew_'.$row.'\').removeClass("btn-success");
    			             jQuery(this).addClass("btn-success");
    			        });
                        
                        // Create new
    			        jQuery(\'#stcoakbles_createnew_'.$row.'\').on("click",function(){
    			             jQuery(\'#derived_product_new'.$row.'\').show();
    			             jQuery(\'#derived_product_existing'.$row.'\').hide();
    			             jQuery(\'#derived_product_image_loader'.$row.'\').show();
    			             jQuery(\'#stcoakbles_loadproduct_'.$row.'\').removeClass("btn-success");
    			             jQuery(this).addClass("btn-success");
    			             jAddElement(\'\',\'\',\'\',0);

    			        })
    				    function jAddElement(productname,productsku, productstock, productprice, productid){
    			             jQuery(\'#derived_product_existing_name'.$row.'\').text(productname);
    			             jQuery(\'#derived_product_existing_sku'.$row.'\').text(productsku);
    			             jQuery(\'#derived_product_existing_stock'.$row.'\').text(productstock);
    			             jQuery(\'#derived_product_existing_price'.$row.'\').text(productprice);
    			             jQuery(\'#derived_product_id'.$row.'\').val(productid);

    			         }
    				    </script>';

	    $html.='
				<table class="table table-bordered" id="derived_product_'.$row.'" style="width:100%; min-width:450px;">
				    <tr>
				        <td id="derived_product_image_loader'.$row.'" style="width:70px; height:85px;">'
	    				            .$this->getImageLoaderMarkup($row).
	    				            '</td>
				         <td>
    				        <table>
                				<thead>
                				<tr>
                    				<th width="50%">'.Text::_('COM_VIRTUEMART_PRODUCT_FORM_NAME').'</th>
                    				<th width="15%">'.Text::_('COM_VIRTUEMART_SKU').'</th>
                    				<th>'.Text::_('COM_VIRTUEMART_PRODUCT_FORM_IN_STOCK').'</th>
                    				<th width="20%">'.Text::_('COM_VIRTUEMART_PRODUCT_FORM_PRICE_COST').'</th>
                    				</tr>
                				</thead>
                				<tbody>
                    				<tr id="derived_product_new'.$row.'">
                        				<td><input type="text" value="'.$product->product_name.'" name="'.$this->_product_paramName.'['.$row.'][product_name]"/></td>
                        				<td><input type="text" value="'.$product->product_sku.'" name="'.$this->_product_paramName.'['.$row.'][product_sku]"/></td>
                        				<td><input type="text" value="'.$product->product_in_stock.'" name="'.$this->_product_paramName.'['.$row.'][product_in_stock]"/></td>
                        				<td><input type="text" value="" name="'.$this->_product_paramName.'['.$row.'][cost_price]"/></td>
                    				</tr>
                        		    <tr id="derived_product_existing'.$row.'" style="display:none;">
                                        <td id="derived_product_existing_name'.$row.'"></td>
                        				<td id="derived_product_existing_sku'.$row.'"></td>
                        		        <td id="derived_product_existing_stock'.$row.'"></td>
                        		        <td id="derived_product_existing_price'.$row.'"></td>
                                    </tr>
                				</tbody>
            				</table>
            	        </td>
        	       </tr>

                </table>
                <input type="hidden" name="'.$this->_product_paramName.'['.$row.'][is_new]" value="1"/>
            </div>';
	    return $html;
	}

    /**
     * Creates the markup that generates the image upload mechanism
     *
     * @param int $row
     * @return string
     * @since  1.4.0
     */
	public function getImageLoaderMarkup($row)
	{
	    $html='
	        <div class="stockable_input-placeholder" style="border:1px solid #bbbbbb; border-radius:4px; width:80px; height:85px; background:url(../plugins/vmcustom/stockablecustomfields/assets/images/image-placeholder.png) no-repeat center 10% #fff">
                <input name="derived_product_img['.$row.']" onchange="jQuery(\'#stockable_input-wrapper_'.$row.'\').css(\'display\',\'block\'); jQuery(\'#stockable_input-info_'.$row.'\').html(jQuery(this).attr(\'value\').split(\'/\').pop());" style="position:absolute; z-index:5; width:80px; height:85px; cursor: pointer;  opacity:0" type="file" />
                <p class="stockable_input-text" style="position:relative; top:55px; font-size:0.7em; text-align:center; line-height:1em;">Click to add an image</p>
                <div id="stockable_input-wrapper_'.$row.'" style="display:none; text-align:center; background:#ffffff; padding:20px 0px; position:relative; top:-15px; border:1px solid #cccccc;">
                    <span id="stockable_input-info_'.$row.'" style=""></span>
                </div>
            </div>';
	    return $html;
	}

    /**
     * Proxy function to get a product
     *
     * @param int $id
     * @return  Table A database Table object
     * @since   1.0
     */
    public function getProduct($id)
    {
        $productModel = \VmModel::getModel('Product');
        $product = $productModel->getProductSingle($id, false, $qty = 1, false);
        if (!empty($product->virtuemart_media_id)) {
            $productModel->addImages($product, $limit = 1);
        }

        return $product;
    }

    /**
     * Store the custom fields for a specific product
     *
     * @param $data
     * @param $plugin_param
     * @return bool
     * @throws Exception
     * @since 1.0
     * @todo If there is child id, check if it exists. Maybe the user has deleted that. If that's the case remove the values of the custom fields for that child
     */
    public function plgVmOnStoreProduct($data, $plugin_param)
    {
        $plugin_name = key($plugin_param);
        $result = false;
        if ($plugin_name != $this->_name) {
            return $result;
        }

        if ($plugin_name == 'stockablecustomfields') {
            if (!$this->isValidInput($plugin_param['stockablecustomfields'])) {
                return false;
            }

            $row = $plugin_param['row'];

            $product_id = (int)$data['virtuemart_product_id'];
            $custom_id = $data['field'][$row]['virtuemart_custom_id'];

            //do not store on child products
            $product = $this->getProduct($product_id);
            if ($product->product_parent_id > 0) {
                return false;
            }

            $virtuemart_customfield_id = $data['field'][$row]['virtuemart_customfield_id'];

            //new record without customfield id
            if (empty($virtuemart_customfield_id)) {
                /*
                 * Get it from the db.
                 * This will not be 100% accurate if the same custom is assigned to the child product more than once
                 */
                $virtuemart_customfield_ids = \CustomfieldStockablecustomfield::getCustomfields($product_id, $cid=0, $limit = 50, 'disabler', '=', 0);

                //We need the numerical index of the customfield to find it's order. The $row is not reliable for that as it does not decrease when we delete a custom field
                $index = array_search($row, array_keys($data['field']));

                if ($virtuemart_customfield_ids[$index]->virtuemart_custom_id == $custom_id) {
                    $virtuemart_customfield_id = $virtuemart_customfield_ids[$index]->virtuemart_customfield_id;
                }
            }

            $derived_product_id = $plugin_param['child_product_id'];

            //if it's a new assignment (product >custom field value)
            $is_new = !empty($plugin_param['is_new']) ? true : false;

            //it is a new product as derived
            if (empty($derived_product_id)) {
                //if we use the parent product as derived, no need to create child products
                if ($plugin_param['parent_product_as_derived']) {
                    $derived_product_id = $product_id;
                }
                else {
                    $derived_product_id = $this->createChildProduct($data, $plugin_param);
                }
                \vmdebug('Stocakbles - $derived_product_id:', $derived_product_id);

                //could not create child
                if (empty($derived_product_id)) {
                    return false;
                }
            }
            //An existing product is selected as derived
            else if ($is_new) {

                //check if the derived is child product of the current parent
                if ($derived_product_id != $product_id) {

                    //update the parent_product_id of the derived
                    $this->_updateproduct($derived_product_id, 'product_parent_id', $product_id);

                }
            }

            // we have child product. Let's give it custom fields or update the existings
            if (!empty($derived_product_id)) {
                if ($is_new) {
                    // update the customfield params of the master product. Set the child id as param
                    $customfield = new \stdClass();
                    $customfield->virtuemart_customfield_id = $virtuemart_customfield_id;
                    $customfield->customfield_params = 'custom_id=""|child_product_id="' . $derived_product_id . '"|';

                    $upated = \CustomfieldStockablecustomfield::updateCustomfield($customfield, 'customfield_params');
                    \vmdebug('Stockables - Master Product\'s custom field\'s ' . $virtuemart_customfield_id . '  params update status:', $upated);
                }

                //store the custom fields to the child product
                $customfield_ids = \CustomfieldStockablecustomfield::storeCustomFields($derived_product_id, $plugin_param['stockablecustomfields']);
                self::$parentProductCustomfieldId = empty(self::$parentProductCustomfieldId) && $derived_product_id == $product_id ? $customfield_ids : self::$parentProductCustomfieldId;


                // Success
                if (count($customfield_ids) > 0) {
                    /*
                     * if successful and not the parent product, assign also a custom field with 'disabler',
                     * to disable the parent custom field from appearing in child products
                     */
                    if($derived_product_id != $product_id && self::$parentProductCustomfieldId) {
                        $childCustomData = [];
                        foreach (self::$parentProductCustomfieldId as $sub_custom_id => $custom_field_id) {
                            $childCustomData[$sub_custom_id] = ['customfield_value' =>'Set As Disabler, to the parent product','disabler' => $custom_field_id, 'virtuemart_product_id' => $derived_product_id, 'custom_id' => $sub_custom_id];
                        }
                        \CustomfieldStockablecustomfield::storeCustomFields($derived_product_id, $childCustomData, true, true);
                    }

                    //check if an image was uploaded
                    $input = Factory::getApplication()->input;
                    $files = $input->files->get('derived_product_img');
                    $file = $files[$row];
                    if (!empty($file)) {
                        $this->_createMediaFile($file, $derived_product_id);
                    }
                }
            }
        }
        return true;
    }

	/**
	 * Check if the user has filled in all the inputs for the custom fields
	 *
	 * @param 	array $input
	 * @return	boolean
	 * @since	1.0
	 */
	public function isValidInput($input)
	{
		foreach ($input as $custom_id=>$inp){
			$value= isset($inp['value']) ? StringHelper::trim($inp['value']) : '';
			if(isset($inp['value']) && empty($value)) {
			    return false;
            }
		}
		return true;
	}

    /**
     *
     * Creates a child product from the main product
     *
     * @param   array $data All the data of the product form
     * @param   array $plugin_param The data/params of the plugin
     * @return  boolean|int The new created product id
     * @since   1.0
     * @author  Sakis Terz
     */
    protected function createChildProduct($data, $plugin_param)
    {
        //we do not want to use existing child products
        if ($data['product_parent_id'] > 0) {
            return false;
        }
        \vmdebug('STOCKABLE Parent id ', $data['virtuemart_product_id']);

        //is child
        $data['isChild'] = true;

        //set the parent product and reset the product id
        $data['product_parent_id'] = (int)$data['virtuemart_product_id'];
        $data['virtuemart_product_id'] = 0;

        //reset custom fields
        $data['field'] = array();

        //reset child products- we do not want the child product to have children
        $data['childs'] = array();

        //reset medias
        $data['active_media_id'] = '';
        $data['virtuemart_media_id'] = array();
        $data['media_published'] = 0;
        $data['mediaordering'] = array();
        $data['file_url'] = '';
        $data['file_title'] = '';
        $data['file_url_thumb'] = '';
        $data['upload'] = '';
        $data['media_action'] = 0;

        if (!empty($plugin_param['product_name'])) {
            $data['product_name'] = $plugin_param['product_name'];
        }
        if (!empty($plugin_param['product_sku'])) {
            $data['product_sku'] = $plugin_param['product_sku'];
        }
        if (!empty($plugin_param['product_in_stock'])) {
            $data['product_in_stock'] = $plugin_param['product_in_stock'];
        }
        else {
            $data['product_in_stock'] = 0;
        }

        //reset prices
        if (!empty($data['mprices']['product_override_price'])) {
            unset($data['mprices']['product_override_price']);
        }
        if (!empty($data['mprices']['override'])) {
            unset($data['mprices']['override']);
        }
        $data['mprices']['product_price'] = array();
        $data['mprices']['product_price'][0] = '';
        $data['mprices']['virtuemart_product_price_id'] = array();
        $data['mprices']['virtuemart_product_price_id'][0] = 0;

        if (!empty($plugin_param['cost_price'])) {
            $data['mprices']['product_price'][0] = $plugin_param['cost_price'];
        }

        /*
         * unset categories and manufacturers
         * If child products have categories they are displayed in the category pages
         */
        $data['virtuemart_manufacturer_id'] = array();
        $data['categories'] = array();

        //call the products model to create a child product
        if (!class_exists('VirtueMartModelProduct')) {
            require VMPATH_ADMIN . DIRECTORY_SEPARATOR . "models" . DIRECTORY_SEPARATOR . "product.php";
        }
        $productModel = new \VirtueMartModelProduct();
        $productTable = $productModel->getTable('products');

        //set a new slug
        $productTable->checkCreateUnique('#__virtuemart_products_' . \VmConfig::$vmlang, 'slug');
        $data['slug'] = $productTable->slug;
        $new_product_id = $productModel->store($data);

        return $new_product_id;
    }

    /**
     * Create a media/image for a specific product
     * @param array $file
     * @param int $product_id The product id
     * @return boolean
     * @since  1.4.0
     */
    protected function _createMediaFile($file, $product_id)
    {
	    jimport('joomla.filesystem.file');

	    //the allowed types of files
	    $allowed_extensions=array('jpg','jpeg','gif','png');
	    $extension = strtolower(JFile::getExt($file['name']));

	    //Tha max allowed file in bytes
	    $max_size=500000000; //~50MB

	    if($file['size']>$max_size){
	        throw new \RuntimeException('File:'.$file['name'].' exceeds the max limit of '.$max_size);
	        return false;
	    }

        // allowed file type and is real file
        if (in_array($extension, $allowed_extensions) && $file['size'] > 0) {
            $file_type = 'product';
            $destination = VmConfig::get('media_product_path');
            $name = JFile::makeSafe($file['name']);
            $img_destination_name = JPATH_SITE . DIRECTORY_SEPARATOR . $destination . $name;
            // upload original image
            $upload = JFile::upload($file['tmp_name'], $img_destination_name);


            //if upload was successfull go on
            if ($upload && file_exists($img_destination_name)) {

                // create thumb
                $thumb_width = \VmConfig::get('img_width', 90);
                $thumb_height = \VmConfig::get('img_height', 90);
                $resized_img_name = $name . '_' . $thumb_width . 'x' . $thumb_height;
                $thumb_destination = $destination . 'resized' . DIRECTORY_SEPARATOR;
                $resized_img_destination_name = $thumb_destination;

                // create the thumb image
                if (! class_exists('Img2Thumb')) {
                    require (VMPATH_ADMIN . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'img2thumb.php');
                }

                $created_thumb_image = new Img2Thumb($img_destination_name, (int) $thumb_width, (int) $thumb_height, $resized_img_destination_name, $maxsize = false, $bgred = 255, $bggreen = 255, $bgblue = 255);

                if($created_thumb_image){
                    $now = new Date();
                    \vmdebug('upload', $upload . ':temp:' . $file['tmp_name'] . '| destin:' . $img_destination_name);

                    // set media vars
                    $data = new \stdClass();
                    $data->virtuemart_vendor_id = 0;
                    $data->file_title = $name;
                    $data->file_description = '';
                    $data->file_meta = '';
                    $data->file_class = '';
                    $data->file_mimetype = $file['file_mimetype'];
                    $data->file_type = $file_type;
                    $data->file_url = $destination . $name;
                    $data->file_url_thumb = '';
                    $data->published = 1;
                    $data->file_is_downloadable = 0;
                    $data->file_is_forSale = 0;
                    $data->file_is_product_image = 0;
                    $data->shared = 0;
                    $data->file_params = 0;
                    $data->file_lang = '';
                    $data->created_on=$now->toSql();

                    Table::addIncludePath(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_virtuemart'.DIRECTORY_SEPARATOR.'tables');
                    //create the media db record
                    $table = Table::getInstance('medias', 'Table');
                    //$table->bind($media);
                    $table->bindChecknStore($data);
                    $media_id=$table->virtuemart_media_id;
                    \vmdebug('media id:',$media_id);

                    //create the record to the product_medias db table
                    if(!empty($media_id)){

                        try{
                            // Create and populate an object.
                           $row=new stdClass();
                           $row->virtuemart_product_id=$product_id;
                           $row->virtuemart_media_id=$media_id;
                           $row->ordering=1;

                           $result = Factory::getDbo()->insertObject('#__virtuemart_product_medias', $row);
                        }
                        catch(\RuntimeException $e){
                            \vmError($e->getMessage());
                            throw $e;
				            $result=false;
                        }
                    }
                    return true;
                }
            }
        }
        return false;
	}

    /**
     * Update the table virtuemart_products witha givev field>value
     *
     * @param $product_id
     * @param $field
     * @param $value
     * @return bool|mixed
     * @throws \RuntimeException
     * @since 1.4.0
     */
    private function _updateproduct($product_id, $field, $value)
    {
        if (empty($product_id) || empty($field) || empty($value)) return false;
        $db = Factory::getDbo();
        $q = $db->getQuery(true);
        $q->update('#__virtuemart_products')->set($db->quoteName($field) . '=' . $db->quote($value))->where('virtuemart_product_id=' . (int)$product_id);
        $db->setQuery($q);
        try {
            $result = $db->execute();
        } catch (\RuntimeException $e) {
            throw $e;
        }
        return $result;
    }

	/**
	 * Sets the price display for a product
	 *
	 * @param 	Table	A database object $product
	 * @since	1.0
	 */
	public function setPriceDisplay(&$product)
	{
		$product->product_price_display='';
		if(empty($product->allPrices[$product->selectedPrice]['product_price']))return;
		$vendor_model = \VmModel::getModel('vendor');
		$vendor_model->setId($product->virtuemart_vendor_id);
		$vendor = $vendor_model->getVendor();
		$vendor_model = \VmModel::getModel('vendor');
		$currencyDisplay = \CurrencyDisplay::getInstance($vendor->vendor_currency,$vendor->virtuemart_vendor_id);
		$product->product_price_display = $currencyDisplay->priceDisplay($product->allPrices[$product->selectedPrice]['product_price'],(int)$product->allPrices[$product->selectedPrice]['product_currency'],1,true);
	}

    /**
     * Checks if the current custom is stockable
     * A bug was found that custom fields(group param) can contain wrong custom_element and the validation is not enough with that
     *
     * @param int $custom_id
     * @return boolean
     * @since    1.4.6
     */
    protected function isStockable($custom_id)
    {
        $customfield = CustomfieldStockablecustomfield::getInstance($custom_id);
        $custom_params = $customfield->getCustomfieldParams();
        if (isset($custom_params['parentOrderable']) && isset($custom_params['outofstockcombinations'])) {
            return true;
        }
        return false;
    }

    /**
     *  Display of the Cart Variant/Non cart variants Custom fields - VM3
     *
     * @param $product
     * @param $group
     * @return bool
     * @throws Exception
     * @since 1.0
     */
    public function plgVmOnDisplayProductFEVM3(&$product, &$group)
    {
        if ($group->custom_element != $this->_name || !$this->isStockable($group->virtuemart_custom_id)) {
            return false;
        }
        $group->show_title = false;
        $input = Factory::getApplication()->input;
        $html = '';

        if ($input->get('option') != 'com_virtuemart' && $input->get('option') != 'com_customfilters' && $input->get('option') != 'com_productbuilder') {
            return false;
        }
        $product->orderable = false;

        // can be added to cart only in product details and category
        if (
            ($input->get('option') == 'com_virtuemart' && ($input->get('view') == 'productdetails' || $input->get('view') == 'category')) ||
            ($input->get('option') == 'com_customfilters' && $input->get('view') == 'products')) {
            $product->orderable = true;
        }

        $context = $input->get('option') . $input->get('view') . $input->get('view') . $input->get('bundled_products', false);

        // we want this function to run only once. Not for every customfield record of this type
        static $printed = false;
        static $printed_inbundle = false;
        static $pb_group_id = '';

        $stockable_customfields = array();
        $custom_id = $group->virtuemart_custom_id;
        $customfield = CustomfieldStockablecustomfield::getInstance($custom_id);
        $custom_params = $customfield->getCustomfieldParams();
        $custom_params['outofstockcombinations'] = !empty($custom_params['outofstockcombinations']) ? $custom_params['outofstockcombinations'] : 'enabled';

        if (empty($group->pb_group_id)) {
            $group->pb_group_id = '';
        }

        $group->custom_params = $custom_params;
        //the customs that consists the stockable
        $custom_ids = !empty($custom_params['custom_id']) ? $custom_params['custom_id'] : array();
        $layout = 'default';

        // this is the parent
        if ($product->product_parent_id == 0) {
            $product_parent_id = $product->virtuemart_product_id;
            if (empty($custom_params['parentOrderable'])) {
                $product->orderable = false;
            }
        } else {
            $ischild = true;
            $product_parent_id = $product->product_parent_id;
        }

        /*
         * this can cause problem when we call that function in a group with a default product.
         * VM will call that function 1st for the generation of the product, setting the printed var to true
         */
        if ($printed == $context . $product->virtuemart_product_id && $input->get('option') != 'com_productbuilder') {
            return false;
        }
        $printed = $context . $product->virtuemart_product_id;
        $ischild = false;

        /*
         * we need to get the stockable cuctomfields of the parent, to load the child product ids from the params of it's stockable custom fields
         * We use them to load their sub custom fields and the order of display of the sub custom fields
         */
        $derived_product_ids = $customfield->getDerivedProductIds($product_parent_id);

        $viewdata = $group;
        $viewdata->product = $product;
        $viewdata->isderived = false;
        if (!empty($derived_product_ids)) {
            $parent_derived = false;
            if (in_array($product->virtuemart_product_id, $derived_product_ids)) {
                $parent_derived = $product->virtuemart_product_id;
            }
            /*
             * exclude the parent derived from the stock control
             * when we visit the parent product we want that combination there.
             * But disabled by the script
             */
            $derived_products = \CustomfieldStockablecustomfield::getOrderableProducts($derived_product_ids, $custom_params, $exclude = $parent_derived);
            $derived_product_ids = array_keys($derived_products);
        }

        if (!empty($custom_ids) && !empty($derived_product_ids)) {
            if (in_array($product->virtuemart_product_id, $derived_product_ids)) {
                $viewdata->isderived = true;
            }

            //wraps all the html generated
            $html .= '<div class="stockablecustomfields_fields_wrapper" id="' . rand(0, 100) . '">';
            foreach ($custom_ids as $cust_id) {
                $custom = \CustomfieldStockablecustomfield::getCustom($cust_id);
                $viewdata->virtuemart_custom_id = $custom->virtuemart_custom_id;
                $viewdata->custom = $custom;
                $custom->pb_group_id = $group->pb_group_id;

                //wrap each custom field
                $html .= '<div class="customfield_wrapper" id="customfield_wrapper_' . $cust_id . '">';
                if ($custom->field_type != 'E') {
                    //get it from the built in function
                    $stockable_customfields_tmp = CustomfieldStockablecustomfield::getCustomfields($derived_product_ids, $cust_id, $limit = false, 'disabler', '=', 0);;
                    $stockable_customfields_display = array();
                    if (!empty($stockable_customfields_tmp)) {
                        //filter to remove duplicates
                        $stockable_customfields_display = CustomfieldStockablecustomfield::filterUniqueValues($stockable_customfields_tmp);
                    }
                    $viewdata->options = $stockable_customfields_display;
                    //cart input
                    if (!empty($viewdata->options) && $group->is_input) {
                        $html .= '<label>' . Text::_($custom->custom_title) . '</label>';
                        $html .= $this->renderByLayout($layout, $viewdata);
                    }
                } //call plugin for the output
                else {
                    //the customfields
                    $customfields = array();
                    //the html output
                    $output = '';
                    PluginHelper::importPlugin('vmcustom');
                    $result = Factory::getApplication()->triggerEvent('plgVmOnStockableDisplayFE', array($product, $custom, $derived_product_ids, &$customfields, &$output));
                    $stockable_customfields_tmp = $customfields;
                    if ($output && $group->is_input) {
                        $html .= '<label>' . Text::_($custom->custom_title) . '</label>';
                        $html .= $output;
                    }
                }
                if (!empty($stockable_customfields_tmp)) {
                    $stockable_customfields = array_merge($stockable_customfields, $stockable_customfields_tmp);
                }
                $html .= '</div>';
            }
            $html .= '</div>';

            $product_parent_id = !empty($product->product_parent_id) ? $product->product_parent_id : $product->virtuemart_product_id;
            //add a hidden input. This will trigger the plugin also in the cart
            $html .= '<input id="stockable_hidden_' . $product->virtuemart_product_id . '" type="hidden" name="customProductData[' . $product->virtuemart_product_id . '][' . $custom_id . '][' . $group->virtuemart_customfield_id . ']" value="">';
            $html .= '<input type="hidden" name="stockable_current_product_id" value="' . $product->virtuemart_product_id . '">';
            $html .= '<input type="hidden" name="stockable_parent_product_id" value="' . $product_parent_id . '">';

            //print the scripts for the fe
            if (!empty($stockable_customfields)) {
                $customfield_product_combinations = CustomfieldStockablecustomfield::getProductCombinations($stockable_customfields, $derived_products);
                $childproduct_urls = $this->getProductUrls($derived_product_ids, $product->virtuemart_category_id);

                foreach ($customfield_product_combinations->combinations as &$derived_product) {
                    $derived_product['product_url'] = $childproduct_urls[$derived_product['product_id']];
                }

                //generate the array based on which, it will load the chilc products getting into account the selected fields
                $script = 'stockableCustomFieldsCombinations:\'' . json_encode($customfield_product_combinations) . '\',';
                $script .= 'stockableCustomFieldsProductUrl:\'' . json_encode($childproduct_urls) . '\',';
                $script .= 'stockable_out_of_stock_display:\'' . $custom_params['outofstockcombinations'] . '\',';
                $finalScript = "
				    if(typeof StockableObjects=='undefined')StockableObjects=new Array();
				    StockableObjects[" . $product_parent_id . "]={" . $script . "};";
                $html .= '<script>' . $finalScript . '</script>';

                $doc = Factory::getDocument();
                // load it, as in Custom Filters
                $doc->addScript(Uri::root(true) . '/plugins/vmcustom/stockablecustomfields/assets/js/stockables_fe.js');

                $group->stockableCombinations = $customfield_product_combinations;
                $group->stockableCustom_ids = $custom_ids;
                $group->display = $html;
                return true;
            }
        }
        return false;
    }

    /**
     * Generate urls for a set of products
     *
     * @param array $product_ids
     * @param int $category_id
     * @return array
     * @throws Exception
     * @since 1.0
     */
    public function getProductUrls($product_ids, $category_id)
    {
        $product_urls = array();
        $input = Factory::getApplication()->input;
        $url = 'index.php?option=com_virtuemart&view=productdetails&virtuemart_category_id=' . (int)$category_id;
        if(Multilanguage::isEnabled()) {
            $url .= '&lang=' . Factory::getLanguage()->getTag() ;
        }

        foreach ($product_ids as $pid) {

            if ($input->get('view', '') == 'productdetails') {
                $product_urls[$pid] = Route::_($url. '&virtuemart_product_id=' . (int)$pid);
            } else {
                $route = Route::_($url);
                if (strpos($route, '?') === false) {
                    $route .= '?virtuemart_product_id=' . (int)$pid;
                } else {
                    $route .= '&virtuemart_product_id=' . (int)$pid;
                }
                $product_urls[$pid] = $route;
            }
        }
        return $product_urls;
    }

    /**
     * Function triggered on cart display- VM3
     *
     * @param $product
     * @param $productCustom
     * @param $html
     * @return bool
     * @throws Exception
     * @since 1.0
     */
    public function plgVmOnViewCartVM3(&$product, &$productCustom, &$html)
    {
        if (empty($productCustom->custom_element) or $productCustom->custom_element != $this->_name ||
            !isset($product->customProductData[$productCustom->virtuemart_custom_id][$productCustom->virtuemart_customfield_id])) {
            return false;
        }
        $custom_id = $productCustom->virtuemart_custom_id;
        $customfield = \CustomfieldStockablecustomfield::getInstance($custom_id);
        $custom_params = $customfield->getCustomfieldParams();
        $custom_ids = $custom_params['custom_id'];
        $newProductCustoms = \CustomfieldStockablecustomfield::getCustomfields($product->virtuemart_product_id, $custom_ids, $limit = false, 'disabler', '=', 0);

        if (!empty($newProductCustoms)) {
            foreach ($newProductCustoms as $newProductCustom) {
                if ($newProductCustom->field_type != 'E') {
                    $html .= '<span class="product-field-type-S">';
                    $html .= '<span class="product-field-label">' . Text::_($newProductCustom->custom_title) . ': </span>';
                    $html .= '<span class="product-field-value">' . Text::_($newProductCustom->customfield_value) . '</span>';
                    $html .= '</span>';
                    $html .= '<br/>';
                } else {
                    $html .= '<span class="product-field-type-E">';
                    PluginHelper::importPlugin('vmcustom');
                    Factory::getApplication()->triggerEvent('plgVmOnStockableDisplayCart', array(&$product, &$newProductCustom, &$html));
                    $html .= '</span>';
                    $html .= '<br/>';
                }
            }
        }
        return true;
    }

    /**
     * Function triggered on display cart module - VM3
     *
     * @param $product
     * @param $productCustom
     * @param $html
     * @throws Exception
     * @since 1.0
     */
    public function plgVmOnViewCartModuleVM3(&$product, &$productCustom, &$html)
    {
        $this->plgVmOnViewCartVM3($product, $productCustom, $html);
    }

    /**
     * Function triggered on order display BE - VM3
     *
     * @param $product
     * @param $productCustom
     * @param $html
     * @throws Exception
     * @since 1.0
     */
    public function plgVmDisplayInOrderBEVM3(&$product, &$productCustom, &$html)
    {
        $this->plgVmOnViewCartVM3($product, $productCustom, $html, $inline_css = true);
    }

    /**
     * Function triggered on order display FE - VM3
     *
     * @param $product
     * @param $productCustom
     * @param $html
     * @throws Exception
     * @since 1.0
     */
    public function plgVmDisplayInOrderFEVM3(&$product, &$productCustom, &$html)
    {
        $this->plgVmOnViewCartVM3($product, $productCustom, $html);
    }
}
