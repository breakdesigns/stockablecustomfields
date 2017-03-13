<?php
/**
 * @version		$Id: simple 2014-12-17 18:16 sakis Terz $
 * @package		stockablecustomfields
 * @author		Sakis Terz
 * @copyright	Copyright (C)2017 breakdesigns.net . All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined("_JEXEC")or die();

if (JFactory::getApplication()->isSite()) {
	JSession::checkToken('get') or die(JText::_('JINVALID_TOKEN'));
}
require_once(JPATH_PLUGINS.DIRECTORY_SEPARATOR.'vmcustom'.DIRECTORY_SEPARATOR.'stockablecustomfields'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'customfield.php');

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.framework', true);
JHtml::_('formbehavior.chosen', 'select');
JHTML::_('behavior.modal');


$app = JFactory::getApplication();
$document=JFactory::getDocument();
$lang = JFactory::getLanguage();
$function  = $app->input->getCmd('function', '');
$keyword=$app->input->getString('keyword');

if($function=='jSelectCustom'){	
	$lang->load('plg_vmcustom_stockablecustomfields');
}

//in versions lower to J3 load also the chosen scripts/styles
if(version_compare(JVERSION, '3.0','<')){	
	$document->addStyleSheet(JURI::root().'components/com_virtuemart/assets/css/chosen.css');
	$document->addScript(JURI::root().'components/com_virtuemart/assets/js/chosen.jquery.min.js');
	vmJsApi::chosenDropDowns();
}

if($function=='jSelectCustom'){
	$document->addStyleSheet( JURI::root(true).'/plugins/vmcustom/stockablecustomfields/assets/css/list.css');
	$document->addStyleSheet( JURI::root(true).'/plugins/vmcustom/stockablecustomfields/assets/css/mybootstrap.css');
	$document->addScript( JURI::root(true).'/plugins/vmcustom/stockablecustomfields/assets/js/list.js');
}
$incompatible_customs=array('C','D','T','M','G','A','X','Y','R','Z');
//get the plugins that can be used
$compatible_plugins=CustomfieldStockablecustomfields::getCompatiblePlugins();

$customs = $this->customs->items;
?>

<form action="index.php" method="post" name="adminForm" id="adminForm" class="form-inline">
	<div id="j-main-container">
		<div id="filter-bar" class="btn-toolbar">
			<div class="filter-search btn-group pull-left">
				<label for="filter_search" class="element-invisible cfhide"><?php echo JText::_('JSEARCH_FILTER_LABEL');?></label> 
				<input type="text" name="keyword" id="filter_search" placeholder="<?php echo JText::_('JSEARCH_FILTER');?>" value="<?php echo $this->escape($keyword); ?>" />
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

			<div class="btn-group pull-left">
	
			<?php 
			/*
			 * backwards compatibility VM 3.0.10 and up uses s$this->customsSelect
			 * previous versions use $this->customs->customsSelect
			 */
			if(!empty($this->customsSelect))echo $this->customsSelect;
			else if(!empty($this->customs->customsSelect))echo $this->$this->customs->customsSelect;?>
			</div>
		</div>
		<table class="adminlist table table-striped">
			<thead>
			<tr>
				<th></th>				
				<th width="20%"><?php echo JText::_('COM_VIRTUEMART_TITLE'); ?></th>
				<th width="10%"><?php echo JText::_('COM_VIRTUEMART_CUSTOM_FIELD_TYPE'); ?></th>
				<th width="10%"><?php echo JText::_('COM_VIRTUEMART_CUSTOM_GROUP'); ?></th>				
				<th width="35%"><?php echo JText::_('COM_VIRTUEMART_CUSTOM_FIELD_DESCRIPTION'); ?></th>
				<th><?php echo JText::_('COM_VIRTUEMART_PUBLISHED'); ?></th>				
				<th><?php echo JText::_('COM_VIRTUEMART_CUSTOM_IS_CART_ATTRIBUTE'); ?></th>						
				<th width="2%" style="min-width:8px;"><?php echo $this->sort('virtuemart_custom_id', 'COM_VIRTUEMART_ID')  ?></th>
			</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="10"><?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
			<tbody>
			<?php
			foreach ($customs as $key => $custom) {
				$compatible=true;
				$icon='';
				if(in_array($custom->field_type, $incompatible_customs) || ($custom->field_type=='E' && !in_array($custom->custom_element, $compatible_plugins))){
					$compatible=false;
					$icon='icon-not-ok';
				}
				?>
				<tr class="row<?php echo $key % 2; ?>" id="element_<?php echo $custom->virtuemart_custom_id?>">
					<td><?php 
					if($function=='jSelectCustom' && $compatible):?>
						<button type="button" class="breakdesigns_btn addBtn"
							title="<?php echo JText::_('PLG_STOCKABLECUSTOMFIELDS_ADD_CUSTOM_LABEL')?>"
							onclick="addToList('<?php echo JText::_($custom->custom_title);?>', '<?php echo JText::_($custom->field_type_display);?>', '<?php echo $custom->virtuemart_custom_id;?>');">
							<i class="bdicon-plus"></i>
						</button>
						<button type="button" class="breakdesigns_btn removeBtn"
							title="<?php echo JText::_('PLG_STOCKABLECUSTOMFIELDS_REMOVE_CUSTOM_LABEL')?>"
							onclick="removeFromList('<?php echo $custom->virtuemart_custom_id;?>');">
							<i class="bdicon-cancel"></i>
						</button> <?php 
						elseif(!$compatible && $icon):?>
						<span class="<?php echo $icon?>"></span>
						<?php 
						endif;
						?>
					</td>					
					<td align="left">
						<?php 
						echo JText::_($custom->custom_title);
						?>						
					</td>
					<td align="right"><?php
						echo JText::_($custom->field_type_display);
						?>					
					</td>		
					<td align="left">
						<?php 
						$link = "index.php?view=custom&keyword=".urlencode($keyword)."&custom_parent_id=".$custom->custom_parent_id."&option=com_virtuemart&layout=stockables&tmpl=component&function=".$function;
						$text='';
						if(!empty($custom->custom_parent_title))$text = $lang->hasKey($custom->custom_parent_title) ? JText::_($custom->custom_parent_title) : $custom->custom_parent_title;
						else if(!empty($custom->group_title))$text = $lang->hasKey($custom->group_title) ? JText::_($custom->group_title) : $custom->group_title;
						echo JHtml::_('link', JRoute::_($link,FALSE),$text, array('title' => JText::_('COM_VIRTUEMART_FILTER_BY').' '.htmlentities($text)));
                        ?>
                    </td>                    
					<td align="left"><?php 
						echo JText::_($custom->custom_desc);
						?>
					</td>
					<td align="right">
						<span class="icon-<?php echo $custom->published?'publish':'unpublish';?>"></span>
					</td>								
					<td align="left">						
						<span class="icon-<?php echo $custom->is_cart_attribute?'publish':'unpublish';?>"></span>
					</td>							
					<td align="right">
						<?php echo $custom->virtuemart_custom_id;?>
					</td>
				</tr>
				<?php
			}//foreach
			?>
			</tbody>
		</table>
	</div>	
		<input type="hidden" name="task" value="" /> 
		<input type="hidden" name="function" value="<?php echo $function;?>" /> 
		<input type="hidden" name="option" value="com_virtuemart" /> 
		<input type="hidden" name="boxchecked" value="0" /> 
		<input type="hidden" name="controller" value="custom" /> 
		<input type="hidden" name="layout" value="stockables" /> 
		<input type="hidden" name="tmpl" value="component" /> 
		<input type="hidden" name="view" value="custom" />
		<?php echo JHtml::_('form.token'); ?>
</form>
