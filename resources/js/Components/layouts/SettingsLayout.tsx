import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { wurl } from '@/lib/workspace-url';
import { PageProps } from '@/types';
import AppLayout from '@/Components/layouts/AppLayout';
import { Tag, GitBranch, FileCode2, CalendarDays } from 'lucide-react';

interface SettingsNavItem {
  label: string;
  path: string;
  pattern: string;
}

interface SettingsToolItem {
  label: string;
  path: string;
  pattern: string;
  icon: React.ComponentType<{ className?: string }>;
}

interface SettingsLayoutProps {
  children: React.ReactNode;
}

const NAV_ITEMS: SettingsNavItem[] = [
  { label: 'Profile',       path: '/settings/profile',       pattern: '/settings/profile' },
  { label: 'Workspace',     path: '/settings/workspace',     pattern: '/settings/workspace' },
  { label: 'Team',          path: '/settings/team',          pattern: '/settings/team' },
  { label: 'Costs',         path: '/settings/costs',         pattern: '/settings/costs' },
  { label: 'Billing',       path: '/settings/billing',       pattern: '/settings/billing' },
  { label: 'Notifications', path: '/settings/notifications', pattern: '/settings/notifications' },
  { label: 'Targets',       path: '/settings/targets',       pattern: '/settings/targets' },
  { label: 'Audit log',     path: '/settings/audit',         pattern: '/settings/audit' },
  { label: 'Stores',        path: '/settings/stores',        pattern: '/settings/stores' },
];

const TOOLS_ITEMS: SettingsToolItem[] = [
  { label: 'Tag Generator',     path: '/integrations/tag-generator',     pattern: '/integrations/tag-generator',     icon: Tag },
  { label: 'Channel Mappings',  path: '/integrations/channel-mappings',  pattern: '/integrations/channel-mappings',  icon: GitBranch },
  { label: 'Naming Convention', path: '/integrations/naming-convention', pattern: '/integrations/naming-convention', icon: FileCode2 },
  { label: 'Holidays / Events', path: '/holidays',                       pattern: '/holidays',                       icon: CalendarDays },
];

export function SettingsLayout({ children }: SettingsLayoutProps) {
  const { url, props } = usePage<PageProps>();
  const workspace = props.workspace;

  return (
    <AppLayout>
      <div className="mx-auto max-w-5xl px-6 py-8">
        <div className="flex gap-8">
          <nav className="w-48 shrink-0">
            <ul className="space-y-0.5">
              {NAV_ITEMS.map((item) => {
                const href = wurl(workspace?.slug, item.path);
                const isActive = url.includes(item.pattern);
                return (
                  <li key={item.path}>
                    <Link
                      href={href}
                      className={cn(
                        'block rounded-md px-3 py-2 text-sm transition-colors',
                        isActive
                          ? 'bg-zinc-900 text-white font-semibold'
                          : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 font-medium',
                      )}
                      aria-current={isActive ? 'page' : undefined}
                    >
                      {item.label}
                    </Link>
                  </li>
                );
              })}
            </ul>

            {/* Tools section */}
            <div className="mt-4 border-t border-border pt-4">
              <div className="px-3 pb-1 text-sm font-semibold text-muted-foreground uppercase tracking-widest select-none">
                Tools
              </div>
              <ul className="space-y-0.5">
                {TOOLS_ITEMS.map((item) => {
                  const href = wurl(workspace?.slug, item.path);
                  const isActive = url.includes(item.pattern);
                  const Icon = item.icon;
                  return (
                    <li key={item.path}>
                      <Link
                        href={href}
                        className={cn(
                          'flex items-center gap-2 rounded-md px-3 py-2 text-sm transition-colors',
                          isActive
                            ? 'bg-zinc-900 text-white font-semibold'
                            : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 font-medium',
                        )}
                      >
                        <Icon
                          className={cn(
                            'h-3.5 w-3.5 shrink-0',
                            isActive ? 'text-primary' : 'text-muted-foreground',
                          )}
                          aria-hidden="true"
                        />
                        {item.label}
                      </Link>
                    </li>
                  );
                })}
              </ul>
            </div>
          </nav>
          <main className="min-w-0 flex-1">{children}</main>
        </div>
      </div>
    </AppLayout>
  );
}
