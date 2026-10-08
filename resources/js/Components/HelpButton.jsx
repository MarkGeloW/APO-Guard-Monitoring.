export default function HelpButton() {
    return (
        <button
            type="button"
            className="fixed bottom-6 right-6 z-40 flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-lg font-bold text-slate-500 shadow-lg hover:text-slate-800"
            aria-label="Help"
        >
            ?
        </button>
    );
}
