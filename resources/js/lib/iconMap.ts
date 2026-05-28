import {
  CalendarCheck,
  Circle,
  Cog,
  CreditCard,
  Frame,
  LayoutDashboard,
  Package,
  Palette,
  Settings,
  ShieldCheck,
  ShoppingCart,
  Sparkles,
  Tag,
  Users,
  Wrench,
} from 'lucide-vue-next'

export const iconMap = {
  CalendarCheck,
  Circle,
  Cog,
  CreditCard,
  Frame,
  LayoutDashboard,
  Package,
  Palette,
  Settings,
  ShieldCheck,
  ShoppingCart,
  Sparkles,
  Tag,
  Users,
  Wrench,
} as const

export type IconName = keyof typeof iconMap
