/**
 * Copyright VirtueMart Team, breakdesigns.net
 */
if (typeof Productbundles === "undefined") {
	var Productbundles = {
			updatePrice:function(form,id){				
				var $ = jQuery, datas = form.serialize();
				var prices = form.find(".productbundles_productprice");
				if (prices.length==0) {
					prices = $("#productbundles_productprice" + id);
				}				
				datas = datas.replace("&view=cart", "");
				prices.fadeTo("fast", 0.75);
				$.getJSON(window.vmSiteurl + 'index.php?option=com_virtuemart&nosef=1&view=productdetails&task=recalculate&virtuemart_product_id='+id+'&format=json' + window.vmLang, encodeURIComponent(datas),
					function (datas, textStatus) {
						prices.fadeTo("fast", 1);
						// refresh price
						for (var key in datas) {
							var value = datas[key];
							if (value!=0) prices.find("span.Price"+key).show().html(value);
							else prices.find(".Price"+key).html(0).hide();
						}
					});
				return false; // prevent reload
			},
			product : function(carts) {
				carts.each(function(){
					var cart = jQuery(this),
					step=cart.find('input[name="quantity"]'),
					addtocart = cart.find('input.addtocart-button'),
					plus   = cart.find('.quantity-plus'),
					minus  = cart.find('.quantity-minus'),
					select = cart.find('select:not(.no-vm-bind)'),
					radio = cart.find('input:radio:not(.no-vm-bind)'),
					virtuemart_product_id = cart.find('input[name="virtuemart_product_id[]"]').val(),
					quantity = cart.find('.quantity-input');

                    var Ste = parseInt(step.val());
                    // Fallback for layouts lower than 2.0.18b
                    if(isNaN(Ste)){
                        Ste = 1;
                    }
					addtocart.click(function(e) { 
						Virtuemart.sendtocart(cart);
						return false;
					});
					plus.click(function() {
						var Qtt = parseInt(quantity.val());
						if (!isNaN(Qtt)) {
							quantity.val(Qtt + Ste);
							Productbundles.updatePrice(cart,virtuemart_product_id);
						}
						
					});
					minus.click(function() {
						var Qtt = parseInt(quantity.val());
						if (!isNaN(Qtt) && Qtt>Ste) {
							quantity.val(Qtt - Ste);
						} else quantity.val(Ste);
						Productbundles.updatePrice(cart,virtuemart_product_id);
					});
					select.change(function() {
						Productbundles.updatePrice(cart,virtuemart_product_id);
					});
					radio.change(function() {
						Productbundles.updatePrice(cart,virtuemart_product_id);
					});
					quantity.keyup(function() {
						Productbundles.updatePrice(cart,virtuemart_product_id);
					});
				});
			},
			
			/**
			 * Hide the titles of the custom fields. We want to use our own
			 * title
			 * 
			 * @since 1.0
			 * @author Sakis Terz
			 */
			hideCustomTitle:function(){
				var productbundles_wrappers=jQuery('.productbundles_wrapper');
				productbundles_wrappers.each(function(){
					// remove it
					jQuery(this).parent().prev('.product-fields-title').remove();
				});
			},
			/**
			 * Handle the add to cart 
			 * @since 	1.0
			 * @author Sakis Terz
			 */
			handleToCart:function(){
				var productForms=jQuery('.productbundles_form');			
				datas=new Array();
				responses=new Array();
				current_temp=0;
				//prints the loader
				if(usefancy){
					jQuery.fancybox.showActivity();
                }
									
				productForms.each(function(){
					var form=jQuery(this);
					var virtuemart_product_id = form.find('input[name="virtuemart_product_id[]"]').val();
					datas.push(form.serialize());										
				});				
				
				Productbundles.multiSynchRequest();
			},
			
			/**
			 * Handles the multiple synchronous requests for the add cart
			 * @since 1.0
			 * @author 	Sakis Terz
			 */
			multiSynchRequest:function(){
				var totalRequests=datas.length;
				if(current_temp<totalRequests){
					Productbundles.callRequest(datas[current_temp],current_temp,totalRequests);
					current_temp++;
				}else{
					Productbundles.informUser(responses);
				}
				
			},
			/**
			 * Makes individual add to cart requests
			 * @since 	1.0
			 * @author 	Sakis Terz
			 */
			callRequest:function(data){
				jQuery.getJSON(vmSiteurl+'index.php?option=com_virtuemart&nosef=1&view=cart&task=addJS&format=json'+vmLang,data).
				done(function(data){
					responses.push(data);
					Productbundles.multiSynchRequest();
				})
			},
			/**
			 * Inform the user about using the messages returned from the server
			 * @since 1.0
			 * @author Sakis Terz
			 */
			informUser:function(responses){				
				var cartHtml='';
				var linksHtml='';
				var links=new Array();
				jQuery.each(responses,function(){
					if(this.stat==1){
					var message='<div>'+this.msg+'</div>';
					var product=jQuery(message).find('h4');
					if(links.length==0)links=jQuery(message).find('a');
					cartHtml+='<h4 class="productbundles_cart_products">'+jQuery(product).html()+'</h4>';
					}					
				});
				
				if(links.length>0){
					jQuery.each(links,function(){
						console.log(jQuery(this));
						var el = jQuery(this).clone().wrap('<p>').parent().html();
						linksHtml+=el;						
					});
					cartHtml='<div id="cart_popup_contents">'+linksHtml+cartHtml+'</div>';
				}
				if(usefancy){
					jQuery.fancybox({
                            "titlePosition" : 	"inside",
                            "transitionIn"	:	"fade",
                            "transitionOut"	:	"fade",
                            "changeFade"    :   "fast",
                            "type"			:	"html",
                            "autoCenter"    :   true,
                            "closeBtn"      :   false,
                            "closeClick"    :   false,
                            "content"       :   cartHtml
                        }
                    );
                } else {
                	jQuery.facebox.settings.closeImage = closeImage;
                	jQuery.facebox.settings.loadingImage = loadingImage;
                    //$.facebox.settings.faceboxHtml = faceboxHtml;
                	jQuery.facebox({ text: cartHtml }, 'my-groovy-style');
                }

                if (jQuery(".vmCartModule")[0]) {
                    Virtuemart.productUpdate(jQuery(".vmCartModule"));
                }
			}
			
	};
	
	jQuery.noConflict();
	jQuery(document).ready(function($) {
		Productbundles.hideCustomTitle();
		Productbundles.product(jQuery("form.productbundles-recalculate"));
		jQuery("form.productbundles-recalculate").each(function(){
			
			if (jQuery(this).find(".product-fields").length && !jQuery(this).find(".no-vm-bind").length) {
				var id= jQuery(this).find('input[name="virtuemart_product_id[]"]').val();
				Productbundles.updatePrice(jQuery(this),id);
			}
		});
		//disable the add to cart form in case of using this way
		jQuery('form.productbundles_cart').submit(function(){
			Productbundles.handleToCart();
			return false;
		});
	});
}