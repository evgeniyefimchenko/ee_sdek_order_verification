{if $addons.ee_sdek_order_verification.hide_shipments == "Y"}
	{literal}
		<style>
			.pull-right {
				display: none;
			}
		</style>
	{/literal}
{/if}	
{if $addons.ee_sdek_order_verification.show_traking != "status_none"}
	{if $addons.ee_sdek_order_verification.show_traking == "status_site"}
		{$status_history = $oi.order_id|fn_get_track_by_order_id}
		{$arr_statuses = $status_history|fn_show_our_status_order}	
		{capture name="ee_traking_popup"}
			{foreach $arr_statuses as $item}
				<p><i>{$item.date}</i> - <b>{$item.status}</b></p><hr/>
			{/foreach}	
		{/capture}
	{else}
		{$status_history = $oi.order_id|fn_get_track_by_order_id}
		{capture name="ee_traking_popup"}
			{foreach $status_history as $item}
				{$arr_statuses[] = 1}
				{$unix_time = $item.date_time|strtotime}
				<p><i>{'d.m.Y h:i:s'|date:$unix_time}</i> - <b>{$item.name}</b>({$item.city})</p><hr/>
			{/foreach}	
		{/capture}	
	{/if}
	{if $arr_statuses|@count}
		<h4>Статусы заказа:</h4>
		{$smarty.capture.ee_traking_popup nofilter}
	{/if}
{/if}
