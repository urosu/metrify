import { toast as sonnerToast } from 'sonner';

interface ToastOptions {
    description?: string;
    undo?: { label?: string; action: () => void };
    duration?: number;
}

export function toast(message: string, options?: ToastOptions): void {
    sonnerToast(message, {
        description: options?.description,
        duration: options?.duration ?? 4000,
        action: options?.undo
            ? { label: options.undo.label ?? 'Undo', onClick: options.undo.action }
            : undefined,
    });
}

export function toastError(message: string, description?: string): void {
    sonnerToast.error(message, { description });
}

export function toastSuccess(message: string): void {
    sonnerToast.success(message);
}
