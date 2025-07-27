<script lang="ts">
	export let title: string = 'ふくぽち';
	export let subtitle: string = '';
</script>

<header class="header">
	<h1 class="header__title">{title}</h1>
	{#if subtitle}
		<p class="header__subtitle">{subtitle}</p>
	{/if}
</header>

<style>
	.header {
		padding: 1rem;
		background-color: transparent;
	}

	.header__title {
		font-size: 1.75rem;
		font-weight: 600;
		color: #2d2d2d;
		margin: 0 0 0.5rem 0;
	}

	.header__subtitle {
		color: #666;
		font-size: 0.9rem;
		margin: 0;
		line-height: 1.4;
	}

	@media (min-width: 768px) {
		.header {
			padding: 2rem;
		}
		
		.header__title {
			font-size: 2rem;
		}
		
		.header__subtitle {
			font-size: 1rem;
		}
	}
</style>