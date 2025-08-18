import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import userEvent from '@testing-library/user-event';
import Button from './Button.svelte';

describe('Button Component', () => {
	it('should render button with default props', () => {
		render(Button, { props: { children: 'Click me' } });
		
		const button = screen.getByRole('button', { name: 'Click me' });
		expect(button).toBeInTheDocument();
		expect(button).toHaveClass('btn', 'btn--primary', 'btn--md');
		expect(button).toHaveAttribute('type', 'button');
		expect(button).not.toBeDisabled();
	});

	describe('variants', () => {
		it('should render primary variant', () => {
			render(Button, { props: { variant: 'primary', children: 'Primary' } });
			
			const button = screen.getByRole('button');
			expect(button).toHaveClass('btn--primary');
		});

		it('should render secondary variant', () => {
			render(Button, { props: { variant: 'secondary', children: 'Secondary' } });
			
			const button = screen.getByRole('button');
			expect(button).toHaveClass('btn--secondary');
		});

		it('should render icon variant', () => {
			render(Button, { props: { variant: 'icon', children: '⚙️' } });
			
			const button = screen.getByRole('button');
			expect(button).toHaveClass('btn--icon');
		});

		it('should render outline variant', () => {
			render(Button, { props: { variant: 'outline', children: 'Outline' } });
			
			const button = screen.getByRole('button');
			expect(button).toHaveClass('btn--outline');
		});
	});

	describe('sizes', () => {
		it('should render small size', () => {
			render(Button, { props: { size: 'sm', children: 'Small' } });
			
			const button = screen.getByRole('button');
			expect(button).toHaveClass('btn--sm');
		});

		it('should render medium size (default)', () => {
			render(Button, { props: { size: 'md', children: 'Medium' } });
			
			const button = screen.getByRole('button');
			expect(button).toHaveClass('btn--md');
		});

		it('should render large size', () => {
			render(Button, { props: { size: 'lg', children: 'Large' } });
			
			const button = screen.getByRole('button');
			expect(button).toHaveClass('btn--lg');
		});

		it('should handle "small" alias for "sm"', () => {
			render(Button, { props: { size: 'small', children: 'Small Alias' } });
			
			const button = screen.getByRole('button');
			expect(button).toHaveClass('btn--sm');
		});
	});

	describe('button types', () => {
		it('should render submit type', () => {
			render(Button, { props: { type: 'submit', children: 'Submit' } });
			
			const button = screen.getByRole('button');
			expect(button).toHaveAttribute('type', 'submit');
		});

		it('should render reset type', () => {
			render(Button, { props: { type: 'reset', children: 'Reset' } });
			
			const button = screen.getByRole('button');
			expect(button).toHaveAttribute('type', 'reset');
		});

		it('should render button type (default)', () => {
			render(Button, { props: { type: 'button', children: 'Button' } });
			
			const button = screen.getByRole('button');
			expect(button).toHaveAttribute('type', 'button');
		});
	});

	describe('disabled state', () => {
		it('should render disabled button', () => {
			render(Button, { props: { disabled: true, children: 'Disabled' } });
			
			const button = screen.getByRole('button');
			expect(button).toBeDisabled();
		});

		it('should not be disabled by default', () => {
			render(Button, { props: { children: 'Enabled' } });
			
			const button = screen.getByRole('button');
			expect(button).not.toBeDisabled();
		});
	});

	describe('click events', () => {
		it('should handle click events', async () => {
			const handleClick = vi.fn();
			const user = userEvent.setup();
			
			const { component } = render(Button, { props: { children: 'Click me' } });
			component.$on('click', handleClick);
			
			const button = screen.getByRole('button');
			await user.click(button);
			
			expect(handleClick).toHaveBeenCalledTimes(1);
		});

		it('should not trigger click when disabled', async () => {
			const handleClick = vi.fn();
			const user = userEvent.setup();
			
			const { component } = render(Button, { 
				props: { disabled: true, children: 'Disabled' } 
			});
			component.$on('click', handleClick);
			
			const button = screen.getByRole('button');
			await user.click(button);
			
			expect(handleClick).not.toHaveBeenCalled();
		});
	});

	describe('slot content', () => {
		it('should render text content', () => {
			render(Button, { props: { children: 'Button Text' } });
			
			expect(screen.getByText('Button Text')).toBeInTheDocument();
		});

		it('should render complex content', () => {
			render(Button, { 
				props: {
					children: '📧 Send Email'
				}
			});
			
			expect(screen.getByText('📧 Send Email')).toBeInTheDocument();
		});
	});

	describe('accessibility', () => {
		it('should be focusable when not disabled', () => {
			render(Button, { props: { children: 'Focusable' } });
			
			const button = screen.getByRole('button');
			button.focus();
			expect(button).toHaveFocus();
		});

		it('should not be focusable when disabled', () => {
			render(Button, { props: { disabled: true, children: 'Not focusable' } });
			
			const button = screen.getByRole('button');
			button.focus();
			expect(button).not.toHaveFocus();
		});

		it('should have proper role attribute', () => {
			render(Button, { props: { children: 'Button' } });
			
			const button = screen.getByRole('button');
			expect(button.tagName).toBe('BUTTON');
		});
	});

	describe('combinations', () => {
		it('should render with multiple props', () => {
			render(Button, {
				props: {
					variant: 'outline',
					size: 'lg',
					type: 'submit',
					disabled: false,
					children: 'Submit Form'
				}
			});
			
			const button = screen.getByRole('button');
			expect(button).toHaveClass('btn', 'btn--outline', 'btn--lg');
			expect(button).toHaveAttribute('type', 'submit');
			expect(button).not.toBeDisabled();
			expect(button).toHaveTextContent('Submit Form');
		});

		it('should handle all size variants with different button variants', () => {
			const combinations = [
				{ variant: 'primary' as const, size: 'sm' as const },
				{ variant: 'secondary' as const, size: 'md' as const },
				{ variant: 'outline' as const, size: 'lg' as const },
				{ variant: 'icon' as const, size: 'small' as const },
			];

			combinations.forEach(({ variant, size }, index) => {
				const { unmount } = render(Button, {
					props: {
						variant,
						size,
						children: `Button ${index}`
					}
				});

				const expectedSizeClass = size === 'small' ? 'btn--sm' : `btn--${size}`;
				const button = screen.getByRole('button');
				
				expect(button).toHaveClass('btn', `btn--${variant}`, expectedSizeClass);
				
				unmount();
			});
		});
	});
});