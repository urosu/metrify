import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';

/**
 * Module-level flag so components initialise to the *current* navigation
 * state — prevents stale-data flicker when Inertia swaps components mid-flight.
 */
let _navigating = false;
router.on('start',  () => { _navigating = true; });
router.on('finish', () => { _navigating = false; });

/**
 * Returns `true` while Inertia is navigating (from start to finish events).
 * Use to show skeleton loaders or disable interactive elements during page
 * transitions.
 */
export function useInertiaNavigating(): boolean {
    const [navigating, setNavigating] = useState(() => _navigating);

    useEffect(() => {
        const removeStart  = router.on('start',  () => setNavigating(true));
        const removeFinish = router.on('finish', () => setNavigating(false));
        return () => { removeStart(); removeFinish(); };
    }, []);

    return navigating;
}
