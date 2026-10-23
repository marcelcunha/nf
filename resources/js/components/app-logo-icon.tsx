import { SVGAttributes } from 'react';

/**
 * Glifo da marca: nota fiscal com canto dobrado e seta de entrada. Usa a cor do texto atual.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 64 64"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            stroke="currentColor"
            strokeWidth="3"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
        >
            <path d="M20 12h17l9 9v31a2 2 0 0 1-2 2H20a2 2 0 0 1-2-2V14a2 2 0 0 1 2-2z" />
            <path d="M37 12v9h9" />
            <path d="M32 28v14M26 36l6 6 6-6" />
        </svg>
    );
}
