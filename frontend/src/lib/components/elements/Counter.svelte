<script lang="ts">
	import Button from './Button.svelte';

	export let value: number = 0;
	export let disabled: boolean = false;
	export let onIncrement: () => void = () => {};
	export let onDecrement: () => void = () => {};

	function handleIncrement() {
		onIncrement();
	}

	function handleDecrement() {
		onDecrement();
	}
</script>

<div class="counter">
	<Button 
		variant="icon" 
		on:click={handleDecrement} 
		disabled={disabled || value === 0}
	>
		<span class="control-symbol">−</span>
	</Button>
	
	<span class="count">{value}</span>
	
	<Button 
		variant="icon" 
		on:click={handleIncrement}
		{disabled}
	>
		<span class="control-symbol">+</span>
	</Button>
</div>

<style>
	.counter {
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.count {
		min-width: 32px;
		text-align: center;
		font-weight: bold;
		font-size: 18px;
	}

	.control-symbol {
		font-size: 20px;
		font-weight: bold;
		line-height: 1;
	}
</style>