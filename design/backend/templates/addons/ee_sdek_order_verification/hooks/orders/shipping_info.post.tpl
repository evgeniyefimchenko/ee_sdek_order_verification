{if $addons.ee_sdek_order_verification.hide_shipments == "Y"}
	{literal}
		<style>
			.pull-right {
				display: none;
			}
		</style>
	{/literal}
{/if}

{$status_history = $oi.order_id|fn_get_track_by_order_id}
{$arr_statuses = $status_history|fn_show_our_status_order}
{if $arr_statuses|@count}
Лютая ссыль на трекинг
	<div class="hidden">
		{foreach $arr_statuses as $item}
			{$item|fn_print_r}
		{/foreach}
	</div>
{/if}
