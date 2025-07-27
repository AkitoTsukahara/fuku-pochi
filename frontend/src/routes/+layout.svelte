<script>
	import '../app.css';
</script>

<main class="safe-area-inset-top safe-area-inset-bottom">
	<slot />
</main>

<style>
	main {
		min-height: 100vh;
		display: flex;
		flex-direction: column;
	}
</style>