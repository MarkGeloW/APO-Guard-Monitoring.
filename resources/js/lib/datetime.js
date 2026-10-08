export const APP_TIMEZONE = 'Asia/Manila';

const dateTimeOptions = { timeZone: APP_TIMEZONE };

export function formatPhilippinesHeaderDate(date = new Date()) {
    const weekday = date.toLocaleDateString('en-US', { ...dateTimeOptions, weekday: 'short' }).toUpperCase();
    const month = date.toLocaleDateString('en-US', { ...dateTimeOptions, month: 'short' }).toUpperCase();
    const day = date.toLocaleDateString('en-US', { ...dateTimeOptions, day: 'numeric' });
    const year = date.toLocaleDateString('en-US', { ...dateTimeOptions, year: 'numeric' });
    const time = date
        .toLocaleTimeString('en-US', {
            ...dateTimeOptions,
            hour: '2-digit',
            minute: '2-digit',
            hour12: true,
        })
        .toUpperCase();

    return `${weekday} · ${month} ${day}, ${year} · ${time}`;
}
