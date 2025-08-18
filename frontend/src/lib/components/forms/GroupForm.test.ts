import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import userEvent from '@testing-library/user-event';
import GroupForm from './GroupForm.svelte';

describe('GroupForm Component', () => {
	it('should render form with default props', () => {
		render(GroupForm);
		
		expect(screen.getByText('新しいグループを作成')).toBeInTheDocument();
		expect(screen.getByText('家族やグループの名前を入力してください')).toBeInTheDocument();
		expect(screen.getByLabelText('グループ名')).toBeInTheDocument();
		expect(screen.getByRole('button', { name: 'グループを作成' })).toBeInTheDocument();
	});

	describe('form input', () => {
		it('should render input with correct attributes', () => {
			render(GroupForm);
			
			const input = screen.getByLabelText('グループ名');
			expect(input).toHaveAttribute('type', 'text');
			expect(input).toHaveAttribute('id', 'groupName');
			expect(input).toHaveAttribute('name', 'name');
			expect(input).toHaveAttribute('placeholder', '例: 田中家');
			expect(input).toBeRequired();
			expect(input).toHaveAttribute('minlength', '1');
			expect(input).toHaveAttribute('maxlength', '100');
			expect(input).not.toBeDisabled();
		});

		it('should allow user to type in input', async () => {
			const user = userEvent.setup();
			render(GroupForm);
			
			const input = screen.getByLabelText('グループ名');
			await user.type(input, '田中家');
			
			expect(input).toHaveValue('田中家');
		});

		it('should be disabled when loading is true', () => {
			render(GroupForm, { props: { loading: true } });
			
			const input = screen.getByLabelText('グループ名');
			expect(input).toBeDisabled();
		});
	});

	describe('submit button', () => {
		it('should render submit button with correct attributes', () => {
			render(GroupForm);
			
			const button = screen.getByRole('button', { name: 'グループを作成' });
			expect(button).toHaveAttribute('type', 'submit');
			expect(button).not.toBeDisabled();
		});

		it('should show loading state when loading is true', () => {
			render(GroupForm, { props: { loading: true } });
			
			const button = screen.getByRole('button', { name: '作成中...' });
			expect(button).toBeDisabled();
		});

		it('should be disabled when loading is true', () => {
			render(GroupForm, { props: { loading: true } });
			
			const button = screen.getByRole('button');
			expect(button).toBeDisabled();
		});
	});

	describe('form element', () => {
		it('should render form with correct attributes', () => {
			render(GroupForm);
			
			const form = screen.getByRole('form', { hidden: true });
			expect(form).toHaveAttribute('method', 'POST');
			expect(form).toHaveAttribute('action', '?/createGroup');
		});
	});

	describe('error handling', () => {
		it('should display field-specific error when form has name error', () => {
			const formWithErrors = {
				errors: {
					name: 'グループ名は必須です'
				}
			};
			
			render(GroupForm, { props: { form: formWithErrors } });
			
			expect(screen.getByText('グループ名は必須です')).toBeInTheDocument();
		});

		it('should display general error when form has general error', () => {
			const formWithError = {
				error: 'サーバーエラーが発生しました'
			};
			
			render(GroupForm, { props: { form: formWithError } });
			
			expect(screen.getByText('サーバーエラーが発生しました')).toBeInTheDocument();
		});

		it('should display both field and general errors', () => {
			const formWithBothErrors = {
				errors: {
					name: 'グループ名が無効です'
				},
				error: 'サーバーエラーが発生しました'
			};
			
			render(GroupForm, { props: { form: formWithBothErrors } });
			
			expect(screen.getByText('グループ名が無効です')).toBeInTheDocument();
			expect(screen.getByText('サーバーエラーが発生しました')).toBeInTheDocument();
		});

		it('should not display errors when form is null', () => {
			render(GroupForm, { props: { form: null } });
			
			expect(screen.queryByText('グループ名は必須です')).not.toBeInTheDocument();
			expect(screen.queryByText('サーバーエラーが発生しました')).not.toBeInTheDocument();
		});

		it('should not display errors when form has no errors', () => {
			const formWithoutErrors = {};
			
			render(GroupForm, { props: { form: formWithoutErrors } });
			
			expect(screen.queryByText('グループ名は必須です')).not.toBeInTheDocument();
			expect(screen.queryByText('サーバーエラーが発生しました')).not.toBeInTheDocument();
		});
	});

	describe('loading states', () => {
		it('should handle loading state correctly', () => {
			render(GroupForm, { props: { loading: true } });
			
			const input = screen.getByLabelText('グループ名');
			const button = screen.getByRole('button');
			
			expect(input).toBeDisabled();
			expect(button).toBeDisabled();
			expect(button).toHaveTextContent('作成中...');
		});

		it('should handle non-loading state correctly', () => {
			render(GroupForm, { props: { loading: false } });
			
			const input = screen.getByLabelText('グループ名');
			const button = screen.getByRole('button');
			
			expect(input).not.toBeDisabled();
			expect(button).not.toBeDisabled();
			expect(button).toHaveTextContent('グループを作成');
		});
	});

	describe('form interaction', () => {
		it('should allow form submission when not loading', async () => {
			const user = userEvent.setup();
			render(GroupForm);
			
			const input = screen.getByLabelText('グループ名');
			const button = screen.getByRole('button', { name: 'グループを作成' });
			
			await user.type(input, '田中家');
			await user.click(button);
			
			// The actual form submission behavior would be handled by SvelteKit's enhance
			// We're just testing that the UI allows the interaction
			expect(input).toHaveValue('田中家');
		});

		it('should prevent interaction when loading', async () => {
			const user = userEvent.setup();
			render(GroupForm, { props: { loading: true } });
			
			const input = screen.getByLabelText('グループ名');
			const button = screen.getByRole('button');
			
			// Try to type - should not work because input is disabled
			await user.type(input, '田中家');
			expect(input).toHaveValue('');
			
			// Button should be disabled
			expect(button).toBeDisabled();
		});
	});

	describe('accessibility', () => {
		it('should have proper label association', () => {
			render(GroupForm);
			
			const input = screen.getByLabelText('グループ名');
			expect(input).toHaveAttribute('id', 'groupName');
			
			const label = screen.getByText('グループ名');
			expect(label).toHaveAttribute('for', 'groupName');
		});

		it('should have semantic form structure', () => {
			render(GroupForm);
			
			// Should have proper heading hierarchy
			expect(screen.getByRole('heading', { level: 2 })).toHaveTextContent('新しいグループを作成');
			
			// Should have form role
			expect(screen.getByRole('form', { hidden: true })).toBeInTheDocument();
		});

		it('should be keyboard accessible', async () => {
			const user = userEvent.setup();
			render(GroupForm);
			
			const input = screen.getByLabelText('グループ名');
			const button = screen.getByRole('button');
			
			// Should be able to navigate with Tab
			await user.tab();
			expect(input).toHaveFocus();
			
			await user.tab();
			expect(button).toHaveFocus();
		});
	});

	describe('integration', () => {
		it('should handle complex error scenarios', () => {
			const complexForm = {
				errors: {
					name: 'グループ名は3文字以上で入力してください'
				},
				error: 'ネットワークエラーが発生しました',
			};
			
			render(GroupForm, { 
				props: { 
					form: complexForm, 
					loading: false 
				} 
			});
			
			// Should display both types of errors
			expect(screen.getByText('グループ名は3文字以上で入力してください')).toBeInTheDocument();
			expect(screen.getByText('ネットワークエラーが発生しました')).toBeInTheDocument();
			
			// Form should still be functional
			const input = screen.getByLabelText('グループ名');
			const button = screen.getByRole('button');
			
			expect(input).not.toBeDisabled();
			expect(button).not.toBeDisabled();
		});
	});
});