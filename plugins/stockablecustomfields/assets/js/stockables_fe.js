/**
 * Copyright 2015-2021 breakdesigns.net
 */
if (typeof Stockablecustomfields === "undefined") {
	var Stockablecustomfields = {

			handleForms : function(forms) {
				var i=0;
				var StockableObj=new Array();
				forms.each(function() {
					var form = jQuery(this);
					var stockArea=form.find('.stockablecustomfields_fields_wrapper');
					//not found go next
					if(stockArea.length==0) {
						return true;
					}
					let stockable = Stockablecustomfields.createStockableObject(form);
					if(typeof stockable == 'undefined') {
						return;
					}
					StockableObj[i]=stockable;

                    StockableObj[i].setSelected();
                    StockableObj[i].handleOutOfStock();

                    //unbind first because we call this function after every update, setting the event multiple times
					stockArea.find('input').unbind('change');
					stockArea.find('input').change(function(){
						Stockablecustomfields.createStockableObject(form).update();
					});

					//unbind first because we call this function after every update, setting the event multiple times
					stockArea.find('select').unbind('change');
					stockArea.find('select').change(function(){
						Stockablecustomfields.createStockableObject(form).update();
					});
					i++;

				});
			},
			setEvents : function() {
					StockabklesStart=true
					var forms=jQuery('form.product');

					//call the function that creates the tooltips
					if (typeof CustomfieldsForAll!='undefined' && typeof CustomfieldsForAll.enableTooltips== "function") {
						CustomfieldsForAll.enableTooltips();
					}

					//call the function that checks the required fields
					if (typeof CustomfieldsForAll!='undefined' && typeof CustomfieldsForAll.handleForms== "function") {
						CustomfieldsForAll.handleForms(forms);
					}

                	if(typeof StockableObjects!='undefined'){
                		Stockablecustomfields.handleForms(forms);
					}
			},
			createStockableObject : function(form) {
				var stockArea=form.find('.stockablecustomfields_fields_wrapper');
				var current_product_id=form.find('input[name="stockable_current_product_id"]').val();
				var parent_product_id=form.find('input[name="stockable_parent_product_id"]').val();

				if(typeof StockableObjects!='undefined' && typeof StockableObjects[parent_product_id]!='undefined'){
					var StockableCombinations=JSON.parse(StockableObjects[parent_product_id].stockableCustomFieldsCombinations);
					var combinations=StockableCombinations.combinations;
					var urls=JSON.parse(StockableObjects[parent_product_id].stockableCustomFieldsProductUrl);
					var stockable_out_of_stock_display=StockableObjects[parent_product_id].stockable_out_of_stock_display;
				}
				else return;
				return new Stockable(stockArea, combinations, urls, current_product_id, stockable_out_of_stock_display);
			},
			//backwards compatibility function for PB 3.0.7
			update:function(stockArea, callback){
				var form=jQuery(stockArea).closest('form');
				Stockablecustomfields.createStockableObject(form).update(callback);
			},
			setSelected:function(stockArea){
				var form=jQuery(stockArea).closest('form');
				Stockablecustomfields.createStockableObject(form).setSelected();
			}
	}

	var Stockable=function(stockArea, combinations, urls, current_product_id, stockable_out_of_stock_display){
		this.stockArea=stockArea;
		this.combinations=combinations;
		this.urls=urls;
		this.current_product_id=current_product_id;
		this.stockable_out_of_stock_display=stockable_out_of_stock_display;
	};

	Stockable.prototype.setSelected=function(){
        var currentCombination=false;
        var currentProductid=this.current_product_id;

        // find the current combination
        jQuery.each(this.combinations, function(index1,combination){
          if(combination.product_id==currentProductid) currentCombination=combination;
        });

        if(currentCombination){
            var customs=jQuery(this.stockArea).find( "[name^='customProductData']" );
            customs_grouped=this.groupByName(customs);

            if(customs.length>0){
            	this.setSelectedFields(customs_grouped, currentCombination);
            	// update by setting incompatible combinations etc
            	this.update();
            }
        }
    },
    /**
     * Disable the out of stock combinations (if it should)
      * @returns
     */
    Stockable.prototype.handleOutOfStock=function(){
            var combinations=this.combinations;
            var stockArea=this.stockArea;

        	if(typeof this.stockable_out_of_stock_display!="undefined" && this.stockable_out_of_stock_display!='enabled'){
        		var outStockCombination=[];
        		//copy by value //js copies variables by reference
        		var tempCombinations=combinations.slice();


        		//find the out of stock combinations
            	jQuery.each(combinations,function(index1,combination){
                    if(parseInt(combination.stock)<=0) {
            			jQuery.each(combination.customfield_ids, function(index2, customfield_id){
            				outStockCombination.push(customfield_id);
            			});
            		}
                  });
            	//copy by value //js copies variables by reference
        		var tempOutStockCombination=outStockCombination.slice();

            	/*
            	 * check again the out of stock custom fields ids versus the other combinations
            	 * Maybe a custom field id used in other combinations and has stock
            	 */
            	jQuery.each(tempCombinations,function(index1,combination){
            		 if(parseInt(combination.stock)>0){
            			 jQuery.each(combination.customfield_ids, function(index2, customfield_id){
                			 jQuery.each(outStockCombination,function(index3,customfield_id2){
                				 if(customfield_id==customfield_id2)tempOutStockCombination.splice(tempOutStockCombination.indexOf(customfield_id2), 1);
                			 });
            			 });
            		 }

            	});

            	//Time to disable the out of stock custom field ids
            	jQuery.each(tempOutStockCombination, function(index1, customfield_id){
    				var element=jQuery(stockArea).find("[value='"+customfield_id+"']");
    				jQuery(element).attr('disabled','disabled');
    			});

        	}
        };
        /**
         * Used both for the stockable custom fields in Virtuemart, in Prod. Builder and the Product Bundles
         */
        Stockable.prototype.update=function(callback){
			// get the customfields
			var customs=jQuery(this.stockArea).find( "[name^='customProductData']" );
			var customs_grouped=this.groupByName(customs);
			var countCustoms=customs_grouped.length;
			var emptyCustoms=new Array();
			var currentCombinations=new Array();
			var nomatch=false;
			var combinations=this.combinations;

			for(num_index=0; num_index<customs_grouped.length; num_index++){
			custom=customs_grouped[num_index];

				//false used when no selection exists (e.g. In radio btns)
				value=custom.value;
				if(!value || value=="0"){
					emptyCustoms[num_index]=this;

					//if the 1st is empty enable all and return
					if(num_index==0){
						this.enableAll(customs);
						return false;
					}
					nomatch=true;
				}

				//if there are combinations, use them to check the following customfields
				if(currentCombinations.length>0)var curCombinations=currentCombinations;
				else curCombinations=combinations;

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

				//show only releveant combinations or load the product
				if(matchedCombinations.length>0){
					//Do not set next compatibles after the last custom
					if(num_index<countCustoms-1){ console.log('num:',num_index, 'countCustoms:',countCustoms);
						//set next compatibles. In some cases (selects/dropdowns) when we deselect an option the browser auto-selects the 1st enabled
						var reupdate=this.setNextCompatibles(customs_grouped, num_index, matchedCombinations);

						//automatically select an option when is the only enabled
						if(reupdate==false && num_index==countCustoms-2)reupdate=this.setSelection(customs_grouped, num_index);

						if(reupdate)this.update(callback);
					}
					//if last maybe we should load the product
					else{
						if(nomatch==false && matchedCombinations.length>0){
							if (callback && typeof(callback) === "function") {
								callback(matchedCombinations,this.stockArea);
							}
							else this.loadProductPage(matchedCombinations);
						}
					}
				}
			};
		};
		/**
		 * Selects an option if is the only 1 enabled
		 */
		Stockable.prototype.setSelection=function(customs, from){
			var enabled=new Array();
			var checked = false;
			//start with the custom fields below that
			for(var i=from+1; i<customs.length; i++){
				if(customs[i].type=='input'){
					jQuery.each(customs[i].options,function(x,input){
						if(!jQuery(input).attr('disabled'))enabled.push(input);
						if(jQuery(input).attr('checked'))checked =true;
					});
				}
			}
			//only 1 enabled. Select it

			if(enabled.length && checked==false){
				var inp=enabled.shift();
				if(!jQuery(inp).attr('checked')){
					jQuery(inp).attr('checked','checked');
					return true;
				}
			}
			return false;
		};
		/**
		 * Groups the custom fields based on their name and returns groups.
		 */
		Stockable.prototype.groupByName=function(customs){
			var obj={};
			var array=[];
			var options=[];
			var stockArea=this.stockArea;

			//create an obj with prop the names of the inputs/selects. 1 name -> 1 var
			customs.each(function(index,custom){
				var name=jQuery(this).attr('name');
				//remove the brackets in case of array variables
				name_filtered=name.replace(/[\[\]]/g,'');

				//the selects have values
				if(jQuery(this).is('select')){
					if(typeof obj[name_filtered]=='undefined')var options=jQuery(custom).find('option');
					customObj={options:options, selected_option:custom, value:jQuery(this).val(), type:'select', name:name_filtered};
				}

				//we have to check one by one the inputs to find the selected value
				else if(jQuery(this).is('input')){

					if(typeof obj[name_filtered]=='undefined' && !jQuery(this).attr('checked')){
						var options=jQuery(stockArea).find('input[name="'+name+'"]');
						customObj={options:options, selected_option:false, value:false, type:'input',name:name_filtered};
					}
					else if(jQuery(this).attr('checked')){
						var options=jQuery(stockArea).find('input[name="'+name+'"]');
						customObj={options:options, selected_option:jQuery(this), value:jQuery(this).val(), type:'input',name:name_filtered};
					}
				}
				obj[name_filtered]=customObj;
			});
			//convert to array
			jQuery.each(obj,function(index,ob){
				array.push(ob);
			});

			return array;
		};

		/**
		 * Disables/enables compatible combinations
		 */
		Stockable.prototype.setNextCompatibles=function(customs, from, current_combinations){
			//indicates if it needs to be updated again
			var reupdate=false
			var stockable_out_of_stock_display=this.stockable_out_of_stock_display;
			//start with the custom fields below that
			for(var i=from+1; i<customs.length; i++){
				//first disable them all
				jQuery(customs[i].options).attr('disabled','disabled');

				//check the custom field against the valid combinations and enable the correct combinations
				jQuery.each(current_combinations,function(index, combination){
					if(customs[i].type=='input'){
						//in case of inputs we have to iterate all until we find the 1 with the same value
						jQuery.each(customs[i].options,function(x,input){
							if(combination.customfield_ids[i]==jQuery(input).val() && (stockable_out_of_stock_display=='enabled' || parseInt(combination.stock)>0)){
								jQuery(input).removeAttr('disabled');
								return false;
							}
						});
					}
					else if(customs[i].type=='select'){
						jQuery.each(customs[i].options,function(){
							var option_value=jQuery(this).val();
							//if it's a combination value and should be enabled according to it's stock and the out of stock display
							if(combination.customfield_ids[i]==option_value && (typeof(stockable_out_of_stock_display)=="undefined"|| stockable_out_of_stock_display=='enabled' || parseInt(combination.stock)>0))jQuery(this).removeAttr('disabled');
							//removed disabled also in case of empty options
							if(!option_value || option_value=="0")jQuery(this).removeAttr('disabled');
						});
					}
				});

				//if disabled and selected, remove selection
				if(customs[i].type=='input'){
					if(customs[i].selected_option!==false && (jQuery(customs[i].selected_option).attr('checked')=='checked' || jQuery(customs[i].selected_option).attr('checked')==true) && (jQuery(customs[i].selected_option).attr('disabled')=='disabled' || jQuery(customs[i].selected_option).attr('disabled')==true)){
						jQuery(customs[i].selected_option).removeAttr('checked');
					}
				}
				else if(customs[i].type=='select'){
					var select=jQuery(customs[i].selected_option);
					var selected=jQuery(select).find('option:selected');
					if((selected!=false && jQuery(selected).attr('disabled')=='disabled' || jQuery(selected).attr('disabled')==true) && StockabklesStart==false){
						jQuery(selected).removeAttr('selected');
						//when we remove selected, the browser sets the selection to an enabled option. Hence we need reupdate
						var reupdate=true;
					}
				}

			}
			StockabklesStart=false;
			return reupdate;
		};

		Stockable.prototype.setSelectedFields=function(customs, currentCombination){
			var customslength=customs.length;
			jQuery.each(customs,function(index,custom){


					jQuery.each(custom.options,function(x,option){
						if(currentCombination.customfield_ids[index]==jQuery(option).val()){
							if(custom.type=='input'){
								jQuery(option).attr('checked',true);
							}
							else if(custom.type=='select'){
								jQuery(option).attr('selected','selected');
								var value=jQuery(option).val();
								jQuery(custom.customfield).val(value);
							}
							if(customslength-1==index)return false;
						}
					});
            });
		};
		Stockable.prototype.enableAll=function(customs){
			customs.each(function(){
				if(jQuery(this).is('input'))jQuery(this).removeAttr('disabled');
				if(jQuery(this).is('select'))jQuery(this).find('option').removeAttr('disabled');
			})
		};
		Stockable.prototype.loadProductPage=function(matchedCombinations){
			var product_id=matchedCombinations[0].product_id;
			//the product is already loaded
			if(this.current_product_id==product_id)return;

			var url=this.urls[product_id];
			if (typeof Virtuemart !== "undefined"){
				if (typeof Virtuemart.updateContent == 'function') {
					if(typeof Virtuemart.containerSelector == 'undefined'){
						Virtuemart.containerSelector = '.product-container';
					}
					Virtuemart.container = jQuery(this.stockArea).closest(Virtuemart.containerSelector);
					Virtuemart.updateContent(url,Stockablecustomfields.setEvents);
				}
			}
		};

	jQuery.noConflict();
	jQuery(document).ready(function($) {
		Stockablecustomfields.setEvents();
	});
}
