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
		padding: var(--spacing-md, 1rem);
		background-color: transparent;
		text-align: center;
	}

	.header__title {
		font-size: var(--text-2xl, 1.75rem);
		font-weight: 600;
		color: #2d2d2d;
		margin: 0 0 var(--spacing-xs, 0.5rem) 0;
		line-height: 1.2;
	}

	.header__subtitle {
		color: #666;
		font-size: var(--text-sm, 0.9rem);
		margin: 0;
		line-height: 1.4;
		max-width: 600px;
		margin-left: auto;
		margin-right: auto;
	}

	/* 横向き時のコンパクト表示 */
	@media (orientation: landscape) and (max-width: 767px) {
		.header {
			padding: var(--spacing-sm, 0.5rem) var(--spacing-md, 1rem);
		}
		
		.header__title {
			font-size: var(--text-xl, 1.25rem);
			margin-bottom: var(--spacing-xs, 0.25rem);
		}
		
		.header__subtitle {
			font-size: var(--text-xs, 0.875rem);
		}
	}

	/* タブレット以上 */
	@media (min-width: 768px) {
		.header {
			padding: var(--spacing-xl, 2rem);
		}
	}
	
	/* デスクトップ */
	@media (min-width: 1024px) {
		.header__title {
			font-size: 2.25rem;
		}
		
		.header__subtitle {
			font-size: var(--text-base, 1rem);
		}
	}
</style>