export const formatDate = (dateString: string): string => {
	const date = new Date(dateString);
	return date.toLocaleDateString('ja-JP', {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
		hour: '2-digit',
		minute: '2-digit'
	});
};

export const formatRelativeTime = (dateString: string): string => {
	const date = new Date(dateString);
	const now = new Date();
	const diffMs = now.getTime() - date.getTime();
	const diffMinutes = Math.floor(diffMs / (1000 * 60));
	const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
	const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

	if (diffMinutes < 1) return 'たった今';
	if (diffMinutes < 60) return `${diffMinutes}分前`;
	if (diffHours < 24) return `${diffHours}時間前`;
	if (diffDays < 7) return `${diffDays}日前`;
	
	return formatDate(dateString);
};