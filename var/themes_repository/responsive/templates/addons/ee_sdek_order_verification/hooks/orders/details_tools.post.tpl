{$status_history = $order_info.order_id|fn_get_track_by_order_id}
{$arr_statuses = $status_history|fn_show_our_status_order}
{if $arr_statuses|@count}
Лютая ссыль на трекинг
	<div class="hidden">
		{foreach $arr_statuses as $item}
			{$item|fn_print_r}
		{/foreach}
	</div>
{/if}