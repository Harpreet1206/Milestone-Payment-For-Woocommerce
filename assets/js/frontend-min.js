jQuery(document).on("change",".awcdp-deposits-wrapper input[name='awcdp_deposit_option']",function(e){e.preventDefault(),$container=jQuery(this).closest(".awcdp-deposits-wrapper"),"yes"==jQuery(this).val()?$container.find(".awcdp-deposits-payment-plans, .awcdp-deposits-description").show():$container.find(".awcdp-deposits-payment-plans, .awcdp-deposits-description").hide()}),jQuery(document).ready(function(){$container=jQuery(".awcdp-deposits-wrapper"),"no"==jQuery('input[name="awcdp_deposit_option"]:checked').val()&&$container.find(".awcdp-deposits-payment-plans, .awcdp-deposits-description").hide()});
