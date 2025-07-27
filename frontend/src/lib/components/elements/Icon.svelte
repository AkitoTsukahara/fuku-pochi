<script lang="ts">
	export let src: string = '';
	export let alt: string = '';
	export let size: 'sm' | 'md' | 'lg' = 'md';
</script>

<div class="icon icon--{size}">
	{#if src}
		<img {src} {alt} />
	{:else}
		<div class="placeholder-icon">
			{alt.charAt(0)}
		</div>
	{/if}
</div>

<style>
	.icon {
		position: relative;
		border-radius: 8px;
		overflow: hidden;
	}

	.icon--sm {
		width: 32px;
		height: 32px;
	}

	.icon--md {
		width: 48px;
		height: 48px;
	}

	.icon--lg {
		width: 64px;
		height: 64px;
	}

	.icon img,
	.placeholder-icon {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.placeholder-icon {
		background-color: #ddd;
		display: flex;
		align-items: center;
		justify-content: center;
		font-weight: bold;
		color: #666;
		font-size: 0.8em;
	}
</style>