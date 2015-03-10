/**
 * Copyright 2015 breakdesigns.net
 */
if (typeof Stockablecustomfields === "undefined") {
	var Stockablecustomfields = {
			setEvents : function(stockableAreas) {
                	var combinations=JSON.parse(stockableCustomFieldsCombinations);
		            Stockablecustomfields.combinations=combinations.combinations;
		            Stockablecustomfields.product_urls=JSON.parse(stockableCustomFieldsProductUrl);

				stockableAreas.each(function() {
						var stockArea = jQuery(this);
	                    Stockablecustomfields.setSelected(stockArea);
	
						stockArea.find('input').change(function(){
							Stockablecustomfields.update(stockArea);
						});
						stockArea.find('select').change(function(){
							Stockablecustomfields.update(stockArea);
						});
					});
				},
                setSelected:function(stockArea){
                    var currentCombination=false;

                    //find the current combination
                    jQuery.each(Stockablecustomfields.combinations,function(index1,combination){
                      if(combination.product_id==currentProductid) currentCombination=combination;
                    });

                    if(currentCombination){
                        var customs=stockArea.find( "[name^='customProductData']" );
                        customs=Stockablecustomfields.getActive(customs);
                        if(customs.length>0){
                        	Stockablecustomfields.setSelectedFields(customs,currentCombination);
                           //update by setting incompatible combinations etc
                          Stockablecustomfields.update(stockArea);
                        }
                    }
                },
				update:function(stockArea){
					// get the customfields
					var customs=stockArea.find( "[name^='customProductData']" );
					customs=Stockablecustomfields.getActive(customs); //console.log(customs);
					var countCustoms=customs.length; 
					var emptyCustoms=new Array();
					var currentCombinations=new Array();
					var nomatch=false;					
					num_index=0;
					jQuery.each(customs,function(index,custom){
						var value=jQuery.trim(jQuery(this).val());
						
						if(!value || value=="0"){
							emptyCustoms[num_index]=this;
							//if the 1st is empty enable all and return
							if(num_index==0){
								Stockablecustomfields.enableAll(customs);
								return false;
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
							if(combinationObj.customfield_ids[num_index]==value){
								matchedCombinations.push(combinationObj);
							}							
						});

						if(matchedCombinations.length>0)currentCombinations=matchedCombinations;
						else nomatch=true;
						
						//console.log("index"+num_index," nomatch:"+nomatch," value:"+value);
						
						//show only releveant combinations or load the product
						if(matchedCombinations.length>0){
							//Do not set next compatibles after the last custom
							if(num_index<countCustoms-1)Stockablecustomfields.setNextCompatibles(customs,num_index,matchedCombinations);
							//if last maybe we should load the product
							else{								
								if(nomatch==false && matchedCombinations.length>0)Stockablecustomfields.loadProductPage(matchedCombinations);
							}
						}
						num_index++;
					});					
				},
				getActive:function(customs){
					var obj={};
					var array=[];
					//create an obj with prop the names of the inputs/selects. 1 name -> 1 var
					customs.each(function(index,custom){
						var name=jQuery(this).attr('name');
						//remove the brackets in case of array variable
						name=name.replace(/[\[\]]/g,'');
						
						if(jQuery(this).is('select'))obj[name]=custom;
						else if(jQuery(this).is('input')){													
							if(typeof obj[name]=='undefined')obj[name]=custom;
							else if(jQuery(this).attr('checked'))obj[name]=custom;							
						}
					});
					//convert to array
					jQuery.each(obj,function(index,ob){
						array.push(ob);
					});
					return array;
				},
				
				setNextCompatibles:function(customs,from,current_combinations){ 
					for(var i=from+1; i<customs.length; i++){
						//first disable them all
						if(jQuery(customs[i]).is('input')){
							var input_name=jQuery(customs[i]).attr('name');
							var inputs=jQuery('input[name="'+input_name+'"]');
							inputs.attr('disabled','disabled');
							var type='input';
						}
						if(jQuery(customs[i]).is('select')){ 
							jQuery(customs[i]).find('option').attr('disabled','disabled');
							var type='select';
						}
						
						//check the custom field against the valid combinations and enable the correct combinations
						jQuery.each(current_combinations,function(index, combination){
							if(type=='input'){	
								//in case of inputs we have to iterate all until we find the 1 with the same value
								jQuery.each(inputs,function(x,input){
									//console.log('combination 0:'+combination.customfield_ids[i-1],' combination 1:'+combination.customfield_ids[i]," val:"+jQuery(input).val());
									if(combination.customfield_ids[i]==jQuery(input).val()){										
										jQuery(input).removeAttr('disabled');
										return false;
									}
								});
								
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
				
				setSelectedFields:function(customs,currentCombination){
					var customslength=customs.length;
					jQuery.each(customs,function(index,custom){
   
    						//set selected for the inputs
    						if(jQuery(custom).is('input')){
    							var input_name=jQuery(custom).attr('name');
    							var options=jQuery('input[name="'+input_name+'"]');
    							var type='input';    							
    						}
    						//set selected for the selects
    						if(jQuery(custom).is('select')){ 
    							var options=jQuery(custom).find('option');
    							var type='select';
    						}
    						
							jQuery.each(options,function(x,option){ 
								if(currentCombination.customfield_ids[index]==jQuery(option).val()){
									if(type=='input'){										
										jQuery(option).attr('checked',true);										
									}									
									else if(type=='select'){
										jQuery(option).attr('selected','selected');
										var value=jQuery(option).val();
										jQuery(custom).val(value);
									}
									if(customslength-1==index)return false;
								}
							});
                    });
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
		Stockablecustomfields.setEvents(stockableAreas);
	});
}