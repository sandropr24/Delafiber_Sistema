export function unidades(valor, decimales, enteros = 8) {
    const texto = String(valor).trim();
    if (!new RegExp(`^\\d{1,${enteros}}(?:\\.\\d{1,${decimales}})?$`).test(texto)) throw new Error('Revisa los importes y sus decimales.');
    const [entero, fraccion = ''] = texto.split('.');
    return BigInt(entero) * 10n ** BigInt(decimales) + BigInt(fraccion.padEnd(decimales, '0'));
}
export function texto(valor, decimales = 2) {
    const escala = 10n ** BigInt(decimales);
    return `${valor / escala}.${String(valor % escala).padStart(decimales, '0')}`;
}
export function calcular(detalles, moneda, tipoCambio, porcentaje, incluye) {
    if (!['PEN', 'USD'].includes(moneda) || !detalles.length || detalles.length > 200) throw new Error('Agrega entre 1 y 200 artículos.');
    const cambio = unidades(tipoCambio, 4, 4), tasa = unidades(porcentaje, 2, 3);
    if (cambio <= 0n || tasa > 10000n) throw new Error('Revisa el tipo de cambio y el IGV (0 a 100 %).');
    let total = 0n;
    const lineas = detalles.map(d => {
        const precio = unidades(d.precio_origen, 4), cantidad = unidades(d.cantidad, 2);
        if (precio <= 0n || cantidad <= 0n) throw new Error('La cantidad y el precio deben ser positivos.');
        let pen = moneda === 'USD' ? (precio * cambio + 500000n) / 1000000n : (precio + 50n) / 100n;
        if (!incluye) pen = (pen * (10000n + tasa) + 5000n) / 10000n;
        if (pen <= 0n || pen > 9999999999n) throw new Error('El precio final en soles está fuera de rango.');
        const importe = (cantidad * pen + 50n) / 100n;
        total += importe;
        return {precioventa: texto(pen), importe: texto(importe)};
    });
    if (total <= 0n || total > 9999999999n) throw new Error('El total está fuera del rango permitido.');
    const base = (total * 10000n + (10000n + tasa) / 2n) / (10000n + tasa);
    return {base: texto(base), igv: texto(total - base), total: texto(total), detalles: lineas};
}
