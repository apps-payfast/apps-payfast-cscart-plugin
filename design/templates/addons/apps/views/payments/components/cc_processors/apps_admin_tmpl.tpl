{include file="common/subheader.tpl" title=__("addons.apps_config_header") target="#apps_merchant_configurations"}
<div id="apps_merchant_configurations">    
    <div class="control-group">
        <label class="control-label cm-required" id="lbl_apps_merchant_id" for="apps_merchant_id">{__("addons.apps_merchant_id")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][apps_merchant_id]" id="apps_merchant_id" size="32" value="{$processor_params.apps_merchant_id}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label cm-required" id="lbl_apps_merchant_key" for="apps_merchant_key">{__("addons.apps_merchant_key")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][apps_merchant_key]" id="apps_merchant_key" size="32" value="{$processor_params.apps_merchant_key}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" id="lbl_apps_merchant_key" for="apps_merchant_key">{__("addons.apps_merchant_name")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][apps_merchant_name]" id="apps_merchant_key" size="32" value="{if $processor_params.apps_merchant_name == ""}{$merchant_name}{else}{$processor_params.apps_merchant_name}{/if}" >
        </div>
    </div>
</div>