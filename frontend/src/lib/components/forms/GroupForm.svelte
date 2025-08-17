<script lang="ts">
	import { enhance } from '$app/forms';
	import Button from '$lib/components/elements/Button.svelte';
	
	export let form: any = null;
	export let loading: boolean = false;
</script>

<div class="group-form">
	<h2>新しいグループを作成</h2>
	<p class="description">家族やグループの名前を入力してください</p>
	
	<form method="POST" action="?/createGroup" use:enhance>
		<div class="form-group">
			<label for="groupName">グループ名</label>
			<input
				type="text"
				id="groupName"
				name="name"
				placeholder="例: 田中家"
				required
				minlength="1"
				maxlength="100"
				disabled={loading}
			/>
			{#if form?.errors?.name}
				<p class="error">{form.errors.name}</p>
			{/if}
		</div>
		
		<Button type="submit" variant="primary" disabled={loading}>
			{loading ? '作成中...' : 'グループを作成'}
		</Button>
		
		{#if form?.error}
			<p class="error general-error">{form.error}</p>
		{/if}
	</form>
</div>

<style>
	.group-form {
		background: white;
		border-radius: 12px;
		padding: 2rem;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
		max-width: 500px;
		margin: 0 auto;
	}
	
	h2 {
		color: #333;
		font-size: 1.5rem;
		margin-bottom: 0.5rem;
		font-weight: 600;
	}
	
	.description {
		color: #666;
		margin-bottom: 1.5rem;
		font-size: 0.95rem;
	}
	
	.form-group {
		margin-bottom: 1.5rem;
	}
	
	label {
		display: block;
		margin-bottom: 0.5rem;
		color: #333;
		font-weight: 500;
		font-size: 0.95rem;
	}
	
	input {
		width: 100%;
		padding: 0.75rem;
		border: 2px solid #e0e0e0;
		border-radius: 8px;
		font-size: 1rem;
		transition: border-color 0.2s;
		background: #fafafa;
	}
	
	input:focus {
		outline: none;
		border-color: #87ceeb;
		background: white;
	}
	
	input:disabled {
		opacity: 0.6;
		cursor: not-allowed;
	}
	
	.error {
		color: #d32f2f;
		font-size: 0.875rem;
		margin-top: 0.5rem;
	}
	
	.general-error {
		margin-top: 1rem;
		padding: 0.75rem;
		background: #ffebee;
		border-radius: 4px;
	}
</style>