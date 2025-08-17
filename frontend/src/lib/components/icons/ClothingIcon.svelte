<script lang="ts">
	export let categoryId: string;
	export let size: 'sm' | 'md' | 'lg' = 'md';
	export let categoryName: string = '';
	
	// カテゴリIDとアイコンファイルのマッピング
	const iconMapping: Record<string, string> = {
		'1': '/icons/tshirt.svg',        // Tシャツ
		'2': '/icons/pants.svg',         // ズボン
		'3': '/icons/socks.svg',         // 靴下
		'4': '/icons/handkerchief.svg',  // ハンカチ
		'5': '/icons/underwear.svg',     // 肌着
		'6': '/icons/hat.svg',           // ぼうし
		'7': '/icons/swimwear.svg',      // 水着セット
		'8': '/icons/plastic_bag.svg'    // ビニール袋
	};
	
	// サイズクラスのマッピング
	const sizeClasses = {
		sm: 'w-6 h-6',
		md: 'w-8 h-8',
		lg: 'w-12 h-12'
	};
	
	$: iconSrc = iconMapping[categoryId] || '/icons/tshirt.svg';
	$: sizeClass = sizeClasses[size];
</script>

<div class="clothing-icon {sizeClass}" role="img" aria-label={categoryName}>
	<img 
		src={iconSrc} 
		alt={categoryName}
		class="icon-image"
		loading="lazy"
	/>
</div>

<style>
	.clothing-icon {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		flex-shrink: 0;
	}
	
	.icon-image {
		width: 100%;
		height: 100%;
		object-fit: contain;
		filter: drop-shadow(0 1px 3px rgba(0, 0, 0, 0.1));
		transition: all 0.2s ease;
	}
	
	.clothing-icon:hover .icon-image {
		filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.15));
		transform: scale(1.05);
	}
	
	/* Tailwind風のサイズクラス */
	.w-6 { width: 1.5rem; }
	.h-6 { height: 1.5rem; }
	.w-8 { width: 2rem; }
	.h-8 { height: 2rem; }
	.w-12 { width: 3rem; }
	.h-12 { height: 3rem; }
	
	/* レスポンシブサイズ調整 */
	@media (max-width: 400px) {
		.w-12 { width: 2.5rem; }
		.h-12 { height: 2.5rem; }
	}
	
	/* アクセシビリティ: reduced motionの場合はアニメーションを無効化 */
	@media (prefers-reduced-motion: reduce) {
		.icon-image {
			transition: none;
		}
		
		.clothing-icon:hover .icon-image {
			transform: none;
		}
	}
</style>