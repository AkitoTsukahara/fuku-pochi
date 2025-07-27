<script lang="ts">
	import Icon from '../elements/Icon.svelte';
	import Badge from '../elements/Badge.svelte';
	import Counter from '../elements/Counter.svelte';
	
	export let itemName: string;
	export let currentStock: number = 0;
	export let iconSrc: string = '';
	export let onStockChange: (newValue: number) => void = () => {};

	function handleIncrement() {
		onStockChange(currentStock + 1);
	}

	function handleDecrement() {
		if (currentStock > 0) {
			onStockChange(currentStock - 1);
		}
	}
</script>

<div class="stock-item">
	<div class="stock-item__icon">
		<Icon src={iconSrc} alt={itemName} size="md" />
		<Badge value={currentStock} />
	</div>
	
	<div class="stock-item__name">{itemName}</div>
	
	<div class="stock-item__controls">
		<Counter 
			value={currentStock}
			onIncrement={handleIncrement}
			onDecrement={handleDecrement}
		/>
	</div>
</div>

<style>
	.stock-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		padding: 16px;
		border-radius: 12px;
		background-color: #faf9f7;
		border: 1px solid #e5e5e5;
		min-width: 120px;
		gap: 12px;
	}

	.stock-item__icon {
		position: relative;
	}

	.stock-item__name {
		font-size: 14px;
		color: #666;
		text-align: center;
		font-weight: 500;
	}

	.stock-item__controls {
		margin-top: auto;
	}
</style>