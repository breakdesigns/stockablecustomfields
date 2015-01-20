/**
 * Copyright 2015 breakdesigns.net
 */
if (typeof Stockablecustomfields === "undefined") {
	var Stockablecustomfields = {
			setEvents : function(stockableAreas) {
				stockableAreas.each(function() {
					var stockArea = jQuery(this);
					stockArea.find('input').change(function(){
						Stockablecustomfields.loadProduct(stockArea);
					});
					stockArea.find('select').change(function(){
						Stockablecustomfields.update(stockArea);
					});
					});
				},
				update:function(stockArea){	
					// get the customfields
					var customs=stockArea.find( "[name^='customProductData']" );
					var countCustoms=customs.length;
					var emptyCustoms=new Array();
					var currentCombinations=new Array();
					var nomatch=false;
					
					customs.each(function(index,custom){
						var value=jQuery.trim(jQuery(this).val());
						// do not proceed if any of the customs have no
						// selection
						
						if(!value || value=="0"){
							emptyCustoms[index]=this;
							//if the 1st is empty enable all and return
							if(index==0){
								Stockablecustomfields.enableAll(customs);
								return;
							}
							nomatch=true;							
						}
						
						//if there are combinations, use them to check the following customfields
						if(currentCombinations.length>0)var curCombinations=currentCombinations;
						else curCombinations=Stockablecustomfields.combinations;
						
						//store the combination found in current check
						var matchedCombinations=new Array();
						
						jQuery.each(curCombinations,function(index2, combinationObj){ 						
							// found
							if(combinationObj.customfield_ids.indexOf(value)>-1){								
								matchedCombinations.push(combinationObj);								
							}							
						});	
						if(matchedCombinations.length>0)currentCombinations=matchedCombinations;
						else nomatch=true;
						
						//show only releveant combinations or load the product
						if(matchedCombinations.length>0){
							//Do not set compatibles for the last custom
							if(index<countCustoms-1)Stockablecustomfields.setNextCompatibles(customs,index,matchedCombinations);
							//if last maybe we should load the product
							else{								
								if(nomatch==false && matchedCombinations.length>0)Stockablecustomfields.loadProductPage(matchedCombinations);
							}
						}
					});					
				},
				
				setNextCompatibles:function(customs,from,current_combinations, hide=false){					
					
					for(var i=from+1; i<customs.length; i++){
						//first disable them all
						if(jQuery(customs[i]).is('input')){
							jQuery(customs[i]).attr('disabled','disabled');
							var type='input';
						}
						if(jQuery(customs[i]).is('select')){
							jQuery(customs[i]).find('option').attr('disabled','disabled');
							var type='select';
						}
						
						//check the custom field against the valid combinations and enable the correct combinations
						jQuery.each(current_combinations,function(index, combination){
							if(type=='input'){
								if(combination.customfield_ids[i]==jQuery(customs[i]).val())jQuery(customs[i]).removeAttr('disabled');
							}
							else if(type=='select'){
								var options=jQuery(customs[i]).find('option');
								jQuery.each(options,function(){
									var option_value=jQuery(this).val();
									if(combination.customfield_ids[i]==option_value)jQuery(this).removeAttr('disabled');
									//removed disabled also in case of empty options
									if(!option_value || option_value=="0")jQuery(this).removeAttr('disabled');
								});
							}
						});	
						//if disabled and selected, remove selection
						if(type=='input'){
							if((jQuery(customs[i]).attr('checked')=='checked' || jQuery(customs[i]).attr('checked')==true) && (jQuery(customs[i]).attr('disabled')=='disabled' || jQuery(customs[i]).attr('disabled')==true)){
									jQuery(customs[i]).removeAttr('checked');
							}
						}
						else if(type=='select'){
							var selected=jQuery(customs[i]).find('option:selected');
							if(jQuery(selected).attr('disabled')=='disabled' || jQuery(selected).attr('disabled')==true)jQuery(selected).removeAttr('selected');
						}
						
					}
				},
				enableAll:function(customs){
					customs.each(function(){
						if(jQuery(this).is('input'))jQuery(this).removeAttr('disabled');
						if(jQuery(this).is('select'))jQuery(this).find('option').removeAttr('disabled');
					})
				},
				loadProductPage:function(matchedCombinations){					
					var product_id=matchedCombinations[0].product_id;
					//the product is already loaded
					if(currentProductid==product_id)return;
					
					var url=Stockablecustomfields.product_urls[product_id];
					if (typeof Virtuemart !== "undefined"){
						if (typeof Virtuemart.updateContent == 'function') { 
							Virtuemart.updateContent(url); 
						}
						
					}
				},				
			
	};
	
	jQuery.noConflict();
	jQuery(document).ready(function($) {
		var stockableAreas=jQuery('.stockablecustomfields_fields_wrapper');
		var combinations=JSON.parse(stockableCustomFieldsCombinations);
		Stockablecustomfields.combinations=combinations.combinations;
		Stockablecustomfields.product_urls=JSON.parse(stockableCustomFieldsProductUrl);
		//Stockablecustomfields.setEvents(stockableAreas);
	});
}