<script lang="ts">
	export let value: number = 0;
	export let variant: 'default' | 'primary' | 'warning' = 'default';
	export let position: 'top-right' | 'bottom-right' | 'standalone' = 'top-right';
</script>

<span class="badge badge--{variant} badge--{position}">
	{value}
</span>

<style>
	.badge {
		background-color: #4a90e2;
		color: white;
		border-radius: 50%;
		width: 24px;
		height: 24px;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 12px;
		font-weight: bold;
		line-height: 1;
	}

	.badge--default {
		background-color: #4a90e2;
	}

	.badge--primary {
		background-color: #2d5aa0;
	}

	.badge--warning {
		background-color: #f39c12;
	}

	.badge--top-right {
		position: absolute;
		top: -8px;
		right: -8px;
	}

	.badge--bottom-right {
		position: absolute;
		bottom: -8px;
		right: -8px;
	}

	.badge--standalone {
		position: static;
	}
</style>