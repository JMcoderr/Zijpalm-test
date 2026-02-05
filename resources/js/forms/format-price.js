/**
 * Format a decimal number into a price string.
 * @param {number} price - The price to format.
 * @returns {string} - The formatted price string.
 */
function formatPrice(price){
    // Returns '€-' if price is 0, otherwise, formats it to '€xx,-' or '€xx,xx' or even '€x.xxx,-'
    // price.toLocaleString('nl-NL') allegedly adds dots for thousands separators
    return '€' + (price === 0 ? '-' : (Number.isInteger(price) ? price.toLocaleString('nl-NL') + ',-' : price.toFixed(2).replace('.', ',').replace(/,00$/, ',-')));
}
