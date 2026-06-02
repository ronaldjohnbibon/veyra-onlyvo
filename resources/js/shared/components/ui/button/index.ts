import type { VariantProps } from 'class-variance-authority'
import { cva } from 'class-variance-authority'

export { default as Button } from './Button.vue'

export const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded text-sm font-semibold transition-all duration-200 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/40 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive active:scale-[0.98]",
  {
    variants: {
      variant: {
        default:
          'bg-primary text-primary-foreground shadow-md hover:shadow-lg hover:bg-primary/90 hover:-translate-y-0.5',
        destructive:
          'bg-destructive text-destructive-foreground shadow-md hover:shadow-lg hover:bg-destructive/90 hover:-translate-y-0.5 focus-visible:ring-destructive/30',
        outline:
          'bg-background shadow-sm hover:shadow-md hover:bg-accent hover:text-accent-foreground',
        outline_default:
          'border-2 border-input bg-background shadow-sm hover:shadow-md hover:bg-accent hover:text-accent-foreground hover:border-accent-foreground/20',
        secondary:
          'bg-secondary text-secondary-foreground shadow-sm hover:shadow-md hover:bg-secondary/80',
        ghost: 'hover:bg-accent/80 hover:text-accent-foreground',
        link: 'text-primary underline-offset-4 hover:underline font-medium',
        navigate:
          'border border-sky-200 bg-sky-50 text-sky-700 shadow-sm hover:border-sky-300 hover:bg-sky-100 hover:text-sky-800 dark:border-sky-900/60 dark:bg-sky-950/40 dark:text-sky-300 dark:hover:bg-sky-900/50',
        select:
          'border border-input bg-background shadow-sm hover:border-primary/60 hover:bg-primary/5 hover:text-primary hover:shadow-md',
        create:
          'bg-gradient-to-r from-emerald-600 to-emerald-500 text-white shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:shadow-emerald-500/40 hover:from-emerald-500 hover:to-emerald-400 hover:-translate-y-0.5',
        edit: 'bg-gradient-to-r from-amber-600 to-amber-500 text-white shadow-lg shadow-amber-500/30 hover:shadow-xl hover:shadow-amber-500/40 hover:from-amber-500 hover:to-amber-400 hover:-translate-y-0.5 focus-visible:ring-amber-400/40',
        update:
          'bg-gradient-to-r from-emerald-700 to-emerald-600 text-white shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:shadow-emerald-500/40 hover:from-emerald-600 hover:to-emerald-500 hover:-translate-y-0.5',
        publish:
          'bg-gradient-to-r from-sky-600 to-cyan-500 text-white shadow-lg shadow-sky-500/30 hover:shadow-xl hover:shadow-sky-500/40 hover:from-sky-500 hover:to-cyan-400 hover:-translate-y-0.5 focus-visible:ring-sky-400/40',
        restore:
          'border border-amber-300 bg-amber-50 text-amber-800 shadow-sm hover:border-amber-400 hover:bg-amber-100 hover:text-amber-900 dark:border-amber-900/70 dark:bg-amber-950/40 dark:text-amber-300 dark:hover:bg-amber-900/50',
        delete:
          'bg-gradient-to-r from-red-600 to-red-500 text-white shadow-lg shadow-red-500/30 hover:shadow-xl hover:shadow-red-500/40 hover:from-red-500 hover:to-red-400 hover:-translate-y-0.5',
        cancel:
          'border border-input bg-background text-muted-foreground shadow-sm hover:bg-muted hover:text-foreground hover:shadow-md',
      },
      size: {
        default: 'h-10 px-5 py-2 has-[>svg]:px-4',
        sm: 'h-8 rounded gap-1.5 px-3.5 has-[>svg]:px-3',
        xs: 'h-6 rounded gap-1 px-2.5 has-[>svg]:px-2',
        lg: 'h-11 rounded px-7 has-[>svg]:px-5',
        icon: 'size-10',
        'icon-sm': 'size-9',
        'icon-lg': 'size-11',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  }
)

export type ButtonVariants = VariantProps<typeof buttonVariants>
