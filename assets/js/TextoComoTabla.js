const separarCadenaEnArregloSiSuperaLongitud = (cadena, maximaLongitud) => {
    const resultado = [];
    let indice = 0;
    while (indice < cadena.length) {
        const pedazo = cadena.substring(indice, indice + maximaLongitud);
        indice += maximaLongitud;
        resultado.push(pedazo);
    }
    return resultado;
}

const dividirCadenasYEncontrarMayorConteoDeBloques = (contenidosConMaximaLongitud) => {
    let mayorConteoDeCadenasSeparadas = 0;
    const cadenasSeparadas = [];
    for (const contenido of contenidosConMaximaLongitud) {
        const separadas = separarCadenaEnArregloSiSuperaLongitud(contenido.contenido, contenido.maximaLongitud);
        cadenasSeparadas.push({ separadas, maximaLongitud: contenido.maximaLongitud });
        if (separadas.length > mayorConteoDeCadenasSeparadas) {
            mayorConteoDeCadenasSeparadas = separadas.length;
        }
    }
    return [cadenasSeparadas, mayorConteoDeCadenasSeparadas];
}

const tabularDatos = (cadenas, relleno, separador, columnasDerecha = [2,3]) => {
    const [arreglosDeContenidosConMaximaLongitudSeparadas, mayorConteoDeBloques] = dividirCadenasYEncontrarMayorConteoDeBloques(cadenas)
    let indice = 0;
    const lineas = [];
    while (indice < mayorConteoDeBloques) {
        let linea = "";
        for (let i = 0; i < arreglosDeContenidosConMaximaLongitudSeparadas.length; i++) {
          const contenidos = arreglosDeContenidosConMaximaLongitudSeparadas[i];  
          let cadena = "";
            if (indice < contenidos.separadas.length) {
                cadena = contenidos.separadas[indice];
            }
            if (cadena.length < contenidos.maximaLongitud) {
                cadena = cadena + relleno.repeat(contenidos.maximaLongitud - cadena.length);
            }
            if (columnasDerecha.includes(i)) {
                cadena = cadena.trim(); // Elimina espacios innecesarios
                cadena = relleno.repeat(contenidos.maximaLongitud - cadena.length) + cadena;
            } else {
                cadena = cadena + relleno.repeat(contenidos.maximaLongitud - cadena.length);
            }
            linea += cadena + separador;
        }
        lineas.push(linea);
        indice++;
    }
    return lineas;
}

const obtenerLineaSeparadora = () => {
  const lineasSeparador = tabularDatos(
    [
      { contenido: "-", maximaLongitud: 4 },
      { contenido: "-", maximaLongitud: 28 },
      { contenido: "-", maximaLongitud: 5 },
      { contenido: "-", maximaLongitud: 7 },
    ],"-", '-'
  );
  let separadorDeLineas = "";
  if (lineasSeparador.length > 0) {
    separadorDeLineas = lineasSeparador[0]
  }
  return separadorDeLineas + '\n';
}