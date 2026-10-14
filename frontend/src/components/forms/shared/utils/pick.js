/**
 * Retorna um novo objeto contendo apenas as propriedades informadas.
 *
 * @param {Object} source
 * @param {string[]} keys
 * @returns {Object}
 */
export function pick(source, keys) {
    return keys.reduce((result, key) => {
        if (Object.prototype.hasOwnProperty.call(source, key)) {
            result[key] = source[key]
        }

        return result
    }, {})
}
