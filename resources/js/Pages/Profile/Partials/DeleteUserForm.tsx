import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef, useState } from 'react';

export default function DeleteUserForm() {
    const [confirmingUserDeletion, setConfirmingUserDeletion] = useState(false);
    const passwordInput = useRef<HTMLInputElement>(null);

    const {
        data,
        setData,
        delete: destroy,
        processing,
        reset,
        errors,
        clearErrors,
    } = useForm({
        password: '',
    });

    const confirmUserDeletion = () => {
        setConfirmingUserDeletion(true);
    };

    const deleteUser: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current?.focus(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setConfirmingUserDeletion(false);
        clearErrors();
        reset();
    };

    return (
        <section className="space-y-6">
            <header>
                <h2 className="text-lg font-medium text-zinc-900">
                    Delete Account
                </h2>
                <p className="mt-1 text-sm text-zinc-600">
                    Once your account is deleted, all of its resources and data
                    will be permanently deleted. Before deleting your account,
                    please download any data or information that you wish to retain.
                </p>
            </header>

            <Button variant="destructive" onClick={confirmUserDeletion}>
                Delete Account
            </Button>

            <Dialog open={confirmingUserDeletion} onOpenChange={(open) => { if (!open) closeModal(); }}>
                <DialogContent>
                    <DialogTitle>Delete account?</DialogTitle>
                    <p className="text-sm text-zinc-600">
                        Once your account is deleted, all of its resources and data will be
                        permanently deleted. Please enter your password to confirm.
                    </p>

                    <form onSubmit={deleteUser} className="mt-4 space-y-4">
                        <div>
                            <Label htmlFor="delete-password" className="sr-only">Password</Label>
                            <Input
                                id="delete-password"
                                type="password"
                                ref={passwordInput}
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder="Password"
                                autoFocus
                            />
                            {errors.password && <p className="mt-2 text-sm text-red-600">{errors.password}</p>}
                        </div>

                        <div className="flex justify-end gap-3">
                            <Button type="button" variant="outline" onClick={closeModal}>
                                Cancel
                            </Button>
                            <Button type="submit" variant="destructive" disabled={processing}>
                                Delete Account
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </section>
    );
}
