<?php
/**
 * @version		$Id: simple 2017-05-06 19:16 sakis Terz $
 * @package		stockable custom fields
 * @author		Sakis Terz
 * @copyright	Copyright (C)2014-2017 breakdesigns.net . All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined("_JEXEC")or die();

if (JFactory::getApplication()->isSite()) {
	JSession::checkToken('get') or die(JText::_('JINVALID_TOKEN'));
}
$document = JFactory::getDocument();

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.framework', true);
JHTML::_('behavior.modal');
JHtml::_('formbehavior.chosen', 'select');


$app = JFactory::getApplication();
$listOrder='virtuemart_product_id';
$listDirn='ASC';
if (array_key_exists('filter_order',$this->lists)):
$listOrder=$this->lists['filter_order'];
$listDirn=$this->lists['filter_order_Dir'];
endif;
$function  = $app->input->getCmd('function', '');
$filter_product=$app->input->getString('filter_product');
$product_id=$app->input->getInt('product_id',0);
$custom_id=$app->input->getInt('custom_id',0);
$row=$app->input->getInt('row',0);
$derived_product_ids=array();

if($function=='jSelectProduct'){
    $document->addStyleSheet( JURI::root(true).'/plugins/vmcustom/stockablecustomfields/assets/css/products.css');
    $document->addScript( JURI::root(true).'/plugins/vmcustom/stockablecustomfields/assets/js/products.js');

	$lang = JFactory::getLanguage();
	$lang->load('plg_vmcustom_stockablecustomfields');

	if(!empty($product_id) && !empty($custom_id)){
	    require_once(JPATH_PLUGINS.DIRECTORY_SEPARATOR.'vmcustom'.DIRECTORY_SEPARATOR.'stockablecustomfields'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'customfield.php');
	    $customfield=CustomfieldStockablecustomfields::getInstance($custom_id);
	    $parent_customfields=CustomfieldStockablecustomfields::getCustomfields($product_id,$custom_id);
	    //get the already derived products. These should not be selectable

	    foreach ($parent_customfields as $pc){
	        $customfield_params=explode('|', $pc->customfield_params);
	        foreach ($customfield_params as $cparam){
	            $item=explode('=', $cparam);
	            if($item[0]=='child_product_id')$derived_product_ids[]=json_decode($item[1]);
	        }
	    }
	}


}

?>
<form action="index.php" method="post" name="adminForm" id="adminForm"
	class="form-inline">
	<div id="j-main-container">
		<div id="filter-bar" class="btn-toolbar">
			<div class="filter-search btn-group pull-left">
				<label for="filter_search"><?php echo JText::_('JSEARCH_FILTER_LABEL'); ?>
				</label> <input type="text" name="filter_product" id="filter_search"
					placeholder="<?php echo JText::_('JSEARCH_FILTER');?>"
					value="<?php echo $this->escape($filter_product); ?>" size="30"
					title="<?php echo JText::_('COM_CONTENT_FILTER_SEARCH_DESC'); ?>" />
			</div>
			<div class="btn-group pull-left">
				<button type="submit" class="btn hasTooltip">
				<?php echo version_compare(JVERSION, '3.0','ge')?'<i class="icon-search"></i>':JText::_('JSEARCH_FILTER_SUBMIT');?>
				</button>
				<button type="button" class="btn hasTooltip"
					onclick="document.id('filter_search').value='';this.form.submit();">
					<?php echo version_compare(JVERSION, '3.0','ge')?'<i class="icon-remove"></i>':JText::_('JSEARCH_FILTER_CLEAR');?>
				</button>
			</div>


			<div class="filter-select fltrt btn-group pull-right">
				<select id="virtuemart_category_id" class="vm-chzn-select"
					name="virtuemart_category_id"
					onchange="this.form.submit(); return false;">
					<option value="">
					<?php echo JText::sprintf( 'COM_VIRTUEMART_SELECT' ,  JText::_('COM_VIRTUEMART_CATEGORY')) ; ?>
					</option>
					<?php echo $this->category_tree; ?>
				</select>
				<?php echo JHTML::_('select.genericlist', $this->manufacturers, 'virtuemart_manufacturer_id', 'class="vm-chzn-select" onchange="this.form.submit(); return false;"', 'value', 'text',
				$this->model->virtuemart_manufacturer_id );
				?>
			</div>
		</div>
		<div class="clr"></div>
		<table class="adminlist table table-striped">
			<thead>
				<tr>
					<th></th>
					<th class="title" width="20%"><?php echo JHtml::_('grid.sort', 'COM_VIRTUEMART_PRODUCT_NAME', 'product_name', $listDirn, $listOrder); ?>
					</th>
					<th width="20%"><?php echo JHtml::_('grid.sort',  'COM_VIRTUEMART_PRODUCT_CHILDREN_OF', 'product_parent_id', $listDirn, $listOrder); ?>
					</th>
					<th width="3%"><?php echo JText::_('COM_VIRTUEMART_PRODUCT_PARENT_LIST_CHILDREN'); ?>
					</th>
					<th width="12%"><?php echo JHtml::_('grid.sort',  'COM_VIRTUEMART_PRODUCT_SKU', 'product_sku', $listDirn, $listOrder); ?>
					</th>
					<th width="8%"><?php echo JHtml::_('grid.sort',  'COM_VIRTUEMART_PRODUCT_PRICE_TITLE', 'product_price', $listDirn, $listOrder); ?>
					</th>
					<th><?php echo JText::_('COM_VIRTUEMART_CATEGORY'); ?>
					</th>
					<th width="15%"><?php echo JHtml::_('grid.sort', 'COM_VIRTUEMART_MANUFACTURER_S', 'mf_name', $listDirn, $listOrder); ?>
					</th>

					<th width="2%"><?php echo JHtml::_('grid.sort', 'COM_VIRTUEMART_ID', 'p.virtuemart_product_id', $listDirn, $listOrder); ?>
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="8"><?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
			<tbody>
			<?php
			foreach ($this->productlist as $key => $product) {
				if(isset($product->product_price_display)) {
					$price=$product->product_price_display;
					$price = floatval($price);
				} elseif(!empty($product->prices)) {
					$price=JText::_('COM_VIRTUEMART_MULTIPLE_PRICES');
				} else {
					$price=JText::_('COM_VIRTUEMART_NO_PRICE_SET');
				}
				//get the child products link output
				ob_start();
				VirtuemartViewProduct::displayLinkToChildList($product->virtuemart_product_id , $product->product_name);
				$child_prod = ob_get_contents();
				ob_end_clean();
				$has_children_msg=!empty($child_prod)?JText::_('JYES'):	JText::_('JNO');
				?>
				<tr class="row<?php echo $key % 2; ?>"
					id="element_<?php echo $product->virtuemart_product_id?>">
					<td><?php
					//enable selection only when the function jSelectProduct is passed and the product is either parent or child of the current parent
					if($function=='jSelectProduct' && $product->virtuemart_product_id!=$product_id &&
					    !in_array($product->virtuemart_product_id, $derived_product_ids) &&
					    empty($child_prod) &&
					    (empty($product->product_parent_id) || $product->product_parent_id==$product_id)):?>

						<button type="button" class="breakdesigns_btn productAddBtn"
							title="<?php echo JText::_('PLG_STOCKABLECUSTOMFIELDS_ADD_CUSTOMS_LABEL')?>"
							onclick="addToList('<?php echo $product->product_name;?>', '<?php echo $product->product_sku;?>', '<?php echo $product->product_in_stock?>','<?php echo $price;?>','<?php echo $product->virtuemart_product_id;?>');">
							<i class="icon-plus"></i>
						</button>
						<?php
						else:?>
						<span><i class="icon-lock"></i></span>
						<?php
						endif;
						?>
					</td>
					<td align="left"><?php echo $product->product_name;?></td>
					<td align="left"><?php
					if ($product->product_parent_id  ) {
						VirtuemartViewProduct::displayLinkToParent($product->product_parent_id);
					}?>
					</td>
					<td align="left"><?php echo $has_children_msg;?></td>
					<td align="left"><?php echo $product->product_sku;?></td>
					<td align="right"><?php
					echo $price;
					?>
					</td>
					<td align="left"><?php
					echo $product->categoriesList;?>
					</td>
					<td align="left"><?php echo $product->mf_name;?></td>
					<td align="right"><?php echo $product->virtuemart_product_id;?></td>
				</tr>
				<?php
			}//foreach
			?>
			</tbody>
		</table>
	</div>

	<input type="hidden" name="filter_order"
		value="<?php echo $listOrder?>" /> <input type="hidden"
		name="filter_order_Dir" value="<?php echo $listDirn?>" /> <input
		type="hidden" name="task" value="" /> <input type="hidden"
		name="function" value="<?php echo $function;?>" /> <input
		type="hidden" name="product_id" value="<?php echo $product_id;?>" /> <input
		type="hidden" name="custom_id" value="<?php echo $custom_id;?>" /> <input
		type="hidden" name="row" value="<?php echo $row;?>" /> <input
		type="hidden" name="option" value="com_virtuemart" /> <input
		type="hidden" name="boxchecked" value="0" /> <input type="hidden"
		name="controller" value="product" /> <input type="hidden"
		name="layout" value="simple2" /> <input type="hidden" name="tmpl"
		value="component" /> <input type="hidden" name="view" value="product" />
	<?php echo JHtml::_('form.token'); ?>
</form>
