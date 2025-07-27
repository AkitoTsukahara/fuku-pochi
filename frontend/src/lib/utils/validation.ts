export const validateRequired = (value: string): boolean => {
	return value.trim().length > 0;
};

export const validateChildName = (name: string): { isValid: boolean; error?: string } => {
	if (!validateRequired(name)) {
		return { isValid: false, error: '子どもの名前を入力してください' };
	}
	
	if (name.length > 20) {
		return { isValid: false, error: '名前は20文字以内で入力してください' };
	}
	
	return { isValid: true };
};

export const validateGroupName = (name: string): { isValid: boolean; error?: string } => {
	if (!validateRequired(name)) {
		return { isValid: false, error: 'グループ名を入力してください' };
	}
	
	if (name.length > 30) {
		return { isValid: false, error: 'グループ名は30文字以内で入力してください' };
	}
	
	return { isValid: true };
};