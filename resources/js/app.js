import './bootstrap';

document.addEventListener('dragstart', (event) => {
    if (event.target instanceof HTMLImageElement) {
        event.preventDefault();
    }
});

const easeInOutSine = (progress) => -(Math.cos(Math.PI * progress) - 1) / 2;

const initializeNeonScenes = () => {
    const scenes = Array.from(document.querySelectorAll('[data-neon-scene]'));
    if (scenes.length === 0) {
        return;
    }

    const root = document.documentElement;

    scenes.forEach((scene) => {
        if (!(scene instanceof HTMLElement) || scene.dataset.neonInitialized === 'true') {
            return;
        }

        const orbElements = Array.from(scene.querySelectorAll('[data-neon-orb]'))
            .filter((element) => element instanceof HTMLElement);
        const cursorOrb = scene.querySelector('[data-cursor-orb]');

        if (orbElements.length === 0) {
            return;
        }

        scene.dataset.neonInitialized = 'true';

        let viewport = {
            width: window.innerWidth,
            height: window.innerHeight,
        };
        let motionEnabled = root.dataset.motion !== 'off';
        let previousFrameTime = null;
        let animationFrameId = null;
        let mouseState = cursorOrb instanceof HTMLElement ? {
            element: cursorOrb,
            currentX: viewport.width * 0.5,
            currentY: viewport.height * 0.5,
            targetX: viewport.width * 0.5,
            targetY: viewport.height * 0.5,
        } : null;

        const randomBetween = (min, max) => min + Math.random() * (max - min);

        const calculateBounds = (element) => {
            const width = element.offsetWidth || viewport.width * 0.2;
            const height = element.offsetHeight || viewport.height * 0.2;

            return {
                width,
                height,
                minX: -width * 0.38,
                maxX: viewport.width - width * 0.62,
                minY: -height * 0.38,
                maxY: viewport.height - height * 0.62,
            };
        };

        const createState = (element) => {
            const bounds = calculateBounds(element);
            const startX = Number.parseFloat(element.dataset.startX ?? '50') / 100;
            const startY = Number.parseFloat(element.dataset.startY ?? '50') / 100;

            const currentX = bounds.minX + (bounds.maxX - bounds.minX) * startX;
            const currentY = bounds.minY + (bounds.maxY - bounds.minY) * startY;
            const currentScale = randomBetween(0.96, 1.1);

            return {
                element,
                bounds,
                currentX,
                currentY,
                fromX: currentX,
                fromY: currentY,
                targetX: currentX,
                targetY: currentY,
                currentScale,
                fromScale: currentScale,
                targetScale: currentScale,
                elapsed: 0,
                duration: randomBetween(9000, 18000),
            };
        };

        const states = orbElements.map(createState);

        const assignNextTarget = (state) => {
            state.fromX = state.currentX;
            state.fromY = state.currentY;
            state.fromScale = state.currentScale;
            state.targetX = randomBetween(state.bounds.minX, state.bounds.maxX);
            state.targetY = randomBetween(state.bounds.minY, state.bounds.maxY);
            state.targetScale = randomBetween(0.92, 1.18);
            state.duration = randomBetween(9000, 18000);
            state.elapsed = 0;
        };

        const renderState = (state) => {
            state.element.style.transform = `translate3d(${state.currentX}px, ${state.currentY}px, 0) scale(${state.currentScale})`;
        };

        const renderCursorOrb = () => {
            if (!mouseState) {
                return;
            }

            mouseState.element.style.transform = `translate3d(${mouseState.currentX}px, ${mouseState.currentY}px, 0)`;
        };

        states.forEach((state) => {
            assignNextTarget(state);
            renderState(state);
        });
        renderCursorOrb();

        const updateState = (state, delta) => {
            state.elapsed = Math.min(state.elapsed + delta, state.duration);

            const progress = state.duration === 0 ? 1 : state.elapsed / state.duration;
            const eased = easeInOutSine(progress);

            state.currentX = state.fromX + (state.targetX - state.fromX) * eased;
            state.currentY = state.fromY + (state.targetY - state.fromY) * eased;
            state.currentScale = state.fromScale + (state.targetScale - state.fromScale) * eased;

            if (progress >= 1) {
                assignNextTarget(state);
            }

            renderState(state);
        };

        const onFrame = (timestamp) => {
            if (previousFrameTime === null) {
                previousFrameTime = timestamp;
            }

            const delta = timestamp - previousFrameTime;
            previousFrameTime = timestamp;

            if (motionEnabled) {
                states.forEach((state) => updateState(state, delta));

                if (mouseState) {
                    const followFactor = Math.min(1, delta / 180);
                    mouseState.currentX += (mouseState.targetX - mouseState.currentX) * followFactor;
                    mouseState.currentY += (mouseState.targetY - mouseState.currentY) * followFactor;
                    renderCursorOrb();
                }
            }

            animationFrameId = window.requestAnimationFrame(onFrame);
        };

        const handleResize = () => {
            viewport = {
                width: window.innerWidth,
                height: window.innerHeight,
            };

            states.forEach((state) => {
                state.bounds = calculateBounds(state.element);
                state.currentX = Math.min(state.bounds.maxX, Math.max(state.bounds.minX, state.currentX));
                state.currentY = Math.min(state.bounds.maxY, Math.max(state.bounds.minY, state.currentY));
                state.fromX = state.currentX;
                state.fromY = state.currentY;
                assignNextTarget(state);
                renderState(state);
            });

            if (mouseState) {
                mouseState.currentX = Math.min(viewport.width, Math.max(0, mouseState.currentX));
                mouseState.currentY = Math.min(viewport.height, Math.max(0, mouseState.currentY));
                mouseState.targetX = Math.min(viewport.width, Math.max(0, mouseState.targetX));
                mouseState.targetY = Math.min(viewport.height, Math.max(0, mouseState.targetY));
                renderCursorOrb();
            }

            previousFrameTime = null;
        };

        const handleMouseMove = (event) => {
            if (!mouseState) {
                return;
            }

            mouseState.targetX = event.clientX - (mouseState.element.offsetWidth * 0.5);
            mouseState.targetY = event.clientY - (mouseState.element.offsetHeight * 0.5);
        };

        const motionObserver = new MutationObserver(() => {
            motionEnabled = root.dataset.motion !== 'off';
            previousFrameTime = null;
        });

        motionObserver.observe(root, {
            attributes: true,
            attributeFilter: ['data-motion'],
        });

        window.addEventListener('resize', handleResize, { passive: true });
        window.addEventListener('mousemove', handleMouseMove, { passive: true });
        animationFrameId = window.requestAnimationFrame(onFrame);

        scene.addEventListener('neon-scene:destroy', () => {
            if (animationFrameId !== null) {
                window.cancelAnimationFrame(animationFrameId);
            }

            motionObserver.disconnect();
            window.removeEventListener('resize', handleResize);
            window.removeEventListener('mousemove', handleMouseMove);
        }, { once: true });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeNeonScenes, { once: true });
} else {
    initializeNeonScenes();
}
