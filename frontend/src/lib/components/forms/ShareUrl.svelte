<script lang="ts">
	import Button from '$lib/components/elements/Button.svelte';
	
	export let shareUrl: string;
	export let groupName: string;
	
	let copied = false;
	let showQrCode = false;
	
	async function copyToClipboard() {
		try {
			await navigator.clipboard.writeText(shareUrl);
			copied = true;
			setTimeout(() => copied = false, 2000);
		} catch (err) {
			console.error('Failed to copy:', err);
		}
	}
	
	function toggleQrCode() {
		showQrCode = !showQrCode;
	}
</script>

<div class="share-container">
	<div class="success-message">
		<svg class="success-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<circle cx="12" cy="12" r="10"></circle>
			<path d="M8 12l2 2 4-4"></path>
		</svg>
		<h2>グループ「{groupName}」を作成しました！</h2>
	</div>
	
	<div class="share-section">
		<h3>共有用URL</h3>
		<p class="description">このURLを家族と共有することで、同じグループにアクセスできます</p>
		
		<div class="url-container">
			<input 
				type="text" 
				value={shareUrl} 
				readonly
				on:click={(e) => e.currentTarget.select()}
			/>
			<Button on:click={copyToClipboard} variant="secondary">
				{copied ? 'コピーしました！' : 'URLをコピー'}
			</Button>
		</div>
		
		<div class="share-options">
			<Button on:click={toggleQrCode} variant="outline">
				{showQrCode ? 'QRコードを隠す' : 'QRコードを表示'}
			</Button>
		</div>
		
		{#if showQrCode}
			<div class="qr-section">
				<div class="qr-placeholder">
					<p>QRコード</p>
					<p class="qr-note">（QRコード生成機能は今後実装予定）</p>
				</div>
			</div>
		{/if}
		
		<div class="next-steps">
			<p class="info">💡 次のステップ:</p>
			<ul>
				<li>URLを家族のLINEやメールで共有</li>
				<li>共有された人がURLにアクセスすると、同じグループを利用できます</li>
				<li>グループ内で子どもの衣類在庫を共有管理できます</li>
			</ul>
		</div>
	</div>
</div>

<style>
	.share-container {
		background: white;
		border-radius: 12px;
		padding: 2rem;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
		max-width: 600px;
		margin: 0 auto;
	}
	
	.success-message {
		text-align: center;
		margin-bottom: 2rem;
	}
	
	.success-icon {
		width: 60px;
		height: 60px;
		color: #4caf50;
		margin: 0 auto 1rem;
	}
	
	h2 {
		color: #333;
		font-size: 1.5rem;
		margin: 0;
		font-weight: 600;
	}
	
	h3 {
		color: #333;
		font-size: 1.2rem;
		margin-bottom: 0.5rem;
		font-weight: 600;
	}
	
	.description {
		color: #666;
		margin-bottom: 1rem;
		font-size: 0.95rem;
	}
	
	.url-container {
		display: flex;
		gap: 0.5rem;
		margin-bottom: 1rem;
	}
	
	input {
		flex: 1;
		padding: 0.75rem;
		border: 2px solid #e0e0e0;
		border-radius: 8px;
		font-size: 0.9rem;
		background: #fafafa;
		font-family: monospace;
	}
	
	input:focus {
		outline: none;
		border-color: #87ceeb;
		background: white;
	}
	
	.share-options {
		margin-bottom: 1.5rem;
		text-align: center;
	}
	
	.qr-section {
		margin: 1.5rem 0;
		text-align: center;
	}
	
	.qr-placeholder {
		display: inline-block;
		padding: 3rem;
		border: 2px dashed #ddd;
		border-radius: 8px;
		background: #f5f5f5;
	}
	
	.qr-placeholder p {
		margin: 0;
		color: #999;
	}
	
	.qr-note {
		font-size: 0.85rem;
		margin-top: 0.5rem !important;
	}
	
	.next-steps {
		background: #f0f8ff;
		border-radius: 8px;
		padding: 1rem;
		margin-top: 1.5rem;
	}
	
	.info {
		color: #1976d2;
		font-weight: 500;
		margin: 0 0 0.5rem;
	}
	
	ul {
		margin: 0;
		padding-left: 1.5rem;
		color: #555;
		font-size: 0.95rem;
	}
	
	li {
		margin-bottom: 0.5rem;
	}
	
	li:last-child {
		margin-bottom: 0;
	}
	
	@media (max-width: 600px) {
		.url-container {
			flex-direction: column;
		}
		
		.share-container {
			padding: 1.5rem;
		}
	}
</style>