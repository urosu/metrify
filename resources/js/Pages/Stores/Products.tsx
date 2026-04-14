import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/Components/layouts/AppLayout';
import { StoreLayout } from '@/Components/layouts/StoreLayout';
import type { StoreData } from '@/Components/layouts/StoreLayout';
import { DateRangePicker } from '@/Components/shared/DateRangePicker';
import { TrendBadge } from '@/Components/shared/TrendBadge';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { formatCurrency, formatNumber } from '@/lib/formatters';
import type { PageProps } from '@/types';

interface ProductRow {
    external_id: string;
    name: string;
    units: number;
    revenue: number;
    revenue_delta: number | null;
    units_delta: number | null;
    stock_status: string | null;
    stock_quantity: number | null;
}

interface Props extends PageProps {
    store: StoreData;
    products: ProductRow[];
    from: string;
    to: string;
}

export default function StoreProducts({ store, products }: Props) {
    const { workspace } = usePage<PageProps>().props;
    const currency = workspace?.reporting_currency ?? 'EUR';

    return (
        <AppLayout dateRangePicker={<DateRangePicker />}>
            <Head title={`${store.name} — Products`} />
            <StoreLayout store={store} activeTab="products">
                {products.length === 0 ? (
                    <div className="flex flex-col items-center justify-center rounded-xl border border-zinc-200 bg-white px-6 py-20 text-center">
                        <p className="text-sm text-zinc-400">No product data for this period.</p>
                        <p className="text-xs text-zinc-400 mt-1">
                            Data appears once the nightly snapshot job has run.
                        </p>
                    </div>
                ) : (
                    <div className="rounded-xl border border-zinc-200 bg-white overflow-hidden">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-zinc-100 bg-zinc-50 text-left">
                                    <th className="px-4 py-3 font-medium text-zinc-400 w-10">#</th>
                                    <th className="px-4 py-3 font-medium text-zinc-400">Product</th>
                                    <th className="px-4 py-3 font-medium text-zinc-400 text-right hidden sm:table-cell">
                                        Units Sold
                                    </th>
                                    <th className="px-4 py-3 font-medium text-zinc-400 text-right">Revenue</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-zinc-100">
                                {products.map((product, index) => (
                                    <tr key={product.external_id} className="hover:bg-zinc-50 transition-colors">
                                        <td className="px-4 py-3 text-zinc-400 tabular-nums">{index + 1}</td>
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-zinc-900">{product.name}</div>
                                            <div className="flex items-center gap-2 mt-0.5">
                                                <span className="text-xs text-zinc-400">ID: {product.external_id}</span>
                                                {product.stock_status && product.stock_status !== 'in_stock' && (
                                                    <StatusBadge status={product.stock_status} preset="stock" />
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-right hidden sm:table-cell">
                                            <div className="text-zinc-600 tabular-nums">{formatNumber(product.units)}</div>
                                            {product.units_delta != null && (
                                                <div className="flex justify-end mt-0.5">
                                                    <TrendBadge value={product.units_delta} />
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="font-medium text-zinc-900 tabular-nums">
                                                {formatCurrency(product.revenue, currency)}
                                            </div>
                                            {product.revenue_delta != null && (
                                                <div className="flex justify-end mt-0.5">
                                                    <TrendBadge value={product.revenue_delta} />
                                                </div>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </StoreLayout>
        </AppLayout>
    );
}
